<?php



namespace app\admin\controller\nod\statement_analysis;


use app\admin\model\NodAccountInfo;
use app\admin\model\NodInventory;
use app\admin\model\NodWarehouse;


use app\admin\model\NodWarehouseInfo;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * @ControllerAnnotation(title="报表 资金收支明细")
 */
class CapitalInfo extends AdminController
{

    use \app\admin\traits\Curd;


    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new NodAccountInfo();


    }

    /**
     * @NodeAnotation(title="资金列表")
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



}
