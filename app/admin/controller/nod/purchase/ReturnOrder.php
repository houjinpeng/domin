<?php


namespace app\admin\controller\nod\purchase;

use app\admin\controller\JvMing;
use app\admin\model\NodAccount;
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
 * @ControllerAnnotation(title="财务-退货单")
 */
class ReturnOrder extends AdminController
{

    use \app\admin\traits\Curd;


    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new NodAccount();
        $this->supplier_model = new NodSupplier();
        $this->warehouse_model = new NodWarehouse();
        $this->account_model = new NodAccount();
        $this->order_model = new NodOrder();
        $this->order_info_model = new NodOrderInfo();
        $this->inventory_model = new NodInventory();


    }

    /**
     * @NodeAnotation(title="采购列表")
     */
    public function index()
    {

        if ($this->request->isAjax()){

            list($page, $limit, $where) = $this->buildTableParames();

            $where[] = ['type','=',2];


            $list = $this->order_model
                ->with(['getWarehouse','getAccount','getSupplier','getOrderUser'],'left')
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
     * @NodeAnotation(title="录入采购数据")
     */
    public function add()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $post = htmlspecialchars_decode($post['data']);
            $post = (json_decode($post,true));
            $post['paid_price'] == '0'&& $this->error('实付金额不能为0');
            $post['practical_price'] == '0'&& $this->error('单据金额不能为0');

            $order_info_rule = [
                'order_time|【单据日期】' => 'require|date',
                'warehouse_id|【仓库】' => 'require|number',
                'supplier_id|【供应商】' => 'require|number',
                'account_id|【账户】' => 'require|number',
                'practical_price|【单据金额】' => 'number|require',
                'paid_price|【实付金额】' => 'number|require',
            ];

            $this->validate($post, $order_info_rule);

            $rule = [
                'good_name|【商品信息】' => 'require',
                'expiration_time|【过期时间】' => 'require|date',
                'register_time|【注册时间】' => 'require|date',
                'unit_price|【购货单价】' => 'number|require',
                'num|【购货数量】' => 'number|require',
                'total_price|【购货金额】' => 'number|require',

            ];

            if (count($post['goods']) == 0) {
                $this->error('不能一个也不提交吧~');
            }
            //验证
            foreach ($post['goods'] as $item) {
                intval($item['total_price']) == 0 && $this->error('域名：【'.$item['good_name'].'】 总金额不能为0');
                $this->validate($item, $rule);
            }


            //单据编号自动生成   GHD+时间戳
            $order_batch_num = 'GHD' . date('YmdHis');

            $save_order = [
                'order_time' => $post['order_time'],
                'order_batch_num' => $order_batch_num,
                'order_user_id' => session('admin.id'),
                'remark' => $post['remark'],
                'warehouse_id' => $post['warehouse_id'],
                'account_id' => $post['account_id'],
                'supplier_id' => $post['supplier_id'],
                'practical_price' => $post['practical_price'],
                'paid_price' => $post['paid_price'],
                'audit_status' => 0,//审核状态
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
                    'warehouse_id' => $post['warehouse_id'],
                    'account_id' => $post['account_id'],
                    'supplier_id' => $post['supplier_id'],
                    'register_time' => $item['register_time'],
                    'expiration_time' => $item['expiration_time'],

                ];
                $insert_all[] = $save_info;

            }
            $this->order_info_model->insertAll($insert_all);
            $this->success('保存成功~');


        }
        $supplier_list = $this->supplier_model->field('id,name')->select()->toArray();
        $warehouse_list = $this->warehouse_model->field('id,name')->select()->toArray();
        $account_list = $this->account_model->field('id,name')->select()->toArray();

        $this->assign('supplier_list', $supplier_list);
        $this->assign('warehouse_list', $warehouse_list);
        $this->assign('account_list', $account_list);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="编辑采购数据")
     */
    public function edit($id)
    {

        //查询为审核的订单
        $data = $this->order_model
            ->with(['getWarehouse','getAccount','getSupplier','getOrderUser'],'left')
            ->find($id);
        if ($this->request->isAjax()){
            $data['audit_status'] != 0 && $this->error('不是可编辑状态，不能再次编辑~');


            $post = $this->request->post();
            $post = htmlspecialchars_decode($post['data']);
            $post = (json_decode($post,true));
            $post['paid_price'] == '0'&& $this->error('实付金额不能为0');
            $post['practical_price'] == '0'&& $this->error('单据金额不能为0');
            $post['practical_price'] = intval($post['practical_price'] );
            $post['paid_price'] = intval($post['paid_price'] );
            $order_info_rule = [
                'order_time|【单据日期】' => 'require|date',
                'warehouse_id|【仓库】' => 'require|number',
                'supplier_id|【供应商】' => 'require|number',
                'account_id|【账户】' => 'require|number',
                'practical_price|【单据金额】' => 'number|require',
                'paid_price|【实付金额】' => 'number|require',
            ];

            $this->validate($post, $order_info_rule);

            $rule = [
                'good_name|【商品信息】' => 'require',
                'expiration_time|【过期时间】' => 'require|date',
                'register_time|【注册时间】' => 'require|date',
                'unit_price|【购货单价】' => 'number|require',
                'num|【购货数量】' => 'number|require',
                'total_price|【购货金额】' => 'number|require',

            ];

            if (count($post['goods']) == 0) {
                $this->error('不能一个也不提交吧~');
            }
            //验证
            foreach ($post['goods'] as $item) {
                intval($item['total_price']) == 0 && $this->error('域名：【'.$item['good_name'].'】 总金额不能为0');
                $item['unit_price'] = intval($item['unit_price']);
                $item['num'] = intval($item['num']);
                $item['total_price'] = intval($item['total_price']);
                $this->validate($item, $rule);
            }


            $save_order = [
                'order_time' => $post['order_time'],
                'order_user_id' => session('admin.id'),
                'remark' => $post['remark'],
                'warehouse_id' => $post['warehouse_id'],
                'account_id' => $post['account_id'],
                'supplier_id' => $post['supplier_id'],
                'practical_price' => $post['practical_price'],
                'paid_price' => $post['paid_price'],
                'audit_status' => 0,//审核状态
            ];
            //获取pid   保存商品详情

            $data->save($save_order);

            $insert_all = [];

            foreach ($post['goods'] as $item) {
                $save_info = [
                    'good_name' => $item['good_name'],
                    'unit_price' => $item['unit_price'],
                    'num' => $item['num'],
                    'total_price' => $item['total_price'],
                    'remark' => isset($item['remark']) ? $item['remark'] : '',
                    'warehouse_id' => $post['warehouse_id'],
                    'account_id' => $post['account_id'],
                    'supplier_id' => $post['supplier_id'],
                    'register_time' => $item['register_time'],
                    'expiration_time' => $item['expiration_time'],
                ];


                $this->order_info_model->where('id','=',$item['id'])->update($save_info);


            }
            $this->success('修改成功~');

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

    /**
     * @NodeAnotation(title="编辑采购数据")
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
     * @NodeAnotation(title="抓取订单数据")
     */
    public function crawl_order_data($warehouse_id)
    {

        $warehouse_data = $this->warehouse_model->find($warehouse_id);
        $username = $warehouse_data['account'];
        $password = $warehouse_data['password'];
        $cookie = $warehouse_data['cookie'];
        //获取所有域名 信息
        $this->jm_api = new JvMing($username, $password, $cookie);
        $all_ym_data = $this->jm_api->download_sales_ym();
        $search_list = [];
        foreach ($all_ym_data as $k => $v) $search_list[] = $k;


        //查找库存 找出没有在库存里的域名
        $all_inventory = $this->inventory_model->select()->toArray();
        $good_name_list = [];
        foreach ($all_inventory as $item) $good_name_list[] = $item['good_name'];

        //对比出可添加的域名
        $search_list = array_diff($search_list, $good_name_list);
        $all_detail = $this->jm_api->get_financial_details($search_list);

        //将每个域名的成交价计算出来  没有的成交价为0
        $ym_detail = [];
        foreach ($search_list as $ym) {
            //如果资金账户不存在
            if (!isset($all_detail[$ym])) {
                $ym_detail[$ym] = 0;
                continue;
            }
            try {
                $d = $all_detail[$ym];
                $price = 0;
                foreach ($d as $v) {
                    //如果是出售的就过滤掉
                    if (strstr($v['sm'], '出售')) continue;
                    $price += $v['qian'];
                }

                $ym_detail[$ym] = -$price;
            } catch (\Exception $e) {
                $ym_detail[$ym] = 0;
            }
        }
        //{'index':'1','total_price':'',remark:'',unit_price:'',good_name:'',num:'1',register_time:'',expiration_time:''}
        $list = [];
        foreach ($search_list as $index=>$ym) {
            $list[] = [
                'index'=>$index+1,
                'total_price' => $ym_detail[$ym],
                'unit_price' => $ym_detail[$ym],
                'num' => '1',
                'good_name' => $ym,
                'register_time' => $all_ym_data[$ym]['zc_time'],
                'expiration_time' => $all_ym_data[$ym]['dq_time'],
                'remark' => '',

            ];
        }

        $data = [
            'code' => 1,
            'data' => $list

        ];

        return json($data);


    }


}
