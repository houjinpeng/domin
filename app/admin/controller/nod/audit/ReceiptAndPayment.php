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
use think\facade\Db;

/**
 * @ControllerAnnotation(title="审核-收付款单")
 */
class ReceiptAndPayment extends AdminController
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
     * @NodeAnotation(title="收付款审核列表")
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
        empty($row)&& $this->error('没有此单');

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
            //付收款单审核
            $rule = [
                'category_id|【收款类别】' => 'require',
                'total_price|【收款金额】' => 'number|require',
            ];

            foreach ($post['goods'] as $item) {

                intval($item['total_price']) == 0 && $this->error('收款类别：【'.$item['category'].'】 总金额不能为0');

                $item['total_price'] = intval($item['total_price']);
                $this->validate($item, $rule);
            }

//            Db::transaction(function (){
//
//            });
            $save_order = [
                'practical_price'=>$post['practical_price'],
                'paid_price'=>$post['paid_price'],
                'audit_status' => 1,//审核状态
                'audit_user_id'=>session('admin.id')
            ];
            //获取pid 修改单据审核状态保存商品详情
            $update = $row->save($save_order);
            $update || $this->error('审核失败~');

            //判断是收款还是付款    收款
            if ($type=='receipt'){
                //增加账户钱
                $account_data = $this->account_model->find($row['account_id']);
                //账户余额等于 实付金额加上余额
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
//
//                //遍历说有收款单 将每一个都放到资金明细中
//                foreach ($post['goods'] as $item){
//                    //账户记录收款
//                    $this->account_info_model->insert([
//                        'account_id' => $row['account_id'],
//                        'customer_id' => $row['customer_id'],
//                        'category_id' => $item['category_id'],
//                        'order_id' => $row['pid'],
//                        'price'=>$item['total_price'],
//                        'receivable_price'=>$receivable_price,
////                        'price'=>$post['paid_price'],
//                        'category'=>'收款',
//                        'sz_type'=>1,
//                        'type'=>4,
//                        'balance_price'=>$balance_price,
//                        'operate_time'=>$row['order_time'],
//                        'remark'=>$post['remark']
//                    ]);
//
//                }
                //账户记录收款
                $this->account_info_model->insert([
                    'account_id' => $row['account_id'],
                    'customer_id' => $row['customer_id'],
                    'order_id' => $row['pid'],
                    'price'=>$post['paid_price'],
                    'practical_price'=>$post['practical_price'],
                    'receivable_price'=>-$receivable_price,
                    'category'=>'收款',
                    'sz_type'=>1,
                    'type'=>4,
                    'balance_price'=>$balance_price,
                    'sale_user_id'=>$row['sale_user_id'],
                    'operate_time'=>$row['order_time'],
                    'remark'=>$post['remark']
                ]);



                //修改账户余额
                $account_data->save(['balance_price'=>$balance_price]);


            }
            elseif ($type=='payment'){
                //增加账户钱
                $account_data = $this->account_model->find($row['account_id']);

                $balance_price = $account_data['balance_price'] - intval($post['paid_price']);


                //应付款  如果实际付款金额与订单金额不符合 会产生欠款情况
                $receivable_price = 0;
                if ($post['practical_price'] != $post['paid_price']){
                    $receivable_price =  $post['paid_price'] - $post['practical_price'];

                    //获取客户id 的欠款记录 更新
                    $customer_row = $this->customer_model->find($row['customer_id']);
                    $customer_row->save([
                        'receivable_price'=>$customer_row['receivable_price'] + $receivable_price,
                    ]);


                }

//                //遍历说有收款单 将每一个都放到资金明细中
//                foreach ($post['goods'] as $item){
//                    //账户记录扣款
//                    $this->account_info_model->insert([
//                        'account_id' => $row['account_id'],
//                        'customer_id' => $row['customer_id'],
//                        'category_id' => $item['category_id'],
//                        'order_id' => $row['pid'],
//                        'price'=>-$item['total_price'],
//                        'price'=>$post['paid_price'],
//                        'practical_price'=>$post['practical_price'],
//                        'category'=>'付款',
//                        'sz_type'=>2,
//                        'type'=>5,
//                        'balance_price'=>$balance_price,
//                        'operate_time'=>$row['order_time'],
//                        'remark'=>$post['remark']
//                    ]);
//
//                }

                //账户记录扣款
                $this->account_info_model->insert([
                    'account_id' => $row['account_id'],
                    'customer_id' => $row['customer_id'],
                    'order_id' => $row['pid'],
                    'price'=>$post['paid_price'],
                    'practical_price'=>$post['practical_price'],
                    'receivable_price'=>$receivable_price,
                    'category'=>'付款',
                    'sz_type'=>2,
                    'type'=>5,
                    'balance_price'=>$balance_price,
                    'operate_time'=>$row['order_time'],
                    'remark'=>$post['remark'],
                    'sale_user_id'=>$row['sale_user_id'],
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