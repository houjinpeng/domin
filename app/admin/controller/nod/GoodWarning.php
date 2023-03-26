<?php


namespace app\admin\controller\nod;

use app\admin\controller\JvMing;
use app\admin\controller\Tool;
use app\admin\model\NodAccount;
use app\admin\model\NodAccountInfo;
use app\admin\model\NodInventory;
use app\admin\model\NodOrder;
use app\admin\model\NodOrderInfo;
use app\admin\model\NodSupplier;
use app\admin\model\NodWarehouse;
use app\admin\model\NodWarehouseInfo;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * @ControllerAnnotation(title="库存 到期预警")
 */
class GoodWarning extends AdminController
{

    use \app\admin\traits\Curd;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new NodInventory();
        $this->tool = new Tool();

    }

    /**
     * @NodeAnotation(title="到期预警列表")
     */
    public function index()
    {

        if ($this->request->isAjax()){
            list($page, $limit, $where) = $this->buildTableParames();
            $w = $this->tool->build_select_where($where);

            $where = [];
            $t = date("Y-m-d H:i:s", strtotime("-20 Days"));
            $where[] = ['expiration_time', '<=', $t];

            $list = $this->model
                ->with(['getSupplier','getWarehouse'],'left')
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

        $total_price = $this->model->sum('unit_price');
        $total_count = $this->model->count();
        $this->assign('total_price',$total_price);
        $this->assign('total_count',$total_count);
        return $this->fetch();
    }


}
