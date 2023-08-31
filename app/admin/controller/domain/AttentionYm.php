<?php


namespace app\admin\controller\domain;


use app\admin\controller\JvMing;
use app\admin\controller\JvMingApi;
use app\admin\model\DomainAttentionYm;
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
            $count = $this->model
                ->where($where)
                ->count();
            $list = $this->model
                ->where($where)
                ->page($page, $limit)
                ->order($this->sort)
                ->select();
            $data = [
                'code' => 0,
                'msg' => '',
                'count' => $count,
                'data' => $list,
            ];
            return json($data);
        }
        return $this->fetch();
    }


    /**
     * @NodeAnotation(title="编辑")
     */
    public function edit($id)
    {
        $row = $this->model->where('id','in',$id)->select();
        empty($row) && $this->error('没有关注改域名');

        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $this->model->where('id','in',$id)->update($post);
            $this->success('修改成功');

        }
        if (count(explode(',',$id))> 1){
            $this->assign('type','batch');
        }else{
            $this->assign('type','one');
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
            $all_remark = explode("\n",htmlspecialchars_decode($post['batch_remark']));
            foreach ($all_remark as $remark){
                if (trim($remark) == '') continue;
                $remark = explode("|",$remark);
                //修改备注信息
                $this->model->where('ym','=',$remark[0])->update(['remark'=>$remark[1]]);
            }

            //修改成本价信息
            $all_cost_price = explode("\n",htmlspecialchars_decode($post['batch_cost_price']));

            foreach ($all_cost_price as $data){
                if (trim($data) == '') continue;
                $data = explode("|",$data);

                $row = $this->model->where('ym','=',$data[0])->find();
                if (empty($row)) continue;
                //修改成本价信息
                $row->save([
                    'cost_price' => $data[1],//成本价
                    'profit_cost' => $row['sale_price'] - intval($data[1]),//利润
                    'profit_cost_lv' => ($row['sale_price'] - intval($data[1]))/$row['sale_price'] *100,//利润率
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
        $tableName = $this->model->getName();
        $tableName = CommonTool::humpToLine(lcfirst($tableName));
        $prefix = config('database.connections.mysql.prefix');
        $dbList = Db::query("show full columns from {$prefix}{$tableName}");
        $header = [];
        foreach ($dbList as $vo) {
            $comment = !empty($vo['Comment']) ? $vo['Comment'] : $vo['Field'];
            if (!in_array($vo['Field'], $this->noExportFields)) {
                $header[] = [$comment, $vo['Field']];
            }
        }
        $list = $this->model
            ->where($where)
            ->limit(100000)
            ->order('id', 'desc')
            ->select()
            ->toArray();
        $fileName = '关注域名'.time();
        return Excel::exportData($list, $header, $fileName, 'xlsx');
    }



    /**
     * @NodeAnotation(title="更新所有关注数据")
     */
    public function crawl()
    {
        if ($this->request->isPost()) {
            $like_time = date('Y-m-d H:s:i');
            $all_data = [];
            //获取全部账号
            $all_account = $this->account_model->where('status', '=', 1)->select()->toArray();
            foreach ($all_account as $account) {
                $this->jvming_api = new JvMing($account['account'], $account['password'], $account['cookie']);
                $data = $this->jvming_api->get_gzlist();
                $all_data = array_merge($all_data, $data);

            }

            $insert_data = [];
            $ym_list = [];
            foreach ($all_data as $data) {
                $ym_list[] = $data['ym'];
                //判断域名是否存在 如果存在更新 不插入
                $ym_row = $this->model->where('ym', '=', $data['ym'])->find();
                if (!empty($ym_row)) {
                    $ym_row->save([
                        'update_time' => $data['gxsj'], //更新时间
                        'remark' => $ym_row['remark']??$data['bz'], //备注
                        'sale_status' => $data['zt_txt'],//出售状态
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
//                    'get_time'=>$data['gxsj'],
                    'update_time' => $data['gxsj'],
                    'sale_price' => $data['qian'],
//                    'cost_price'=>'', //成本
//                    'profit_cost'=>'',//利润
//                    'profit_cost_lv'=>'',//利润率
                    'store_id' => $data['uid'],
                    'remark' => $data['bz'],
                    'sale_status' => $data['zt_txt'],
//                    'channel'=>'',  //注册    竟价  和入库
//                    'zcs'=>'',  //注册商

                ];
            }
            $this->model->insertAll($insert_data);

            //删除不在列表中的域名
            $this->model->where('ym', 'not in', $ym_list)->delete();

            $this->success('添加成功，2分钟后自动补全任务启动~ 请耐心等待！');

        }
        return $this->fetch();
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
        $all_data = $this->model->where('crawl_status','=',0)
            ->where('cost_price', '=', 0)
            ->limit(500)->select()->toArray();
        //获取所有域名列表
        $all_ym = array_map(function ($x) {
            return $x['ym'];
        }, $all_data);

        //获取所有域名售价
        $sale_price = [];
        foreach ($all_data as $data){
            $sale_price[$data['ym']] = $data['sale_price'];
        }

        if ($all_ym==[]){
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
                    'profit_cost_lv' => ($sale_price[$item['ym']] - $item['sj_qian'])/$sale_price[$item['ym']] *100,//利润率
                    'crawl_status' => 1,//已抓取 为1
                ];
//                dd($update,1);
                $this->model->where('ym', '=', $item['ym'])->update($update);

            }
            //去重 取差集 之后查询的都是没有找到的
            $all_ym = array_diff($all_ym, $have_ym);
            //查询资金明细
            $result = $this->jvming_api->get_financial_details($all_ym);
            if (!$result)continue;
            $have_ym = [];
            foreach ($result as $ym=>$item){
                $have_ym[] =$ym;
                if (count($item) == 1){
                    $cost_price = $item[0]['qian'];
                }else{
                    $cost_price = abs( $item[1]['qian'] - $item[0]['qian']);
                }
                try{
                    if (!isset($item[0]['sj'])){
                        dd($item);
                        return ;
                    }
                    $update = [
                        'get_time' => $item[0]['sj'],//结束时间
                        'cost_price' =>$cost_price,//成本价
                        'profit_cost' => $sale_price[$item[0]['ym']] - $cost_price,//利润
                        'profit_cost_lv' => ($sale_price[$item[0]['ym']] - $cost_price)/$sale_price[$item[0]['ym']] *100,//利润率
                        'crawl_status' => 1,//已抓取 为1
                    ];
                }catch (\Exception $e){
                    dd($e->getMessage(),$item);
                }
//                dd($update,2,$item);
                $this->model->where('ym', '=', $ym)->update($update);
            }

            //去重 取差集 之后查询的都是没有找到的
            $all_ym = array_diff($all_ym, $have_ym);

        }
        //将没找到的都改外已抓取
        $this->model->where('ym','in',$all_ym)->update(['crawl_status'=>1]);

        return '更新成功';
    }



    /**
     * @NodeAnotation(title="定时任务 更新关注域名渠道")
     */
    public function crawl_attention_channel(){
        $all_data = $this->model->where('channel', '=', null)->limit(500)->select();
        foreach ($all_data as $data){
            $ym_data = $this->jm_api->get_ykj_info($data['ym']);
            //如果未在出售中   渠道为未知
            if ($ym_data['code'] == -1){
                $data->save(['channel'=>'未知']);
                continue;
            }

            if (strstr($ym_data['data']['dbsjsm'],'新增')){
                $data->save(['channel'=>'注册','get_time'=>$ym_data['data']['dbsj']]);
            }

            elseif(strstr($ym_data['data']['dbsjsm'],'得标')){
                $data->save(['channel'=>'竞价','get_time'=>$ym_data['data']['dbsj']]);
            }
            elseif (strstr($ym_data['data']['dbsjsm'],'入库')){
                $data->save(['channel'=>'入库','get_time'=>$ym_data['data']['dbsj']]);
            }else{
                $data->save(['channel'=>'其他','get_time'=>$ym_data['data']['dbsj']]);
            }


        }


        return '更新成功';
    }



}
