<?php


namespace app\admin\controller\nod\purchase;

use app\admin\controller\JvMing;
use app\admin\model\NodAccount;
use app\admin\model\NodAccountInfo;
use app\admin\model\NodCustomerManagement;
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
        $this->account_info_model = new NodAccountInfo();
        $this->order_model = new NodOrder();
        $this->order_info_model = new NodOrderInfo();
        $this->inventory_model = new NodInventory();
        $this->jvming_log = new NodJvMingOrderLog();

    }

    /**
     * @NodeAnotation(title="销货单列表")
     */
    public function index()
    {

        if ($this->request->isAjax()){

            list($page, $limit, $where) = $this->buildTableParames();

            $where[] = ['type','=',3];


            $is_search_ym = false;
            foreach ($where as $w){
                if ($w[0] == 'order_info') $is_search_ym = $w[2];
            }
            $where = delete_where_filter($where,'order_info');
            $where = format_where_datetime($where,'order_time');
            //判断是否查询了域名
            if ($is_search_ym == false){
                $list = $this->order_model->where($where)
                    ->with(['getWarehouse','getAccount','getSupplier','getOrderUser','getCustomer','getCustomer','getSaleUser'],'left')
                    ->order('id','desc')
                    ->page($page,$limit)
                    ->select()->toArray();

                $count = $this->order_model->where($where)->order('id','desc')->count();
            }else{
                $list = $this->order_model->where($where)
                    ->with(['getWarehouse','getAccount','getSupplier','getOrderUser','getCustomer','getSaleUser'],'left')
                    ->hasWhere('getOrderInfo',[['good_name', 'in', $is_search_ym]])
                    ->order('id','desc')
                    ->page($page,$limit)
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
     * @NodeAnotation(title="录入销货单数据")
     */
    public function add()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $post = htmlspecialchars_decode($post['data']);
            $post = (json_decode($post,true));

            $post['practical_price'] == '0'&& $this->error('单据金额不能为0');
            if ($post['practical_price'] < $post['paid_price']) $this->error('实际金额不能大于单据金额！');



            $order_info_rule = [
                'order_time|【单据日期】' => 'require|date',
                'customer|【客户】' => 'require',
                'account_id|【账户】' => 'require|number',
                'practical_price|【单据金额】' => 'number|require',
                'paid_price|【实收金额】' => 'number|require',
                'sale_user_id|【销售员】' => 'number|require',
            ];

            $this->validate($post, $order_info_rule);
            //检查单据金额是否与内容一样
            check_practical_price($post['practical_price'],$post['goods'])|| $this->error('单据中的内容与单据金额不付~ 请重新计算');
            $rule = [
                'good_name|【商品信息】' => 'require',
                'unit_price|【售货单价】' => 'number|require',

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
            $customer = $this->kehu_model->where('user_id','=',session('admin.id'))->where('name','=',$post['customer'])->find();
            if (empty($customer)){
                $customer_id = $this->kehu_model->insertGetId(['name'=>$post['customer'],'user_id'=>session('admin.id')]);
            }else{
                if ($customer['receivable_price'] > 0){
//                    $this->error('当前客户应收款：'.$customer['receivable_price'].'元  请先结清上一笔款项！！');
                }
                $customer_id = $customer['id'];
            }


            //单据编号自动生成   XHD+时间戳
            $order_batch_num = 'XHD' .  now_time();

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
                'sale_user_id' => $post['sale_user_id'],//销售员
                'type'=>3,//售货单

            ];
            //获取pid   保存商品详情

            $pid = $this->order_model->insertGetId($save_order);

            $insert_all = [];

            foreach ($post['goods'] as $item) {
                $save_info = [
                    'good_name' => $item['good_name'],
                    'unit_price' => $item['unit_price'],
                    'remark' => isset($item['remark']) ? $item['remark'] : '',
                    'category' =>'销售',
                    'pid' => $pid,
                    'warehouse_id' => $ym_dict[$item['good_name']]['warehouse_id'],
                    'customer_id' => $customer_id,
                    'account_id' => $post['account_id'],
                    'supplier_id' => $ym_dict[$item['good_name']]['supplier_id'],
                    'sale_time' => $post['order_time'],
                    'order_time' => $post['order_time'],
                    'sale_user_id' => $post['sale_user_id'],//销售员
                    'order_user_id' => session('admin.id'),

                ];
                $insert_all[] = $save_info;

            }
            $this->order_info_model->insertAll($insert_all);
            $this->success('保存成功~');

        }
        $account_list = $this->account_model->field('id,name')->select()->toArray();
        $warehouse_list = $this->warehouse_model->field('id,name')->select()->toArray();

        $this->assign('account_list', $account_list);
        $this->assign('warehouse_list', $warehouse_list);
        $this->assign('admin', session('admin'));
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
            $post['practical_price'] == '0'&& $this->error('单据金额不能为0');
            $post['practical_price'] = intval($post['practical_price'] );
            $post['paid_price'] = intval($post['paid_price'] );

            $order_info_rule = [
                'order_time|【单据日期】' => 'require|date',
                'customer|【客户】' => 'require',
                'account_id|【账户】' => 'require|number',
                'practical_price|【单据金额】' => 'float|require',
                'paid_price|【实收金额】' => 'float|require',
                'sale_user_id|【销售员】' => 'number|require',
            ];

            $this->validate($post, $order_info_rule);
            //检查单据金额是否与内容一样
            check_practical_price($post['practical_price'],$post['goods'])|| $this->error('单据中的内容与单据金额不付~ 请重新计算');
            $rule = [
                'good_name|【商品信息】' => 'require',
                'unit_price|【购货单价】' => 'number|require',

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
                if (count($dif) !=0 ){
                    $this->error('下列商品不在库存中，请尽快入库 共：'.count($dif).'个<br>'.join("<br>",$dif),wait: 10);

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
                'practical_price' => $post['practical_price'],
                'paid_price' => $post['paid_price'],
                'audit_status' => 0,//审核状态
                'sale_user_id' => $post['sale_user_id'],//销售员
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
                        'sale_time' => $post['order_time'],
                        'sale_user_id' => $post['sale_user_id'],//销售员
                    ];
                    $this->order_info_model->where('id','=',$item['id'])->update($save_info);
                }else{
                    $save_info = [
                        'pid'=>$id,
                        'good_name' => $item['good_name'],
                        'unit_price' => $item['unit_price'],
                        'remark' => isset($item['remark']) ? $item['remark'] : '',
                        'customer_id' => $customer_id,
                        'account_id' => $post['account_id'],
                        'sale_time' => $post['order_time'],
                        'sale_user_id' => $post['sale_user_id'],//销售员
                        'order_user_id' => session('admin.id'),
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


    /**
     * @NodeAnotation(title="抓取销货数据")
     */
    public function crawl_order_data($crawl_time,$warehouse_id)
    {

        $warehouse = $this->warehouse_model->find($warehouse_id);

        $error_data = [];
        $list = [];
        $index = 0;


        $username = $warehouse['account'];
        $password = $warehouse['password'];
        $cookie = $warehouse['cookie'];
        //获取所有域名 信息
        $jm_api = new JvMing($username, $password, $cookie);
        $all_ym_data = $jm_api->get_sale_ym($crawl_time,$crawl_time);
        if ($all_ym_data['code'] ==999){
            $error_data[] = $all_ym_data['msg'];
        }
        if ($all_ym_data['code'] != 1){
            return json(['code'=>0,
                'msg'=>$all_ym_data['msg'],
                'data'=>[]
            ]);
        }

        $sale_good_data = $this->account_info_model->field('good_name')
            ->whereRaw("DATE_FORMAT(operate_time,'%Y-%m-%d') = ".$crawl_time)
//            ->where('operate_time','=',$crawl_time)
            ->group('good_name')->select()->toArray();

        $sale_data = [];
        foreach ($sale_good_data as $item){
            $sale_data[] = $item['good_name'];
        }


        foreach ($all_ym_data['data'] as $item){
            //今日审核过的销售域名过滤
            if (in_array($item['ym'],$sale_data)){
                continue;
            }

            $list[] = [
                'index'=>$index+1,
                'unit_price' => intval($item['wtqian']),
                'good_name' => $item['ym'],
                'remark' => '',
            ];
            $index+=1;
        }


        $data = [
            'code' => 1,
            'data' => $list
        ];

        return json($data);


    }

    /**
     * @NodeAnotation(title="抓取所有销售订单数据")
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
        $all_warehouse_data = $this->warehouse_model->select();
        //获取每个账户的资金明细  查询指定日期购买的域名 按照类型分类
        $sale_order = 0;
        $warehouse_name_list = [];
        foreach ($all_warehouse_data as $w) {
            $warehouse_name_list[] = $w['name'];
        }
        try {
            foreach ($all_warehouse_data as $warehouse_data) {
                //获取账号id
                $account = $this->account_model->where('name','=',$warehouse_data['name'])->find();
                if (empty($account)){
                    continue;
                }
                $username = $warehouse_data['account'];
                $password = $warehouse_data['password'];
                $cookie = $warehouse_data['cookie'];
                $this->jm_api = new JvMing($username, $password, $cookie);


                //获取出售订单
                $all_sale_data = $this->jm_api->get_sale_ym($start_time,$end_time);
                if ($all_sale_data['code'] == 999){
                    $this->error($all_sale_data['msg']);
                }

                $yikoujia_data = []; //一口价 销售购单
                //获取域名竞价得标单
                foreach ($all_sale_data['data'] as $item) {

                    //判断销售单是否存在
                    if (check_order_exist(ym: $item['ym'],time: $start_time,cate: 3) == true){
                        continue;
                    }
                    if ($item['zt_txt'] == '已出售') {
                        $yikoujia_data[$item['ym']] = $item['wtqian'];

                    }
                }
                //生成销售单
                $paid_price = calculator_paid_price($yikoujia_data);
                $order_time = $crawl_time;
                //单据编号自动生成   XHD+时间戳
                $save_order = [
                    'order_time' => $order_time,
                    'order_batch_num' => 'XHD' .  now_time(),
                    'order_user_id' => session('admin.id'),
                    'remark' => '销售日期为：'.$start_time .'一口价出售',
                    'account_id' => $account['id'],
                    'practical_price' => $paid_price,
                    'paid_price' => $paid_price,
                    'audit_status' => 0,//审核状态
                    'sale_user_id' => session('admin.id'),//销售员
                    'type'=>3,//售货单
                ];
                if ($yikoujia_data != []){
                    $sale_order +=1;
                    $pid = $this->order_model->insertGetId($save_order);

                    $insert_all = [];
                    foreach ($yikoujia_data as $ym=>$price) {
                        $save_info = [
                            'good_name' => $ym,
                            'unit_price' => $price,
                            'remark' => '销售日期为:'.$start_time.' 一口价出售',
                            'category' =>'销售',
                            'pid' => $pid,
                            'warehouse_id' => $warehouse_data['id'],
                            'account_id' => $account['id'],
                            'sale_time' => $order_time,
                            'order_time' => $order_time,
                            'sale_user_id' => session('admin.id'),//销售员
                            'order_user_id' => session('admin.id'),

                        ];
                        $insert_all[] = $save_info;

                    }
                    $this->order_info_model->insertAll($insert_all);
                }



                //获取发送的域名
                $push_data = $this->jm_api->get_push_list($start_time,$end_time);
                //遍历 判断是否在 自己的仓库列表中
                foreach ($push_data['data'] as $item){

                    //如果在自己的仓库列表中  过滤掉
                    if ($item['zt_txt'] != '已接受')continue;
                    //如果是自己账号都过滤掉 因为都开调拨单了
                    if (in_array($item['puid'],$warehouse_name_list)) continue;
                    //判断是否存在
                    $c_list = explode(',',$item['ymlbx']);
                    $new_c_list =$c_list;
                    foreach ($new_c_list as $index=>$ym){
                        if (check_order_exist(ym:$ym,time: $start_time,cate: 3) == true){
                            unset($c_list[$index]);
                        }
                    }

                    if ($c_list == [])continue;
                    $sale_order +=1;
                    //一行一单
                    $save_order = [
                        'order_time' => $order_time,
                        'order_batch_num' => 'XHD' .  now_time(),
                        'order_user_id' => session('admin.id'),
                        'remark' => '时间：'.$start_time.' PUST 发送到：'.$item['puid'],
                        'account_id' => $account['id'],
                        'practical_price' => $item['qian'],
                        'paid_price' => $item['qian'],
                        'audit_status' => 0,//审核状态
                        'sale_user_id' => session('admin.id'),//销售员
                        'type'=>3,//售货单
                    ];

                    $pid = $this->order_model->insertGetId($save_order);

                    $one_good_price = $item['qian']/count($c_list);
                    $insert_all = [];
                    foreach ($c_list as $ym) {
                        $save_info = [
                            'good_name' => $ym,
                            'unit_price' => $one_good_price,
                            'remark' => '时间：'.$start_time.' PUST：'.$item['puid'],
                            'category' =>'销售',
                            'pid' => $pid,
                            'warehouse_id' => $warehouse_data['id'],
                            'account_id' => $account['id'],
                            'sale_time' => $order_time,
                            'order_time' => $order_time,
                            'sale_user_id' => session('admin.id'),//销售员
                            'order_user_id' => session('admin.id'),

                        ];
                        $insert_all[] = $save_info;

                    }
                    if ($insert_all != []){
                        $this->order_info_model->insertAll($insert_all);

                    }

                }


                //获取转出域名列表
                $zhuanchu_data = $this->jm_api->get_zhuanchu_list($start_time,$end_time);
                //遍历
                $insert_zhuanchu_data = [];
                foreach ($zhuanchu_data['data'] as $item){

                    //如果在自己的仓库列表中  过滤掉
                    if ($item['zt_txt'] !='转出成功'){
                        continue;
                    }
                    //如果已经录入  过滤掉
                    if (check_order_exist(ym:$item['ym'],time: $start_time,cate: 3) == true){
                       continue;
                    }

                    $insert_zhuanchu_data[] = [
                        'good_name' => $item['ym'],
                        'unit_price' => 0,
                        'remark' => '时间：'.$start_time.' 转出方式：'.$item['fs'],
                        'category' =>'销售',
                        'warehouse_id' => $warehouse_data['id'],
                        'account_id' => $account['id'],
                        'sale_time' => $order_time,
                        'order_time' => $order_time,
                        'sale_user_id' => session('admin.id'),//销售员
                        'order_user_id' => session('admin.id'),
                    ];


                }

                //保存转出域名
                if ($insert_zhuanchu_data != []){
                    $sale_order += 1;
                    $save_order = [
                        'order_time' => $order_time,
                        'order_batch_num' => 'XHD' .  now_time(),
                        'order_user_id' => session('admin.id'),
                        'remark' =>  '时间：'.$start_time.' 程序生成转出域名',
                        'account_id' => $account['id'],
                        'practical_price' => 0,
                        'paid_price' => 0,
                        'audit_status' => 0,//审核状态
                        'sale_user_id' => session('admin.id'),//销售员
                        'type'=>3,//售货单
                    ];

                    $pid = $this->order_model->insertGetId($save_order);
                    foreach ($insert_zhuanchu_data as &$item){
                        $item['pid'] = $pid;
                    }
                    $this->order_info_model->insertAll($insert_zhuanchu_data);
                }


            }




        }catch (\Exception  $e){
            $this->error($e->getLine().'行 错误:'.$e->getMessage());
        }


        $result_data = [
            'code'=>1,
            'data'=>[],
            'msg'=>'采集成功 销售单：'.strval($sale_order).'个'

        ];
        return json($result_data);
//        $this->success('采集成功,请刷新页面~');

    }
}
