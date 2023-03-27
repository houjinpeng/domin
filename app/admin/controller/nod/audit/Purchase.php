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
 * @ControllerAnnotation(title="审核-采购售货退货单")
 */
class Purchase extends AdminController
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
     * @NodeAnotation(title="账户列表")
     */
    public function index()
    {
        if ($this->request->isAjax()){
            //查询为审核的订单
            $list = $this->order_model
                ->with(['getWarehouse','getAccount','getSupplier','getOrderUser'],'left')
                ->where('audit_status','=',0)
                ->order('id','desc')
                ->select()->toArray();
            $count = $this->order_model->where('audit_status','=',0)->count();
            $data = [
                'code'=>0,
                'count'=>$count,
                'data'=>$list
            ];
            return json($data);

        }
        return $this->fetch();

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
            //采购单审核
            if ($type=='stock'){

                $rule = [
                    'good_name|【商品信息】' => 'require',
                    'unit_price|【购货单价】' => 'number|require',
                ];


                $ym_list = [];
                foreach ($post['goods'] as $item) {
                    $ym_list[] = trim($item['good_name']);
                    intval($item['unit_price']) == 0 && $this->error('域名：【'.$item['good_name'].'】 总金额不能为0');
                    $item['unit_price'] = intval($item['unit_price']);
                    $this->validate($item, $rule);
                }
                //查询商品是否在库存中
                $exist = $this->inventory_model->where('good_name','in',$ym_list)->select()->toArray();


                if (count($exist)!== 0) $this->error('有商品已经在库存中~ 不能再次添加 审核失败！');





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
                $insert_inventory_all = [];
                foreach ($post['goods'] as $item) {
                    $save_info = [
                        'good_name' => $item['good_name'],
                        'unit_price' => $item['unit_price'],
                        'remark' => isset($item['remark']) ? $item['remark'] : '',
                        'pid' => $id,
                        'warehouse_id' => $row['warehouse_id'],
                        'account_id' => $row['account_id'],
                        'supplier_id' => $row['supplier_id'],

                    ];
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
                        'type'              => 1,
                        'good_category'     => 1, //1 采购单 2 采购退货单 3销货单 4收款单 5付款单 6销售退货单 7 调拨单
                        'order_user_id'     =>$item['order_user_id']
                    ];
                    $insert_inventory_info = [
                        'good_name'         => $item['good_name'],
                        'unit_price'        => $item['unit_price'],
                        'remark'            => isset($item['remark']) ? $item['remark'] : '',
                        'pid'               => $id,
                        'warehouse_id'      => $row['warehouse_id'],
                        'account_id'        => $row['account_id'],
                        'supplier_id'       => $row['supplier_id'],
                        'order_time'        => $item['order_time'],
                    ];

                    $insert_warehouse_all[] = $insert_warehouse_info;
                    $insert_inventory_all[] = $insert_inventory_info;

                }

                delete_unnecessary_order_info($id,$post['goods']);

                //商品存入库存明细表
                $this->warehouse_info_model->insertAll($insert_warehouse_all);

                //存入库存表
                $this->inventory_model->insertAll($insert_inventory_all);

                //扣款
                $account_data = $this->account_model->find($row['account_id']);

                //获取总账户余额
                $all_balance_price = $this->account_model->sum('balance_price');

                //应收款  如果实际付款金额与订单金额不符合 会产生欠款情况
                $receivable_price = 0;
                if ($post['practical_price'] != $post['paid_price']){
                    $receivable_price =  $post['paid_price'] - $post['practical_price'];

                    //获取来源渠道id 的欠款记录 更新
                    $account_row = $this->supplier_model->find($row['supplier_id']);
                    $account_row->save([
                        'receivable_price'=>$account_row['receivable_price'] + $receivable_price,
                    ]);


                }

                $insert_account_info = [];
                //遍历所有单据 录入明细中
                $balance_price = $account_data['balance_price'];
                foreach ($post['goods'] as $item){
                    $balance_price -= intval($item['unit_price']);
                    $all_balance_price -= intval($item['unit_price']);
                    $insert_account_info[] = [
                        'sz_type'           => 2, //1收入 2支出
                        'category'          => '采购单',
                        'type'              => 1, //1 采购单 2 采购退货单 3销货单 4收款单 5付款单 6销售退货单 7 调拨单
                        'good_name'         => $item['good_name'], //商品名称
                        'remark'            => $item['remark'], //备注
                        'price'             => -$item['unit_price'], //价格
                        'account_id'        => $row['account_id'], // 账户id
                        'sale_user_id'      => $row['sale_user_id'],//销售人员
                        'supplier_id'       => $row['supplier_id'],//渠道id
                        'warehouse_id'      => $row['warehouse_id'],//仓库id
                        'customer_id'       => $row['customer_id'],//客户id
                        'order_id'          => $row['id'],//订单id
                        'practical_price'   => $post['practical_price'],
                        'balance_price'     => $balance_price, //账户余额
                        'all_balance_price' => $all_balance_price,//总账户余额
                        'operate_time'      => $row['order_time'],
                        'receivable_price'  => $receivable_price,
                        'order_user_id'     => $row['order_user_id'],
                    ];

                }

                //账户记录扣款
                $this->account_info_model->insertAll($insert_account_info);

                //修改账户余额
                $account_data->save(['balance_price'=>$balance_price]);



            }
            //销货单审核
            elseif ($type =='sale'){


                $rule = [
                    'good_name|【商品信息】' => 'require',
                    'sale_time|【销售时间】' => 'require|date',
                    'unit_price|【购货单价】' => 'number|require',
                    'sale_user_id|【销售员】' => 'number|require',
                ];


                $ym_list = [];
                //验证
                foreach ($post['goods'] as $item) {
                    $ym_list[] = $item['good_name'];
                    intval($item['unit_price']) == 0 && $this->error('域名：【'.$item['good_name'].'】 总金额不能为0');
                    $item['unit_price'] = intval($item['unit_price']);
                    $this->validate($item, $rule);
                }
                //查询库存中的商品
                $inventory_data = $this->inventory_model->where('good_name','in',$ym_list)->select()->toArray();
                $ym_dict = [];

                foreach ($inventory_data as $it){
                    $ym_dict[$it['good_name']] = $it;
                }

                //如果不相等 查询差的
                if (count($inventory_data) != count($ym_list)){
                    $inventory_list = [];
                    foreach ($inventory_data as $it){
                        $ym_dict[$it['good_name']] = $it;
                        $inventory_list[] = $it['good_name'];
                    }
                    $dif = array_diff($ym_list,$inventory_list);
                    $this->error('下列商品不在库存中，请尽快入库 共：'.count($dif).'个<br>'.join("<br>",$dif),wait: 10);
                }




                //修改审核状态
                $save_order = [
                    'practical_price'=>$post['practical_price'],
                    'paid_price'=>$post['paid_price'],
                    'audit_status' => 1,//审核状态
                    'audit_user_id'=>session('admin.id'),
                ];
                //获取pid 修改单据审核状态保存商品详情
                $update= $row->save($save_order);
                $update || $this->error('审核失败~');




               //存入库存明细表中
                $insert_all  = [];
                foreach ($post['goods'] as $item){
                    //存入仓库出入明细表中
                    $insert_all[] = [
                        'pid'                   =>$id,
                        'good_name'             =>$item['good_name'],
                        'sale_time'             =>$row['order_time'],
                        'unit_price'            => $item['unit_price'], //售价
                        'cost_price'            => $ym_dict[$item['good_name']]['unit_price'], //成本价
                        'profit_price'          => $item['unit_price'] - $ym_dict[$item['good_name']]['unit_price'],//利润
                        'remark'                =>$item['remark'],
                        'warehouse_id'          =>$item['warehouse_id'],
                        'account_id'            =>$row['account_id'],
                        'customer_id'           =>$row['customer_id'],
                        'order_time'            =>$row['order_time'],
                        'sale_user_id'          =>$row['sale_user_id'],#销售人员
                        'order_user_id'         =>$row['order_user_id'],
                        'type'                  =>3,   //1入库 2出库
                        'good_category'         =>3   //1 采购单 2 采购退货单 3销货单 4收款单 5付款单 6销售退货单 7 调拨单
                    ];
                }

                //删除无用的数据
                delete_unnecessary_order_info($id,$post['goods']);


                //减少库存   保存库存明细
                $this->inventory_model->where('good_name','in',$ym_list)->delete();
                //商品存入库存明细表
                $this->warehouse_info_model->insertAll($insert_all);

                //收款
                $account_data = $this->account_model->find($row['account_id']);
                //获取总账户余额
                $all_balance_price = $this->account_model->sum('balance_price');


                //应收款  如果实际付款金额与订单金额不符合 会产生欠款情况
                $receivable_price = 0;
                if ($post['practical_price'] != $post['paid_price']){
                    $receivable_price =  $post['practical_price'] -  $post['paid_price'];

                    //获取客户id 的欠款记录 更新
                    $customer_row = $this->customer_model->find($row['customer_id']);
                    $customer_row->save([
                        'receivable_price'=>$customer_row['receivable_price'] + $receivable_price,
                    ]);


                }


                $insert_account_info = [];
                //遍历所有单据 录入明细中
                $balance_price = $account_data['balance_price'];
                foreach ($post['goods'] as $item){
                    $balance_price += intval($item['unit_price']);
                    $all_balance_price += intval($item['unit_price']);
                    $insert_account_info[] = [
                        'sz_type'           => 1, //1收入 2支出
                        'category'          => '销售单',
                        'type'              => 3, //1 采购单 2 采购退货单 3销货单 4收款单 5付款单 6销售退货单 7 调拨单
                        'good_name'         => $item['good_name'], //商品名称
                        'remark'            => $item['remark'], //备注
                        'price'             => $item['unit_price'], //价格
                        'cost_price'        => $ym_dict[$item['good_name']]['unit_price'], //成本价
                        'profit_price'      => $item['unit_price'] - $ym_dict[$item['good_name']]['unit_price'],//利润
                        'account_id'        => $row['account_id'], // 账户id
                        'sale_user_id'      => $row['sale_user_id'],//销售人员
                        'supplier_id'       => $row['supplier_id'],//渠道id
                        'warehouse_id'      => $row['warehouse_id'],//仓库id
                        'customer_id'       => $row['customer_id'],//客户id
                        'order_id'          => $row['id'],//订单id
                        'practical_price'   => $post['practical_price'],
                        'balance_price'     => $balance_price, //账户余额
                        'all_balance_price' => $all_balance_price,//总账户余额
                        'operate_time'      => $row['order_time'],
                        'receivable_price'  => $receivable_price,
                        'order_user_id'     => $row['order_user_id'],
                    ];

                }

                //修改余额
                $account_data->save(['balance_price'=>$balance_price]);
                $this->account_info_model->insertAll($insert_account_info);

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
