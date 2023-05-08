<?php


namespace app\admin\controller\nod;

use app\admin\controller\JvMing;
use app\admin\controller\Tool;
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
use jianyan\excel\Excel;
use think\App;

/**
 * @ControllerAnnotation(title="报表 采购明细")
 */
class PurchaseDetail extends AdminController
{

    use \app\admin\traits\Curd;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new NodAccountInfo();
        $this->category_model = new NodCategory();
        $this->tool = new Tool();

    }

    /**
     * @NodeAnotation(title="采购销售明细列表")
     */
    public function index()
    {
        $type = $this->request->get('type');
        if ($this->request->isAjax()){

            list($page, $limit, $where) = $this->buildTableParames();

            if ($type == null){
                $whereOr = [['type','=',1],['type','=',2]];
            }else{
                $whereOr = [['type','=',3],['type','=',6]];
                //查询销售费用单id
                $cate = $this->category_model->where('name','=','销售费用')->find();
                if (!empty($cate)){
                    $whereOr[] = ['category_id','=',$cate['id']];
                }
            }
            $where = format_where_datetime($where,'operate_time');

            $list = $this->model
                ->with(['getWarehouse','getAccount','getSupplier','getOrderUser','getCustomer','getSaleUser','getCategory'],'left')
                ->where($where)
                ->where(function ($query) use ($whereOr){
                    $query->whereOr($whereOr);
                })
                ->page($page,$limit)->order('id','desc')->select()->toArray();
            $count = $this->model->where($where)
                ->where(function ($query) use ($whereOr){
                    $query->whereOr($whereOr);
                })
                ->count();
            $data = [
                'code'=>0,
                'data'=>$list,
                'count'=>$count,
            ];
            return json($data);

        }

        if ($type == 'sale'){
            $total_stock_price = $this->model->where('type','=',3)->sum('price');
            $total_stock_count = $this->model->where('type','=',3)->count();
            $total_sale_price = $this->model->where('type','=',6)->sum('price');
            $total_sale_count = $this->model->where('type','=',6)->count();

        }else{
            $total_stock_price = $this->model->where('type','=',1)->sum('price');
            $total_stock_count = $this->model->where('type','=',1)->count();
            $total_sale_price = $this->model->where('type','=',2)->sum('price');
            $total_sale_count = $this->model->where('type','=',2)->count();
        }

        $this->assign('total_stock_price',$total_stock_price);
        $this->assign('total_sale_price',$total_sale_price);
        $this->assign('total_stock_count',$total_stock_count);
        $this->assign('total_sale_count',$total_sale_count);
        $this->assign('type',$type);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="采购销售明细列表导出")
     */
    public function export()
    {
        list($page, $limit, $where) = $this->buildTableParames();
        $type = $this->request->get('type');
        if ($type == null){
            $whereOr = [['type','=',1],['type','=',2]];

            $header = [
                ['操作时间', 'operate_time'],
                ['域名', 'good_name'],
                ['经手人', 'getOrderUser.username'],
                ['类型', 'category'],
                ['说明', 'sm'],
                ['账号', 'getAccount.name'],
                ['变动', 'price'],
                ['账号余额', 'balance_price'],
                ['总资金剩额', 'all_balance_price'],
                ['应收款', 'customer_receivable_price'],
                ['总应收款', 'total_customer_receivable_price'],
                ['备注信息', 'remark'],

            ];
            $fileName = '采购明细单'.time();
        }
        else{
            $where[] = ['sale_user_id','<>','null'];

            $whereOr = [['type','=',3],['type','=',6]];
            //查询销售费用单id
            $cate = $this->category_model->where('name','=','销售费用')->find();
            if (!empty($cate)){
                $whereOr[] = ['category_id','=',$cate['id']];
            }
            $fileName = '销售明细单'.time();
            $header = [
                ['操作时间', 'operate_time'],
                ['域名', 'good_name'],
                ['经手人', 'getOrderUser.username'],
                ['销售员', 'getSaleUser.username'],
                ['客户', 'getCustomer.name'],
                ['类型', 'category'],
                ['说明', 'sm'],
                ['账号', 'getAccount.name'],
                ['变动', 'price'],
                ['账号余额', 'balance_price'],
                ['总资金剩额', 'all_balance_price'],
                ['应收款', 'customer_receivable_price'],
                ['总应收款', 'total_customer_receivable_price'],
                ['备注信息', 'remark'],
                ['是否付款', 'is_compute_profit'],
//                ['成本价', 'cost_price'],
//                ['销售价', 'practical_price'],
//                ['利润', 'profit_price'],
//                ['总利润', 'total_profit_price'],

            ];
        }

        $where = format_where_datetime($where,'operate_time');

        $list = $this->model
            ->with(['getWarehouse','getAccount','getSupplier','getOrderUser','getCustomer','getSaleUser','getCategory'],'left')
            ->where($where)
            ->where(function ($query) use ($whereOr){
                $query->whereOr($whereOr);
            })
            ->order('id','desc')->select()->toArray();
        foreach ($list as &$item){$item['sm'] = $this->tool->bulid_remark($item);}



        return Excel::exportData($list, $header, $fileName, 'xlsx');
    }

}
