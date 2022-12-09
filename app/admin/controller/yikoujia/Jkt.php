<?php


namespace app\admin\controller\yikoujia;


use app\admin\controller\Tool;
use app\admin\model\SystemAdmin;
use app\admin\model\YikoujiaAccountPool;
use app\admin\model\YikoujiaBuy;
use app\admin\model\YikoujiaBuyFilter;
use app\admin\model\YikoujiaJkt;

use app\admin\model\YikoujiaSpiderStatus;
use app\admin\model\YikouLogs;
use app\admin\service\TriggerService;
use app\common\constants\AdminConstant;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use EasyAdmin\tool\CommonTool;
use jianyan\excel\Excel;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use think\App;
use think\cache\driver\Redis;
use think\facade\Db;

/**
 * @ControllerAnnotation(title="一口价监控台")
 */
class Jkt extends AdminController
{

    use \app\admin\traits\Curd;


    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->filter_model = new YikoujiaBuyFilter();
        $this->model = new YikoujiaJkt();
        $this->spider_status_model = new YikoujiaSpiderStatus();
        $this->account_pool_model = new YikoujiaAccountPool();
        $this->buy_model = new YikoujiaBuy();
        $this->logs_model = new YikouLogs();
    }

    /**
     * @NodeAnotation(title="列表")
     */
    public function index()
    {

        if ($this->request->isAjax()) {
            list($page, $limit, $where) = $this->buildTableParames();
            $start_time = time();
            $list = $this->model
                ->where($where)->page($page, $limit)->select()->toArray();
            foreach ($list as $index => &$item) {
                $item['zhixian'] = $this->filter_model->where('main_filter_id', $item['id'])
                    ->order('sort','desc')
                    ->select()->toArray();
            }
            $count = $this->model->where($where)->count();
            $data = [
                'code' => 0,
                'msg' => '',
                'count' => $count,
                'data' => $list,
                'time'=>time()-$start_time
            ];
            return json($data);
        }

        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="添加")
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            //保存控制台数据   关联
            $save = $this->model->insertGetId($post);
            //启动任务
