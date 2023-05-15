<?php


namespace app\admin\controller\nod\purchase;

use app\admin\controller\JvMing;
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
        $this->account_model = new NodAccount();
        $this->account_info_model = new NodAccountInfo();
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

            $is_search_ym = false;
            foreach ($where as $w){
                if ($w[0] == 'order_info') $is_search_ym = $w[2];
            }
            $where = delete_where_filter($where,'order_info');
            $where = format_where_datetime($where,'order_time');
            //判断是否查询了域名
            if ($is_search_ym == false){
                $list = $this->order_model->where($where)
                    ->with(['getWarehouse','getAccount','getSupplier','getOrderUser'],'left')
                    ->order('id','desc')
                    ->page($page,$limit)
                    ->select()->toArray();

                $count = $this->order_model->where($where)->order('id','desc')->count();
            }else{
                $list = $this->order_model->where($where)
                    ->with(['getWarehouse','getAccount','getSupplier','getOrderUser'],'left')
                    ->order('id','desc')
                    ->page($page,$limit)
                    ->hasWhere('getOrderInfo',[['good_name', 'in', $is_search_ym]])
                    ->select()->toArray();
                $count = $this->order_model->where($where)
                    ->hasWhere('getOrderInfo',[['good_name', 'in', $is_search_ym]])
                    ->order('id','desc')->count();
            }


            foreach ($list as &$item){
                $item['order_info'] = $this->order_info_model->where('pid','=',$item['id'])->select()->toArray();
                $item['order_count'] = count($item['order_info']);
            }
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
            $post['practical_price'] == '0'&& $this->error('单据金额不能为0');
            $post['practical_price'] = floatval($post['practical_price'] );
            $post['paid_price'] = floatval($post['paid_price'] );
            if ($post['practical_price'] != $post['paid_price']) $this->error('实际金额和单据金额不等！');


            $order_info_rule = [
                'order_time|【单据日期】' => 'require|date',
                'customer|【客户】' => 'require',
                'sale_user_id|【销售员】' => 'require',
                'account_id|【账户】' => 'require|number',
                'warehouse_id|【仓库】' => 'require|number',
                'practical_price|【单据金额】' => 'float|require',
                'paid_price|【实退金额】' => 'float|require',
            ];

            $this->validate($post, $order_info_rule);

            $rule = [
                'good_name|【商品信息】' => 'require',
                'unit_price|【退货单价】' => 'float|require',
            ];

            if (count($post['goods']) == 0) {
                $this->error('不能一个也不提交吧~');
            }
            //查询是否有不在库中的域名
            $ym_list = [];

            //验证
            foreach ($post['goods'] as $item) {
                $ym_list[] = $item['good_name'];
                floatval($item['unit_price']) == 0 && $this->error('域名：【'.$item['good_name'].'】 总金额不能为0');
                $item['unit_price'] = floatval($item['unit_price']);
                $this->validate($item, $rule);
            }
            check_practical_price($post['practical_price'],$post['goods'])|| $this->error('单据中的内容与单据金额不付~ 请重新计算');


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

            //判断退货价是否等于售货价
            foreach ($post['goods'] as $item){
                if ($ym_xiaoshou_data[$item['good_name']]['practical_price'] != $item['unit_price']){
                    $this->error('域名【'.$item['good_name'].'】销售价:'.$ym_xiaoshou_data[$item['good_name']]['practical_price'].'退货价:'.$item['unit_price'].'  退货价与销售价不等！');
                }
            }

            //判断客户是否存在 不存在添加
            $customer = $this->kehu_model->where('user_id','=',session('admin.id'))->where('name','=',$post['customer'])->find();
            if (empty($customer)){
                $customer_id = $this->kehu_model->insertGetId(['name'=>$post['customer'],'user_id'=>session('admin.id')]);
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
                'warehouse_id' => $post['warehouse_id'],
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
                    'warehouse_id' => $post['warehouse_id'],
                    'customer_id' => $customer_id,
                    'account_id' => $post['account_id'],
                    'supplier_id' => $ym_caigou_data[$item['good_name']]['supplier_id'],
                    'order_time' => $post['order_time'],
                    'sale_user_id'=>$post['sale_user_id'],
                    'order_user_id' => session('admin.id'),
                ];
                $insert_all[] = $save_info;

            }
            $this->order_info_model->insertAll($insert_all);
            $this->success('保存成功~');

        }
        $account_list = $this->account_model->field('id,name')->select()->toArray();
        $this->assign('admin', session('admin'));

        $warehouse_list = $this->warehouse_model->field('id,name')->select()->toArray();

        $this->assign('warehouse_list', $warehouse_list);

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
            $post['practical_price'] == '0'&& $this->error('单据金额不能为0');
            $post['practical_price'] = floatval($post['practical_price'] );
            $post['paid_price'] = floatval($post['paid_price'] );
            if ($post['practical_price'] != $post['paid_price']) $this->error('实际金额与单据金额不等！');

            $order_info_rule = [
                'order_time|【单据日期】' => 'require|date',
                'customer|【客户】' => 'require',
                'account_id|【账户】' => 'require|number',
                'warehouse_id|【仓库】' => 'require|number',
                'practical_price|【单据金额】' => 'float|require',
                'paid_price|【实收金额】' => 'float|require',
                'sale_user_id|【销售员】' => 'require',
            ];

            $this->validate($post, $order_info_rule);

            $rule = [
                'good_name|【商品信息】' => 'require',
                'unit_price|【退货单价】' => 'float|require',

            ];

            if (count($post['goods']) == 0) {
                $this->error('不能一个也不提交吧~');
            }
            $ym_list = [];
            //验证
            foreach ($post['goods'] as $item) {
                $ym_list[] = $item['good_name'];
                floatval($item['unit_price']) == 0 && $this->error('域名：【'.$item['good_name'].'】 总金额不能为0');
                $item['unit_price'] = floatval($item['unit_price']);
                $this->validate($item, $rule);
            }
            check_practical_price($post['practical_price'],$post['goods'])|| $this->error('单据中的内容与单据金额不付~ 请重新计算');

            //查找域名是否已经被销售
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
            //判断退货价是否等于售货价
            foreach ($post['goods'] as $item){
                if ($ym_xiaoshou_data[$item['good_name']]['practical_price'] != $item['unit_price']){
                    $this->error('域名【'.$item['good_name'].'】销售价:'.$ym_xiaoshou_data[$item['good_name']]['practical_price'].'退货价:'.$item['unit_price'].'  退货价和销售价不等！');
                }
            }


            //判断客户是否存在 不存在添加
            $customer = $this->kehu_model->where('user_id','=',session('admin.id'))->where('name','=',$post['customer'])->find();
            if (empty($customer)){
                $customer_id = $this->kehu_model->insertGetId(['name'=>$post['customer'],'user_id'=>session('admin.id')]);
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
                'warehouse_id' => $post['warehouse_id'],
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
                        'warehouse_id' => $post['warehouse_id'],
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
                        'warehouse_id' => $post['warehouse_id'],
                        'customer_id' => $customer_id,
                        'account_id' => $post['account_id'],
                        'supplier_id' => $ym_caigou_data[$item['good_name']]['supplier_id'],

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
        $warehouse_list = $this->warehouse_model->field('id,name')->select()->toArray();

        $this->assign('warehouse_list', $warehouse_list);


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
