<?php


namespace app\admin\controller\guoqi_domain;


use app\admin\controller\Tool;
use app\admin\model\DomainGuoqi;
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
use EasyAdmin\upload\FileBase;
use jianyan\excel\Excel;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use think\App;
use think\cache\driver\Redis;
use think\facade\Db;

/**
 * @ControllerAnnotation(title="过期域名监控台")
 */
class Domain extends AdminController
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
        $this->guoqi_model = new DomainGuoqi();
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
                ->where('cate','=','过期域名')
                ->where($where)->page($page, $limit)->select()->toArray();
            foreach ($list as $index => &$item) {
                $item['filter_count'] = $this->guoqi_model->count();
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
            $post['spider_status'] = -1;
            //保存控制台数据   关联
            $post['cate'] = '过期域名';
            $save = $this->model->insertGetId($post);
            $save ? $this->success('保存成功') : $this->error('保存失败');

        }
        $filters = $this->filter_model->field('id,title')->select()->toArray();
        $search = $this->model->field('id,title')->select()->toArray();
        $this->assign('filters', $filters);
        $this->assign('searchs', $search);
        return $this->fetch();
    }

    /**
     *  @NodeAnotation(title="上传域名")
     */
    public function upload_ym(){
        if ($this->request->isAjax()){
            $post = $this->request->post();

            $file_path = $post['file_path'];
            ini_set('max_execution_time', '0');//执行时间
            ini_set('memory_limit','1024M');
            #打开文件
            $fp = fopen($file_path,"r");
            $str = fread($fp,filesize($file_path));//指定读取大小，这里把整个文件内容读取出来

            $insert_data = [];
            foreach (explode("\n",$str) as $item){
                if (trim($item)== '' || strstr($item,'.') == false) continue;
                $insert_data[] = ['ym'=>trim($item)];
            }
            if ($insert_data){
                $this->guoqi_model->where('filter_id','=',null)->delete(true);
                $count = $this->guoqi_model->insertAll($insert_data);
                $this->model->where('cate','=','过期域名')->update(['filter_count'=>$count]);
                $this->success('插入成功');
            }
            $this->error('插入失败');



        }
        return $this->fetch();


    }

    /**
     * @NodeAnotation(title="编辑主线")
     */
    public function edit($id)
    {
        $row = $this->model->find($id);
        empty($row) && $this->error('数据不存在');

        if ($this->request->isPost()) {
            $post = $this->request->post();
//            $post['spider_status'] = 0;//爬虫状态修改为0  重新抓取
            //保存控制台数据   关联
            $save = $this->model->where('id',$id)->update($post);
//            if ($save){
//                kill_task($row['p_id']);
//                //如果修改主线条件  直接停止支线数据
//                $zhi = $this->filter_model->where('main_filter_id','=',$row['id'])->select();
//                foreach ($zhi as $item){
//                    $this->filter_model->where('id','=',$item['id'])->update(['spider_status'=>3]);
//                    kill_task($item['pid']);
//                }
//                $row = Db::connect('mongo')
//                    ->table('ym_data_'.$id)->delete(true);
//                $save ? $this->success('保存完毕~成功停止所有程序') : $this->error('保存失败');
//            }
            $this->success('修改成功~');

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
            $save_data['main_filter_id'] = $id;
            $save_data['sort'] = $post['sort'];
            $save_data['title'] = $post['title'];
            $save_data['is_buy_qiang'] = $post['is_buy_qiang'];
            $save_data['is_buy_wx'] = $post['is_buy_wx'];
            $save_data['is_buy_qq'] = $post['is_buy_qq'];
            $save_data['is_buy_beian'] = $post['is_buy_beian'];
            $save_data['is_buy_history'] = $post['is_buy_history'];

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
                $data['sogou']['sogou_kz'] = $post['so_fxts'];
                $data['sogou']['sogou_is_com_word'] = $post['sogou_is_com_word'];
                $data['sogou']['sogou_jg'] = $post['sogou_jg'];
                $data['sogou']['sogou_jv_now_day_1'] = $post['sogou_jv_now_day_1'];
                $data['sogou']['sogou_jv_now_day_2'] = $post['sogou_jv_now_day_2'];
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
                $data['so']['so_is_com_word'] = $post['so_is_com_word'];


            }


            //历史
            if ($post['is_com_history'] == '1') {

                if ($post['history_age_1'] == '0' && $post['history_age_2'] == '0' && $post['history_chinese_1'] == '0' && $post['history_chinese_2']== '0'  && $post['history_five_1'] == '0' && $post['history_five_2']== '0'&& $post['history_five_lianxu_1'] == '0' && $post['history_five_lianxu_2']== '0' && $post['history_lianxu_1'] == '0' && $post['history_lianxu_2']== '0' && $post['history_score_1'] == '0' && $post['history_score_2']== '0' && $post['history_tongyidu_1'] == '0' && $post['history_tongyidu_2']== '0' ) {
                    $this->error('请完善历史信息~');
                }
                $data['history']['history_age_1'] = $post['history_age_1'];
                $data['history']['history_age_2'] = $post['history_age_2'];
                $data['history']['history_is_com_word'] = $post['history_is_com_word'];

                $data['history']['history_chinese_1'] = $post['history_chinese_1'];
                $data['history']['history_chinese_2'] = $post['history_chinese_2'];

                $data['history']['history_five_1'] = $post['history_five_1'];
                $data['history']['history_five_2'] = $post['history_five_2'];

                $data['history']['history_five_lianxu_1'] = $post['history_five_lianxu_1'];
                $data['history']['history_five_lianxu_2'] = $post['history_five_lianxu_2'];

                $data['history']['history_lianxu_1'] = $post['history_lianxu_1'];
                $data['history']['history_lianxu_2'] = $post['history_lianxu_2'];

                $data['history']['history_score_1'] = $post['history_score_1'];
                $data['history']['history_score_2'] = $post['history_score_2'];

                $data['history']['history_tongyidu_1'] = $post['history_tongyidu_1'];
                $data['history']['history_tongyidu_2'] = $post['history_tongyidu_2'];

            }

            //爱站
            if ($post['is_com_aizhan'] == '1') {

                if ($post['aizhan_baidu_pr_1'] == '0' && $post['aizhan_baidu_pr_2'] == '0' && $post['aizhan_yidong_pr_1'] == '0' && $post['aizhan_yidong_pr_2'] == '0' && $post['aizhan_so_pr_1'] == '0' && $post['aizhan_so_pr_2'] == '0'&& $post['aizhan_sm_pr_1'] == '0'  && $post['aizhan_sm_pr_2'] == '0' && $post['aizhan_sogou_pr_1'] == '0'  && $post['aizhan_sogou_pr_2'] == '0') {
                    $this->error('请完善爱站信息 不能全部是0！');
                }
                $data['aizhan']['aizhan_baidu_pr_1'] = $post['aizhan_baidu_pr_1'];
                $data['aizhan']['aizhan_baidu_pr_2'] = $post['aizhan_baidu_pr_2'];
                $data['aizhan']['aizhan_yidong_pr_1'] = $post['aizhan_yidong_pr_1'];
                $data['aizhan']['aizhan_yidong_pr_2'] = $post['aizhan_yidong_pr_2'];
                $data['aizhan']['aizhan_so_pr_1'] = $post['aizhan_so_pr_1'];
                $data['aizhan']['aizhan_so_pr_2'] = $post['aizhan_so_pr_2'];
                $data['aizhan']['aizhan_sm_pr_1'] = $post['aizhan_sm_pr_1'];
                $data['aizhan']['aizhan_sm_pr_2'] = $post['aizhan_sm_pr_2'];
                $data['aizhan']['aizhan_sogou_pr_1'] = $post['aizhan_sogou_pr_1'];
                $data['aizhan']['aizhan_sogou_pr_2'] = $post['aizhan_sogou_pr_2'];

            }

            //桔子
            if ($post['is_com_jvzi'] == '1') {

                if ($post['jvzi_age_1'] == '0' && $post['jvzi_age_2'] == '0' && $post['jvzi_five_1'] == '0' && $post['jvzi_five_2']== '0'  && $post['jvzi_five_lianxu_1'] == '0' && $post['jvzi_five_lianxu_2']== '0'&& $post['jvzi_lianxu_1'] == '0' && $post['jvzi_lianxu_2']== '0' && $post['jvzi_tongyidu_1'] == '0'
                    && $post['jvzi_tongyidu_2']== '0'&& $post['jvzi_title_mingan']== '0'&& $post['jvzi_neirong_mingan']== '0'&& $post['jvzi_soulu_mingan']== '0' ) {
                    $this->error('请完善桔子信息~');
                }
                $data['jvzi']['jvzi_age_1'] = $post['jvzi_age_1'];
                $data['jvzi']['jvzi_age_2'] = $post['jvzi_age_2'];

                $data['jvzi']['jvzi_five_1'] = $post['jvzi_five_1'];
                $data['jvzi']['jvzi_five_2'] = $post['jvzi_five_2'];

                $data['jvzi']['jvzi_five_lianxu_1'] = $post['jvzi_five_lianxu_1'];
                $data['jvzi']['jvzi_five_lianxu_2'] = $post['jvzi_five_lianxu_2'];

                $data['jvzi']['jvzi_lianxu_1'] = $post['jvzi_lianxu_1'];
                $data['jvzi']['jvzi_lianxu_2'] = $post['jvzi_lianxu_2'];

                $data['jvzi']['jvzi_tongyidu_1'] = $post['jvzi_tongyidu_1'];
                $data['jvzi']['jvzi_tongyidu_2'] = $post['jvzi_tongyidu_2'];

                $data['jvzi']['jvzi_pingfen_1'] = $post['jvzi_pingfen_1'];
                $data['jvzi']['jvzi_pingfen_2'] = $post['jvzi_pingfen_2'];

                $data['jvzi']['jvzi_title_mingan'] = $post['jvzi_title_mingan'];
                $data['jvzi']['jvzi_neirong_mingan'] = $post['jvzi_neirong_mingan'];
                $data['jvzi']['jvzi_soulu_mingan'] = $post['jvzi_soulu_mingan'];

            }

            $save_data['data'] = $data;

            $filter_insert_id = $this->filter_model->json(['data'])->insertGetId($save_data);
            //win 启动
            // exec('start /min "" ./python_script/yikoujia/filter_buy_ym.py '.$filter_insert_id);
            //启动任务
//            $out = exec('nohup python3 ./python_script/yikoujia/filter_buy_ym.py '.$filter_insert_id.' > ./python_script/nohup.log 2>&1 &');
            start_task('./python_script/yikoujia/filter_buy_ym.py',$filter_insert_id);

            $filter_insert_id ? $this->success('保存成功 等待程序启动吧~') : $this->error('保存失败');

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

            $save_data['title'] = $post['title'];
            $save_data['sort'] = $post['sort'];
            $save_data['is_buy_qiang'] = $post['is_buy_qiang'];
            $save_data['is_buy_wx'] = $post['is_buy_wx'];
            $save_data['is_buy_qq'] = $post['is_buy_qq'];
            $save_data['is_buy_beian'] = $post['is_buy_beian'];
            $save_data['is_buy_history'] = $post['is_buy_history'];
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
                $data['sogou']['sogou_is_com_word'] = $post['sogou_is_com_word'];
                $data['sogou']['sogou_jg'] = $post['sogou_jg'];
                $data['sogou']['sogou_jv_now_day_1'] = $post['sogou_jv_now_day_1'];
                $data['sogou']['sogou_jv_now_day_2'] = $post['sogou_jv_now_day_2'];
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
                $data['so']['so_is_com_word'] = $post['so_is_com_word'];

            }

            //历史
            if ($post['is_com_history'] == '1') {

                if ($post['history_age_1'] == '0' && $post['history_age_2'] == '0' && $post['history_chinese_1'] == '0' && $post['history_chinese_2']== '0'  && $post['history_five_1'] == '0' && $post['history_five_2']== '0'&& $post['history_five_lianxu_1'] == '0' && $post['history_five_lianxu_2']== '0' && $post['history_lianxu_1'] == '0' && $post['history_lianxu_2']== '0' && $post['history_score_1'] == '0' && $post['history_score_2']== '0' && $post['history_tongyidu_1'] == '0' && $post['history_tongyidu_2']== '0' ) {
                    $this->error('请完善历史信息~');
                }
                $data['history']['history_age_1'] = $post['history_age_1'];
                $data['history']['history_age_2'] = $post['history_age_2'];
                $data['history']['history_is_com_word'] = $post['history_is_com_word'];

                $data['history']['history_chinese_1'] = $post['history_chinese_1'];
                $data['history']['history_chinese_2'] = $post['history_chinese_2'];

                $data['history']['history_five_1'] = $post['history_five_1'];
                $data['history']['history_five_2'] = $post['history_five_2'];

                $data['history']['history_five_lianxu_1'] = $post['history_five_lianxu_1'];
                $data['history']['history_five_lianxu_2'] = $post['history_five_lianxu_2'];

                $data['history']['history_lianxu_1'] = $post['history_lianxu_1'];
                $data['history']['history_lianxu_2'] = $post['history_lianxu_2'];

                $data['history']['history_score_1'] = $post['history_score_1'];
                $data['history']['history_score_2'] = $post['history_score_2'];

                $data['history']['history_tongyidu_1'] = $post['history_tongyidu_1'];
                $data['history']['history_tongyidu_2'] = $post['history_tongyidu_2'];

            }

            //桔子
            if ($post['is_com_jvzi'] == '1') {

                if ($post['jvzi_age_1'] == '0' && $post['jvzi_age_2'] == '0' && $post['jvzi_five_1'] == '0' && $post['jvzi_five_2']== '0'  && $post['jvzi_five_lianxu_1'] == '0' && $post['jvzi_five_lianxu_2']== '0'&& $post['jvzi_lianxu_1'] == '0' && $post['jvzi_lianxu_2']== '0' && $post['jvzi_tongyidu_1'] == '0'
                    && $post['jvzi_tongyidu_2']== '0'&& $post['jvzi_title_mingan']== '0'&& $post['jvzi_neirong_mingan']== '0'&& $post['jvzi_soulu_mingan']== '0' ) {
                    $this->error('请完善桔子信息~');
                }
                $data['jvzi']['jvzi_age_1'] = $post['jvzi_age_1'];
                $data['jvzi']['jvzi_age_2'] = $post['jvzi_age_2'];

                $data['jvzi']['jvzi_five_1'] = $post['jvzi_five_1'];
                $data['jvzi']['jvzi_five_2'] = $post['jvzi_five_2'];

                $data['jvzi']['jvzi_five_lianxu_1'] = $post['jvzi_five_lianxu_1'];
                $data['jvzi']['jvzi_five_lianxu_2'] = $post['jvzi_five_lianxu_2'];

                $data['jvzi']['jvzi_lianxu_1'] = $post['jvzi_lianxu_1'];
                $data['jvzi']['jvzi_lianxu_2'] = $post['jvzi_lianxu_2'];

                $data['jvzi']['jvzi_tongyidu_1'] = $post['jvzi_tongyidu_1'];
                $data['jvzi']['jvzi_tongyidu_2'] = $post['jvzi_tongyidu_2'];

                $data['jvzi']['jvzi_pingfen_1'] = $post['jvzi_pingfen_1'];
                $data['jvzi']['jvzi_pingfen_2'] = $post['jvzi_pingfen_2'];

                $data['jvzi']['jvzi_title_mingan'] = $post['jvzi_title_mingan'];
                $data['jvzi']['jvzi_neirong_mingan'] = $post['jvzi_neirong_mingan'];
                $data['jvzi']['jvzi_soulu_mingan'] = $post['jvzi_soulu_mingan'];

            }

            //爱站
            if ($post['is_com_aizhan'] == '1') {

                if ($post['aizhan_baidu_pr_1'] == '0' && $post['aizhan_baidu_pr_2'] == '0' && $post['aizhan_yidong_pr_1'] == '0' && $post['aizhan_yidong_pr_2'] == '0' && $post['aizhan_so_pr_1'] == '0' && $post['aizhan_so_pr_2'] == '0'&& $post['aizhan_sm_pr_1'] == '0'  && $post['aizhan_sm_pr_2'] == '0' && $post['aizhan_sogou_pr_1'] == '0'  && $post['aizhan_sogou_pr_2'] == '0') {
                    $this->error('请完善爱站信息 不能全部是0！');
                }
                $data['aizhan']['aizhan_baidu_pr_1'] = $post['aizhan_baidu_pr_1'];
                $data['aizhan']['aizhan_baidu_pr_2'] = $post['aizhan_baidu_pr_2'];
                $data['aizhan']['aizhan_yidong_pr_1'] = $post['aizhan_yidong_pr_1'];
                $data['aizhan']['aizhan_yidong_pr_2'] = $post['aizhan_yidong_pr_2'];
                $data['aizhan']['aizhan_so_pr_1'] = $post['aizhan_so_pr_1'];
                $data['aizhan']['aizhan_so_pr_2'] = $post['aizhan_so_pr_2'];
                $data['aizhan']['aizhan_sm_pr_1'] = $post['aizhan_sm_pr_1'];
                $data['aizhan']['aizhan_sm_pr_2'] = $post['aizhan_sm_pr_2'];
                $data['aizhan']['aizhan_sogou_pr_1'] = $post['aizhan_sogou_pr_1'];
                $data['aizhan']['aizhan_sogou_pr_2'] = $post['aizhan_sogou_pr_2'];

            }
            $save_data['data'] = $data;
            $d =$row['data'] ?json_decode($row['data'],true):null;
            //判断是否修改了参数 如果修改直接停止
            if (json_encode($d) != json_encode($data) || $row['place_1'] != intval($post['place_1']) || $row['place_2'] != intval($post['place_2'])|| $row['is_buy']!= intval($post['is_buy']) || $row['is_buy_sh']!= intval($post['is_buy_sh'])){
                $save_data['spider_status'] = 0;

                //删除订单数据
                $this->buy_model->where('buy_filter_id','=',$id)->delete();
                //停止程序
                kill_task($row['pid']);
            }
            $save = $this->filter_model->json(['data'])->where('id',$id)->update($save_data);
            $save ? $this->success('保存成功') : $this->error('保存失败');

        }
