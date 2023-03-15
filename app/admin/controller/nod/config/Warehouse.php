<?php



namespace app\admin\controller\nod\config;


use app\admin\model\NodWarehouse;


use app\admin\model\NodWarehouseInfo;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * @ControllerAnnotation(title="财务-仓库管理")
 */
class Warehouse extends AdminController
{

    use \app\admin\traits\Curd;


    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new NodWarehouse();
        $this->info_model = new NodWarehouseInfo();


    }

    /**
     * @NodeAnotation(title="仓库列表")
     */
    public function index()
    {

        if ($this->request->isAjax()){
            list($page, $limit, $where) = $this->buildTableParames();
            $count = $this->model
                ->where($where)
                ->count();
            $list = $this->model
                ->withoutField('password')
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
     * @NodeAnotation(title="仓库出入记录")
     */
    public function show()
    {

        if ($this->request->isAjax()){
            list($page, $limit, $where) = $this->buildTableParames();
            $id = $this->request->get('warehouse_id');
//            dd($where);
            $list = $this->info_model
                ->with(['getOrder','getAccount','getSupplier'],'left')
                ->where($where)
                ->where('warehouse_id','=',$id)
                ->page($page,$limit)
                ->order('id','desc')->select()->toArray();

            $count = $this->info_model
                ->where($where)->where('warehouse_id','=',$id)->count();
            $data = [
                'code'=>0,
                'data'=>$list,
                'count'=>$count,
                'msg'=>''
            ];
            return json($data);

        }
        $id = $this->request->get('id');
        $row = $this->model->find($id);
        empty($row)&&$this->error('没有此仓库');
        $this->assign('warehouse_id',$id);
        return $this->fetch();

    }


}
