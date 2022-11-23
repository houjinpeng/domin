<?php



namespace app\admin\controller\yikoujia;


use app\admin\controller\Tool;
use app\admin\model\SystemAdmin;
use app\admin\model\YikoujiaBuyFilter;

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

/**
 * @ControllerAnnotation(title="一口价购买设置")
 */
class BuyFilter extends AdminController
{

    use \app\admin\traits\Curd;


    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new YikoujiaBuyFilter();

    }

    /**
     * @NodeAnotation(title="列表")
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($page, $limit, $where) = $this->buildTableParames();
            $list = $this->model->where($where)
                ->order('id','desc')
                ->page($page,$limit)->select()->toArray();
            $count = $this->model->where($where)->count();

            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => $count,
                'data'  => $list,
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
            $save_data = ['title'=>$post['title']];
            $save_data['place_1'] = $post['place_1'];
            $save_data['place_2'] = $post['place_2'];
            $save_data['is_buy'] = $post['is_buy'];
            $data = [];
            //备案
            if ($post['is_com_beian']=='1'){
                if (!$post['beian_suffix'] || !$post['beian_pcts'] || ! $post['beian_xz']){
                    $this->error('请完善备案信息~');
                }

                $data['beian']['beian_suffix'] = $post['beian_suffix'];
                $data['beian']['beian_pcts'] = $post['beian_pcts'];
                $data['beian']['beian_xz'] = $post['beian_xz'];

            }
            //百度
            if ($post['is_com_baidu']=='1'){
                if ($post['baidu_sl_1'] == '0' && !$post['baidu_sl_1'] == '0'){
                    $this->error('请完善百度收录信息~');
                }

                $data['baidu']['baidu_sl_1'] = $post['baidu_sl_1'];
                $data['baidu']['baidu_sl_2'] = $post['baidu_sl_2'];
                $data['baidu']['baidu_is_com_chinese'] = $post['baidu_is_com_chinese'];
                $data['baidu']['baidu_jg'] = $post['baidu_jg'];
                $data['baidu']['baidu_is_com_word'] = $post['baidu_is_com_word'];

            }
            //搜狗
            if ($post['is_com_sogou']=='1'){
                if ($post['sogou_sl_1'] == '0' && !$post['sogou_sl_2'] == '0'){
                    $this->error('请完善搜狗收录信息~');
                }
                $data['sogou']['sogou_sl_1'] = $post['so_sl_1'];
                $data['sogou']['sogou_sl_2'] = $post['so_sl_2'];
                $data['sogou']['sogou_kz'] = $post['so_fxts'];

            }
            //360
            if ($post['is_com_so']=='1'){
                if ($post['so_sl_1'] == '0' && !$post['so_sl_2'] == '0'){
                    $this->error('请完善搜狗收录信息~');
                }
                $data['so']['so_sl_1'] = $post['so_sl_1'];
                $data['so']['so_sl_2'] = $post['so_sl_2'];
                $data['so']['so_fxts'] = $post['so_fxts'];
                $data['so']['so_jg'] = $post['so_jg'];

            }
            //注册商
            if ($post['is_com_zcs']=='1'){

                if ($post['zcs_include'] == '' ){
                    $this->error('请完善注册商信息~');
                }
                $data['zcs']['zcs_include'] = $post['zcs_include'];

            }

            //历史
            if ($post['is_com_history']=='1'){

                if ($post['history_age_1'] == '0'&& $post['history_age_2'] == '0' ){
                    $this->error('请完善历史信息~');
                }
                $data['history']['history_age_1'] = $post['history_age_1'];
                $data['history']['history_age_2'] = $post['history_age_2'];
                $data['history']['history_is_com_word'] = $post['history_is_com_word'];

            }


            $save_data['data'] = $data;


            $save = $this->model->json(['data'])->save($save_data);
            $save ?$this->success('保存成功'):$this->error('保存失败');
        }
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
            $save_data = ['title'=>$post['title']];
            $save_data['place_1'] = $post['place_1'];
            $save_data['place_2'] = $post['place_2'];
            $save_data['is_buy'] = $post['is_buy'];
            $data = [];
            //备案
            if ($post['is_com_beian']=='1'){
                if (!$post['beian_suffix'] || !$post['beian_pcts'] || ! $post['beian_xz']){
                    $this->error('请完善备案信息~');
                }

                $data['beian']['beian_suffix'] = $post['beian_suffix'];
                $data['beian']['beian_pcts'] = $post['beian_pcts'];
                $data['beian']['beian_xz'] = $post['beian_xz'];

            }
            //百度
            if ($post['is_com_baidu']=='1'){
                if ($post['baidu_sl_1'] == '0' && !$post['baidu_sl_1'] == '0'){
                    $this->error('请完善百度收录信息~');
                }

                $data['baidu']['baidu_sl_1'] = $post['baidu_sl_1'];
                $data['baidu']['baidu_sl_2'] = $post['baidu_sl_2'];
                $data['baidu']['baidu_is_com_chinese'] = $post['baidu_is_com_chinese'];
                $data['baidu']['baidu_jg'] = $post['baidu_jg'];
                $data['baidu']['baidu_is_com_word'] = $post['baidu_is_com_word'];

            }
            //搜狗
            if ($post['is_com_sogou']=='1'){
                if ($post['sogou_sl_1'] == '0' && !$post['sogou_sl_2'] == '0'){
                    $this->error('请完善搜狗收录信息~');
                }
                $data['sogou']['sogou_sl_1'] = $post['so_sl_1'];
                $data['sogou']['sogou_sl_2'] = $post['so_sl_2'];
                $data['sogou']['sogou_kz'] = $post['so_fxts'];

            }
            //360
            if ($post['is_com_so']=='1'){
                if ($post['so_sl_1'] == '0' && !$post['so_sl_2'] == '0'){
                    $this->error('请完善搜狗收录信息~');
                }
                $data['so']['so_sl_1'] = $post['so_sl_1'];
                $data['so']['so_sl_2'] = $post['so_sl_2'];
                $data['so']['so_fxts'] = $post['so_fxts'];
                $data['so']['so_jg'] = $post['so_jg'];

            }
            //注册商
            if ($post['is_com_zcs']=='1'){

                if ($post['zcs_include'] == '' ){
                    $this->error('请完善注册商信息~');
                }
                $data['zcs']['zcs_include'] = $post['zcs_include'];

            }

            //历史
            if ($post['is_com_history']=='1'){

                if ($post['history_age_1'] == '0'&& $post['history_age_2'] == '0' ){
                    $this->error('请完善历史信息~');
                }
                $data['history']['history_age_1'] = $post['history_age_1'];
                $data['history']['history_age_2'] = $post['history_age_2'];
                $data['history']['history_is_com_word'] = $post['history_is_com_word'];

            }


            $save_data['data'] = $data;

            $save = $row->json(['data'])->update($save_data);
            $save ?$this->success('修改成功'):$this->error('修改失败');
        }
        $this->assign('data', json_decode($row['data'],true));
        $this->assign('row', $row);
        return $this->fetch();
    }

    

    /**
     * @NodeAnotation(title="添加监控")
     */
    public function add_jk($id)
    {
        $row = $this->model->find($id);
        empty($row) && $this->error('数据不存在');
        if (strstr($row['store_id'],'*') != false){
            $this->error('未匹配到卖家，不允许添加到监控中~');
        }
        if ($this->request->isPost()) {
            $save = $this->model_jk->insert([
                'name'=>$row['ym'],
                'store_id'=>$row['ym']['store_id'],
                'crawl_time'=>600,
            ]);

            $save?$this->success('添加成功'):$this->error('添加失败,没有匹配到卖家数据');
        }
        $this->assign('row', $row);
        return $this->fetch();
    }



    /**
     * @NodeAnotation(title="导出")
     */
    public function export()
    {
        ini_set ("memory_limit","-1");
        ini_set('max_execution_time', '0');//执行时间

        list($page, $limit, $where) = $this->buildTableParames();
        foreach ($where as $index=>&$item){
            if ($item[0] =='fixture_date'){
                $item[2] = date('Y-m-d',$item[2]);
            }
        }
        $list = $this->model
            ->where($where)
//            ->limit(50000)
            ->select()
            ->toArray();

        $download_data = [];
        foreach ($list as $index=>&$item){
            $item['jj'] = str_ireplace('=','+',$item['jj']);
            $item['mj_jj'] = str_ireplace('=','+',$item['mj_jj']);
            $download_data[] = $item;
        }
        $header = [['域名','ym'],['长度','len'],['域名简介','jj'],['卖家简介','mj_jj'],['卖家ID','store_id'],['成交日期','fixture_date'],['价格','price']];
        $fileName = '销量信息'.time();

        return Excel::exportData($list, $header, $fileName, 'xlsx');
    }


    /**
     * @NodeAnotation(title="添加关注")
     */
    public function add_like($id){
        if ($this->request->isAjax()){
            $data = $this->model->find($id);
            empty($data)&& $this->error('没有数据');

            $store_data = $this->model_store->where('store_id','=',$data['store_id'])->find();
            empty($store_data)&& $this->error('没有对应到店铺，无法关注~');
            $store_data->save(['is_like'=>1]);
            $this->success('关注成功~');


        }


    }


    /**
     * @NodeAnotation(title="重置本月匹配失败数据")
     */
    public function reset_data(){
        $date = date('Y-m-d');
        $firstDay = date('Y-m-01', strtotime($date));
        $lastDay = date('Y-m-d', strtotime("$firstDay +1 month -1 day"));
        $this->model->where('is_get_store','=',2)->where('fixture_date','>=',$firstDay)
            ->where('fixture_date','<=',$lastDay)->update(['is_get_store'=>0]);
        $this->success('重置成功');
    }

}
