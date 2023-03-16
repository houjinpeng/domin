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
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * @ControllerAnnotation(title="财务-销货单")
 */
class SaleOrder extends AdminController
{

    use \app\admin\traits\Curd;


    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new NodAccount();
        $this->kehu_model = new NodCustomerManagement();
        $this->warehouse_model = new NodWarehouse();
        $this->account_model = new NodAccount();
        $this->order_model = new NodOrder();
        $this->order_info_model = new NodOrderInfo();
        $this->inventory_model = new NodInventory();


    }

    /**
     * @NodeAnotation(title="销货单列表")
     */
    public function index()
    {

        if ($this->request->isAjax()){

            list($page, $limit, $where) = $this->buildTableParames();

            $where[] = ['type','=',3];


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
     * @NodeAnotation(title="录入销货单数据")
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
                'account_id|【账户】' => 'require|number',
                'practical_price|【单据金额】' => 'number|require',
                'paid_price|【实收金额】' => 'number|require',
            ];

            $this->validate($post, $order_info_rule);

            $rule = [
                'good_name|【商品信息】' => 'require',
                'sale_time|【出售时间】' => 'require|date',
                'unit_price|【售货单价】' => 'number|require',
                'num|【售货数量】' => 'number|require',
                'total_price|【售货金额】' => 'number|require',

            ];

            if (count($post['goods']) == 0) {
                $this->error('不能一个也不提交吧~');
            }
            //查询是否有不在库中的域名
            $ym_list = [];

            //验证
            foreach ($post['goods'] as $item) {
                $ym_list[] = $item['good_name'];
                intval($item['total_price']) == 0 && $this->error('域名：【'.$item['good_name'].'】 总金额不能为0');
                $this->validate($item, $rule);
            }

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
            //判断客户是否存在 不存在添加
            $customer = $this->kehu_model->where('name','=',$post['customer'])->find();
            if (empty($customer)){
                $customer_id = $this->kehu_model->insertGetId(['name'=>$post['customer']]);
            }else{
                $customer_id = $customer['id'];
            }


            //单据编号自动生成   XHD+时间戳
            $order_batch_num = 'XHD' . date('YmdHis');

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
                'type'=>3,//售货单
            ];
            //获取pid   保存商品详情

            $pid = $this->order_model->insertGetId($save_order);

            $insert_all = [];

            foreach ($post['goods'] as $item) {
                $save_info = [
                    'good_name' => $item['good_name'],
                    'unit_price' => $item['unit_price'],
                    'num' => $item['num'],
                    'total_price' => $item['total_price'],
                    'remark' => isset($item['remark']) ? $item['remark'] : '',
                    'pid' => $pid,
                    'warehouse_id' => $ym_dict[$item['good_name']]['warehouse_id'],
                    'customer_id' => $customer_id,
                    'account_id' => $post['account_id'],
                    'supplier_id' => $ym_dict[$item['good_name']]['supplier_id'],
                    'sale_time' => $item['sale_time'],
                    'expiration_time' =>$ym_dict[$item['good_name']]['expiration_time'],
                    'register_time' =>$ym_dict[$item['good_name']]['register_time'],

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
     * @NodeAnotation(title="编辑销货单数据")
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
            ];

            $this->validate($post, $order_info_rule);

            $rule = [
                'good_name|【商品信息】' => 'require',
                'sale_time|【销售时间】' => 'require|date',
                'unit_price|【购货单价】' => 'number|require',
                'num|【购货数量】' => 'number|require',
                'total_price|【购货金额】' => 'number|require',

            ];

            if (count($post['goods']) == 0) {
                $this->error('不能一个也不提交吧~');
            }
            $ym_list = [];
            //验证
            foreach ($post['goods'] as $item) {
                $ym_list[] = $item['good_name'];
                intval($item['total_price']) == 0 && $this->error('域名：【'.$item['good_name'].'】 总金额不能为0');
                $item['unit_price'] = intval($item['unit_price']);
                $item['num'] = intval($item['num']);
                $item['total_price'] = intval($item['total_price']);
                $this->validate($item, $rule);
            }

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
                'audit_status' => 0,//审核状态
            ];
            $data->save($save_order);

            $insert_all = [];

            foreach ($post['goods'] as $item) {
                $save_info = [
                    'good_name' => $item['good_name'],
                    'unit_price' => $item['unit_price'],
                    'num' => $item['num'],
                    'total_price' => $item['total_price'],
                    'remark' => isset($item['remark']) ? $item['remark'] : '',
                    'customer_id' => $customer_id,
                    'account_id' => $post['account_id'],
                    'sale_time' => $item['sale_time'],
                ];
                $this->order_info_model->where('id','=',$item['id'])->update($save_info);


            }
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


    /**
     * @NodeAnotation(title="抓取销货数据")
     */
    public function crawl_order_data($crawl_time)
    {

        $warehouse_data = $this->warehouse_model->select()->toArray();
//        $start_time = date('Y-m-d',strtotime('-1 day'));
//        $end_time = date('Y-m-d');
//        dd($post['order_time']);
        $error_data = [];
        $list = [];
        $index = 0;
        foreach ($warehouse_data as $warehouse){

            $username = $warehouse['account'];
            $password = $warehouse['password'];
            $cookie = $warehouse['cookie'];
            //获取所有域名 信息
            $jm_api = new JvMing($username, $password, $cookie);
            $all_ym_data = $jm_api->get_sale_ym($crawl_time,$crawl_time);
            if ($all_ym_data['code'] ==999){
                $error_data[] = $all_ym_data['msg'];
            }
            foreach ($all_ym_data['data'] as $item){
                $list[] = [
                    'index'=>$index+1,
                    'total_price' =>intval($item['wtqian']),
                    'unit_price' => intval($item['wtqian']),
                    'num' => '1',
                    'good_name' => $item['ym'],
                    'sale_time' => $item['jssj'],
                    'remark' => '',

                ];
                $index+=1;

            }



        }

        $data = [
            'code' => 1,
            'data' => $list

        ];

        return json($data);


    }


}
