<?php


namespace app\admin\controller\nod\purchase;

use app\admin\controller\JvMing;
use app\admin\model\NodAccount;
use app\admin\model\NodCategory;
use app\admin\model\NodInventory;
use app\admin\model\NodJvMingOrderLog;
use app\admin\model\NodOrder;
use app\admin\model\NodOrderInfo;
use app\admin\model\NodSupplier;
use app\admin\model\NodWarehouse;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * @ControllerAnnotation(title="财务-采购")
 */
class StockOrder extends AdminController
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
        $this->jvming_log = new NodJvMingOrderLog();
        $this->cate_model = new NodCategory();


    }

    /**
     * @NodeAnotation(title="采购列表")
     */
    public function index()
    {

        if ($this->request->isAjax()) {

            list($page, $limit, $where) = $this->buildTableParames();

            $where[] = ['type', '=', 1];

            $is_search_ym = false;
            foreach ($where as $w) {
                if ($w[0] == 'order_info') $is_search_ym = $w[2];
            }
            $where = delete_where_filter($where, 'order_info');
            $where = format_where_datetime($where,'order_time');
            //判断是否查询了域名
            if ($is_search_ym == false) {
                $list = $this->order_model->where($where)
                    ->with(['getWarehouse', 'getAccount', 'getSupplier', 'getOrderUser'], 'left')
                    ->order('id', 'desc')
                    ->page($page,$limit)
                    ->select()->toArray();

                $count = $this->order_model->where($where)->order('id', 'desc')->count();
            } else {

                $list = $this->order_model->where($where)
                    ->with(['getWarehouse', 'getAccount', 'getSupplier', 'getOrderUser'], 'left')
                    ->order('id', 'desc')
                    ->page($page,$limit)
                    ->hasWhere('getOrderInfo',[['good_name', 'in', $is_search_ym]])
                    ->select()->toArray();
                $count = $this->order_model->where($where)
                    ->hasWhere('getOrderInfo',[['good_name', 'in', $is_search_ym]])
                    ->order('id', 'desc')->count();
            }


            foreach ($list as &$item) {
                $item['order_info'] = $this->order_info_model->where('pid', '=', $item['id'])->select()->toArray();
                $item['order_count'] = count($item['order_info']);
            }


            $data = [
                'code' => 0,
                'data' => $list,
                'count' => $count,
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
            $post = (json_decode($post, true));
            $post['practical_price'] == '0' && $this->error('单据金额不能为0');
            if ($post['practical_price'] < $post['paid_price']) $this->error('实际金额不能大于单据金额！');

            $order_info_rule = [
                'order_time|【单据日期】' => 'require|date',
                'warehouse_id|【仓库】' => 'require|number',
                'supplier_id|【来源渠道】' => 'require|number',
                'account_id|【账户】' => 'require|number',
                'practical_price|【单据金额】' => 'number|require',
                'paid_price|【实付金额】' => 'number|require',
            ];

            $this->validate($post, $order_info_rule);
            //检查单据金额是否与内容一样
            check_practical_price($post['practical_price'], $post['goods']) || $this->error('单据中的内容与单据金额不付~ 请重新计算');

            $rule = [
                'good_name|【商品信息】' => 'require',
                'unit_price|【购货单价】' => 'number|require',
            ];

            if (count($post['goods']) == 0) {
                $this->error('不能一个也不提交吧~');
            }
            //验证
            foreach ($post['goods'] as $item) {
                intval($item['unit_price']) == 0 && $this->error('域名：【' . $item['good_name'] . '】 总金额不能为0');
                $this->validate($item, $rule);
            }


            //单据编号自动生成   GHD+时间戳
            $order_batch_num = 'GHD' . now_time();

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
                    'remark' => isset($item['remark']) ? $item['remark'] : '',
                    'category' => '采购',
                    'pid' => $pid,
                    'warehouse_id' => $post['warehouse_id'],
                    'account_id' => $post['account_id'],
                    'supplier_id' => $post['supplier_id'],
                    'order_time' => $post['order_time'],
                    'order_user_id' => session('admin.id'),


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
            ->with(['getWarehouse', 'getAccount', 'getSupplier', 'getOrderUser'], 'left')
            ->find($id);
        empty($data) && $this->error('没有此单据~');
        if ($this->request->isAjax()) {
            $data['audit_status'] != 0 && $this->error('不是可编辑状态，不能再次编辑~');


            $post = $this->request->post();
            $post = htmlspecialchars_decode($post['data']);
            $post = (json_decode($post, true));
            $post['paid_price'] == '0' && $this->error('实付金额不能为0');
            $post['practical_price'] == '0' && $this->error('单据金额不能为0');
            $post['practical_price'] = intval($post['practical_price']);
            $post['paid_price'] = intval($post['paid_price']);
            $order_info_rule = [
                'order_time|【单据日期】' => 'require|date',
                'warehouse_id|【仓库】' => 'require|number',
                'supplier_id|【来源渠道】' => 'require|number',
                'account_id|【账户】' => 'require|number',
                'practical_price|【单据金额】' => 'number|require',
                'paid_price|【实付金额】' => 'number|require',
            ];

            $this->validate($post, $order_info_rule);
            //检查单据金额是否与内容一样
            check_practical_price($post['practical_price'], $post['goods']) || $this->error('单据中的内容与单据金额不付~ 请重新计算');
            $rule = [
                'good_name|【商品信息】' => 'require',
                'unit_price|【购货单价】' => 'float|require',

            ];

            if (count($post['goods']) == 0) {
                $this->error('不能一个也不提交吧~');
            }
            //验证
            foreach ($post['goods'] as $item) {
                intval($item['unit_price']) == 0 && $this->error('域名：【' . $item['good_name'] . '】 总金额不能为0');
                $item['unit_price'] = intval($item['unit_price']);
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
                if (isset($item['id'])) {
                    $save_info = [
                        'order_time' => $post['order_time'],
                        'good_name' => $item['good_name'],
                        'unit_price' => $item['unit_price'],
                        'remark' => isset($item['remark']) ? $item['remark'] : '',
                        'warehouse_id' => $post['warehouse_id'],
                        'account_id' => $post['account_id'],
                        'supplier_id' => $post['supplier_id'],
                    ];
                    $this->order_info_model->where('id', '=', $item['id'])->update($save_info);
                } else {
                    $save_info = [
                        'pid' => $id,
                        'order_time' => $post['order_time'],
                        'good_name' => $item['good_name'],
                        'unit_price' => $item['unit_price'],
                        'category' => '采购',
                        'remark' => isset($item['remark']) ? $item['remark'] : '',
                        'warehouse_id' => $post['warehouse_id'],
                        'account_id' => $post['account_id'],
                        'supplier_id' => $post['supplier_id'],
                        'order_user_id' => session('admin.id'),
                    ];
                    $this->order_info_model->save($save_info);

                }


            }

            delete_unnecessary_order_info($id, $post['goods']);

            $this->success('修改成功~');

        }
        $supplier_list = $this->supplier_model->field('id,name')->select()->toArray();
        $warehouse_list = $this->warehouse_model->field('id,name')->select()->toArray();
        $account_list = $this->account_model->field('id,name')->select()->toArray();

        //获取所有订单详情中的数据
        $all_goods = $this->order_info_model->where('pid', '=', $id)->select()->toArray();
        $this->assign('all_goods', json_encode($all_goods));
        $this->assign('data', $data);


        $this->assign('supplier_list', $supplier_list);
        $this->assign('warehouse_list', $warehouse_list);
        $this->assign('account_list', $account_list);
        return $this->fetch();

    }

    /**
     * @NodeAnotation(title="编辑采购数据")
     */
    public function chexiao($id)
    {
        if ($this->request->isAjax()) {
            $row = $this->order_model->find($id);
            if ($row['audit_status'] !== 0) {
                $this->error('当前状态不能撤销');
            }
            $row->save(['audit_status' => 2]);
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
        $list = [];
        foreach ($search_list as $index => $ym) {
            $list[] = [
                'index' => $index + 1,
                'unit_price' => $ym_detail[$ym],
                'good_name' => $ym,
                'remark' => '',

            ];
        }

        $data = [
            'code' => 1,
            'data' => $list

        ];

        return json($data);


    }


    /**
     * @NodeAnotation(title="抓取所有订单数据")
     */
    public function crawl_all_order($crawl_time)
    {

        $rule = [
            'start_time|起始时间' => 'date',
            'end_time|结束时间' => 'date',
        ];
        $start_time = $crawl_time;
        $end_time = $crawl_time;

        $data = [
            'start_time' => $start_time,
            'end_time' => $end_time,
        ];

        $this->validate($data, $rule);


        //获取所有账户名字
        $all_warehouse_data = $this->warehouse_model->where('status','=',1)->select();
        //获取每个账户的资金明细  查询指定日期购买的域名 按照类型分类
        $caigou_order = 0;
        $diaobo_order = 0;
        $zhuanyi_order =0 ;
        $other_receipt_order = 0;
        $return_stock_order = 0 ;//采购退货单
        try {
            foreach ($all_warehouse_data as $warehouse_data) {
                //获取账号id
                $account = $this->account_model->where('name','=',$warehouse_data['name'])->find();

                $username = $warehouse_data['account'];
                $password = $warehouse_data['password'];
                $cookie = $warehouse_data['cookie'];
                $this->jm_api = new JvMing($username, $password, $cookie);
                //获取资金明细单
                $financial_data = $this->jm_api->get_financial_detailss(start_time: $start_time, end_time: $end_time);
                $yikoujia_data = []; //一口价 开采购单
                $jingjia_data = []; //竞价 开采购单
                $push_data = []; //同行push 开采购单
                $quan_data = []; //券 开采购单
                $other_receipt_data = []; //竞价活动   开成其它收款单
                $return_stock_order_data = []; //竞价活动 域名得标    开成退货单
                //获取域名竞价得标单
                $last_quan_price = $this->jm_api->get_quan_price();
                foreach ($financial_data as $item) {
                    //判断是否在库存中 如果存在的话过滤
                    if ($item['lx_txt'] == '充值') { //开提现转存单

                        //如果存在过滤
                        $is_exist = $this->order_model->whereRaw('DATE_FORMAT(order_time,"%Y-%m-%d") = "'.$start_time.'"')
                            ->where('type','=',10)
                            ->where('to_account','=',$account['id'])->where('remark','=',$item['zu'].' '.$item['sm'])->find();
                        if (!empty($is_exist)) continue;
                        $zhuanyi_order += 1;
                        //单据编号自动生成   ZCTX+时间戳
                        $order_batch_num = 'ZCTX' .now_time();
                        $save_order = [
                            'to_account' => $account['id'],
                            'order_batch_num' => $order_batch_num,
                            'order_time' => date('Y-m-d H:i:s'),
                            'order_user_id' => session('admin.id'),
                            'practical_price' => $item['qian'],
                            'paid_price' =>  $item['qian'],
                            'remark' => $item['zu'].' '.$item['sm'],
                            'type' => 10, //转存提现
                            'audit_status' => 0,//审核状态
                        ];
                        $this->order_model->insert($save_order);

                    }
                    if ($item['zu'] == '域名得标') {
                        if ($item['lx_txt'] == '退款') {
                            //如果活动在sm中 开其他收入单
                            if (strstr($item['sm'],'活动')){
                                $other_receipt_data[$item['sm']] = $item['qian'];
                                continue;
                            }

                            //判断采购退货单是否存在
                            if (check_order_exist(ym: $item['ym'],time: $start_time,cate: 2) == true){
                                continue;
                            }

                            $return_stock_order_data[$item['id']] = $item;
                            continue;
                        };


                        //判断采购单是否存在
                        if (check_order_exist(ym: $item['ym'],time: $start_time,cate: 1) == true){
                            continue;
                        }


                        if (!isset($jingjia_data[$item['ym']])) {
                            $jingjia_data[$item['ym']] = $item['qian'];
                        } else {
                            $jingjia_data[$item['ym']] += $item['qian'];
                        }

                    }
                    elseif ($item['zu'] == '一口价购买') {
                        //判断采购单是否存在
                        if (check_order_exist(ym: $item['ym'],time: $start_time,cate: 1) == true){
                            continue;
                        }

                        if (!isset($yikoujia_data[$item['ym']])) {
                            $yikoujia_data[$item['ym']] = $item['qian'];
                        } else {
                            $yikoujia_data[$item['ym']] += $item['qian'];
                        }

                    }
                    elseif ($item['zu'] == '域名注册(券)' ) {
                        //判断采购单是否存在
                        if (check_order_exist(ym: $item['ym'],time: $start_time,cate: 1) == true){
                            continue;
                        }
                        $quan_data[$item['ym']] = -$last_quan_price;
                    }
                    elseif($item['zu'] =='竞价活动' ){
                        //如果是退款单  跳过
                        if ($item['lx_txt'] == '退款') {
                            //如果活动在sm中 开其他收入单
                            if (strstr($item['sm'],'活动')){
                                $other_receipt_data[$item['sm']] = $item['qian'];
                                continue;
                            }
                            //判断采购退货单是否存在
                            if (check_order_exist(ym: $item['ym'],time: $start_time,cate: 2) == true){
                                continue;
                            }
                            $return_stock_order_data[$item['id']] = $item;
                            continue;
                        }
                        //判断采购单是否存在
                        if (check_order_exist(ym: $item['ym'],time: $start_time,cate: 1) == true){
                            continue;
                        }
                        $other_receipt_data[$item['ym']] = $item['qian'];
                    }

                }


                //获取同行push数据
                $all_push_data = $this->jm_api->get_ruku_list(start_time: $start_time, end_time: $end_time);
                foreach ($all_push_data as $item) {
                    foreach ($item['ymlb'] as $v) {
                        //判断采购退货单是否存在
                        if (check_order_exist(ym: $v,time: $start_time,cate: 1) == true){
                            continue;
                        }
                        $push_data[$v] = 0;
                    }
                }

                //将数据组装到一起
                $all_cate_data = [
                    '竞价'=>$jingjia_data,
                    '一口价'=>$yikoujia_data,
                    'push'=>$push_data,
                    '券'=>$quan_data,

                ];


                //所有金额
                $order_time = $start_time;
                //遍历所有分类数据  插入订单
                $supplier = '';
                $remark = '';
                foreach ($all_cate_data as $cate=>$data){
                    $paid_price = calculator_paid_price($data);
                    if ($cate == '一口价'){
                        //获取来单渠道id
                        $supplier = $this->supplier_model->where('name','=','一口价购买')->find();
                        $remark = '日期：'.$start_time.' 一口价购买';
                    }
                    elseif ($cate == '竞价'){
                        //获取来单渠道id
                        $supplier = $this->supplier_model->where('name','=','域名得标')->find();
                        $remark = '日期：'.$start_time.' 域名得标';
                    }
                    elseif ($cate == 'push'){
                        //获取来单渠道id
                        $supplier = $this->supplier_model->where('name','=','同行push')->find();
                        $remark = '日期：'.$start_time.' 同行push';
                    }
                    elseif ($cate == '券'){
                        //获取来单渠道id
                        $supplier = $this->supplier_model->where('name','=','域名注册')->find();
                        $remark = '日期：'.$start_time. ' 域名注册';
                    }

                    $new_data = $data;
                    foreach ($new_data as $ym){
                        //如果存在过滤  并删除存在的数据
                        if (check_order_exist(ym: $ym,time: $start_time,cate: 1) == true) {
                            unset($data[$ym]);
                        };
                    }


                    if ($data == []) continue;
                    $caigou_order += 1;
                    //组装数据插入数据库
                    $insert_order = [
                        'order_time' =>$order_time,
                        'order_batch_num' => 'GHD' . now_time(),
                        'order_user_id' => session('admin.id'),
                        'remark' => $remark,
                        'warehouse_id' => $warehouse_data['id'],
                        'account_id'=> empty($account) ?'':$account['id'],
                        'supplier_id'=> empty($supplier) ?'':$supplier['id'],
                        'practical_price'=>-$paid_price,
                        'paid_price'=>-$paid_price,
                        'audit_status' => 0,//审核状态
                    ];

                    $pid = $this->order_model->insertGetId($insert_order);
                    foreach ($data as $ym=>$price){
                        $save_yikoujia_info = [
                            'good_name' => $ym,
                            'unit_price' => -$price,
                            'remark' => $remark,
                            'category' => '采购',
                            'pid' => $pid,
                            'warehouse_id' => $warehouse_data['id'],
                            'account_id' =>empty($account) ?'':$account['id'],
                            'supplier_id' => empty($supplier) ?'':$supplier['id'],
                            'order_time' => $order_time,
                            'order_user_id' => session('admin.id'),
                        ];
                        $this->order_info_model->insert($save_yikoujia_info);
                    }
                }


                //获取调拨单
                $pull_list = $this->jm_api->get_pull_list($start_time,$end_time);
                if ($pull_list['code'] == 999){
                    $this->error('部分采集成功 ！  当前错误:'.$push_data['msg']);
                }


                $insert_all = [];
                foreach ($pull_list['data']  as $item){
                    if ($item['zt_txt'] == '请求已取消'){ continue;}


                    //判断是调拨单还是采购单   调拨单（在自己账号下） 采购单 不属于自己账号的
                    $from_werahouse = $this->warehouse_model->where('name','=',$item['uid'])->find();
                    $c_list =explode(',',$item['ymlbx']);
                    $one_good_price = $item['qian']/count($c_list);

                    if (empty($from_werahouse)){
                        $supplier = $this->supplier_model->where('name','=','同行push')->find();
                        //采购单

                        $new_c_list = $c_list;

                        foreach ($new_c_list as $index=>$ym){
                            if (check_order_exist(ym: $ym,time: $start_time,cate: 1) == true){
                                unset($c_list[$index]);
                            }
                        }
                        //如果为0跳过
                        if (count($c_list) == 0)continue;
                        $caigou_order += 1;
                        $insert_order = [
                            'order_time' =>$order_time,
                            'order_batch_num' => 'GHD' . now_time(),
                            'order_user_id' => session('admin.id'),
                            'remark' => '时间：'.$start_time.' 收到的请求 来自:'.$item['uid'],
                            'warehouse_id' => $warehouse_data['id'],
                            'account_id'=> empty($account) ?'':$account['id'],
                            'supplier_id'=> empty($supplier) ?'':$supplier['id'],
                            'practical_price'=>$item['qian'],
                            'paid_price'=>$item['qian'],
                            'audit_status' => 0,//审核状态
                        ];
                        $pid = $this->order_model->insertGetId($insert_order);

                        foreach ($c_list as $ym){
                            $save_yikoujia_info = [
                                'good_name' => $ym,
                                'unit_price' => $one_good_price,
                                'remark' =>'时间：'.$start_time. '  收到的请求',
                                'category' => '采购',
                                'pid' => $pid,
                                'warehouse_id' => $warehouse_data['id'],
                                'account_id' =>empty($account) ?'':$account['id'],
                                'supplier_id' => empty($supplier) ?'':$supplier['id'],
                                'order_time' => $order_time,
                                'order_user_id' => session('admin.id'),
                            ];
                            $this->order_info_model->insert($save_yikoujia_info);
                        }
                    }
                    else{
                        //调拨单
                        $zy_ym_list = [];
                        foreach ($c_list as $ym){
                            $zy_ym_list[] = $ym;
                        }

                        foreach ($c_list as $index=>$ym){
                            if (check_order_exist(ym: $ym,time: $start_time,cate: 7) == true){
                                unset($zy_ym_list[$index]);
                            }
                        }
                        //如果为0跳过
                        if ($zy_ym_list ==[])continue;


                        //生成调拨单
                        $push_order = [
                            'order_time' => $order_time,
                            'order_batch_num' => 'DBD' . now_time(),
                            'order_user_id' => session('admin.id'),
                            'remark' => '时间:'.$crawl_time.' 调拨单 仓库：'.$item['uid'] .' 发送到仓库：'.$warehouse_data['name'],
                            'warehouse_id' => $warehouse_data['id'],
                            'type' => 7, //调拨单
                            'audit_status' => 0,//审核状态
                        ];
                        //插入调拨单 获取插入id
                        $diaobo_order += 1;
                        $pid = $this->order_model->insertGetId($push_order);
                        foreach ($zy_ym_list as $ym) {
                            $save_info = [
                                'good_name' => $ym,
                                'remark' =>'时间：'.$start_time. ' 调拨单',
                                'category' =>'调拨单',
                                'pid' => $pid,
                                'warehouse_id' => $warehouse_data['id'],
                                'order_time' => $order_time,
                            ];
                            $insert_all[] = $save_info;

                        }
                    }

                }
                if ($insert_all != []){
                    $this->order_info_model->insertAll($insert_all);
                }


                $other_receipt_list = [];//其他收入单插入所有数据
                $other_receipt_price = 0; //其他收入单总金额

                //生成其它收款单
                foreach ($other_receipt_data as $ym=>$price){
                    if (check_order_exist(ym: $ym,time: $start_time,cate: 9) == true){
                      continue;
                    }


                    $other_receipt_price += $price;
                    $other_receipt_list[] = [
                        'category' => '其他收入单',
                        'unit_price' => $price,
                        'remark' =>  '日期：'.$start_time.' 竞价活动 '.$ym,
                        'order_user_id' => session('admin.id'),
                        'account_id' => $account['id'],
                    ];

                }
                if ($other_receipt_list != []){
                    $cate = $this->cate_model->where('name','=','竞价活动')->find();


                    $other_receipt_order += 1;
                    $pid = $this->order_model->insertGetId(
                        [
                            'order_time' => $order_time,
                            'order_batch_num' => 'QTSRD' . now_time() ,

                            'order_user_id' => session('admin.id'),
                            'remark' =>'时间：'.$crawl_time.' 程序自动生成来源:竞价活动',
                            'account_id'=>$account['id'],
                            'type'=>9, //其他收入单
                            'practical_price' => $other_receipt_price,
                            'paid_price' => $other_receipt_price,
                            'audit_status' => 0,//审核状态
                        ]
                    );
                    foreach ($other_receipt_list as &$item){
                        $item['pid'] = $pid;
                        $item['category_id']= empty($cate)? null: $cate['id']
                    ;}

                    $this->order_info_model->insertAll($other_receipt_list);

                }


                //生成退货单
                if ($return_stock_order_data != []){
                    $return_stock_order += 1;

                    $pid = $this->order_model->insertGetId(
                        [
                            'order_time' =>$order_time,
                            'order_batch_num' => 'CGTHD' . now_time(),
                            'order_user_id' => session('admin.id'),
                            'remark' => '时间：'.$start_time .' 采购退货单',
                            'account_id' => $account['id'],
                            'practical_price' => 0,
                            'paid_price' => 0,
                            'audit_status' => 0,//审核状态
                            'type' => 2,//采购退货单
                        ]
                    );

                    foreach ($return_stock_order_data as $return_item){
                        try {
                            if (strstr($return_item['sm'],'竞价域名')){
                                preg_match('/竞价域名[\w+\.]+/', $return_item['sm'], $matches);
                                $good_name = explode('竞价域名',$matches[0])[1];
                            }else{
                                preg_match('/得标域名[\w+\.]+/', $return_item['sm'], $matches);
                                $good_name = explode('得标域名',$matches[0])[1];
                            }
                            //插入采购退货单数据
                            $save_info = [
                                'good_name' =>$good_name,
                                'unit_price' => -$return_item['qian'],
                                'remark' => $return_item['sm'],
                                'pid' => $pid,
                                'category' =>'采购退货',
                                'account_id' => $account['id'],
                                'order_user_id' => session('admin.id'),
                            ];
                            $this->order_info_model->insert($save_info);
                        }catch (\Exception $e){
                            continue;
                        }

                    }

                }

            }
        }
        catch (\Exception $e){
            $this->error($e->getLine().'行 错误:'.$e->getMessage());
        }


        $result_data = [
            'code'=>1,
            'data'=>[],
            'msg'=>'采集成功 采购单：'.strval($caigou_order).'个<br>采购退货单：'.$return_stock_order.'个<br>调拨单：'.strval($diaobo_order).'个<br>其它收入单：'.strval($other_receipt_order).'个<br>转存提现单：'.strval($zhuanyi_order).'个'

        ];
        return json($result_data);
//        $this->success('采集成功,请刷新页面~');

    }

}
