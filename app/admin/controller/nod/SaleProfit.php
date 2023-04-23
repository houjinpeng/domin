<?php


namespace app\admin\controller\nod;

use app\admin\controller\JvMing;
use app\admin\model\NodAccount;
use app\admin\model\NodAccountInfo;
use app\admin\model\NodCategory;
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
 * @ControllerAnnotation(title="报表 销售利润表")
 */
class SaleProfit extends AdminController
{

    use \app\admin\traits\Curd;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new NodAccountInfo();
        $this->category_model = new NodCategory();


    }

    /**
     * @NodeAnotation(title="销售利润表列表")
     */
    public function index()
    {
        if ($this->request->isAjax()){
            list($page, $limit, $where) = $this->buildTableParames();
            $whereOr = [['type','=',3],['type','=',6],['type','=',8],['type','=',9]];
            $where[] = ['sale_user_id','<>','null'];
            //查询销售费用单id
            $cate = $this->category_model->where('name','=','销售费用')->find();
            if (!empty($cate)){
                $whereOr[] = ['category_id','=',$cate['id']];
            }

            $where = format_where_datetime($where,'operate_time');

            $list = $this->model
                ->with(['getOrder','getAccount','getSupplier','getWarehouse','getOrderUser','getCustomer','getCategory','getSaleUser'],'left')
                ->where($where)->where(function ($query) use ($whereOr){
                    $query->whereOr($whereOr);
                })
                ->page($page,$limit)
                ->order('id','desc')->select()->toArray();

            $count = $this->model->where($where)->where(function ($query) use ($whereOr){
                $query->whereOr($whereOr);
            })->count();

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
