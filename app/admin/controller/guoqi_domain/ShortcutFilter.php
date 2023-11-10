<?php


namespace app\admin\controller\guoqi_domain;


use app\admin\model\SystemConfig;
use app\admin\model\YmAllDoamin;
use app\admin\model\DomainGroup;
use app\admin\model\YmMainFilter;
use app\admin\model\YmZhiFilter;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;

use think\App;

/**
 * @ControllerAnnotation(title="快捷筛选")
 */
class ShortcutFilter extends AdminController
{

    use \app\admin\traits\Curd;


    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->filter_model = new YmZhiFilter();
        $this->model = new YmMainFilter();
        $this->filter_group_model = new DomainGroup();
        $this->ym_model = new YmAllDoamin();
        $this->config_model = new SystemConfig();
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
                ->withJoin('getGroup', 'left')
                ->where('cate', '=', '一口价')
                ->where($where)->page($page, $limit)->select()->toArray();
            foreach ($list as $index => &$item) {
                $item['zhixian'] = $this->filter_model->where('main_filter_id', $item['id'])
                    ->order('sort', 'desc')
                    ->select()->toArray();
            }
            $count = $this->model->where($where)->count();
            $data = [
                'code' => 0,
                'msg' => '',
                'count' => $count,
                'data' => $list,
                'time' => time() - $start_time
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
            $save ? $this->success('保存成功') : $this->error('保存失败');

        }
        $all_group_list = $this->filter_group_model->select()->toArray();

