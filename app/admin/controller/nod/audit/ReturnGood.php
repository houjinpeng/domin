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
            $post['paid_price'] == '0'&& $this->error('实收金额不能为0');
            $post['practical_price'] == '0'&& $this->error('单据金额不能为0');
            $post['practical_price'] = intval($post['practical_price']);
            $post['paid_price'] = intval($post['paid_price']);
            //验证
            $order_info_rule = [
                'practical_price|【实际金额】' => 'number|require',
                'paid_price|【实付金额】' => 'number|require',
            ];
            $this->validate($post, $order_info_rule);
            if (count($post['goods']) == 0) {
                $this->error('不能一个也不提交吧~');
            }
            $ym_dict = [];
            //采购退货单审核
            if ($type=='stock'){

                $rule = [
                    'good_name|【商品信息】' => 'require',
                    'unit_price|【退货单价】' => 'number|require',

                ];


                $all_ym_list = [];
                foreach ($post['goods'] as $item) {
                    $all_ym_list[] = trim($item['good_name']);
                    intval($item['unit_price']) == 0 && $this->error('域名：【'.$item['good_name'].'】 总金额不能为0');
                    $item['unit_price'] = intval($item['unit_price']);
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

                $save_order = [
                    'practical_price'=>$post['practical_price'],
                    'paid_price'=>$post['paid_price'],
                    'audit_status' => 1,//审核状态
                    'audit_user_id'=>session('admin.id')
                ];
                //获取pid 修改单据审核状态保存商品详情
                $update = $row->save($save_order);
                $update || $this->error('审核失败~');

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
                    $insert_warehouse_info = [
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
                    ];

                    $insert_warehouse_all[] = $insert_warehouse_info;

                }


                //商品删除库存
                $this->inventory_model->where('good_name','in',$all_ym_list)->delete();

                //商品存入库存明细表
                $this->warehouse_info_model->insertAll($insert_warehouse_all);


                //增加款
                $account_data = $this->account_model->find($row['account_id']);

                $balance_price = $account_data['balance_price'] + intval($post['paid_price']);

                //应收款  如果实际付款金额与订单金额不符合 会产生欠款情况
                $receivable_price = 0;
                if ($post['practical_price'] != $post['paid_price']){
                    $receivable_price =  $post['paid_price'] - $post['practical_price'];

                    //获取来源渠道id 的欠款记录 更新
                    $account_row = $this->account_model->find($row['account_id']);
                    $account_row->save([
                        'receivable_price'=>$account_row['receivable_price'] - $receivable_price,
                    ]);


                }


                //账户记录扣款
                $this->account_info_model->insert([
                    'account_id'        => $row['account_id'],
                    'sale_user_id'      => $row['sale_user_id'],#销售人员
                    'supplier_id'       => $row['supplier_id'],
                    'warehouse_id'      => $row['warehouse_id'],
                    'order_id'          => $row['id'],
                    'price'             => $post['paid_price'],
                    'practical_price'   => $post['practical_price'],
                    'category'          => '采购退货',
                    'sz_type'           => 1, //1收入 2支出
                    'type'              => 2, //1 采购单 2 采购退货单 3销货单 4收款单 5付款单 6销售退货单
                    'balance_price'     => $balance_price,
                    'operate_time'      => $row['order_time'],
                    'receivable_price'  => -$receivable_price,
                ]);

                $account_data->save(['balance_price'=>$balance_price]);

            }

            //销售退货单审核
            elseif ($type =='sale'){
                $rule = [
                    'good_name|【商品信息】' => 'require',
                    'unit_price|【退货单价】' => 'number|require',

                ];


                $ym_shoujia = [];
                $ym_list = [];
                //验证
                foreach ($post['goods'] as $item) {
                    $ym_shoujia[$item['good_name']] = ['unit_price'=>$item['unit_price'],'sale_time'=>$item['sale_time']];
                    intval($item['unit_price']) == 0 && $this->error('域名：【'.$item['good_name'].'】 总金额不能为0');
                    $item['unit_price'] = intval($item['unit_price']);
                    $this->validate($item, $rule);
                }
                $not_ym_list = [];
                //查询库存明细中的商品 判断是否已销售
                foreach ($post['goods'] as $item){
                    $ym_list[] = $item['good_name'];
                    $warehouse_info = $this->warehouse_info_model->where('good_name','=',$item['good_name'])->order('order_time','desc')->find();
                    if (empty($warehouse_info)){
                        $not_ym_list[] = $item['good_name'];
                        continue;
                    }
                    $ym_dict[$item['good_name']] = $warehouse_info->toArray();
                }


                if (count($not_ym_list) != 0){
                    $this->error('下列商品没有出售过不能退货~ 共：'.count($not_ym_list).'个<br>'.join("<br>",$not_ym_list),wait: 10);
                }

                //修改审核状态
                $save_order = [
                    'practical_price'=>$post['practical_price'],
                    'paid_price'=>$post['paid_price'],
                    'audit_status' => 1,//审核状态
                    'sale_user_id'=>'',
                    'audit_user_id'=>session('admin.id')
                ];
                //获取pid 修改单据审核状态保存商品详情
                $update= $row->save($save_order);
                $update || $this->error('审核失败~');

                $insert_all  = [];
                foreach ($post['goods'] as $item){

                    $insert_all[] = [
                        'pid'                   => $id,
                        'good_name'             => $item['good_name'],
                        'sale_time'             => $ym_dict[$item['good_name']]['sale_time'],
                        'unit_price'            => $ym_dict[$item['good_name']]['unit_price'], //售价
                        'profit_price'          => $ym_dict[$item['good_name']]['unit_price'] - $item['unit_price'],
                        'remark'                => $item['remark'],
                        'warehouse_id'          => $ym_dict[$item['good_name']]['warehouse_id'],
                        'account_id'            => $row['account_id'],
                        'customer_id'           => $row['customer_id'],
                        'order_time'            => $row['order_time'],
                        'type'                  => 6 ,
                        'good_category'         => 6, //1 采购单 2 采购退货单 3销货单 4收款单 5付款单 6销售退货单 7 调拨单
                        'sale_user_id'          => $row['sale_user_id'],
                    ];
                    //增加库存  还原之前卖掉的成本数据
                    $insert_inventory_info = [
                        'pid'                   => $id,
                        'good_name'             => $item['good_name'],
                        'unit_price'            => $ym_dict[$item['good_name']]['unit_price'], //售价
                        'profit_price'          => - $item['unit_price'], //利润为退货价格
                        'remark'                => $item['remark'],
                        'warehouse_id'          => $ym_dict[$item['good_name']]['warehouse_id'],
                        'account_id'            => $row['account_id'],
                        'customer_id'           => $row['customer_id'],
                        'order_time'            => $row['order_time'],
                        'type'                  => 2 ,  //1 采购  2退货 3转移
//                        'good_category'         => 4, //1 采购 2销售 3采购退货 4销售退货
                        'sale_user_id'          => $row['sale_user_id'],

                    ];
                    $this->inventory_model->save($insert_inventory_info);

                }

                //商品存入库存明细表
                $this->warehouse_info_model->insertAll($insert_all);



                //收款
                $account_data = $this->account_model->find($row['account_id']);
                $balance_price = $account_data['balance_price'] + intval($post['paid_price']);


                //应收款  如果实际付款金额与订单金额不符合 会产生欠款情况
                $receivable_price = 0;
                if ($post['practical_price'] != $post['paid_price']){
                    $receivable_price =  $post['paid_price'] - $post['practical_price'];

                    //获取客户id 的欠款记录 更新
                    $customer_row = $this->customer_model->find($row['customer_id']);
                    $customer_row->save([
                        'receivable_price'=>$customer_row['receivable_price'] - $receivable_price,
                    ]);


                }

                //账户记录扣款
                $this->account_info_model->insert([
                    'account_id'        => $row['account_id'],
                    'supplier_id'       => $row['supplier_id'],
                    'warehouse_id'      => $row['warehouse_id'],
                    'customer_id'       => $row['customer_id'],
                    'order_id'          => $row['id'],
                    'price'             => $post['paid_price'],
                    'practical_price'   => $post['practical_price'],
                    'category'          => '销售退货',
                    'sz_type'           => 1,
                    'type'              => 6,
                    'balance_price'     => $balance_price,
                    'operate_time'      => $row['order_time'],
                    'receivable_price'  => -$receivable_price,
                    'sale_user_id'      => $row['sale_user_id'],
                ]);

                $account_data->save(['balance_price'=>$balance_price]);
            }
            $this->success('审核成功~');

        }




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
