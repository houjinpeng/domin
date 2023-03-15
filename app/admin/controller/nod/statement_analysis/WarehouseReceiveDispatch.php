<?php



namespace app\admin\controller\nod\statement_analysis;


use app\admin\model\NodWarehouse;


use app\admin\model\NodWarehouseInfo;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * @ControllerAnnotation(title="报表 收发明细")
 */
class WarehouseReceiveDispatch extends AdminController
{

    use \app\admin\traits\Curd;


    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new NodWarehouseInfo();


    }

    /**
     * @NodeAnotation(title="仓库明细列表")
     */
    public function index()
    {

        if ($this->request->isAjax()){
            list($page, $limit, $where) = $this->buildTableParames();

            $list = $this->model
                ->with(['getOrder','getAccount','getSupplier','getWarehouse'],'left')
                ->where($where)
                ->page($page,$limit)
                ->order('id','desc')->select()->toArray();

            $count = $this->model->where($where)->count();
            $data = [
                'code'=>0,
                'data'=>$list,
                'count'=>$count,
                'msg'=>''
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


        $id = $this->request->get('id');
        $row = $this->model->find($id);
        empty($row)&&$this->error('没有此仓库');
        $this->assign('warehouse_id',$id);
        return $this->fetch();

    }


}
