<?php



namespace app\admin\controller\domain;


use app\admin\controller\JvMingApi;
use app\admin\model\DomainCrawlStore;
use app\admin\model\DomainStore;
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

/**
 * @ControllerAnnotation(title="店铺列表")
 */
class Store extends AdminController
{

    use \app\admin\traits\Curd;

    protected $sort = [
        'id'   => 'asc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new DomainStore();
        $this->crawl_store_model = new DomainCrawlStore();

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
            $file_path = $post['file_path'];
            ini_set('max_execution_time', '0');//执行时间
            ini_set('memory_limit','1024M');

            $all_store_data = $this->model->field('store_id')->select()->toArray();
            $all_store = [];
            foreach ($all_store_data as $index=>$item){
                $all_store[] = $item['store_id'];
            }

            $data = Excel::import($file_path,$startRow = 2);
            $insert_data = [];
            foreach ($data as $index=>$item){
                if ($item[0] == 'id'|| $item[0] == 'ID')continue;
                if (in_array($item[0],$all_store))continue;

                $d = [
                    'store_id'=>$item[0],
                    'url'=>$item[1],
                    'name'=>$item[2],
                    'register_time'=>$item[3] ==''?null:$item[3],
                    'yunying_num'=>$item[4]== ''? 0 :$item[4],
                    'brief_introduction'=>$item[5],
                    'sales'=>$item[6],
                    'repertory'=>$item[7]== ''? 0 :$item[7],
                    'store_cate_analyse'=>$item[8],
                    'phone'=>$item[9],
                    'team'=>$item[10],
                    'individual_opinion'=>$item[11],
                ];
                $is_have = $this->model->where('store_id',$item[0])->find();
                if ($is_have){
                    //覆盖
                    $is_have->save($d);
                }else{
                    $insert_data[] = $d;

//                    $this->model->insert($d);
                }

            }
            try {
                $this->model->insertAll($insert_data);
            }catch (\Exception $e){
                $this->error($e->getMessage());
            }

            $this->success('导入成功');

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

            $save = $row->save($this->request->post());
            $save?$this->success('修改成功'):$this->error('修改失败');
        }
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
            $save = $row->delete();
        } catch (\Exception $e) {
            $this->error('删除失败');
        }
        $save ? $this->success('删除成功') : $this->error('删除失败');
    }


    /**
     * @NodeAnotation(title="导出")
     */
    public function export()
    {
        ini_set ("memory_limit","-1");
        ini_set('max_execution_time', '0');//执行时间

        list($page, $limit, $where) = $this->buildTableParames();
        $list = $this->model
//            ->limit(50000)
            ->where($where)
            ->select()
            ->toArray();

        $download_data = [];
        foreach ($list as $index=>&$item){
            $item['brief_introduction'] = str_ireplace('=','+',$item['brief_introduction']);
            $download_data[] = $item;
        }
        $header = [['店铺ID','store_id'],['店铺名称','name'],['店铺链接','url'],['注册时间','register_time'],['运营天数','yunying_num'],['简介','brief_introduction'],['销量','sales']
            ,['库存','repertory'],['店铺品类分析','store_cate_analyse'],['联系方式','phone'],['所属团队','team'],['个人信息','individual_opinion']];
        $fileName = '店铺信息'.time();

        return Excel::exportData($list, $header, $fileName, 'xlsx');
    }


    /**
     * @NodeAnotation(title="添加关注")
     */
    public function add_like(){
        if ($this->request->isAjax()){
            $post = $this->request->post();
            if (isset($post['id'])){
                $id = $post['id'];
                $update = $this->model->where('id','in',$id)->update(['is_like'=>1]);
            }else{
                $get = $this->request->get();
                $id = $get['id'];
                $like_type = $get['type'];
                $update = $this->model->where('id','in',$id)->update(['is_like'=>$like_type]);
            }

            $update ? $this->success('操作成功'):$this->error('操作失败');

        }

    }

    /**
     * @NodeAnotation(title="批量编辑")
     */
    public function batch_edit($id){
        if ($this->request->isAjax()){
            $post = $this->request->post();
            $this->model->where('id','in',$id)->update($post);
            $this->success('批量保存成功~');

        }

        return $this->fetch();
    }


    /**
     * @NodeAnotation(title="批量更新店铺")
     */
    public function refresh_store($id){

        $all_store = $this->model->field('store_id')->where('id','in',$id)->select()->toArray();
        foreach ($all_store as $item){
            $this->crawl_store_model->insert(['store_id'=>$item['store_id']]);

        }
        $this->success('已经加入到更新队列，请耐心等待~ 2分钟运行一次哦！');

    }

    /**
     * @NodeAnotation(title="批量全部店铺")
     */
    public function refresh_all_store(){
        $all_store = $this->model->field('store_id')->select()->toArray();
        $insertall= [];
        foreach ($all_store as $item){
            $insertall[] = ['store_id'=>$item['store_id']];
        }
        $this->crawl_store_model->insertAll($insertall);

        $this->success('已经加入到更新队列，请耐心等待~ 2分钟运行一次哦！');
    }



    /**
     * @NodeAnotation(title="抓取店铺信息")
     */
    public function crawl_store(){

        $this->jm_api = new JvMingApi();
        //获取全部要抓取的店铺
        $all_crawl_store = $this->crawl_store_model->select()->toArray();
        foreach ($all_crawl_store as $item){

            $data = $this->jm_api->get_store_data($item['store_id']);
            if (strstr('不开放店铺,您无法查看该店铺出售信息',$data['msg']) || $data['msg'] == '对不起,店铺不存在!'){
                $this->crawl_store_model->where('id','=',$item['id'])->delete();
                continue;
            }
            try {
                $result = $data['data'];
            }catch (\Exception $e){
                dd($data ,$e->getMessage());
            }

            $store_cate = '未签约';
            if ($result['lxtxt'] == '企业签约店铺'){
                $store_cate = '企业';
            }
            elseif ($result['lxtxt'] == '个人签约店铺'){
                $store_cate = '个人';
            }

            //查询库存
            $form_params = [
                'tao'=>$item['store_id'],
                'psize'=>50,
            ];
            $sale_data = $this->jm_api->get_yikoujia_list($form_params);

            //要更新的数据
            $save_data = [
                'name'=>$result['dpmc'],
                'register_time'=>$result['sj'],
                'brief_introduction'=>$result['dpgg'], //简介
                'sales'=>$result['m_xinyong']=='已隐藏'?-1:$result['m_xinyong'],//销量
                'repertory'=>$sale_data['count'],//库存
                'store_cate'=>$store_cate, //店铺类型
                'crawl_time'=>date('Y-m-d H:i:s'), //店铺类型

            ];
            //更新数据
            $this->model->where('store_id','=',$item['store_id'])->update($save_data);
            //删除更新过的
            $this->crawl_store_model->where('id','=',$item['id'])->delete();
        }

        return '更新成功';
    }
}
