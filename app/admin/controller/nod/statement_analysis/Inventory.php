<?php



namespace app\admin\controller\nod\statement_analysis;


use app\admin\controller\Tool;
use app\admin\model\NodInventory;
use app\admin\model\NodWarehouse;


use app\admin\model\NodWarehouseInfo;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * @ControllerAnnotation(title="报表 库存")
 */
class Inventory extends AdminController
{

    use \app\admin\traits\Curd;


    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new NodInventory();
        $this->tool = new Tool();

    }

    /**
     * @NodeAnotation(title="库存列表")
     */
    public function index()
    {

        if ($this->request->isAjax()){
            list($page, $limit, $where) = $this->buildTableParames();
            $w = $this->tool->build_select_where($where);

            $where = [];
            foreach ($w as $item){
                if ($item[0] == 'dqsj'){
                    //判断大于小于
                    if ($item[1] == '>=') {
                        $t = date("Y-m-d H:i:s", strtotime("-" . $item[2] . " Months"));
                        $where[] = ['expiration_time', '<=', $t];
                    } else {
                        $t = date("Y-m-d H:i:s", strtotime("-" . $item[2] . " Months"));
                        $where[] = ['expiration_time', '>=', $t];
                    }
                    continue;
                }elseif ($item[0] == 'register_time'){
                    $where[] = [$item[0],$item[1],date('Y-m-d H:i:s',$item[2])];
                    continue;
                }
                $where[] = $item;
            }

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
