<?php



namespace app\admin\controller\domain;


use app\admin\controller\Tool;
use app\admin\model\DomainHistory;
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
 * @ControllerAnnotation(title="特别关注")
 */
class Jk extends AdminController
{

    use \app\admin\traits\Curd;


    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new DomainStore();
        $this->model_sales = new DomainSalse();
        $this->model_history = new DomainHistory();

    }

    /**
     * @NodeAnotation(title="列表")
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($page, $limit, $where) = $this->buildTableParames();
            $count = $this->model
                ->where('is_like','=',1)
                ->where($where)
                ->count();
            $list = $this->model
                ->where($where)
                ->where('is_like','=',1)
                ->page($page, $limit)
                ->select()->toArray();

            foreach ($list as $index=>&$item){
                $item['get_sales_data_count'] = $this->model_sales->where('store_id','=',$item['store_id'])->where('fixture_date','=',date('Y-m-d'))->count();

            }

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
     * @NodeAnotation(title="删除")
     */
    public function delete($id)
    {
        $row = $this->model->whereIn('id', $id)->select();
        try {
            $save = $row->delete();
        } catch (\Exception $e) {
            $this->error('删除失败');
        }
        $save ? $this->success('删除成功') : $this->error('删除失败');
    }

    /**
     * @NodeAnotation(title="查看详情")
     */
    public function show()
    {
        if ($this->request->isAjax()){
            list($page, $limit, $where) = $this->buildTableParames();
            $store_id = $this->request->get('store_id');
            $count = $this->model_sales
                ->where('store_id','=',$store_id)
                ->count();
            $list = $this->model_sales
                ->with('getSalesData')
                ->where('store_id','=',$store_id)
                ->page($page, $limit)
                ->order('fixture_date','desc')
                ->select()->toArray();
            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => $count,
                'data'  => $list,
            ];
            return json($data);

        }
        if ($this->request->get('type') == 'sale'){
            $this->assign('store_id',$this->request->get('store_id') );
        }else{
            $id = $this->request->get('id');
            $row = $this->model->find($id);
            $this->assign('store_id',$row['store_id']);
        }

        return $this->fetch();
    }


    /**
     * @NodeAnotation(title="刷新店铺销量")
     */
    public function refresh_store($id){
//        $out = shell_exec('python3 wsl$\Ubuntu-20.04\www\wwwroot\domain\easyadmin\app\admin\controller\domain\aaa.py 2>&1');
        $store_ids = $this->model->field('store_id')->where('id','in',$id)->select()->toArray();
        $ids = [];

        foreach ($store_ids as $index=>$item){ $ids[] = $item['store_id'];}
        $out = shell_exec('python3 ./python_script/refresh_store_data.py '.join(',',$ids) .'  2>&1');

        strstr($out,'success')?$this->success('更新完成'):$this->error('更新失败 ');


    }

}
