<?php


namespace app\admin\controller\nod\purchase;

use app\admin\controller\JvMing;
use app\admin\model\NodAccount;
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
                    ->select()->toArray();

                $count = $this->order_model->where($where)->order('id', 'desc')->count();
            } else {
                $list = $this->order_model->where($where)
                    ->with(['getWarehouse', 'getAccount', 'getSupplier', 'getOrderUser'], 'left')
                    ->order('id', 'desc')
                    ->hasWhere('getOrderInfo', ['good_name' => $is_search_ym])
                    ->select()->toArray();
                $count = $this->order_model->where($where)->hasWhere('getOrderInfo', ['good_name' => $is_search_ym])->order('id', 'desc')->count();
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
                'unit_price|【购货单价】' => 'number|require',

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
        //获取库存
        $inventory_list = get_inventory();




        //获取所有账户名字
        $all_warehouse_data = $this->warehouse_model->select();
        //获取每个账户的资金明细  查询指定日期购买的域名 按照类型分类
        $caigou_order = 0;
        $xiaoshou_order = 0;
        $diaobo_order = 0;
        $other_receipt_order = 0;
        try {
            foreach ($all_warehouse_data as $warehouse_data) {
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
                //获取域名竞价得标单
                $last_quan_price = $this->jm_api->get_quan_price();
                foreach ($financial_data as $item) {
                    $log_data = $this->jvming_log->where('order_id','=',$item['id'])->where('cate','=','资金明细')->find();
                    if (!empty($log_data)) continue;
                    //判断是否在库存中 如果存在的话过滤
//                if (in_array($item['ym'], $inventory_list)) continue;
                    if ($item['lx_txt'] == '退款') continue;
                    if ($item['zu'] == '域名得标') {
                        if (!isset($jingjia_data[$item['ym']])) {
                            $jingjia_data[$item['ym']] = $item['qian'];
                        } else {
                            $jingjia_data[$item['ym']] += $item['qian'];
                        }

                    }
                    elseif ($item['zu'] == '一口价购买') {
                        if (!isset($yikoujia_data[$item['ym']])) {
                            $yikoujia_data[$item['ym']] = $item['qian'];
                        } else {
                            $yikoujia_data[$item['ym']] += $item['qian'];
                        }

                    }
                    elseif ($item['zu'] == '域名注册(券)' ) {
                        $quan_data[$item['ym']] = -$last_quan_price;
                    }
                    elseif($item['zu'] =='竞价活动'){
                        $other_receipt_data[$item['ym']] = $item['qian'];
                    }

                }





                //获取同行push数据
                $all_push_data = $this->jm_api->get_ruku_list(start_time: $start_time, end_time: $end_time);

                foreach ($all_push_data as $item) {
                    $log_data = $this->jvming_log->where('order_id','=',$item['id'])->where('cate','=','资金明细')->find();
                    if (!empty($log_data)) continue;

                    foreach ($item['ymlb'] as $v) {
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

                //获取账号id
                $account = $this->account_model->where('name','=',$warehouse_data['name'])->find();

                //所有金额

                $order_time =  date('Y-m-d H:i:s');
                //遍历所有分类数据  插入订单
                $supplier = '';
                $remark = '';
                foreach ($all_cate_data as $cate=>$data){
                    $paid_price = calculator_paid_price($data);
                    if ($cate == '一口价'){
                        //获取来单渠道id
                        $supplier = $this->supplier_model->where('name','=','一口价购买')->find();
                        $remark = '一口价购买';
                    }
                    elseif ($cate == '竞价'){
                        //获取来单渠道id
                        $supplier = $this->supplier_model->where('name','=','域名得标')->find();
                        $remark = '域名得标';
                    }
                    elseif ($cate == 'push'){
                        //获取来单渠道id
                        $supplier = $this->supplier_model->where('name','=','同行push')->find();
                        $remark = '同行push';
                    }
                    elseif ($cate == '券'){
                        //获取来单渠道id
                        $supplier = $this->supplier_model->where('name','=','域名注册')->find();
                        $remark = '域名注册';
                    }
                    if ($data == []) continue;
                    $caigou_order += 1;
                    //组装数据插入数据库
                    $insert_order = [
                        'order_time' =>$order_time,
                        'order_batch_num' => 'GHD' . date('YmdHis'),
                        'order_user_id' => session('admin.id'),
                        'remark' => $remark,
                        'warehouse_id' => $warehouse_data['id'],
                        'account_id'=> empty($account) ?'':$account['id'],
                        'supplier_id'=> empty($supplier) ?'':$supplier['id'],
                        'practical_price'=>-$paid_price,
                        'paid_price'=>-$paid_price,
                        'audit_status' => 0,//审核状态
                    ];
//               dd($insert_yikoujia_order,$yikoujia_data,$jingjia_data,$push_data,$quan_data);

                    $pid = $this->order_model->insertGetId($insert_order);
                    foreach ($data as $ym=>$price){
                        $save_yikoujia_info = [
                            'good_name' => $ym,
                            'unit_price' => -$price,
                            'remark' => '',
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
                $push_list = $this->jm_api->get_push_list($start_time,$end_time);
                if ($push_list['code'] == 999){
                    $this->error('部分采集成功 ！  当前错误:'.$push_data['msg']);
                }


                $insert_all = [];
                $sale_order_info = [];
                foreach ($push_list['data']  as $item){
                    $log_data = $this->jvming_log->where('order_id','=',$item['id'])->where('cate','=','调拨单')->find();
                    if (!empty($log_data)) continue;
                    //需要获取目标仓库的id
                    $mubiao_werahouse = $this->warehouse_model->where('name','=',$item['puid'])->find();
                    //如果不存在 生成销售单 金额为0
                    if (empty($mubiao_werahouse)){
                        $c_list =explode(',',$item['ymlbx']);
                        foreach ($c_list as $ym){
                            $sale_order_info[] = [
                                'good_name' =>$ym,
                                'remark' => isset($item['remark']) ? $item['remark'] : '',
                                'account_id' =>$account['id'],
                                'sale_time' => $order_time,
                            ];
                        }

                        continue;
                    }


                    //判断域名是否已经在此仓库下 如果在则不转移了
                    $zy_ym_list = [];
                    $warehouse_ym_list = get_warehouse_ym($item['puid']);
                    $c_list =explode(',',$item['ymlbx']);
                    foreach ($c_list as $ym){
                        if (in_array($ym,$warehouse_ym_list))continue;
                        $zy_ym_list[] = $ym;
                    }


                    //如果没有过滤掉
                    if ($zy_ym_list ==[])continue;
                    //生成调拨单
                    $push_order = [
                        'order_time' => $order_time,
                        'order_batch_num' => 'DBD' . date('YmdHis'),
                        'order_user_id' => session('admin.id'),
                        'remark' => '程序批量生成 .'.$warehouse_data['name'] .' 发送到：'.$item['puid'],
                        'warehouse_id' => $mubiao_werahouse['id'],
                        'type' => 7, //调拨单
                        'audit_status' => 0,//审核状态
                    ];
                    //插入调拨单 获取插入id
                    $diaobo_order += 1;
                    $pid = $this->order_model->insertGetId($push_order);


                    foreach ($zy_ym_list as $ym) {
                        $save_info = [
                            'good_name' => $ym,
                            'remark' => '',
                            'category' =>'调拨单',
                            'pid' => $pid,
                            'warehouse_id' => $mubiao_werahouse['id'],
                            'order_time' => $order_time,
                        ];
                        $insert_all[] = $save_info;

                    }
                }
                if ($insert_all != []){
                    $this->order_info_model->insertAll($insert_all);
                }

                //判断是否有销售单
                if ($sale_order_info != []){
                    //保存一个销售单的数据
                    $sale_order = [
                        'order_time' => $order_time,
                        'order_batch_num'=>'XSD' . date('YmdHis'),
                        'order_user_id' => session('admin.id'),
                        'remark' =>'程序批量生成转移销售单',
                        'practical_price' =>0,
                        'paid_price' => 0,
                        'type' => 3, //调拨单
                        'audit_status' => 0,//审核状态

                    ];
                    $pid = $this->order_model->insertGetId($sale_order);

                    foreach ($sale_order_info as &$item){
                        $item['pid'] = $pid;
                    };
                    $this->order_info_model->insertAll($sale_order_info);
                    $xiaoshou_order+=1;
                }

                //生成其它收款单
                foreach ($other_receipt_data as $ym=>$price){
                    $pid = $this->order_model->insertGetId(
                        [
                            'order_time' => $order_time,
                            'order_batch_num' => 'QTSRD' . date('YmdHis') ,
                            'order_user_id' => session('admin.id'),
                            'remark' =>'程序自动生成来源:竞价活动',
                            'account_id'=>$account['id'],
                            'type'=>9, //其他收入单
                            'practical_price' => $price,
                            'paid_price' => $price,
                            'audit_status' => 0,//审核状态
                        ]
                    );
                    //保存详情
                    $this->order_info_model->insert(
                        [
                            'category' => '其他收入单',
                            'unit_price' => $price,
                            'remark' =>  '竞价活动 '.$ym,
                            'pid' => $pid,
                            'order_user_id' => session('admin.id'),
                            'account_id' => $account['id'],
                        ]
                    );
                    $other_receipt_order += 1;

                }

                //保存log
                save_jvming_order_log($all_push_data,'同行push',$username,$crawl_time);
                save_jvming_order_log($financial_data,'资金明细',$username,$crawl_time);
                save_jvming_order_log($push_list['data'],'调拨单',$username,$crawl_time);
            }
        }catch (\Exception  $e){
            dd($e->getMessage());
        }


        $result_data = [
            'code'=>1,
            'data'=>[],
            'msg'=>'采集成功 采购单：'.strval($caigou_order).'个<br>销售单：'.strval($xiaoshou_order).'个<br>调拨单：'.strval($diaobo_order).'个<br>其它收入单：'.strval($other_receipt_order).'个 '

        ];
        return json($result_data);
//        $this->success('采集成功,请刷新页面~');

    }

}
