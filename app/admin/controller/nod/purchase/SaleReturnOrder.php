<?php


namespace app\admin\controller\nod\purchase;

use app\admin\controller\JvMing;
use app\admin\model\NodAccount;
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
 * @ControllerAnnotation(title="财务-销货退货单单")
 */
class SaleReturnOrder extends AdminController
{

    use \app\admin\traits\Curd;


    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new NodAccount();
        $this->kehu_model = new NodCustomerManagement();
        $this->warehouse_model = new NodWarehouse();
        $this->warehouse_info_model = new NodWarehouseInfo();
        $this->account_model = new NodAccount();
        $this->order_model = new NodOrder();
        $this->order_info_model = new NodOrderInfo();
        $this->inventory_model = new NodInventory();


    }

    /**
     * @NodeAnotation(title="销货单退货列表")
     */
    public function index()
    {

        if ($this->request->isAjax()){

            list($page, $limit, $where) = $this->buildTableParames();

            $where[] = ['type','=',6];


            $list = $this->order_model
                ->with(['getCustomer','getAccount','getSupplier','getOrderUser'],'left')
                ->where($where)->page($page,$limit)->order('id','desc')->select()->toArray();
            $count = $this->order_model->where($where)->order('id','desc')->count();
            $data = [
                'code'=>0,
                'data'=>$list,
                'count'=>$count,
            ];
            return json($data);

        }



        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="录入销货退货单数据")
     */
    public function add()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $post = htmlspecialchars_decode($post['data']);
            $post = (json_decode($post,true));
            $post['paid_price'] == '0'&& $this->error('实收金额不能为0');
            $post['practical_price'] == '0'&& $this->error('单据金额不能为0');



            $order_info_rule = [
                'order_time|【单据日期】' => 'require|date',
                'customer|【客户】' => 'require',
                'sale_user_id|【销售员】' => 'require',
                'account_id|【账户】' => 'require|number',
                'practical_price|【单据金额】' => 'number|require',
                'paid_price|【实退金额】' => 'number|require',
            ];

            $this->validate($post, $order_info_rule);

            $rule = [
                'good_name|【商品信息】' => 'require',
                'unit_price|【退货单价】' => 'number|require',
            ];

            if (count($post['goods']) == 0) {
                $this->error('不能一个也不提交吧~');
            }
            //查询是否有不在库中的域名
            $ym_list = [];

            //验证
            foreach ($post['goods'] as $item) {
                $ym_list[] = $item['good_name'];
                intval($item['unit_price']) == 0 && $this->error('域名：【'.$item['good_name'].'】 总金额不能为0');
                $item['unit_price'] = intval($item['unit_price']);
                $this->validate($item, $rule);
            }

            //查找域名是否已经被销售
            $sale_good = $this->warehouse_info_model->where('good_category','=',3)->where('good_name','in',$ym_list)->select()->toArray();


            $inventory_data = $this->inventory_model->where('good_name','in',$ym_list)->select()->toArray();
            if (count($inventory_data)!=0){
                $this->error('库存中有此商品，不能再次退货！');
            }


            $ym_dict = [];

            foreach ($sale_good as $it){
                $ym_dict[$it['good_name']] = $it;
            }
            //如果不相等 查询差的
            if (count($sale_good) != count($ym_list)){
                $inventory_list = [];
                foreach ($sale_good as $it){
                    $ym_dict[$it['good_name']] = $it;
                    $inventory_list[] = $it['good_name'];
                }
                $dif = array_diff($ym_list,$inventory_list);
                $this->error('下列商品没有出售，不能进行退货处理 共：'.count($dif).'个<br>'.join("<br>",$dif),wait: 10);
            }



            //判断客户是否存在 不存在添加
            $customer = $this->kehu_model->where('name','=',$post['customer'])->find();
            if (empty($customer)){
                $customer_id = $this->kehu_model->insertGetId(['name'=>$post['customer']]);
            }else{
                $customer_id = $customer['id'];
            }


            //单据编号自动生成   XHD+时间戳
            $order_batch_num = 'XSTH' . date('YmdHis');

            $save_order = [
                'order_time' => $post['order_time'],
                'order_batch_num' => $order_batch_num,
                'order_user_id' => session('admin.id'),
                'remark' => $post['remark'],
                'account_id' => $post['account_id'],
                'customer_id' =>$customer_id,
                'practical_price' => $post['practical_price'],
                'paid_price' => $post['paid_price'],
                'audit_status' => 0,//审核状态
                'type'=>6,//售货退货单
                'sale_user_id'=>$post['sale_user_id'],
            ];
            //获取pid   保存商品详情

            $pid = $this->order_model->insertGetId($save_order);

            $insert_all = [];

            foreach ($post['goods'] as $item) {
                $save_info = [
                    'good_name' => $item['good_name'],
                    'unit_price' => $item['unit_price'],
                    'remark' => isset($item['remark']) ? $item['remark'] : '',
                    'category' =>'销售退货',
                    'pid' => $pid,
                    'warehouse_id' => $ym_dict[$item['good_name']]['warehouse_id'],
                    'customer_id' => $customer_id,
                    'account_id' => $post['account_id'],
                    'supplier_id' => $ym_dict[$item['good_name']]['supplier_id'],
                    'order_time' => $post['order_time'],
                    'sale_user_id'=>$post['sale_user_id'],
                ];
                $insert_all[] = $save_info;

            }
            $this->order_info_model->insertAll($insert_all);
            $this->success('保存成功~');

        }
        $account_list = $this->account_model->field('id,name')->select()->toArray();

        $this->assign('account_list', $account_list);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="编辑销货退单数据")
     */
    public function edit($id)
    {

        //查询为审核的订单
        $data = $this->order_model
            ->with(['getCustomer','getAccount','getSupplier','getOrderUser'],'left')
            ->find($id);
        if ($this->request->isAjax()){
            $data['audit_status'] != 0 && $this->error('不是可编辑状态，不能再次编辑~');


            $post = $this->request->post();
            $post = htmlspecialchars_decode($post['data']);
            $post = (json_decode($post,true));
            $post['paid_price'] == '0'&& $this->error('实收金额不能为0');
            $post['practical_price'] == '0'&& $this->error('单据金额不能为0');
            $post['practical_price'] = intval($post['practical_price'] );
            $post['paid_price'] = intval($post['paid_price'] );

            $order_info_rule = [
                'order_time|【单据日期】' => 'require|date',
                'customer|【客户】' => 'require',
                'account_id|【账户】' => 'require|number',
                'practical_price|【单据金额】' => 'number|require',
                'paid_price|【实收金额】' => 'number|require',
                'sale_user_id|【销售员】' => 'require',
            ];

            $this->validate($post, $order_info_rule);

            $rule = [
                'good_name|【商品信息】' => 'require',
                'unit_price|【退货单价】' => 'number|require',

            ];

            if (count($post['goods']) == 0) {
                $this->error('不能一个也不提交吧~');
            }
            $ym_list = [];
            //验证
            foreach ($post['goods'] as $item) {
                $ym_list[] = $item['good_name'];
                intval($item['unit_price']) == 0 && $this->error('域名：【'.$item['good_name'].'】 总金额不能为0');
                $item['unit_price'] = intval($item['unit_price']);
                $this->validate($item, $rule);
            }

            //查找域名是否已经被销售
            $sale_good = $this->warehouse_info_model->where('good_category','=',3)->where('good_name','in',$ym_list)->select()->toArray();


            $inventory_data = $this->inventory_model->where('good_name','in',$ym_list)->select()->toArray();
            if (count($inventory_data)!=0){
                $this->error('库存中有此商品，不能再次退货！');
            }

            $ym_dict = [];

            foreach ($sale_good as $it){
                $ym_dict[$it['good_name']] = $it;
            }
            //如果不相等 查询差的
            if (count($sale_good) != count($ym_list)){
                $inventory_list = [];
                foreach ($sale_good as $it){
                    $ym_dict[$it['good_name']] = $it;
                    $inventory_list[] = $it['good_name'];
                }
                $dif = array_diff($ym_list,$inventory_list);
                $this->error('下列商品没有出售，不能进行退货处理 共：'.count($dif).'个<br>'.join("<br>",$dif),wait: 10);
            }


            //判断客户是否存在 不存在添加
            $customer = $this->kehu_model->where('name','=',$post['customer'])->find();
            if (empty($customer)){
                $customer_id = $this->kehu_model->insertGetId(['name'=>$post['customer']]);
            }else{
                $customer_id = $customer['id'];
            }
            //获取pid   保存商品详情
            $save_order = [
                'order_time' => $post['order_time'],
                'order_user_id' => session('admin.id'),
                'remark' => $post['remark'],
                'account_id' => $post['account_id'],
                'customer_id' =>$customer_id,
                'practical_price' => $post['practical_price'],
                'paid_price' => $post['paid_price'],
                'sale_user_id'=>$post['sale_user_id'],
            ];
            $data->save($save_order);



            foreach ($post['goods'] as $item) {
                if (isset($item['id'])){
                    $save_info = [
                        'good_name' => $item['good_name'],
                        'unit_price' => $item['unit_price'],
                        'remark' => isset($item['remark']) ? $item['remark'] : '',
                        'customer_id' => $customer_id,
                        'account_id' => $post['account_id'],
                        'sale_time' => $item['sale_time'],
                        'sale_user_id'=>$post['sale_user_id'],
                    ];
                    $this->order_info_model->where('id','=',$item['id'])->update($save_info);

                }
                else{
                    $save_info = [
                        'good_name' => $item['good_name'],
                        'unit_price' => $item['unit_price'],
                        'remark' => isset($item['remark']) ? $item['remark'] : '',
                        'category' =>'销售退货',
                        'pid' => $id,
                        'warehouse_id' => $ym_dict[$item['good_name']]['warehouse_id'],
                        'customer_id' => $customer_id,
                        'account_id' => $post['account_id'],
                        'supplier_id' => $ym_dict[$item['good_name']]['supplier_id'],

                        'order_time' => $post['order_time'],
                        'sale_user_id'=>$post['sale_user_id'],
                    ];
                    $this->order_info_model->save($save_info);
                }


            }

            delete_unnecessary_order_info($id,$post['goods']);


            $this->success('修改成功~');

        }
        $account_list = $this->account_model->field('id,name')->select()->toArray();
        $this->assign('account_list', $account_list);

        //获取所有订单详情中的数据
        $all_goods= $this->order_info_model->where('pid','=',$id)->select()->toArray();
        $this->assign('all_goods',json_encode($all_goods));
        $this->assign('data',$data);


        $this->assign('account_list', $account_list);
        return $this->fetch();

    }

    /**
     * @NodeAnotation(title="撤销销货单数据")
     */
    public function chexiao($id){
        if ($this->request->isAjax()){
            $row = $this->order_model->find($id);
            if ($row['audit_status'] !==0){
                $this->error('当前状态不能撤销');
            }
            $row->save(['audit_status'=>2]);
            $this->success('撤销成功~ 请重新提交采购数据！');
        }
    }


}
