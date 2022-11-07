<?php



namespace app\admin\controller\domain;


use app\admin\controller\Tool;
use app\admin\model\DomainJk;
use app\admin\model\DomainSalse;
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
 * @ControllerAnnotation(title="销量列表")
 */
class Sales extends AdminController
{

    use \app\admin\traits\Curd;

    protected $sort = [
        'fixture_date'   => 'desc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new DomainSalse();
        $this->model_store = new DomainStore();
        $this->model_jk = new DomainJk();
        $this->tool = new Tool();

    }

    /**
     * @NodeAnotation(title="列表")
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($page, $limit, $where) = $this->buildTableParames();

            $get = $this->request->get();
            if (isset($get['field'])){
                $this->sort = [$get['field']=>$get['order']];
            }else{
                $this->sort = ['id'=>'desc'];
            }
            $w = [];
            $whereOr =[];
            foreach ($where as $index=>&$item){
                if ($item[0] == 'hz'){
                    $hz_list= explode(',',substr($item[2],1,-1));
                    foreach ($hz_list as $indx=>$hz){
                        $whereOr[] = ['ym','like','%'.$hz];
                    }
                    continue;
                }elseif ($item[0] =='fixture_date'){
                    $item[2] = date('Y-m-d',$item[2]);

                }
                $w[] = $item;
            }
            $where = $this->tool->build_select_where($w);
            $count = $this->model->whereOr($whereOr)
                ->where($where)
                ->count();
            $list = $this->model
                ->with('getSalesData')
                ->whereOr($whereOr)
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
            ini_set('memory_limit','1024M');

            $data = Excel::import($file_path,$startRow = 2);
            $insert_data = [];
            foreach ($data as $index=>$item){
                if ($item[0] == 'id')continue;
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
                $insert_data[] = $d;
//                $is_have = $this->model->where('store_id',$item[0])->find();
//                if ($is_have){
//                    $is_have->save($d);
//                }else{
//                    $this->model->insert($d);
//                }

            }
            $this->model->insertAll($insert_data);
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
        $is_have = $this->model_store->where('store_id','=',$row['store_id'])->find();
        empty($is_have) &&  $this->error('未匹配到卖家，不允许修改~');

        if ($this->request->isPost()) {
            $post = $this->request->post();
            $save = $this->model_store->where('store_id','=',$row['store_id'])->update($post);
            $save?$this->success('修改成功'):$this->error('修改失败,没有匹配到数据');
        }
        $this->assign('row', $is_have);
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
