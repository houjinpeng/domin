<?php


namespace app\admin\controller\domain;


use app\admin\controller\JvMing;
use app\admin\controller\JvMingApi;
use app\admin\model\DomainAttentionYm;
use app\admin\model\DomainAttentionYmLog;
use app\admin\model\DomainCrawlStore;
use app\admin\model\DomainStore;
use app\admin\model\NodAccount;
use app\admin\model\NodWarehouse;
use app\admin\model\SystemAdmin;
use app\admin\service\TriggerService;
use app\common\constants\AdminConstant;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use EasyAdmin\tool\CommonTool;
use jianyan\excel\Excel;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use think\App;
use think\facade\Db;
use function GuzzleHttp\Promise\all;

/**
 * @ControllerAnnotation(title="关注域名")
 */
class AttentionYm extends AdminController
{

    use \app\admin\traits\Curd;

    protected $sort = [
        'id' => 'asc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new DomainAttentionYm();
        $this->model_log = new DomainAttentionYmLog();
        //账户表
        $this->account_model = new NodWarehouse();
        //聚名API
        $this->jm_api = new JvMingApi();


    }


    /**
     * @NodeAnotation(title="列表")
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($page, $limit, $where) = $this->buildTableParames();
            $where = format_where_datetime($where, 'update_time');
            $where = format_where_datetime($where, 'get_time');
            $count = $this->model
                ->where($where)
                ->count();
            $list = $this->model
                ->where($where)
                ->withJoin('getStore','left')
                ->page($page, $limit)
                ->order($this->sort)
                ->select();
            $new_list = [];
            foreach ($list as &$item){
                $c = $item->toArray();
                $c['logs']= $item->getLogs()->select()->toArray();
                $new_list[] = $c;
            }

            //获取所有数据过滤掉成本价为0的
            $cost_price = $this->model
                ->where($where)
                ->where('cost_price', '<>', 0)
                ->select();

            $all_sale_price = 0;
            $all_cost_price = 0;
            $all_lirun_price = 0;
            $all_lirun_lv = 0;
            unset($item);
            foreach ($cost_price as $item) {
                $all_sale_price += $item['sale_price'];
                $all_cost_price += $item['cost_price'];
                $all_lirun_price += round($item['sale_price'] - $item['cost_price'], 2);
            }
            if ($all_cost_price != 0) {
                $all_lirun_lv = round(($all_sale_price - $all_cost_price) / $all_sale_price * 100, 2);
            }


            $data = [
                'code' => 0,
                'msg' => '',
                'count' => $count,
                'data' => $new_list,
                'all_sale_price' => $all_sale_price,
                'all_cost_price' => $all_cost_price,
                'all_lirun_price' => $all_lirun_price,
                'all_lirun_lv' => $all_lirun_lv,
            ];
            return json($data);
        }

        $result_url = sysconfig('spider','attention');
        $this->assign('result_url',$result_url);
        return $this->fetch();
    }


    /**
     * @NodeAnotation(title="编辑")
     */
    public function edit($id)
    {
        $row = $this->model->where('id', 'in', $id)->select();
        empty($row) && $this->error('没有关注改域名');

        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $this->model->where('id', 'in', $id)->update($post);
            $this->success('修改成功');

        }
        if (count(explode(',', $id)) > 1) {
            $this->assign('type', 'batch');
        } else {
            $this->assign('type', 'one');
        }
        $this->assign('row', $row[0]);
        return $this->fetch();


    }


    /**
     * @NodeAnotation(title="批量编辑")
     */
    public function batch_edit()
    {

        if ($this->request->isAjax()) {
            $post = $this->request->post();

            //先获取修改批量修改备注数据
            $all_remark = explode("\n", htmlspecialchars_decode($post['batch_remark']));
            foreach ($all_remark as $remark) {
                if (trim($remark) == '') continue;
                $remark = explode("|", $remark);
                //修改备注信息
                $this->model->where('ym', '=', $remark[0])->update(['remark' => $remark[1]]);
            }

            //修改成本价信息
            $all_cost_price = explode("\n", htmlspecialchars_decode($post['batch_cost_price']));

            foreach ($all_cost_price as $data) {
                if (trim($data) == '') continue;
                $data = explode("|", $data);

                $row = $this->model->where('ym', '=', $data[0])->find();
                if (empty($row)) continue;
                //修改成本价信息
                $row->save([
                    'cost_price' => $data[1],//成本价
                    'profit_cost' => $row['sale_price'] - intval($data[1]),//利润
                    'profit_cost_lv' => ($row['sale_price'] - intval($data[1])) / $row['sale_price'] * 100,//利润率
                ]);
            }


            $this->success('修改成功');

        }

        return $this->fetch();


    }


    /**
     * @NodeAnotation(title="导出")
     */
    public function export()
    {
        list($page, $limit, $where) = $this->buildTableParames();
        $where = format_where_datetime($where, 'update_time');
        $where = format_where_datetime($where, 'get_time');
        $tableName = $this->model->getName();
        $tableName = CommonTool::humpToLine(lcfirst($tableName));
        $prefix = config('database.connections.mysql.prefix');
        $dbList = Db::query("show full columns from {$prefix}{$tableName}");

        foreach ($dbList as $vo) {
            $comment = !empty($vo['Comment']) ? $vo['Comment'] : $vo['Field'];
            if (!in_array($vo['Field'], $this->noExportFields)) {
                $header[] = [$comment, $vo['Field']];
            }
        }
        $header = [];
        $header[] = ['id', 'id'];
        $header[] = ['账号名称', 'account'];
        $header[] = ['域名', 'ym'];


        $list = $this->model
            ->withJoin('getStore','left')
            ->where($where)
            ->limit(100000)
            ->order('id', 'desc')
            ->select();

        $sales = 1;
        $new_list = [];
        $ym_list = [];
        foreach ($list as $item){
            $ym_list[] = $item['ym'];
        }
        //获取所有历史信息域名
        $logs_list = $this->model_log->where('ym','in',$ym_list)->select()->toArray();
        $ym_log_dict = [];
        foreach ($logs_list as $item){
            if (!isset($ym_log_dict[$item['ym']])){
                $ym_log_dict[$item['ym']] = [];
            }
            $ym_log_dict[$item['ym']][] = $item;
        }

        //开始获取数据
        foreach ($list as &$item){
            $c = $item->toArray();
            $c['logs']= isset($ym_log_dict[$item['ym']])?$ym_log_dict[$item['ym']]:[];
            $c['sale_price1'] = $item['sale_price'];
            $c['store_id1'] = $item['store_id'];

            if (count($c['logs']) > 0){
                $sales = count($c['logs'] )+ 1;
                foreach ($c['logs'] as $index=>$l){
                    $c['sale_price'.$index+1] = $l['sale_price'];
                    $c['store_id'.$index+1] = $l['store_id'];
                }


            }

            $new_list[] = $c;
        }


        for ($i = $sales; $i >= 1; $i--) {
            $header[] = ['售价'.$i, 'sale_price'.$i];
        }
        for ($i = $sales; $i >= 1; $i--) {
            $header[] = ['卖家'.$i, 'store_id'.$i];
        }
        $header[] = ['关注日期', 'like_time'];
        $header[] = ['拿货日期', 'get_time'];
        $header[] = ['更新时间', 'update_time'];
        $header[] = ['成本价', 'cost_price'];
        $header[] = ['利润', 'profit_cost'];
        $header[] = ['利润率', 'profit_cost_lv'];
        $header[] = ['备注', 'remark'];
        $header[] = ['出售状态', 'sale_status'];
        $header[] = ['渠道', 'channel'];
        $header[] = ['注册商', 'zcs'];
        $header[] = ['所属团队', 'getStore.team'];

        $fileName = '关注域名' . time();
        return Excel::exportData($new_list, $header, $fileName, 'xlsx');
    }


    /**
     * @NodeAnotation(title="更新所有关注数据")
     */
    public function crawl()
    {
        $like_time = date('Y-m-d H:s:i');
        $all_data = [];
        //获取全部账号
        $all_account = $this->account_model->where('status', '=', 1)->select()->toArray();
        foreach ($all_account as $account) {
            $this->jvming_api = new JvMing($account['account'], $account['password'], $account['cookie']);
            $data = $this->jvming_api->get_gzlist();
            $all_data = array_merge($all_data, $data);

        }


        $all_data_array = $this->model->select();
        $all_ym_detail = [];
        foreach ($all_data_array as $item) {
            $all_ym_detail[$item['ym']] = $item;
        }

        $insert_data = [];
        $ym_list = [];
        foreach ($all_data as $data) {

            $ym_list[] = $data['ym'];
            //判断域名是否存在 如果存在更新 不插入

            if (isset($all_ym_detail[$data['ym']])) {
                //判断是否有改变   出售价格 店铺id
                if ($all_ym_detail[$data['ym']]['sale_price'] != $data['qian'] || $all_ym_detail[$data['ym']]['store_id'] != $data['uid']) {
                    $c = $all_ym_detail[$data['ym']]->toArray();
                    unset($c['id']);
                    DomainAttentionYmLog::insert($c);
                }

                $all_ym_detail[$data['ym']]->where('ym','=',$data['ym'])->update([
                    'update_time' => $data['gxsj'], //更新时间
                    'remark' => $ym_row['remark'] ?? $data['bz'], //备注
                    'sale_status' => $data['zt_txt'],//出售状态
                    'store_id' => $data['uid'],
                    'ym_id' => $data['id'], //域名id
                    'sale_price' => $data['qian'],//价格
                    'account' => $data['account'],//账户
                    'crawl_status' => 0,//爬虫状态修改为0
                ]);
                continue;
            }

            $insert_data[] = [
                'account' => $data['account'],
                'ym' => $data['ym'],
                'ym_id' => $data['id'],
                'like_time' => $like_time,
                'update_time' => $data['gxsj'],
                'sale_price' => $data['qian'],
                'store_id' => $data['uid'],
                'remark' => $data['bz'],
                'sale_status' => $data['zt_txt'],

            ];
        }
        if (count($insert_data) != 0){
            $this->model->insertAll($insert_data);

        }

        //删除不在列表中的域名
        $this->model->where('ym', 'not in', $ym_list)->delete();

        $this->success('添加成功，2分钟后自动补全任务启动~ 请耐心等待！');
        try {

        } catch (\Exception $e) {
            $this->error('更新失败 ' . $e->getMessage());
        }

    }


    /**
     * @NodeAnotation(title="取消关注")
     */
    public function cancel_like($ym_id)
    {
        $all_account = $this->account_model->where('status', '=', 1)->select()->toArray();
        foreach ($all_account as $account) {
            $this->jvming_api = new JvMing($account['account'], $account['password'], $account['cookie']);
            $result = $this->jvming_api->qx_gz($ym_id);
            if ($result['msg'] == '取消关注成功') {
                $this->model->where('ym_id', '=', $ym_id)->delete();
            }
        }
        $this->success('取消关注成功');

    }


    /**
     * @NodeAnotation(title="批量取消关注")
     */
    public function cancel_like_batch($id)
    {

        $all_data = $this->model->where('id', 'in', $id)->select();
        $all_account = $this->account_model->where('status', '=', 1)->select()->toArray();

        foreach ($all_data as $item) {
            foreach ($all_account as $account) {
                $this->jvming_api = new JvMing($account['account'], $account['password'], $account['cookie']);
                $result = $this->jvming_api->qx_gz($item['ym_id']);
                if ($result['msg'] == '取消关注成功') {
                    $this->model->where('ym_id', '=', $item['ym_id'])->delete();
                }
            }
        }

        $this->success('取消关注成功');


    }

    /**
     * @NodeAnotation(title="关注域名")
     */
    public function attention()
    {

        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $all_account = $this->account_model->where('status', '=', 1)->select()->toArray();

            $data = json_decode(htmlspecialchars_decode($post['data']), true);

            foreach ($data as $item) {
                $insert_data = [
                    'ym' => $item['ym'],
                    'ym_id' => $item['id'],
                    'like_time' => date('Y-m-d H:s:i'),
                    'update_time' => $item['zcsj'],
                    'sale_price' => $item['jg'],
                    'store_id' => $item['sid'],
                    'remark' => $item['ms'],
                    'sale_status' => '出售中',
                    'zcs' => $item['zcs'],  //注册商

                ];

                foreach ($all_account as $account) {
                    $insert_data['account'] = $account['account'];
                    $this->jvming_api = new JvMing($account['account'], $account['password'], $account['cookie']);
                    $result = $this->jvming_api->add_gz($item['id']);
                    //如果关注成功 保存关注列表内容
                    if ($result['msg'] == '已关注') {
                        //判断库里面是否有这个域名  没有的话添加 有则忽略
                        $ym_row = $this->model->where('ym', '=', $item['ym'])->find();
                        if (empty($ym_row)) {
                            $this->model->insert($insert_data);
                        }
                    } else {
                        $this->error('账号：' . $account['account'] . ' ' . $result['msg']);
                    }

                }


            }


            $this->success('关注成功~');

        }

        return $this->fetch();
    }


    /**
     * @NodeAnotation(title="搜索域名")
     */
    public function search_ym()
    {

        $get = $this->request->get();


        $form_params = [
            'gjz_cha' => $get['ym'],
            'page' => $get['page'],
            'psize' => $get['limit'],
        ];
        $result = $this->jm_api->get_yikoujia_list($form_params);
        return $result;
    }


    /**
     * @NodeAnotation(title="定时任务 更新关注域名数据")
     */
    public function crawl_attention_data()
    {
        //获取需要更新的数据
        $all_data = $this->model->where('crawl_status', '=', 0)
            ->where('cost_price', '=', 0)
            ->limit(500)->select()->toArray();
        //获取所有域名列表
        $all_ym = array_map(function ($x) {
            return $x['ym'];
        }, $all_data);

        //获取所有域名售价
        $sale_price = [];
        foreach ($all_data as $data) {
            $sale_price[$data['ym']] = $data['sale_price'];
        }

        if ($all_ym == []) {
            return '更新成功';
        }
        //所有账户
        $all_account = $this->account_model->where('status', '=', 1)->select()->toArray();
        foreach ($all_account as $account) {
            //获取竞价列表
            $this->jvming_api = new JvMing($account['account'], $account['password'], $account['cookie']);
            $result = $this->jvming_api->get_jj_list($all_ym);
            //对比竞价列表中的数据
            $have_ym = [];
            foreach ($result['data'] as $item) {
                if (!isset($sale_price[$item['ym']])) continue;
                $have_ym[] = $item['ym'];
                $update = [
                    'get_time' => $item['jssj'],//结束时间
                    'cost_price' => $item['sj_qian'],//成本价
                    'profit_cost' => $sale_price[$item['ym']] - $item['sj_qian'],//利润
                    'profit_cost_lv' => ($sale_price[$item['ym']] - $item['sj_qian']) / $sale_price[$item['ym']] * 100,//利润率
                    'crawl_status' => 1,//已抓取 为1
                ];
//                dd($update,1);
                $this->model->where('ym', '=', $item['ym'])->update($update);

            }
            //去重 取差集 之后查询的都是没有找到的
            $all_ym = array_diff($all_ym, $have_ym);
            //查询资金明细
            $result = $this->jvming_api->get_financial_details($all_ym);
            if (!$result) continue;
            $have_ym = [];
            foreach ($result as $ym => $item) {
                $have_ym[] = $ym;
                if (count($item) == 1) {
                    $cost_price = $item[0]['qian'];
                } else {
                    $cost_price = abs($item[1]['qian'] - $item[0]['qian']);
                }
                try {
                    if (!isset($item[0]['sj'])) {
                        dd($item);
                        return;
                    }
                    $update = [
                        'get_time' => $item[0]['sj'],//结束时间
                        'cost_price' => $cost_price,//成本价
                        'profit_cost' => $sale_price[$item[0]['ym']] - $cost_price,//利润
                        'profit_cost_lv' => ($sale_price[$item[0]['ym']] - $cost_price) / $sale_price[$item[0]['ym']] * 100,//利润率
                        'crawl_status' => 1,//已抓取 为1
                    ];
                } catch (\Exception $e) {
                    dd($e->getMessage(), $item);
                }
//                dd($update,2,$item);
                $this->model->where('ym', '=', $ym)->update($update);
            }

            //去重 取差集 之后查询的都是没有找到的
            $all_ym = array_diff($all_ym, $have_ym);

        }
        //将没找到的都改外已抓取
        $this->model->where('ym', 'in', $all_ym)->update(['crawl_status' => 1]);

        return '更新成功';
    }


    /**
     * @NodeAnotation(title="定时任务 更新关注域名渠道")
     */
    public function crawl_attention_channel()
    {
        $all_data = $this->model->where('channel', '=', null)->limit(500)->select();
        foreach ($all_data as $data) {
            $ym_data = $this->jm_api->get_ykj_info($data['ym']);
            //如果未在出售中   渠道为未知
            if ($ym_data['code'] == -1) {
                $data->save(['channel' => '未知']);
                continue;
            }

            if (strstr($ym_data['data']['dbsjsm'], '新增')) {
                $data->save(['channel' => '注册', 'get_time' => $ym_data['data']['dbsj'], 'zcs' => $ym_data['data']['zcs']]);
            } elseif (strstr($ym_data['data']['dbsjsm'], '得标')) {
                $data->save(['channel' => '竞价', 'get_time' => $ym_data['data']['dbsj'], 'zcs' => $ym_data['data']['zcs']]);
            } elseif (strstr($ym_data['data']['dbsjsm'], '入库')) {
                $data->save(['channel' => '入库', 'get_time' => $ym_data['data']['dbsj'], 'zcs' => $ym_data['data']['zcs']]);
            } else {
                $data->save(['channel' => '其他', 'get_time' => $ym_data['data']['dbsj'], 'zcs' => $ym_data['data']['zcs']]);
            }


        }


        return '更新成功';
    }


    /**
     * @NodeAnotation(title="分析报表")
     */
    public function fx_data()
    {
        //查询log中的所有数据

//        $all_log = $this->model_log->select()->toArray();
//        foreach ()

        if ($this->request->isAjax()){

            list($page, $limit, $where) = $this->buildTableParames();
            $count = $this->model_log
                ->where($where)
                ->group('ym')
                ->count();
            //先分组查询
            $all_ym = $this->model_log->field('ym')->where($where)->group('ym')
                ->page($page,$limit)
                ->select()->toArray();
            //查询所有
            $all_data = [];
            foreach ($all_ym as $item){
                $ym_data = $this->model_log->where('ym_domain_attention_ym_log.ym',$item['ym'])
                    ->withJoin(['getStore'=>['team'],'getNowData'=>['sale_price','store_id']],'left')
                    ->select()->toArray();
                $data = [
                    'ym'=>$item['ym'],
                    'sale_price'=>[],
                    'store_id'=>[],
                    'team'=>[],
                ];
                foreach ($ym_data as $ym){
                    $data['store_id'][] = $ym['store_id'];
                    $data['sale_price'][] = $ym['sale_price'];
                    $data['team'][] = isset($ym['getStore']['team'])? $ym['getStore']['team']:'无';
                }
                $data['store_id'][] =  isset($ym_data[0]['getNowData']['store_id'])? $ym_data[0]['getNowData']['store_id']:'无';;
                $data['sale_price'][] = $ym_data[0]['getNowData']['sale_price'];


                $data['sale_price'] = join('->',$data['sale_price']);
                $data['store_id'] = join('->',$data['store_id']);
                $data['team'] = join('->',$data['team']);
                $all_data[] = $data;

            }


            $data = [
                'code' => 0,
                'msg' => '',
                'count' => $count,
                'data' => $all_data,
            ];
            return json($data);



        }



        return $this->fetch();


    }


}
