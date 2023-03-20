<?php



namespace app\admin\controller\nod\statement_analysis;


use app\admin\model\NodAccountInfo;
use app\admin\model\NodInventory;
use app\admin\model\NodOrder;
use app\admin\model\NodOrderInfo;
use app\admin\model\NodWarehouse;


use app\admin\model\NodWarehouseInfo;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * @ControllerAnnotation(title="报表 客户资金往来")
 */
class CustomerInfo extends AdminController
{

    use \app\admin\traits\Curd;


    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new NodAccountInfo();
        $this->order_info_model = new NodOrderInfo();
        $this->order_model = new NodOrder();


    }

    /**
     * @NodeAnotation(title="资金列表")
     */
    public function index()
    {

        if ($this->request->isAjax()){
            list($page, $limit, $where) = $this->buildTableParames();
            //查询订单已审核的 并且有客户id的订单
            $order_data = $this->order_model->field('id')->where('customer_id','<>',null)->where('audit_status','=',1)->select()->toArray();
            $ids =[];
            foreach ($order_data as $item) $ids[] = $item['id'];



            $list = $this->order_info_model->where('pid','in',$ids)
                ->with(['getOrder','getAccount','getSupplier','getWarehouse','getCustomer','getCategory','getSaleUser'],'left')
                ->where($where)
                ->page($page,$limit)
                ->order('id','desc')
                ->select()->toArray();

//            dd($list);

            $count = $this->order_info_model->where('pid','in',$ids)->where($where)->count();
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
