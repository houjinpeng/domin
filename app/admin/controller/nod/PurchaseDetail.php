<?php


namespace app\admin\controller\nod;

use app\admin\controller\JvMing;
use app\admin\model\NodAccount;
use app\admin\model\NodAccountInfo;
use app\admin\model\NodCategory;
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
 * @ControllerAnnotation(title="报表 采购明细")
 */
class PurchaseDetail extends AdminController
{

    use \app\admin\traits\Curd;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new NodAccountInfo();
        $this->category_model = new NodCategory();


    }

    /**
     * @NodeAnotation(title="采购明细列表")
     */
    public function index()
    {
        $type = $this->request->get('type');
        if ($this->request->isAjax()){

            list($page, $limit, $where) = $this->buildTableParames();

            if ($type == null){
                $whereOr = [['type','=',1],['type','=',2]];
            }else{
                $whereOr = [['type','=',3],['type','=',6]];
                //查询销售费用单id
                $cate = $this->category_model->where('name','=','销售费用')->find();
                if (!empty($cate)){
                    $whereOr[] = ['category_id','=',$cate['id']];
                }
            }
            $where = format_where_datetime($where,'operate_time');

            $list = $this->model
                ->with(['getWarehouse','getAccount','getSupplier','getOrderUser','getCustomer','getSaleUser','getCategory'],'left')
                ->where($where)
                ->where(function ($query) use ($whereOr){
                    $query->whereOr($whereOr);
                })
                ->page($page,$limit)->order('id','desc')->select()->toArray();
            $count = $this->model->where($where)
                ->where(function ($query) use ($whereOr){
                    $query->whereOr($whereOr);
                })
                ->count();
            $data = [
                'code'=>0,
                'data'=>$list,
                'count'=>$count,
            ];
            return json($data);

        }

        if ($type == 'sale'){
            $total_stock_price = $this->model->where('type','=',3)->sum('price');
            $total_stock_count = $this->model->where('type','=',3)->count();
            $total_sale_price = $this->model->where('type','=',6)->sum('price');
            $total_sale_count = $this->model->where('type','=',6)->count();

        }else{
            $total_stock_price = $this->model->where('type','=',1)->sum('price');
            $total_stock_count = $this->model->where('type','=',1)->count();
            $total_sale_price = $this->model->where('type','=',2)->sum('price');
            $total_sale_count = $this->model->where('type','=',2)->count();
        }

        $this->assign('total_stock_price',$total_stock_price);
        $this->assign('total_sale_price',$total_sale_price);
        $this->assign('total_stock_count',$total_stock_count);
        $this->assign('total_sale_count',$total_sale_count);
        $this->assign('type',$type);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="录入调拨单数据")
     */
    public function add()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $post = htmlspecialchars_decode($post['data']);
            $post = (json_decode($post,true));

            $order_info_rule = [
                'order_time|【单据日期】' => 'require|date',
                'warehouse_id|【目标仓库】' => 'require|number',
            ];

            $this->validate($post, $order_info_rule);

            $rule = [
                'good_name|【商品信息】' => 'require',
            ];

            if (count($post['goods']) == 0) {
                $this->error('不能一个也不提交吧~');
            }
            //验证
            $ym_list = [];
            foreach ($post['goods'] as $item) {
                $ym_list[] = $item['good_name'];
                $this->validate($item, $rule);
            }
            //判断是否在库存中
            $inventory_data = $this->inventory_model->where('good_name','in',$ym_list)->select()->toArray();
            if (count($inventory_data) !=count($ym_list)){
                $msg = [];
                foreach ($inventory_data as $it){
                    $msg[] = $it['good_name'];
                }
                $dif = array_diff($ym_list,$msg);
                $this->error('下列域名不在仓库中 快去入库吧：'.join('<br>',$dif));

            }


            //判断是不是本身就在仓库中
            $inventory_data = $this->inventory_model->where('warehouse_id','=',$post['warehouse_id'])->where('good_name','in',$ym_list)->select()->toArray();
            if (count($inventory_data) !=0){
                $msg = [];
                foreach ($inventory_data as $it){
                    $msg[] = $it['good_name'];
                }
                $this->error('下列域名本身就在此仓库中 请删除：'.join('<br>',$msg));
            }

            //单据编号自动生成   DBD+时间戳
            $order_batch_num = 'DBD' . date('YmdHis');

            $save_order = [
                'order_time' => $post['order_time'],
                'order_batch_num' => $order_batch_num,
                'order_user_id' => session('admin.id'),
                'remark' => $post['remark'],
                'warehouse_id' => $post['warehouse_id'],
                'type' => 7, //调拨单
                'audit_status' => 0,//审核状态
            ];
            //获取pid   保存商品详情

            $pid = $this->order_model->insertGetId($save_order);

            $insert_all = [];

            foreach ($post['goods'] as $item) {
                $save_info = [
                    'good_name' => $item['good_name'],
                    'remark' => isset($item['remark']) ? $item['remark'] : '',
                    'category' =>'调拨单',
                    'pid' => $pid,
                    'warehouse_id' => $post['warehouse_id'],
                    'order_time' => $post['order_time'],
                ];
                $insert_all[] = $save_info;

            }
            $this->order_info_model->insertAll($insert_all);
            $this->success('保存成功~');


        }
        $warehouse_list = $this->warehouse_model->field('id,name')->select()->toArray();

        $this->assign('warehouse_list', $warehouse_list);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="编辑调拨单数据")
     */
    public function edit($id)
    {

        //查询为审核的订单
        $data = $this->order_model
            ->with(['getWarehouse','getAccount','getSupplier','getOrderUser'],'left')
            ->find($id);
        empty($data)&& $this->error('没有此单据~');
        if ($this->request->isAjax()){
            $data['audit_status'] != 0 && $this->error('不是可编辑状态，不能再次编辑~');


            $post = $this->request->post();
            $post = htmlspecialchars_decode($post['data']);
            $post = (json_decode($post,true));
            $order_info_rule = [
                'order_time|【单据日期】' => 'require|date',
                'warehouse_id|【目标仓库】' => 'require|number',

            ];

            $this->validate($post, $order_info_rule);

            $rule = [
                'good_name|【商品信息】' => 'require',
            ];

            if (count($post['goods']) == 0) {
                $this->error('不能一个也不提交吧~');
            }
            //验证
            $ym_list = [];
            foreach ($post['goods'] as $item) {
                $ym_list[] = $item['good_name'];
                $this->validate($item, $rule);
            }
            //判断是否在库存中
            $inventory_data = $this->inventory_model->where('good_name','in',$ym_list)->select()->toArray();
            if (count($inventory_data) !=count($ym_list)){
                $msg = [];
                foreach ($inventory_data as $it){
                    $msg[] = $it['good_name'];
                }
                $dif = array_diff($ym_list,$msg);
                $this->error('下列域名不在仓库中 快去入库吧：'.join('<br>',$dif));

            }


            //判断是不是本身就在仓库中
            $inventory_data = $this->inventory_model->where('warehouse_id','=',$post['warehouse_id'])->where('good_name','in',$ym_list)->select()->toArray();
            if (count($inventory_data) !=0){
                $msg = [];
                foreach ($inventory_data as $it){
                    $msg[] = $it['good_name'];
                }
                $this->error('下列域名本身就在此仓库中 请删除：'.join('<br>',$msg));
            }

            $save_order = [
                'order_time' => $post['order_time'],
                'order_user_id' => session('admin.id'),
                'remark' => $post['remark'],
                'warehouse_id' => $post['warehouse_id'],
            ];
            //获取pid   保存商品详情

            $data->save($save_order);


            foreach ($post['goods'] as $item) {
               if (isset($item['id'])){
                   $save_info = [
                       'order_time' => $post['order_time'],
                       'good_name' => $item['good_name'],
                       'remark' => isset($item['remark']) ? $item['remark'] : '',
                       'warehouse_id' => $post['warehouse_id'],
                   ];

                   $this->order_info_model->where('id','=',$item['id'])->update($save_info);
               }else{
                   $save_info = [
                       'order_time' => $post['order_time'],
                       'good_name' => $item['good_name'],
                       'remark' => isset($item['remark']) ? $item['remark'] : '',
                       'warehouse_id' => $post['warehouse_id'],
                   ];
                   $this->order_info_model->save($save_info);
               }


            }

            delete_unnecessary_order_info($id,$post['goods']);

            $this->success('修改成功~');

        }
        $warehouse_list = $this->warehouse_model->field('id,name')->select()->toArray();

        //获取所有订单详情中的数据
        $all_goods= $this->order_info_model->where('pid','=',$id)->select()->toArray();
        $this->assign('all_goods',json_encode($all_goods));
        $this->assign('data',$data);


        $this->assign('warehouse_list', $warehouse_list);
        return $this->fetch();

    }

    /**
     * @NodeAnotation(title="撤销调拨单数据")
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
     * @NodeAnotation(title="审核调拨单数据")
     */
    public function audit($id)
    {

        //查询为审核的订单
        $data = $this->order_model
            ->with(['getWarehouse','getAccount','getSupplier','getOrderUser'],'left')
            ->find($id);
        empty($data)&& $this->error('没有此单据~');
        if ($this->request->isAjax()){
            $data['audit_status'] != 0 && $this->error('不是可编辑状态，不能再次编辑~');


            $post = $this->request->post();
            $post = htmlspecialchars_decode($post['data']);
            $post = (json_decode($post,true));
            $order_info_rule = [
                'order_time|【单据日期】' => 'require|date',
                'warehouse_id|【目标仓库】' => 'require|number',
            ];

            $this->validate($post, $order_info_rule);

            $rule = [
                'good_name|【商品信息】' => 'require',
            ];

            if (count($post['goods']) == 0) {
                $this->error('不能一个也不提交吧~');
            }
            //验证
            $ym_list =[];
            foreach ($post['goods'] as $item) {
                $ym_list[] = $item['good_name'];
                $this->validate($item, $rule);
            }

            //判断是否在库存中
            $inventory_data = $this->inventory_model->where('good_name','in',$ym_list)->select()->toArray();
            $ym_dict = [];
            foreach ($inventory_data as $it){
                $ym_dict[$it['good_name']] = $it;
            }


            if (count($inventory_data) !=count($ym_list)){
                $msg = [];
                foreach ($inventory_data as $it){
                    $msg[] = $it['good_name'];
                }
                $dif = array_diff($ym_list,$msg);
                $this->error('下列域名不在仓库中 快去入库吧：'.join('<br>',$dif));

            }


            //判断是不是本身就在仓库中
            $inventory_data = $this->inventory_model->where('warehouse_id','=',$post['warehouse_id'])->where('good_name','in',$ym_list)->select()->toArray();
            if (count($inventory_data) !=0){
                $msg = [];
                foreach ($inventory_data as $it){
                    $msg[] = $it['good_name'];
                }
                $this->error('下列域名本身就在此仓库中 请删除：'.join('<br>',$msg));
            }

            $save_order = [
                'order_time' => $post['order_time'],
                'order_user_id' => session('admin.id'),
                'remark' => $post['remark'],
                'warehouse_id' => $post['warehouse_id'],
                'audit_status' => 1,
            ];
            //获取pid   保存商品详情

            $data->save($save_order);

            $insert_all = [];
            foreach ($post['goods'] as $item) {
                $save_info = [
                    'order_time' => $post['order_time'],
                    'good_name' => $item['good_name'],
                    'remark' => isset($item['remark']) ? $item['remark'] : '',
                    'warehouse_id' => $post['warehouse_id'],
                ];

                $this->order_info_model->where('id','=',$item['id'])->update($save_info);
                $insert_all[] = [
                    'type'=>7,
                    'good_category'=>7,
                    'warehouse_id' => $post['warehouse_id'],
                    'form_warehouse_id'=>$ym_dict[$item['good_name']]['warehouse_id'],
                    'good_name'=>$item['good_name'],
                    'pid'=>$id,
                    'order_time'=>$ym_dict[$item['good_name']]['order_time'],

                ];
            }
            $this->warehouse_info_model->insertAll($insert_all);

            $update_result = $this->inventory_model->where('good_name','in',$ym_list)
                ->update(['warehouse_id'=>$post['warehouse_id']
                    ,'type'=>2]);
            $this->success('审核成功~');

        }
        $supplier_list = $this->supplier_model->field('id,name')->select()->toArray();
        $warehouse_list = $this->warehouse_model->field('id,name')->select()->toArray();
        $account_list = $this->account_model->field('id,name')->select()->toArray();

        //获取所有订单详情中的数据
        $all_goods= $this->order_info_model->where('pid','=',$id)->select()->toArray();
        $this->assign('all_goods',json_encode($all_goods));
        $this->assign('data',$data);


        $this->assign('supplier_list', $supplier_list);
        $this->assign('warehouse_list', $warehouse_list);
        $this->assign('account_list', $account_list);
        return $this->fetch();

    }


}
