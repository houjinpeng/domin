<?php



namespace app\admin\controller\nod\audit;

use app\admin\model\NodAccount;
use app\admin\model\NodAccountInfo;
use app\admin\model\NodCustomerManagement;
use app\admin\model\NodInventory;
use app\admin\model\NodOrder;
use app\admin\model\NodOrderInfo;
use app\admin\model\NodSupplier;
use app\admin\model\NodWarehouse;
use app\admin\model\NodWarehouseInfo;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * @ControllerAnnotation(title="审核-退货单")
 */
class ReturnGood extends AdminController
{

    use \app\admin\traits\Curd;



    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new NodAccount();
        $this->supplier_model = new NodSupplier();
        $this->warehouse_model = new NodWarehouse();
        $this->warehouse_info_model = new NodWarehouseInfo();
        $this->account_model = new NodAccount();
        $this->account_info_model = new NodAccountInfo();
        $this->order_model = new NodOrder();
        $this->order_info_model = new NodOrderInfo();
        $this->inventory_model = new NodInventory();
        $this->customer_model = new NodCustomerManagement();

    }



    /**
     * @NodeAnotation(title="审核")
     */
    public function audit($id)
    {
        $row = $this->order_model->find($id);
        $type = $this->request->get('type');
        $type ==''&& $this->error('审核类型错误~ 请刷新页面后重试！');
        empty($row)&& $this->error('没有此采购单');

        if ($this->request->isAjax()){

            $row['audit_status'] == 1 && $this->error('已审核~');

            $post = $this->request->post();
            $post = htmlspecialchars_decode($post['data']);
            $post = (json_decode($post,true));
            $post['practical_price'] == '0'&& $this->error('单据金额不能为0');
            $post['practical_price'] = floatval($post['practical_price']);
            $post['paid_price'] = floatval($post['paid_price']);
            if ($post['practical_price'] < $post['paid_price']) $this->error('实际金额不能大于单据金额！');

            //验证
            $order_info_rule = [
                'practical_price|【实际金额】' => 'float|require',
                'paid_price|【实付金额】' => 'float|require',
            ];
            $this->validate($post, $order_info_rule);

            //检查单据金额是否与内容一样
            check_practical_price($post['practical_price'],$post['goods'])|| $this->error('单据中的内容与单据金额不付~ 请重新计算');

            if (count($post['goods']) == 0) {
                $this->error('不能一个也不提交吧~');
            }
            $ym_dict = [];
            //采购退货单审核
            if ($type=='stock'){

                $rule = [
                    'good_name|【商品信息】' => 'require',
                    'unit_price|【退货单价】' => 'float|require',

                ];


                $all_ym_list = [];
                foreach ($post['goods'] as $item) {
                    $all_ym_list[] = trim($item['good_name']);
                    floatval($item['unit_price']) == 0 && $this->error('域名：【'.$item['good_name'].'】 总金额不能为0');
                    $item['unit_price'] = floatval($item['unit_price']);
                    //验证
                    $this->validate($item, $rule);
                }

                //查询库存中是否存在此商品
                $inventory_data = $this->inventory_model->where('good_name','in',$all_ym_list)->select()->toArray();


                //先查看所有域名并判断或所属一个仓库和是一个来源渠道
                foreach ($inventory_data as $it){
                    $ym_dict[$it['good_name']] = $it;
                }


                //如果不相等 查询差的
                if (count($inventory_data) != count($all_ym_list)){
                    $inventory_list = [];
                    foreach ($inventory_data as $it){
                        $ym_dict[$it['good_name']] = $it;
                        $inventory_list[] = $it['good_name'];
                    }
                    $dif = array_diff($all_ym_list,$inventory_list);
                    $this->error('下列商品不在库存中，请联系制单人删除域名 共：'.count($dif).'个<br>'.join("<br>",$dif),wait: 10);
                }
                $this->model->startTrans();
                try {
                    $save_order = [
                        'practical_price'=>$post['practical_price'],
                        'paid_price'=>$post['paid_price'],
                        'audit_status' => 1,//审核状态
                        'audit_user_id'=>session('admin.id')
                    ];
                    //获取pid 修改单据审核状态保存商品详情
                    $row->save($save_order);


                    //判断是采购审批还是销货审批


                    //商品入库
                    $insert_warehouse_all = [];
                    foreach ($post['goods'] as $item) {
                        $save_info = [
                            'good_name'         => $item['good_name'],
                            'unit_price'        => $item['unit_price'],
                            'remark'            => isset($item['remark']) ? $item['remark'] : '',
                            'pid'               => $id,
                            'warehouse_id'      => $row['warehouse_id'],
                            'account_id'        => $row['account_id'],
                            'supplier_id'       => $row['supplier_id'],
                            'sale_user_id'      => $row['sale_user_id'],
                        ];
                        //修改单据内容
                        $this->order_info_model->where('id','=',$item['id'])->update($save_info);

                        //入库时间  取单据时间
                        $insert_warehouse_all[] = [
                            'good_name'         => $item['good_name'],
                            'unit_price'        => $item['unit_price'],
                            'remark'            => isset($item['remark']) ? $item['remark'] : '',
                            'pid'               => $id,
                            'warehouse_id'      => $row['warehouse_id'],
                            'account_id'        => $row['account_id'],
                            'supplier_id'       => $row['supplier_id'],
                            'order_time'        => $item['order_time'],
                            'type'              => 2,
                            'good_category'     => 2, //1 采购单 2 采购退货单 3销货单 4收款单 5付款单 6销售退货单 7 调拨单
                            'sale_user_id'      => $row['sale_user_id'],
                            'order_user_id'     => $row['order_user_id'],
                        ];


                    }


                    //商品删除库存
                    $this->inventory_model->where('good_name','in',$all_ym_list)->delete();

                    //商品存入库存明细表
                    $this->warehouse_info_model->insertAll($insert_warehouse_all);


                    //获取账户数据
                    $account_data = $this->account_model->find($row['account_id']);

                    //获取总账户余额
                    $all_balance_price = $this->account_model->sum('balance_price');
                    //账户余额
                    $balance_price = $account_data['balance_price'];


                    $paid_price = $post['paid_price'];
                    //遍历说有收款单 将每一个都放到资金明细中
                    foreach ($post['goods'] as $item){

                        //获取渠道id 的欠款记录
                        $supplier_row = $this->supplier_model->find($row['supplier_id']);
                        //获取总应收金额
                        $total_customer_receivable_price = get_total_receivable_price();


                        //每次出库金额减去支付金额 如果小于0 则是应收款
                        $paid_price -= floatval($item['unit_price']);
                        if ($paid_price < 0){
                            //判断差了多少钱 补一单 然后补一单应收款
                            if (floatval($item['unit_price']) != -$paid_price){
                                //正常数据 收款
                                $save_price = floatval($item['unit_price']) + $paid_price;
                                $balance_price += $save_price;
                                $all_balance_price += $save_price;
                                $this->account_info_model->insert( [
                                    'sz_type'           => 1, //1收入 2支出
                                    'category'          => '采购退货单',
                                    'type'              => 2, //1 采购单 2 采购退货单 3销货单 4收款单 5付款单 6销售退货单 7 调拨单
                                    'good_name'         => $item['good_name'], //商品名称
                                    'remark'            => $item['remark'], //备注
                                    'price'             => $save_price, //价格
                                    'practical_price'   => $item['unit_price'],
                                    'receivable_price'  => 0,//应收款

                                    'account_id'        => $row['account_id'], // 账户id
                                    'supplier_id'       => $row['supplier_id'],//渠道id
                                    'warehouse_id'      => $row['warehouse_id'],//仓库id
                                    'order_id'          => $row['id'],//订单id
                                    'balance_price'     => $balance_price, //账户余额
                                    'all_balance_price' => $all_balance_price,//总账户余额
                                    'operate_time'      => $row['order_time'],
                                    'order_user_id'     => $row['order_user_id'],

                                ]);

                                //计算当前客户的应收款

                                //增加客户的欠款金额
                                $receivable_price = $supplier_row['receivable_price'] + (-$paid_price);
                                $supplier_row->save(['receivable_price'=>$receivable_price]);



                                //补充应收款
                                $this->account_info_model->insert( [
                                    'sz_type'           => 1, //1收入 2支出
                                    'category'          => '应收款',
                                    'type'              => 2, //1 采购单 2 采购退货单 3销货单 4收款单 5付款单 6销售退货单 7 调拨单
                                    'good_name'         => $item['good_name'], //商品名称
                                    'remark'            => $item['remark'], //备注
                                    'price'             => -$paid_price, //价格
                                    'cost_price'        => $ym_dict[$item['good_name']]['unit_price'], //成本价
                                    'profit_price'      => 0,//利润
                                    'account_id'        => $row['account_id'], // 账户id
                                    'supplier_id'       => $row['supplier_id'],//渠道id
                                    'warehouse_id'      => $row['warehouse_id'],//仓库id
                                    'order_id'          => $row['id'],//订单id
                                    'practical_price'   => $item['unit_price'],//实际金额
                                    'balance_price'     => $balance_price, //账户余额
                                    'all_balance_price' => $all_balance_price,//总账户余额
                                    'operate_time'      => $row['order_time'], //操作时间
                                    'receivable_price'  => -$paid_price, //应收款 为正数
                                    'order_user_id'     => $row['order_user_id'], //操作人 经手人
                                    'customer_receivable_price' =>$receivable_price, // 客户应收应付款金额
                                    'total_customer_receivable_price' =>$total_customer_receivable_price+(-$paid_price), // 所有客户应收应付款金额
                                ]);

                                $paid_price = 0;

                            }

                            else{

                                //增加欠款金额
                                $customer_receivable_price = $supplier_row['receivable_price'] + (-$paid_price);
                                $supplier_row->save(['receivable_price'=>$customer_receivable_price,]);

                                //全部是应收款
                                $this->account_info_model->insert( [
                                    'sz_type'           => 1, //1收入 2支出
                                    'category'          => '应收款',
                                    'type'              => 2, //1 采购单 2 采购退货单 3销货单 4收款单 5付款单 6销售退货单 7 调拨单
                                    'good_name'         => $item['good_name'], //商品名称
                                    'remark'            => $item['remark'], //备注
                                    'price'             => -$paid_price, //价格
                                    'account_id'        => $row['account_id'], // 账户id
                                    'supplier_id'       => $row['supplier_id'],//渠道id
                                    'warehouse_id'      => $row['warehouse_id'],//仓库id
                                    'customer_id'       => $row['customer_id'],//客户id
                                    'order_id'          => $row['id'],//订单id
                                    'practical_price'   => $item['unit_price'],
                                    'balance_price'     => $balance_price, //账户余额
                                    'all_balance_price' => $all_balance_price,//总账户余额
                                    'operate_time'      => $row['order_time'], //操作时间
                                    'receivable_price'  => -$paid_price, //应收款
                                    'order_user_id'     => $row['order_user_id'], //操作人 经手人
                                    'customer_receivable_price' =>$customer_receivable_price, // 客户应收应付款金额
                                    'total_customer_receivable_price' =>$total_customer_receivable_price+(-$paid_price), // 所有客户应收应付款金额
                                ]);
                                $paid_price = 0;

                            }

                        }
                        else{
                            //入库
                            $balance_price += floatval($item['unit_price']);
                            $all_balance_price += floatval($item['unit_price']);
                            $this->account_info_model->insert( [
                                'sz_type'           => 1, //1收入 2支出
                                'category'          => '采购退货单',
                                'type'              => 2, //1 采购单 2 采购退货单 3销货单 4收款单 5付款单 6销售退货单 7 调拨单
                                'good_name'         => $item['good_name'], //商品名称
                                'remark'            => $item['remark'], //备注
                                'price'             => $item['unit_price'], //价格
                                'cost_price'        => $ym_dict[$item['good_name']]['unit_price'], //成本价
                                'account_id'        => $row['account_id'], // 账户id
                                'sale_user_id'      => $row['sale_user_id'],//销售人员
                                'supplier_id'       => $row['supplier_id'],//渠道id
                                'warehouse_id'      => $row['warehouse_id'],//仓库id
                                'customer_id'       => $row['customer_id'],//客户id
                                'order_id'          => $row['id'],//订单id
                                'practical_price'   => $item['unit_price'],
                                'balance_price'     => $balance_price, //账户余额
                                'all_balance_price' => $all_balance_price,//总账户余额
                                'operate_time'      => $row['order_time'],
                                'receivable_price'  => 0, //应收款
                                'order_user_id'     => $row['order_user_id'],
                                'customer_receivable_price' => $supplier_row['receivable_price'],
                                'total_customer_receivable_price' => $total_customer_receivable_price

                            ]);
                        }

//                    $balance_price += intval($item['unit_price']);
//                    $all_balance_price += intval($item['unit_price']);
//                    //账户记录收款
//                    $this->account_info_model->insert([
//                        'good_name'        => $item['good_name'],
//                        'account_id'        => $row['account_id'],
//                        'customer_id'       => $row['customer_id'],
//                        'sale_user_id'      => $row['sale_user_id'],
//                        'order_user_id'     => $row['order_user_id'],
//                        'order_id'          => $row['id'],
//                        'price'             => $item['unit_price'],
//                        'category'          => '采购退货单',
//                        'sz_type'           => 1,
//                        'type'              => 2,
//                        'operate_time'      => $row['order_time'],
//                        'remark'            => $item['remark'],
//                        'balance_price'     => $balance_price, //账户余额
//                        'all_balance_price' => $all_balance_price,//总账户余额
//                        'receivable_price'  => $receivable_price,//对方欠咱们的钱
//                    ]);

                    }


                    //修改账户余额
                    $account_data->save(['balance_price'=>$balance_price]);

                    $this->model->commit();
                }catch (\Exception $e) {
                    // 回滚事务
                    $this->model->rollback();
                    $this->error('第【'.$e->getLine().'】行 审核错误：'.$e->getMessage());
                }


            }

            //销售退货单审核
            elseif ($type =='sale'){
                $rule = [
                    'good_name|【商品信息】' => 'require',
                    'unit_price|【退货单价】' => 'float|require',

                ];

                if ($post['practical_price'] != $post['paid_price']) $this->error('实际金额和单据金额不等！');



                $ym_list = [];
                //验证
                foreach ($post['goods'] as $item) {
                    $ym_list[] = $item['good_name'];
                    floatval($item['unit_price']) == 0 && $this->error('域名：【'.$item['good_name'].'】 总金额不能为0');
                    $item['unit_price'] = floatval($item['unit_price']);
                    $this->validate($item, $rule);
                }
                $not_ym_list = [];
                $ym_caigou_data = [];//采购数据
                $ym_xiaoshou_data = [];//销售数据
                //查询库存明细中的商品 判断是否已销售
                foreach ($post['goods'] as $item){
                    //查询域名成本价
                    $ym_data = $this->account_info_model->where('good_name','=',$item['good_name'])->where('type','=',3)->order('id','desc')->find();
                    //域名销售数据
                    $ym_sale_data = $this->account_info_model->where('good_name','=',$item['good_name'])->where('type','=',3)->order('id','desc')->find();

                    if (empty($ym_data) || empty($ym_sale_data)){
                        $not_ym_list[] = $item['good_name'];
                        continue;
                    }
                    //域名采购时的数据
                    $ym_caigou_data[$ym_data['good_name']] = $ym_data->toArray();
                    //域名销售数据
                    $ym_xiaoshou_data[$ym_sale_data['good_name']] = $ym_sale_data->toArray();
                }

                if (count($not_ym_list) != 0){
                    $this->error('下列商品没有出售过不能退货~ 共：'.count($not_ym_list).'个<br>'.join("<br>",$not_ym_list),wait: 10);
                }

                $inventory_data = $this->inventory_model->where('good_name','in',$ym_list)->select()->toArray();
                if (count($inventory_data)!=0){
                    $this->error('库存中有此商品，不能再次退货！');
                }

                //判断退货价是否大于售货价
                foreach ($post['goods'] as $item){
                    if ($ym_xiaoshou_data[$item['good_name']]['practical_price'] < $item['unit_price']){
                        $this->error('域名【'.$item['good_name'].'】销售价:'.$ym_xiaoshou_data[$item['good_name']]['practical_price'].'退货价:'.$item['unit_price'].'  退货价与销售价不相等！');
                    }
                }
                check_practical_price($post['practical_price'],$post['goods'])|| $this->error('单据中的内容与单据金额不付~ 请重新计算');
                isset($post['sale_user_id']) || $this->error('销售员不能为空！');
                //获取总利润
                $total_profit_price = $this->account_info_model->where('sale_user_id','=',$post['sale_user_id'])->sum('profit_price');


                $this->model->startTrans();
                try {
                    //修改审核状态
                    $save_order = [
                        'practical_price'=>$post['practical_price'],
                        'paid_price'=>$post['paid_price'],
                        'audit_status' => 1,//审核状态
                        'sale_user_id'=>$post['sale_user_id'],
                        'audit_user_id'=>session('admin.id')
                    ];
                    //获取pid 修改单据审核状态保存商品详情
                    $row->save($save_order);
                    $warehouse_info_insert_all  = [];
                    foreach ($post['goods'] as $item){

                        $warehouse_info_insert_all[] = [
                            'pid'                   => $id,
                            'good_name'             => $item['good_name'],
                            'sale_time'             => $row['order_time'],
                            'unit_price'            => $item['unit_price'], //退货价格
                            'cost_price'            => $ym_caigou_data[$item['good_name']]['practical_price'], //成本价
                            'profit_price'          => $item['unit_price']-$ym_caigou_data[$item['good_name']]['practical_price'] ,//利润
                            'remark'                => $item['remark'],
                            'warehouse_id'          => $ym_xiaoshou_data[$item['good_name']]['warehouse_id'],
                            'account_id'            => $row['account_id'],
                            'customer_id'           => $row['customer_id'],
                            'order_time'            => $row['order_time'],
                            'type'                  => 6 ,
                            'good_category'         => 6, //1 采购单 2 采购退货单 3销货单 4收款单 5付款单 6销售退货单 7 调拨单
                            'sale_user_id'          => $post['sale_user_id'],
                            'order_user_id'         => $row['order_user_id'],

                        ];
                        //增加库存  还原之前卖掉的成本数据 还原成本价
                        $insert_inventory_info = [
                            'pid'                   => $id,
                            'good_name'             => $item['good_name'],
                            'unit_price'            => $ym_caigou_data[$item['good_name']]['cost_price'], //成本价
                            'remark'                => $item['remark'],
                            'warehouse_id'          => $ym_caigou_data[$item['good_name']]['warehouse_id'],
                            'account_id'            => $row['account_id'],
                            'order_time'            => $row['order_time'],
                            'type'                  => 2 ,  //1 采购  2退货 3转移

                        ];
                        $this->inventory_model->insert($insert_inventory_info);

                    }

                    //商品存入库存明细表
                    $this->warehouse_info_model->insertAll($warehouse_info_insert_all);



                    //获取账户信息
                    $account_data = $this->account_model->find($row['account_id']);
                    //获取总账户余额
                    $all_balance_price = $this->account_model->sum('balance_price');
                    $balance_price = $account_data['balance_price'];
                    //遍历说有收款单 将每一个都放到资金明细中
                    //每次入库金额减去支付金额 如果小于0 则是应付款
                    $paid_price = $post['paid_price'];
                    foreach ($post['goods'] as $item){
//                    //获取供应商id 的欠款记录
//                    $customer_row = $this->customer_model->find($row['customer_id']);
//                    //获取所有应收的金额
//                    $total_receivable_price = get_total_receivable_price();

                        //获取客户的欠钱
                        $c_data = $this->customer_model->where('id','=',$row['customer_id'])->find();
                        $receivable_price = $c_data['receivable_price'];

                        //判断如果客户应收款为正数 直接扣除用收款的金额 扣到0
                        if ($receivable_price > 0){
                            if ($receivable_price <= $item['unit_price']){
                                //修改为0
                                $c_data->save(['receivable_price'=>0]);
                                // 增加减少金额
                                $balance_price = $balance_price - ($item['unit_price']-$receivable_price);
                                $all_balance_price = $all_balance_price - ($item['unit_price']-$receivable_price);
                            }else{
                                //修改为0
                                $c_data->save(['receivable_price'=>$receivable_price - $item['unit_price']]);
//                            // 增加减少金额
//                            $balance_price = $balance_price - ($receivable_price - $item['unit_price']);
//                            $all_balance_price = $all_balance_price - ($receivable_price - $item['unit_price']);
                            }
                        }else{
                            $all_balance_price -= floatval($item['unit_price']);
                            $balance_price -= floatval($item['unit_price']);
                        }
                        if (isset($post['sale_user_id'])){
                            $total_profit_price -=  $ym_xiaoshou_data[$item['good_name']]['cost_price']-$ym_caigou_data[$item['good_name']]['price'];
                        }

                        $this->account_info_model->insert( [
                            'sz_type'           => 2, //1收入 2支出
                            'category'          => '销售退货单',
                            'type'              => 6, //1 采购单 2 采购退货单 3销货单 4收款单 5付款单 6销售退货单 7 调拨单
                            'good_name'         => $item['good_name'], //商品名称
                            'remark'            => $item['remark'], //备注
                            'cost_price'        => $ym_xiaoshou_data[$item['good_name']]['price'], //成本价
                            'profit_price'      => $ym_xiaoshou_data[$item['good_name']]['cost_price']-$ym_caigou_data[$item['good_name']]['price'],//利润
                            'total_profit_price'=> isset($post['sale_user_id'])? $total_profit_price :0,//总利润
                            'price'             => -$item['unit_price'], //实际付款价格
                            'practical_price'   => $ym_xiaoshou_data[$item['good_name']]['cost_price'],//单据实际价格
                            'account_id'        => $row['account_id'], // 账户id
                            'sale_user_id'      => $row['sale_user_id'],//销售人员
                            'supplier_id'       => $ym_caigou_data[$item['good_name']]['supplier_id'],//渠道id
                            'warehouse_id'      => $row['warehouse_id'],//仓库id
                            'customer_id'       => $row['customer_id'],//客户id
                            'order_id'          => $row['id'],//订单id
                            'balance_price'     => $balance_price, //账户余额
                            'all_balance_price' => $all_balance_price,//总账户余额
                            'operate_time'      => $row['order_time'],// 操作时间
                            'receivable_price'  => 0, // 应付款 为负数
                            'order_user_id'     => $row['order_user_id'],
                        ]);

                        //将退货的域名计算利润修改
                        foreach ($ym_xiaoshou_data as $xiaoshou_data){
                            $this->account_info_model->where('id','=',$xiaoshou_data['id'])->update(['is_compute_profit'=>2]);
                        }




//                    //每次入库金额减去支付金额 如果小于0 则是应付款
//                    $paid_price -= intval($item['unit_price']);
//
//
//                    if ($paid_price < 0){
//                        //判断差了多少钱 补一单 然后补一单应收款
//                        if (intval($item['unit_price']) != -$paid_price){
//                            //正常数据 付款
//                            $save_price = intval($item['unit_price']) + $paid_price;
//                            $balance_price -= $save_price;
//                            $all_balance_price -= $save_price;
//
//                            //增加应付款金额
//                            $supplier_receivable_price = $customer_row['receivable_price'] + ($paid_price);
//                            $customer_row->save(['receivable_price'=>$supplier_receivable_price,]);
//
//                            $this->account_info_model->insert( [
//                                'sz_type'           => 2, //1收入 2支出
//                                'category'          => '销售退货单',
//                                'type'              => 6, //1 采购单 2 采购退货单 3销货单 4收款单 5付款单 6销售退货单 7 调拨单
//                                'good_name'         => $item['good_name'], //商品名称
//                                'remark'            => $item['remark'], //备注
//                                'price'             => -$save_price, //价格
//                                'cost_price'        => $ym_caigou_data[$item['good_name']]['practical_price'], //成本价
//                                'profit_price'      => $item['unit_price']-$ym_caigou_data[$item['good_name']]['practical_price'],//利润
//                                'total_profit_price'=> $total_profit_price + $item['unit_price']-$ym_caigou_data[$item['good_name']]['practical_price'],//总利润
//
//                                'account_id'        => $row['account_id'], // 账户id
//                                'sale_user_id'      => $row['sale_user_id'],//销售人员
//                                'warehouse_id'      => $row['warehouse_id'],//仓库id
//                                'customer_id'       => $row['customer_id'],//客户id
//                                'order_id'          => $row['id'],//订单id
//                                'practical_price'   => $item['unit_price'],
//                                'balance_price'     => $balance_price, //账户余额
//                                'all_balance_price' => $all_balance_price,//总账户余额
//                                'operate_time'      => $row['order_time'],// 操作时间
//                                'receivable_price'  => 0, // 应付款 为负数
//                                'order_user_id'     => $row['order_user_id'],
//                                'customer_receivable_price' => $supplier_receivable_price, // 渠道应收应付款金额
//                                'total_customer_receivable_price' =>$total_receivable_price+$paid_price, // 所有渠道应收应付款金额
//                            ]);
//
//
//                            //补充应付款数据
//                            $this->account_info_model->insert( [
//                                'sz_type'           => 2, //1收入 2支出
//                                'category'          => '应付款',
//                                'type'              => 6, //1 采购单 2 采购退货单 3销货单 4收款单 5付款单 6销售退货单 7 调拨单
//                                'good_name'         => $item['good_name'], //商品名称
//                                'remark'            => $item['remark'], //备注
//                                'price'             => $paid_price, //实际付款价格
//                                'cost_price'        => $ym_caigou_data[$item['good_name']]['practical_price'], //成本价
//                                'profit_price'      => $item['unit_price']-$ym_caigou_data[$item['good_name']]['practical_price'],//利润
//                                'total_profit_price'=> $total_profit_price + $item['unit_price']-$ym_caigou_data[$item['good_name']]['practical_price'],//总利润
//
//                                'practical_price'   => $item['unit_price'],//单据实际价格
//                                'account_id'        => $row['account_id'], // 账户id
//                                'sale_user_id'      => $row['sale_user_id'],//销售人员
//                                'supplier_id'       => $row['supplier_id'],//渠道id
//                                'warehouse_id'      => $row['warehouse_id'],//仓库id
//                                'customer_id'       => $row['customer_id'],//客户id
//                                'order_id'          => $row['id'],//订单id
//
//                                'balance_price'     => $balance_price, //账户余额
//                                'all_balance_price' => $all_balance_price,//总账户余额
//                                'operate_time'      => $row['order_time'],// 操作时间
//                                'receivable_price'  => $paid_price, // 应付款 为负数
//                                'order_user_id'     => $row['order_user_id'],
//                                'customer_receivable_price' => $supplier_receivable_price, // 渠道应收应付款金额
//                                'total_customer_receivable_price' =>$total_receivable_price+$paid_price, // 所有渠道应收应付款金额
//                            ]);
//
//                        }
//                        //全部是为0 的 直接全部是付款单
//                        else{
//                            //增加渠道的应付款金额
//                            $supplier_receivable_price = $customer_row['receivable_price'] + ($paid_price);
//                            $customer_row->save(['receivable_price'=>$supplier_receivable_price,]);
//                            //补充应付款数据
//                            $this->account_info_model->insert( [
//                                'sz_type'           => 2, //1收入 2支出
//                                'category'          => '应付款',
//                                'type'              => 6, //1 采购单 2 采购退货单 3销货单 4收款单 5付款单 6销售退货单 7 调拨单
//                                'good_name'         => $item['good_name'], //商品名称
//                                'remark'            => $item['remark'], //备注
//                                'price'             => $paid_price, //实付价
//                                'cost_price'        => $ym_caigou_data[$item['good_name']]['practical_price'], //成本价
//                                'profit_price'      => $item['unit_price']-$ym_caigou_data[$item['good_name']]['practical_price'],//利润
//                                'total_profit_price'=> $total_profit_price + $item['unit_price']-$ym_caigou_data[$item['good_name']]['practical_price'],//总利润
//
//                                'practical_price'   => $item['unit_price'],//实际单据价格
//                                'account_id'        => $row['account_id'], // 账户id
//                                'sale_user_id'      => $row['sale_user_id'],//销售人员
//                                'supplier_id'       => $row['supplier_id'],//渠道id
//                                'warehouse_id'      => $row['warehouse_id'],//仓库id
//                                'customer_id'       => $row['customer_id'],//客户id
//                                'order_id'          => $row['id'],//订单id
//
//                                'balance_price'     => $balance_price, //账户余额
//                                'all_balance_price' => $all_balance_price,//总账户余额
//                                'operate_time'      => $row['order_time'],// 操作时间
//                                'receivable_price'  => $paid_price, // 应付款 为负数
//                                'order_user_id'     => $row['order_user_id'],
//                                'customer_receivable_price' => $supplier_receivable_price, // 渠道应收应付款金额
//                                'total_customer_receivable_price' =>$total_receivable_price+$paid_price, // 所有渠道应收应付款金额
//                            ]);
//
//                        }
//                        $paid_price = 0;
//
//                    }
//                    //全部正常采购单
//                    else{
//                        $all_balance_price -= intval($item['unit_price']);
//                        $balance_price -= intval($item['unit_price']);
//                        $this->account_info_model->insert( [
//                            'sz_type'           => 2, //1收入 2支出
//                            'category'          => '销售退货单',
//                            'type'              => 6, //1 采购单 2 采购退货单 3销货单 4收款单 5付款单 6销售退货单 7 调拨单
//                            'good_name'         => $item['good_name'], //商品名称
//                            'remark'            => $item['remark'], //备注
//                            'price'             => -$item['unit_price'], //实际付款价格
//                            'cost_price'        => $ym_caigou_data[$item['good_name']]['practical_price'], //成本价
//                            'profit_price'      => $item['unit_price']-$ym_caigou_data[$item['good_name']]['practical_price'],//利润
//                            'total_profit_price'=> $total_profit_price + $item['unit_price']-$ym_caigou_data[$item['good_name']]['practical_price'],//总利润
//
//                            'practical_price'   => $item['unit_price'],//单据实际价格
//                            'account_id'        => $row['account_id'], // 账户id
//                            'sale_user_id'      => $row['sale_user_id'],//销售人员
//                            'supplier_id'       => $row['supplier_id'],//渠道id
//                            'warehouse_id'      => $row['warehouse_id'],//仓库id
//                            'customer_id'       => $row['customer_id'],//客户id
//                            'order_id'          => $row['id'],//订单id
//                            'balance_price'     => $balance_price, //账户余额
//                            'all_balance_price' => $all_balance_price,//总账户余额
//                            'operate_time'      => $row['order_time'],// 操作时间
//                            'receivable_price'  => 0, // 应付款 为负数
//                            'order_user_id'     => $row['order_user_id'],
//                        ]);
//                    }



//                    //账户记录扣款
//                    $this->account_info_model->insert([
//                        'good_name'         => $item['good_name'],
//                        'account_id'        => $row['account_id'],
//                        'customer_id'       => $row['customer_id'],
//                        'sale_user_id'      => $post['sale_user_id'],
//                        'order_user_id'     => $row['order_user_id'],
//                        'order_id'          => $row['id'],
//                        'cost_price'        => $ym_caigou_data[$item['good_name']]['unit_price'], //成本价
//                        'profit_price'      => $item['unit_price']-$ym_caigou_data[$item['good_name']]['unit_price'],//利润
//                        'total_profit_price'=> $total_profit_price + $item['unit_price']-$ym_caigou_data[$item['good_name']]['unit_price'],//总利润
//                        'price'             => -$item['unit_price'],//退货价格
//                        'category'          => '销售退货单',
//                        'sz_type'           => 1,
//                        'type'              => 6,
//                        'operate_time'      => $row['order_time'],
//                        'remark'            => $item['remark'],
//                        'balance_price'     => $balance_price, //账户余额
//                        'all_balance_price' => $all_balance_price,//总账户余额
//                        'receivable_price'  => $receivable_price,//对方欠咱们的钱
//                    ]);

                    }

                    //修改账户余额
                    $account_data->save(['balance_price'=>$balance_price]);
                    $this->model->commit();
                }catch (\Exception $e) {
                    // 回滚事务
                    $this->model->rollback();
                    $this->error('第【'.$e->getLine().'】行 审核错误：'.$e->getMessage());
                }


            }


            $this->success('审核成功~');

        }



        $warehouse_list = $this->warehouse_model->field('id,name')->select()->toArray();

        $this->assign('warehouse_list', $warehouse_list);
        //查询为审核的订单
        $data = $this->order_model
            ->with(['getWarehouse','getAccount','getSupplier','getOrderUser','getCustomer'],'left')
            ->find($id);
        //获取所有订单详情中的数据
        $all_goods= $this->order_info_model->where('pid','=',$id)->select()->toArray();
        $this->assign('all_goods',json_encode($all_goods));
        $this->assign('data',$data);
        $this->assign('type',$type);
        return $this->fetch();
    }


}