        $this->assign('all_group_list', $all_group_list);
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
            //保存控制台数据   关联
            $this->model->where('id', $id)->update($post);
            $this->success('修改成功~');

        }
        $this->assign('row', $row);
        $all_group_list = $this->filter_group_model->select()->toArray();
        $this->assign('all_group_list', $all_group_list);

        return $this->fetch();
    }


    /**
     * @NodeAnotation(title="查看符合列表")
     */
    public function show_fuhe_list($id)
    {
        ini_set("memory_limit", "-1");
        ini_set('max_execution_time', '0');//执行时间
        $row = $this->model->find($id);
        $main_filter = $row['main_filter'];
        //获取筛选日期 前4天后4天数据
        $date = sysconfig('wjxt','delete_filter_time');

        $prevDate = date('Y-m-d', strtotime("-4 days", strtotime($date)));
        $nextDate  = date('Y-m-d', strtotime("+4 days", strtotime($date)));



        $where = [['source', '=', '每日删除'],['delete_time','between',[$prevDate,$nextDate]]];



        if ($main_filter == '百度') {
            $row = $this->ym_model->field('ym,delete_time')->where($where)->where('baidu_num', '>', 0)->limit(10000)->select()->toArray();
        } elseif ($main_filter == '备案') {
            $row = $this->ym_model->field('ym,delete_time')->where($where)->where('beian_pcts', '<>', null)->limit(10000)->select()->toArray();

        } elseif ($main_filter == '历史') {
            $row = $this->ym_model->field('ym,delete_time')->where($where)->where('history_age', '>', 0)->limit(10000)->select()->toArray();
        } elseif ($main_filter == '搜狗') {
            $row = $this->ym_model->field('ym,delete_time')->where($where)->where('sogou_num', '>', 0)->limit(10000)->select()->toArray();

        } elseif ($main_filter == '爱站') {
            $row = $this->ym_model->field('ym,delete_time')->limit(10000)->where($where)->whereOr([['bd_pr', '>', 0], ['so_pr', '>', 0], ['google_pr', '>', 0], ['yd_pr', '>', 0], ['sogou_pr', '>', 0], ['sm_pr', '>', 0]])->select()->toArray();

        } elseif ($main_filter == '360') {
            $row = $this->ym_model->field('ym,delete_time')->where($where)->where('so_num', '>', 0)->limit(10000)->select()->toArray();

        }
        $this->assign('sql',$this->ym_model->getLastSql());

        $this->model->where('id', '=', $id)->update(['filter_count' => count($row)]);


        $all_ym_list = [];
        foreach ($row as $data){
            if (isset($all_ym_list[$data['delete_time']]) == false){
                $all_ym_list[$data['delete_time']] = [];
            }
            $all_ym_list[$data['delete_time']][] = $data['ym'];
        }
        $this->assign('all_data',$all_ym_list);
        $this->assign('main_id',$id);
        return $this->fetch();

    }



    /**
     * @NodeAnotation(title="下载所有符合的域名")
     */
    public function download_ym($id)
    {
        ini_set("memory_limit", "-1");
        ini_set('max_execution_time', '0');//执行时间
        $row = $this->model->find($id);
        $file_name = $row['title'];


        $main_filter = $row['main_filter'];
        //获取筛选日期 前4天后4天数据
        $date = sysconfig('wjxt','delete_filter_time');

        $prevDate = date('Y-m-d', strtotime("-4 days", strtotime($date)));
        $nextDate  = date('Y-m-d', strtotime("+4 days", strtotime($date)));



        $where = [['source', '=', '每日删除'],['delete_time','between',[$prevDate,$nextDate]]];



        if ($main_filter == '百度') {
            $row = $this->ym_model->field('ym,delete_time')->where($where)->where('baidu_num', '>', 0)->select()->toArray();
        } elseif ($main_filter == '备案') {
            $row = $this->ym_model->field('ym,delete_time')->where($where)->where('beian_pcts', '<>', null)->select()->toArray();

        } elseif ($main_filter == '历史') {
            $row = $this->ym_model->field('ym,delete_time')->where($where)->where('history_age', '>', 0)->select()->toArray();
        } elseif ($main_filter == '搜狗') {
            $row = $this->ym_model->field('ym,delete_time')->where($where)->where('sogou_num', '>', 0)->select()->toArray();

        } elseif ($main_filter == '爱站') {
            $row = $this->ym_model->field('ym,delete_time')->where($where)->whereOr([['bd_pr', '>', 0], ['so_pr', '>', 0], ['google_pr', '>', 0], ['yd_pr', '>', 0], ['sogou_pr', '>', 0], ['sm_pr', '>', 0]])->select()->toArray();

        } elseif ($main_filter == '360') {
            $row = $this->ym_model->field('ym,delete_time')->where($where)->where('so_num', '>', 0)->select()->toArray();

        }



        $all_ym_list = [];
        foreach ($row as $data){
            $all_ym_list[] = $data['delete_time'].','.$data['ym'];
        }

        $data = join("\n",$all_ym_list);
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="主线'.$file_name.'.txt"');
        header('Content-Length: ' . strlen($data));

        echo $data;


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
            $save_data['title'] = $post['title'];

            $data = [];
            //域名后缀
            if ($post['hz'] != ''){
                $data['hz'] = $post['hz'];
            }


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
                $data['baidu']['baidu_is_com_chinese_title'] = $post['baidu_is_com_chinese_title'];
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
                $data['sogou']['sogou_is_com_word'] = $post['sogou_is_com_word'];
                $data['sogou']['sogou_jg'] = $post['sogou_jg'];
                $data['sogou']['sogou_jv_now_day_1'] = $post['sogou_jv_now_day_1'];
                $data['sogou']['sogou_jv_now_day_2'] = $post['sogou_jv_now_day_2'];

            }

            //360
            if ($post['is_com_so'] == '1') {
                if ($post['so_sl_1'] == '0' && !$post['so_sl_2'] == '0') {
                    $this->error('请完善360收录信息~');
                }
                $data['so']['so_sl_1'] = $post['so_sl_1'];
                $data['so']['so_sl_2'] = $post['so_sl_2'];
                $data['so']['so_fxts'] = $post['so_fxts'];
                $data['so']['so_jg'] = $post['so_jg'];
                $data['so']['so_is_com_word'] = $post['so_is_com_word'];


            }

            //历史
            if ($post['is_com_history'] == '1') {

                if ($post['history_age_1'] == '0' && $post['history_age_2'] == '0' && $post['history_chinese_1'] == '0'
                    && $post['history_chinese_2'] == '0' && $post['history_five_1'] == '0'
                    && $post['history_five_2'] == '0' && $post['history_five_lianxu_1'] == '0'
                    && $post['history_five_lianxu_2'] == '0' && $post['history_lianxu_1'] == '0'
                    && $post['history_lianxu_2'] == '0' && $post['history_score_1'] == '0'
                    && $post['history_score_2'] == '0' && $post['history_tongyidu_1'] == '0'
                    && $post['history_tongyidu_2'] == '0' && $post['history_is_com_word'] == '0' && $post['history_is_com_erci_word'] == '0') {
                    $this->error('请完善历史信息~');
                }
                $data['history']['history_age_1'] = $post['history_age_1'];
                $data['history']['history_age_2'] = $post['history_age_2'];
                $data['history']['history_is_com_word'] = $post['history_is_com_word'];
                $data['history']['history_is_com_erci_word'] = $post['history_is_com_erci_word'];

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

                if ($post['aizhan_baidu_pr_1'] == '0' && $post['aizhan_baidu_pr_2'] == '0' && $post['aizhan_yidong_pr_1'] == '0' && $post['aizhan_yidong_pr_2'] == '0' && $post['aizhan_so_pr_1'] == '0' && $post['aizhan_so_pr_2'] == '0' && $post['aizhan_sm_pr_1'] == '0' && $post['aizhan_sm_pr_2'] == '0' && $post['aizhan_sogou_pr_1'] == '0' && $post['aizhan_sogou_pr_2'] == '0') {
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

            $filter_insert_id = $this->filter_model->json(['data'])->insertGetId($save_data);
            $filter_insert_id ? $this->success('保存成功~') : $this->error('保存失败');

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

            $data = [];
            //域名后缀
            if ($post['hz'] != ''){
                $data['hz'] = $post['hz'];
            }
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
                $data['baidu']['baidu_is_com_chinese_title'] = $post['baidu_is_com_chinese_title'];
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
                $data['sogou']['sogou_jg'] = $post['sogou_jg'];
                $data['sogou']['sogou_is_com_word'] = $post['sogou_is_com_word'];
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

                if ($post['history_age_1'] == '0' && $post['history_age_2'] == '0'
                    && $post['history_chinese_1'] == '0' && $post['history_chinese_2'] == '0'
                    && $post['history_five_1'] == '0' && $post['history_five_2'] == '0'
                    && $post['history_five_lianxu_1'] == '0' && $post['history_five_lianxu_2'] == '0'
                    && $post['history_lianxu_1'] == '0' && $post['history_lianxu_2'] == '0'
                    && $post['history_score_1'] == '0' && $post['history_score_2'] == '0'
                    && $post['history_tongyidu_1'] == '0' && $post['history_tongyidu_2'] == '0'
                    && $post['history_is_com_word'] == '0'&& $post['history_is_com_erci_word'] == '0') {
                    $this->error('请完善历史信息~');
                }
                $data['history']['history_age_1'] = $post['history_age_1'];
                $data['history']['history_age_2'] = $post['history_age_2'];
                $data['history']['history_is_com_word'] = $post['history_is_com_word'];//对比敏感词
                $data['history']['history_is_com_erci_word'] = $post['history_is_com_erci_word'];//二次对比敏感词

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

                if ($post['aizhan_baidu_pr_1'] == '0' && $post['aizhan_baidu_pr_2'] == '0' && $post['aizhan_yidong_pr_1'] == '0' && $post['aizhan_yidong_pr_2'] == '0' && $post['aizhan_so_pr_1'] == '0' && $post['aizhan_so_pr_2'] == '0' && $post['aizhan_sm_pr_1'] == '0' && $post['aizhan_sm_pr_2'] == '0' && $post['aizhan_sogou_pr_1'] == '0' && $post['aizhan_sogou_pr_2'] == '0') {
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
            $d = $row['data'] ? json_decode($row['data'], true) : null;
            //判断是否修改了参数 如果修改直接停止

            $save = $this->filter_model->json(['data'])->where('id', $id)->update($save_data);
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
        $row = $this->model->whereIn('id', $id)->select();
        $row->isEmpty() && $this->error('数据不存在');
        try {
            $this->model->whereIn('id', $id)->delete();

            //删除支线任务
            $zhi_list = $this->filter_model->where('main_filter_id', 'in', $id)->select()->toArray();
            foreach ($zhi_list as $index => $item) {
                //删除支线数据
                $this->filter_model->where('id', '=', $item['id'])->delete(true);
            }
        } catch (\Exception $e) {
            $this->error('删除失败 ' . $e->getMessage());
        }
        $this->success('删除成功');
    }


    /**
     * @NodeAnotation(title="展示支线详情")
     */
    public function show_zhi($id)
    {
        $row = $this->filter_model->find($id);
        $row = $this->filter_model->where('main_filter_id', '=', $row['main_filter_id'])->select()->toArray();
        $this->assign('row', json_encode($row));
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
     * @NodeAnotation(title="展示可购买域名")
     */
    public function show_buy_ym($id)
    {

        //获取主线信息  再根据支线中的信息来查找数据
        $zhi_row = $this->filter_model->find($id);

        //获取筛选日期 前4天后4天数据
        $date = sysconfig('wjxt','delete_filter_time');
        $prevDate = date('Y-m-d', strtotime("-4 days", strtotime($date)));
        $nextDate  = date('Y-m-d', strtotime("+4 days", strtotime($date)));
        $where = [['source', '=', '每日删除'],['delete_time','between',[$prevDate,$nextDate]]];

        $main_row = $this->model->where('id', '=', $zhi_row['main_filter_id'])->find();
        $main_filter = $main_row['main_filter'];

        if ($main_filter == '百度') {
            $where[] = ['baidu_num', '>', 0];
        } elseif ($main_filter == '备案') {

            $where[] = ['beian_pcts', '>', '2000-01-01'];

        } elseif ($main_filter == '历史') {
            $where[] = ['history_age', '>', 0];
        } elseif ($main_filter == '搜狗') {
            $where[] = ['sogou_num', '>', 0];

        } elseif ($main_filter == '爱站') {
//            $where[] = ['sogou_num','>',0];
//            $row = $this->ym_model->whereOr([['bd_pr','>',0],['so_pr','>',0],['google_pr','>',0],['yd_pr','>',0],['sogou_pr','>',0],['sm_pr','>',0]])->select()->toArray();

        } elseif ($main_filter == '360') {
            $where[] = ['so_num', '>', 0];
        }


        //判断支线
        $detail = json_decode($zhi_row['data'], true);


        if (isset($detail['baidu'])){
            //百度收录
            $where[] = ['baidu_num','between',[$detail['baidu']['baidu_sl_1'], $detail['baidu']['baidu_sl_2']=='0'?9999999:$detail['baidu']['baidu_sl_2']]];

            //百度标题语言
            if (isset($detail['baidu']['baidu_is_com_chinese_title'])){
                if ($detail['baidu']['baidu_is_com_chinese_title'] == '1'){
                    $where[]  = ['baidu_title_lang','=','中文'];
                }
            }


            //百度结构
            if ($detail['baidu']['baidu_jg'] == '1'){//首页
                $where[]  = ['baidu_jg','like','%首页%'];
            } elseif ($detail['baidu']['baidu_jg'] == '3'){//内页
                $where[]  = ['baidu_jg','like','%内页%'];
            }elseif ($detail['baidu']['baidu_jg'] == '2'){//泛
                $where[]  = ['baidu_jg','like','%泛%'];
            }

            //百度url语言
            if ($detail['baidu']['baidu_is_com_chinese'] == '1'){
                $where[]  = ['baidu_url_lang','=','中文'];
            }
            //百度敏感词
            if ($detail['baidu']['baidu_is_com_word'] == '1'){
                $where[]  = ['baidu_mingan','=',''];
            }

        }

        if (isset($detail['beian'])){

            if ($detail['beian']['beian_xz']){ #排除备案性质
                foreach (explode(',',$detail['beian']['beian_xz']) as $xz){
                    $where[]  = ['beian','<>',trim($xz)];
                }
            }
            if ($detail['beian']['beian_pcts']){ #排除时间

                // 获取当前时间
                $currentDate = new \DateTime();

                // 减去10天
                $currentDate->sub(new \DateInterval('P'.$detail['beian']['beian_pcts'].'D'));
                // 格式化日期为字符串
                $modifiedDate = $currentDate->format('Y-m-d');
                $where[]  = ['beian_pcts','<=',$modifiedDate];
            }
            if ($detail['beian']['beian_suffix']){ #备案后缀
                $where[]  = ['beian_ws','<=',$detail['beian']['beian_suffix']];
            }

        }

        if (isset($detail['sogou'])){
            //搜狗收录
            $where[] = ['sogou_num','between',[$detail['sogou']['sogou_sl_1'], $detail['sogou']['sogou_sl_2']=='0'?9999999:$detail['sogou']['sogou_sl_2']]];

            //百度结构
            if ($detail['sogou']['sogou_jg'] == '1'){//首页
                $where[]  = ['sogou_jg','like','%首页%'];
            } elseif ($detail['sogou']['baidu_jg'] == '3'){//内页
                $where[]  = ['sogou_jg','like','%内页%'];
            }elseif ($detail['sogou']['baidu_jg'] == '2'){//泛
                $where[]  = ['sogou_jg','like','%泛%'];
            }

            //百度敏感词
            if ($detail['sogou']['sogou_is_com_word'] == '1'){
                $where[]  = ['sogou_mingan','=',''];
            }

            //判断距离时间
            // 获取当前时间
            $currentDate = new \DateTime();

            // 减去10天
            $currentDate->sub(new \DateInterval('P'.$detail['sogou']['sogou_jv_now_day_1'].'D'));
            // 格式化日期为字符串
            if ($detail['sogou']['sogou_jv_now_day_1'] == '0'){
                $modifiedDate = '2000-02-22';
            }else{
                $modifiedDate = $currentDate->format('Y-m-d');

            }

            // 获取当前时间
            $currentDate1 = new \DateTime();

            // 减去10天
            $currentDate1->sub(new \DateInterval('P'.$detail['sogou']['sogou_jv_now_day_2'].'D'));
            // 格式化日期为字符串
            if ($detail['sogou']['sogou_jv_now_day_2'] == '0'){
                $modifiedDate1 = '2322-02-22';
            }else{
                $modifiedDate1 = $currentDate1->format('Y-m-d');
            }

            $where[] = ['sogou_kz','between',[$modifiedDate,$modifiedDate1]];





        }

        if (isset($detail['so'])){
            //360收录
            $where[] = ['so_num','between',[$detail['so']['so_sl_1'], $detail['so']['so_sl_2']=='0'?9999999:$detail['so']['so_sl_2']]];

            //360结构
            if ($detail['so']['so_jg'] == '1'){//首页
                $where[]  = ['so_jg','like','%首页%'];
            } elseif ($detail['so']['so_jg'] == '3'){//内页
                $where[]  = ['so_jg','like','%内页%'];
            }elseif ($detail['so']['so_jg'] == '2'){//泛
                $where[]  = ['so_jg','like','%泛%'];
            }

            //360敏感词
            if ($detail['so']['so_is_com_word'] == '1'){
                $where[]  = ['so_mingan','=',''];
            }
            //360风险提示
            if ($detail['so']['so_fxts'] == '1'){
                $where[]  = ['so_fengxian','=',0];
            }
        }

        if (isset($detail['history'])){
            //历史年龄
            $where[] = ['history_age','between',[$detail['history']['history_age_1'], $detail['history']['history_age_2']=='0'?9999999:$detail['history']['history_age_2']]];
            //五年历史
            $where[] = ['history_five_num','between',[$detail['history']['history_five_1'], $detail['history']['history_five_2']=='0'?9999999:$detail['history']['history_five_2']]];
            //五年连续
            $where[] = ['history_five_lianxu','between',[$detail['history']['history_five_lianxu_1'], $detail['history']['history_five_lianxu_2']=='0'?9999999:$detail['history']['history_five_lianxu_2']]];
            //历史评分
            $where[] = ['history_score','between',[$detail['history']['history_score_1'], $detail['history']['history_score_2']=='0'?9999999:$detail['history']['history_score_2']]];
            //中文条数
            $where[] = ['history_chinese_num','between',[$detail['history']['history_chinese_1'], $detail['history']['history_chinese_2']=='0'?9999999:$detail['history']['history_chinese_2']]];
            //统一度
            $where[] = ['history_tongyidu','between',[$detail['history']['history_tongyidu_1'], $detail['history']['history_tongyidu_2']=='0'?9999999:$detail['history']['history_tongyidu_2']]];
            //最长连续
            $where[] = ['history_max_lianxu','between',[$detail['history']['history_lianxu_1'], $detail['history']['history_lianxu_2']=='0'?9999999:$detail['history']['history_lianxu_2']]];
            //敏感词
            if ($detail['history']['history_is_com_word'] == '1'){
                $where[] = ['history_mingan','=',''];
            }
            //二次敏感词
            if ($detail['history']['history_is_com_erci_word'] == '1'){
                $where[] = ['history_erci_mingan','=',''];
            }
        }

        if (isset($detail['aizhan'])){
            //爱站百度pr
            $where[] = ['bd_pr','between',[$detail['aizhan']['aizhan_baidu_pr_1'], $detail['aizhan']['aizhan_baidu_pr_2']=='0'?9999999:$detail['aizhan']['aizhan_baidu_pr_2']]];
            //爱站神马pr
            $where[] = ['sm_pr','between',[$detail['aizhan']['aizhan_sm_pr_1'], $detail['aizhan']['aizhan_sm_pr_2']=='0'?9999999:$detail['aizhan']['aizhan_sm_pr_2']]];
            //爱站360pr
            $where[] = ['so_pr','between',[$detail['aizhan']['aizhan_so_pr_1'], $detail['aizhan']['aizhan_so_pr_2']=='0'?9999999:$detail['aizhan']['aizhan_so_pr_2']]];
            //爱站搜狗pr
            $where[] = ['sogou_pr','between',[$detail['aizhan']['aizhan_sogou_pr_1'], $detail['aizhan']['aizhan_sogou_pr_2']=='0'?9999999:$detail['aizhan']['aizhan_sogou_pr_2']]];
            //爱站移动pr
            $where[] = ['yd_pr','between',[$detail['aizhan']['aizhan_yidong_pr_1'], $detail['aizhan']['aizhan_yidong_pr_2']=='0'?9999999:$detail['aizhan']['aizhan_yidong_pr_2']]];

        }


        if (isset($detail['hz'])){
            $where[] = ['hz','in',$detail['hz']];
        }

        $all_data = $this->ym_model->field('ym,delete_time')
                ->where($where)->select()->toArray();

        $all_ym_list = [];
        foreach ($all_data as $data){
            if (isset($all_ym_list[$data['delete_time']]) == false){
                $all_ym_list[$data['delete_time']] = [];
            }
            $all_ym_list[$data['delete_time']][] = $data['ym'];
        }
        $this->assign('all_data',$all_ym_list);
        $this->assign('sql',$this->ym_model->getLastSql());

        return $this->fetch();

    }


    /**
     * @NodeAnotation(title="复制模型")
     */
    public function copy_model($id)
    {

        $get = $this->request->get();
        if (isset($get['type'])) {
            $row = $this->filter_model->find($id);
            $row = $row->toArray();
            $row['title'] = $row['title'] . '复制';
            $row['create_time'] = date('Y-m-d h:i:s');
            $row['spider_status'] = 3;
            unset($row['id']);
            $save = $this->filter_model->insert($row);
        } else {
            $row = $this->model->find($id);
            empty($row) && $this->error('没有此模型，复制失败');
            $row = $row->toArray();
            $row['title'] = $row['title'] . '复制';
            $row['create_time'] = date('Y-m-d h:i:s');
            $row['spider_status'] = 3;
            unset($row['id']);
            $pid = $this->model->insertGetId($row);
            $all_data = $this->filter_model->where('main_filter_id', '=', $id)->select()->toArray();
            foreach ($all_data as $item) {
                unset($item['create_time']);
                unset($item['id']);
                $item['main_filter_id'] = $pid;
                $item['title'] = $item['title'] . ' 复制';
                $item['spider_status'] = 3;


                $this->filter_model->insert($item);
            }
        }

        $this->success('复制成功');


    }


    /**
     * @NodeAnotation(title="设置")
     */
    public function set_time(){

        if ($this->request->isAjax()){
            $post = $this->request->post();
            $t = $this->config_model->where('group','=','wjxt')->where('name','=','delete_filter_time')->find();
            if (empty($t)){
                $this->config_model->insert([
                    'group'=>'wjxt',
                    'name'=>'delete_filter_time',
                    'value'=>$post['filter_time'],
                ]);
            }else{
                $t->save(['value'=>$post['filter_time']]);
            }

            $this->success('设置成功~');


        }
        $t = $this->config_model->where('group','=','wjxt')->where('name','=','delete_filter_time')->find();

        $this->assign('time',empty($t)?'':$t['value']);
        return $this->fetch();

    }



}