//            start_task('./python_script/yikoujia/search_ym_list_and_filter.py',$save);
//            $out = exec('nohup python3 ./python_script/yikoujia/search_ym_list_and_filter.py '.$save.' > ./python_script/nohup.log 2>&1 &');
            $save ? $this->success('保存成功') : $this->error('保存失败');

        }
        $filters = $this->filter_model->field('id,title')->select()->toArray();
        $searchs = $this->model->field('id,title')->select()->toArray();
        $this->assign('filters', $filters);
        $this->assign('searchs', $searchs);
        return $this->fetch();
    }


    /**
     * @NodeAnotation(title="编辑")
     */
    public function edit($id)
    {
        $row = $this->model->find($id);
        empty($row) && $this->error('数据不存在');

        if ($this->request->isPost()) {
            $post = $this->request->post();
            $post['spider_status'] = 0;//爬虫状态修改为0  重新抓取
            //保存控制台数据   关联
            $save = $this->model->where('id',$id)->update($post);
            if ($save){
                //如果修改主线条件  直接停止支线数据
                $zhi = $this->filter_model->where('main_filter_id','=',$row['id'])->select();
                foreach ($zhi as $item){
                    $item->update(['spider_status'=>3]);
                    kill_task($item['pid']);
                }
                $row = Db::connect('mongo')
                    ->table('ym_data_'.$id)->delete(true);
                $save ? $this->success('保存完毕~成功停止所有程序') : $this->error('保存失败');
            }
            $this->error('没有修改任何数据~');

        }
        $this->assign('row', $row);
        return $this->fetch();
    }


    /**
     * @NodeAnotation(title="查看符合列表")
     */
    public function show_fuhe_list($id){
        ini_set ("memory_limit","-1");
        ini_set('max_execution_time', '0');//执行时间
        $row = Db::connect('mongo')
            ->table('ym_data_'.$id)->select()->toArray();
        $this->assign('row',$row);
        return $this->fetch();

    }

    /**
     * @NodeAnotation(title="增加支线")
     */
    public function add_zhi($id)
    {
        $row = $this->model->find($id);
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $save_data = [];
            $save_data['place_1'] = $post['place_1'];
            $save_data['place_2'] = $post['place_2'];
            $save_data['is_buy'] = $post['is_buy'];
            $save_data['is_buy_sh'] = $post['is_buy_sh'];
            $save_data['main_filter_id'] = $id;
            $save_data['sort'] = $post['sort'];
            $save_data['title'] = $post['title'];
            $save_data['is_buy_qiang'] = $post['is_buy_qiang'];
            $save_data['is_buy_wx'] = $post['is_buy_wx'];
            $save_data['is_buy_qq'] = $post['is_buy_qq'];
            $save_data['is_buy_beian'] = $post['is_buy_beian'];
            $data = [];
            //备案
            if ($post['is_com_beian'] == '1') {
                if (!$post['beian_suffix'] || !$post['beian_pcts']) {
                    $this->error('请完善备案信息~');
                }

                $data['beian']['beian_suffix'] = $post['beian_suffix'];
                $data['beian']['beian_pcts'] = $post['beian_pcts'];
                $data['beian']['beian_xz'] = $post['beian_xz'];

            }
            //百度
            if ($post['is_com_baidu'] == '1') {
                if ($post['baidu_sl_1'] == '0' && !$post['baidu_sl_1'] == '0') {
                    $this->error('请完善百度收录信息~');
                }

                $data['baidu']['baidu_sl_1'] = $post['baidu_sl_1'];
                $data['baidu']['baidu_sl_2'] = $post['baidu_sl_2'];
                $data['baidu']['baidu_is_com_chinese'] = $post['baidu_is_com_chinese'];
                $data['baidu']['baidu_jg'] = $post['baidu_jg'];
                $data['baidu']['baidu_is_com_word'] = $post['baidu_is_com_word'];

            }
            //搜狗
            if ($post['is_com_sogou'] == '1') {
                if ($post['sogou_sl_1'] == '0' && !$post['sogou_sl_2'] == '0') {
                    $this->error('请完善搜狗收录信息~');
                }
                $data['sogou']['sogou_sl_1'] = $post['so_sl_1'];
                $data['sogou']['sogou_sl_2'] = $post['so_sl_2'];
                $data['sogou']['sogou_kz'] = $post['so_fxts'];

            }
            //360
            if ($post['is_com_so'] == '1') {
                if ($post['so_sl_1'] == '0' && !$post['so_sl_2'] == '0') {
                    $this->error('请完善搜狗收录信息~');
                }
                $data['so']['so_sl_1'] = $post['so_sl_1'];
                $data['so']['so_sl_2'] = $post['so_sl_2'];
                $data['so']['so_fxts'] = $post['so_fxts'];
                $data['so']['so_jg'] = $post['so_jg'];

            }
            //注册商
            if ($post['is_com_zcs'] == '1') {

                if ($post['zcs_include'] == '') {
                    $this->error('请完善注册商信息~');
                }
                $data['zcs']['zcs_include'] = $post['zcs_include'];

            }

            //历史
            if ($post['is_com_history'] == '1') {

                if ($post['history_age_1'] == '0' && $post['history_age_2'] == '0') {
                    $this->error('请完善历史信息~');
                }
                $data['history']['history_age_1'] = $post['history_age_1'];
                $data['history']['history_age_2'] = $post['history_age_2'];
                $data['history']['history_is_com_word'] = $post['history_is_com_word'];

            }


            $save_data['data'] = $data;

            $filter_insert_id = $this->filter_model->json(['data'])->insertGetId($save_data);
            //win 启动
            // exec('start /min "" ./python_script/yikoujia/filter_buy_ym.py '.$filter_insert_id);
            //启动任务
//            $out = exec('nohup python3 ./python_script/yikoujia/filter_buy_ym.py '.$filter_insert_id.' > ./python_script/nohup.log 2>&1 &');
            start_task('./python_script/yikoujia/filter_buy_ym.py',$filter_insert_id);

            $filter_insert_id ? $this->success('保存成功 任务已启动') : $this->error('保存失败');

        }


        $this->assign('row', $row);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="修改支线")
     */
    public function edit_zhi($id)
    {
        $row = $this->filter_model->find($id);
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $save_data = [];
            $save_data['place_1'] = $post['place_1'];
            $save_data['place_2'] = $post['place_2'];
            $save_data['is_buy'] = $post['is_buy'];
            $save_data['is_buy_sh'] = $post['is_buy_sh'];
            $save_data['title'] = $post['title'];
            $save_data['sort'] = $post['sort'];
            $save_data['is_buy_qiang'] = $post['is_buy_qiang'];
            $save_data['is_buy_wx'] = $post['is_buy_wx'];
            $save_data['is_buy_qq'] = $post['is_buy_qq'];
            $save_data['is_buy_beian'] = $post['is_buy_beian'];
            $data = [];
            //备案
            if ($post['is_com_beian'] == '1') {
                if (!$post['beian_suffix'] || !$post['beian_pcts']) {
                    $this->error('请完善备案信息~');
                }

                $data['beian']['beian_suffix'] = $post['beian_suffix'];
                $data['beian']['beian_pcts'] = $post['beian_pcts'];
                $data['beian']['beian_xz'] = $post['beian_xz'];

            }
            //百度
            if ($post['is_com_baidu'] == '1') {
                if ($post['baidu_sl_1'] == '0' && !$post['baidu_sl_1'] == '0') {
                    $this->error('请完善百度收录信息~');
                }

                $data['baidu']['baidu_sl_1'] = $post['baidu_sl_1'];
                $data['baidu']['baidu_sl_2'] = $post['baidu_sl_2'];
                $data['baidu']['baidu_is_com_chinese'] = $post['baidu_is_com_chinese'];
                $data['baidu']['baidu_jg'] = $post['baidu_jg'];
                $data['baidu']['baidu_is_com_word'] = $post['baidu_is_com_word'];

            }
            //搜狗
            if ($post['is_com_sogou'] == '1') {
                if ($post['sogou_sl_1'] == '0' && !$post['sogou_sl_2'] == '0') {
                    $this->error('请完善搜狗收录信息~');
                }
                $data['sogou']['sogou_sl_1'] = $post['sogou_sl_1'];
                $data['sogou']['sogou_sl_2'] = $post['sogou_sl_2'];
                $data['sogou']['sogou_kz'] = $post['sogou_kz'];

            }
            //360
            if ($post['is_com_so'] == '1') {
                if ($post['so_sl_1'] == '0' && !$post['so_sl_2'] == '0') {
                    $this->error('请完善搜狗收录信息~');
                }
                $data['so']['so_sl_1'] = $post['so_sl_1'];
                $data['so']['so_sl_2'] = $post['so_sl_2'];
                $data['so']['so_fxts'] = $post['so_fxts'];
                $data['so']['so_jg'] = $post['so_jg'];

            }
            //注册商
            if ($post['is_com_zcs'] == '1') {

                if ($post['zcs_include'] == '') {
                    $this->error('请完善注册商信息~');
                }
                $data['zcs']['zcs_include'] = $post['zcs_include'];

            }

            //历史
            if ($post['is_com_history'] == '1') {

                if ($post['history_age_1'] == '0' && $post['history_age_2'] == '0') {
                    $this->error('请完善历史信息~');
                }
                $data['history']['history_age_1'] = $post['history_age_1'];
                $data['history']['history_age_2'] = $post['history_age_2'];
                $data['history']['history_is_com_word'] = $post['history_is_com_word'];

            }


            $save_data['data'] = $data;
            $d =$row['data'] ?json_decode($row['data'],true):null;
            //判断是否修改了参数 如果修改直接停止
            if (json_encode($d) != json_encode($data) || $row['place_1'] != intval($post['place_1']) || $row['place_2'] != intval($post['place_2'])|| $row['is_buy']!= intval($post['is_buy']) || $row['is_buy_sh']!= intval($post['is_buy_sh'])){
                $save_data['spider_status'] = 3;

                //删除订单数据
                $this->buy_model->where('buy_filter_id')->delete();
                //停止程序
                $this->stop_task($row['pid']);
            }
            $save = $this->filter_model->json(['data'])->where('id',$id)->update($save_data);
            $save ? $this->success('保存成功') : $this->error('保存失败');

        }

        $this->assign('data', json_decode($row['data'], true));
        $this->assign('row', $row);
        return $this->fetch();
    }


    /**
     * @NodeAnotation(title="删除")
     */
    public function delete($id)
    {
        $row = $this->model->whereIn('id', $id)->select();
        $row->isEmpty() && $this->error('数据不存在');
        try {
            foreach ($row as $v){
                $v->delete();
                //删除数据库数据 停止任务
                $row = Db::connect('mongo')
                    ->table('ym_data_'.$id)->delete(true);
                kill_task($v['p_id']);
            }

            //删除支线任务
            $zhi_list  = $this->filter_model->where('main_filter_id', 'in', $id)->select()->toArray();
            foreach ($zhi_list as $index=>$item){
                //删除支线数据
                $this->filter_model->where('id', '=', $item['id'])->delete(true);
                kill_task($item['pid']);
            }
        } catch (\Exception $e) {
            $this->error('删除失败 '.$e->getMessage());
        }
        $this->success('删除成功');
    }


    /**
     * @NodeAnotation(title="展示支线详情")
     */
    public function show_zhi($id)
    {
       $row = $this->filter_model->find($id);
       $row = $this->filter_model->where('main_filter_id','=',$row['main_filter_id'])->select()->toArray();
       $this->assign('row',json_encode($row));
       return $this->fetch();
    }

    /**
     * @NodeAnotation(title="删除支线")
     */
    public function delete_zhi($id)
    {
        $row = $this->filter_model->whereIn('id', $id)->select();
        $row->isEmpty() && $this->error('数据不存在');
        try {
            $save = $row->delete();
            //也要停止所有任务
        } catch (\Exception $e) {
            $this->error('删除失败');
        }
        $save ? $this->success('删除成功') : $this->error('删除失败');
    }

    /**
     * @NodeAnotation(title="停止支线任务")
     */
    public function stop_zhi_task($id)
    {
        //查询进程号
        $row = $this->filter_model->find($id);
        empty($row) && $this->error('没有该数据 无法停止~');
//        exec('taskkill -f -pid ' . $row['pid']);
        kill_task($row['pid']);
        $row ->save(['spider_status'=>3]);
        $this->success('成功停止');

    }

    /**
     * @NodeAnotation(title="重启任务")
     */
    public function restart_task($id,$type)
    {
        if ($type=='zhu'){
            //查询进程号
            $row = $this->model->find($id);
            empty($row) && $this->error('没有该数据 无法重启~');
            //查询主线是否在运行

//            $out = exec('ps -p '.$row['p_id']);
            $out = exec('tasklist | findstr '.$row['p_id'],$rep);
            //如果程序不存在  爬虫程序为进行中  报错程序异常
            if (strstr($out,$row['p_id'])){
                $this->error('请先停止主线任务后重启~');
            }
            $row ->save(['spider_status'=>0,'p_id'=>null]);
            //删除之前数据 重新运行
            $row = Db::connect('mongo')
                ->table('ym_data_'.$id)->delete(true);
//            start_task('./python_script/yikoujia/search_ym_list_and_filter.py',$id);
            $this->success('主线等待运行中~');
        }else{
            //查询进程号
            $row = $this->filter_model->find($id);
            empty($row) && $this->error('没有该数据 无法重启~');
            $row ->save(['spider_status'=>0,'pid'=>null]);

//            start_task('./python_script/yikoujia/filter_buy_ym.py',$id);
            $this->success('支线等待运行中~');
        }


    }

    /**
     * @NodeAnotation(title="展示可购买域名")
     */
    public function show_buy_ym($id)
    {

        $all_buy_data = $this->buy_model->where('buy_filter_id', '=', $id)->select()->toArray();
        $this->assign('all_buy_data', $all_buy_data);
        return $this->fetch();

    }


    /**
     * @NodeAnotation(title="停止全部任务")
     */
    public function stop_task($id)
    {
        //查询进程号
        $row = $this->model->find($id);
        empty($row) && $this->error('没有该数据 无法停止~');
        $zhi = $this->filter_model->where('main_filter_id','=',$row['id'])->select();
        foreach ($zhi as $item){
            $item->save(['spider_status'=>3]);
            kill_task($item['pid']);
        }
        //停止主线程
        kill_task($row['p_id']);
        $row->save(['spider_status'=>3]);
        $this->success('成功停止全部任务');
    }

    /**
     * @NodeAnotation(title="检测运行状态")
     */
    public function check_status($id,$type){

        if ($type =='zhu'){
            $row = $this->model->find($id);
            empty($row)&& $this->error('没有要找的任务呀~');
            $pid = $row['p_id'];
//            $out = exec('ps -p '.$pid);
            $out = exec('tasklist | findstr '.$pid,$res);
            //如果程序不存在  爬虫程序为进行中  报错程序异常
            if (!strstr($out,$pid)){
                if ($row['spider_status'] == 1){
                    $row->save(['spider_status'=>4]);
                    $this->error('程序异常~');
                }
                $this->error('程序未运行');
            }
            $this->success('程序正在运行中~');
        }else{
            $row = $this->filter_model->find($id);
            empty($row)&& $this->error('没有要找的任务呀~');
            $pid = $row['pid'];
            $out = exec('ps -p '.$pid);
            //如果程序不存在  爬虫程序为进行中  报错程序异常
            if (!strstr($out,$pid)){
                if ($row['spider_status'] == 1){
                    $row->save(['spider_status'=>4]);
                    $this->error('程序异常~');
                }
                $this->error('程序未运行');
            }
            $this->success('程序正在运行中~');
        }
    }

    /**
     *@NodeAnotation(title="日志")
     */
    public function logs($id,$type){
        $where[] = ['type','=',$type];
        $where[] = ['filter_id','=',$id];
        $logs = $this->logs_model->where($where)->order('id','desc')->limit(300)->select();
        $this->assign('logs',$logs);
        return $this->fetch();

    }

    /**
     *@NodeAnotation(title="日志")
     */
    public function delete_buy_list($id,$type){
        $this->buy_model->where('buy_filter_id','=',$id)->delete();
        $this->success('清除成功');


    }
}