//        dd(json_decode($row['data'],true));
        $this->assign('data', json_decode($row['data'], true));
        $this->assign('row', $row);
        return $this->fetch();
    }


    /**
     * @NodeAnotation(title="删除")
     */
    public function delete($id)
    {
        $row = $this->model->whereIn('id', $id)->select()->toArray();
        empty($row) && $this->error('数据不存在');
        try {
            $this->model->whereIn('id', $id)->delete();
            foreach ($row as $v){
                kill_task($v['p_id']);
                //删除数据库数据 停止任务
                foreach ($id as $id1){
                    Db::connect('mongo')
                        ->table('ym_data_'.$id1)->delete(true);
                }
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
            $row = $this->model->where('id','in',$id)->select();
            empty($row) && $this->error('没有该数据 无法重启~');
            //查询主线是否在运行

            foreach ($row as $zhu){
                $zhu->save(['spider_status'=>0,'p_id'=>null]);
            }

            $this->success('主线等待运行中~');
        } elseif ($type=='zhi_all'){
            //查询进程号
            $row = $this->model->where('id','in',$id)->select();
            foreach ($row as $zhu){
                $zhi = $this->filter_model->where('main_filter_id','=',$zhu['id'])->select();
                foreach ($zhi as $z){
                    $z ->save(['spider_status'=>0,'pid'=>null]);
                }
            }

            $this->success('支线等待运行中~');
        }
        elseif ($type=='zhi'){
            $zhi = $this->filter_model->where('id','=',$id)->update(['spider_status'=>0,'pid'=>null]);
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
        if (!is_array($id)){
            $id = [$id];
        }

        foreach ($id as $main_id){
            $row = $this->model->find($main_id);
            if (empty($row)) continue;
            kill_task($row['p_id']);
            $row ->save(['spider_status'=>3]);
            #查询支线数据
            $zhi = $this->filter_model->where('main_filter_id','=',$row['id'])->select();
            foreach ($zhi as $item){
                $item->save(['spider_status'=>3]);
                kill_task($item['pid']);
            }
            $this->success('成功停止全部任务');
        }

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
            $out = exec('tasklist | findstr '.$pid);
            //如果程序不存在  爬虫程序为进行中  报错程序异常
            if (!strstr($out,$pid)){
                if ($row['spider_status'] == 1){
                    $row->save(['spider_status'=>4]);
                    $this->error('程序异常~');
                }
                $this->error('程序未运行 '.$out);
            }
            $this->success('程序正在运行中~');
        }else{
            $row = $this->filter_model->find($id);
            empty($row)&& $this->error('没有要找的任务呀~');
            $pid = $row['pid'];
            $out = exec('tasklist | findstr '.$pid);
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

        if ($type == 1){
            $file_name = 'logs_'.date('Ymd').'/main_log/main_'.$id.'.log';

        }else{
            $file_name = 'logs_'.date('Ymd').'/zhi_log/zhi_'.$id.'.log';
        }
        try {
            $fp=fopen('./python_script/yikoujia/logs/'.$file_name,'r');


        }catch (\Exception $e){
            $this->assign('str', ["打开文件失败，请检查文件路径是否正确，路径和文件名不要包含中文"]);
            return $this->fetch();
        }

        $pos=-2;
        $eof="";
        $str="";
        $linesArr = array();
        $n = 10000;
//        $linesArr = file('./python_script/yikoujia/logs/'.$file_name);
//        fclose($fp);

        while($n>0){
            while($eof!="\n"){
                if(!fseek($fp,$pos,SEEK_END)){
                    $eof=fgetc($fp);
                    $pos--;
                } else {
                    break;
                }
            }
            // $str.=fgets($fp);
            $linesArr[] = fgets($fp);
            $eof="";
            $n--;
        }


        $linesArr = array_reverse($linesArr);
        foreach ($linesArr as $one) {
            $str .= $one;
        }

        $str = array_reverse(explode("\r\n",$str));
        $this->assign('str',$str);
        return $this->fetch();

    }
    /**
     *@NodeAnotation(title="删除购买域名")
     */
    public function delete_buy_list($id,$type){
        if($type == 'zhi'){
            $this->buy_model->where('buy_filter_id','=',$id)->delete();
            $this->success('清除成功');
        }else{
            $row = Db::connect('mongo') ->table('ym_data_'.$id)->delete(true);
            Db::connect('mongo') ->table('out_ym')->where('filter_id','=',intval($id))->delete();
            $this->success('清除成功');
        }



    }
    /**
     *@NodeAnotation(title="一键清空所有结果")
     */
    public function clear_result(){
        //获取所有过期主线支线id
        $all_main = $this->model->where('cate','=','过期域名')->select()->toArray();
        foreach ($all_main as $main){

            //清除主线数据
            Db::connect('mongo') ->table('ym_data_'.$main['id'])->delete(true);
            //查询支线数据
            $all_zhi = $this->filter_model->where('main_filter_id','=',$main['id'])->select()->toArray();

            foreach ($all_zhi as $zhi){
                $this->buy_model->where('buy_filter_id','=',$zhi['id'])->delete();

            }


        }
        $this->success('删除成功');



    }



}
