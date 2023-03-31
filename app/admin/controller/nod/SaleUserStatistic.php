<?php


namespace app\admin\controller\nod;

use app\admin\controller\JvMing;
use app\admin\model\NodAccount;
use app\admin\model\NodAccountInfo;
use app\admin\model\NodCategory;
use app\admin\model\NodInventory;
use app\admin\model\NodOrder;
use app\admin\model\NodOrderInfo;
use app\admin\model\NodSaleUser;
use app\admin\model\NodSupplier;
use app\admin\model\NodWarehouse;
use app\admin\model\NodWarehouseInfo;
use app\admin\model\SystemAdmin;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * @ControllerAnnotation(title="报表 销售员利润统计")
 */
class SaleUserStatistic extends AdminController
{

    use \app\admin\traits\Curd;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new SystemAdmin();
        $this->account_info_model = new NodAccountInfo();
        $this->category_model = new NodCategory();


    }

    /**
     * @NodeAnotation(title="销售员利润页面")
     */
    public function index()
    {
        //获取所有销售员
        $all_sale_user = $this->model->select()->toArray();
        $profit_price_list = [];
        $sale_count_list = [];
        $sale_user_list = [];
        foreach ($all_sale_user as $user){
            $sale_user_list[] = $user['username'];
            //获取销售员的销售条数及利润
            $count = $this->account_info_model->where('sale_user_id','=',$user['id'])->where('type','=','3')->group('good_name')->count();//销售单
            $th_count = $this->account_info_model->where('sale_user_id','=',$user['id'])->where('type','=','6')->count();//退货单销售单
            $sale_count_list[] = $count-$th_count;

            $cate = $this->category_model->where('name','=','销售费用')->find();
            if (!empty($cate)){

                //计算利润
                $profit_price = $this->account_info_model->where('sale_user_id','=',$user['id'])->whereRaw('(type=3 or type=6 or type=9  or type=8 or category_id='.$cate['id'].')')->sum('profit_price');//销售单
            }else{
                //计算利润
                $profit_price = $this->account_info_model->where('sale_user_id','=',$user['id'])->whereRaw('(type=3 or type=6 or type=9  or type=8)')->sum('profit_price');//销售单
            }


            $profit_price_list[] = $profit_price;

        }

        $this->assign('user_list',json_encode($sale_user_list));
        $this->assign('sale_count_list',json_encode($sale_count_list));
        $this->assign('profit_price_list',json_encode($profit_price_list));
        return $this->fetch();
    }


}
