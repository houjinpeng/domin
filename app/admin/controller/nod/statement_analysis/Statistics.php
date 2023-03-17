<?php



namespace app\admin\controller\nod\statement_analysis;


use app\admin\model\NodAccount;
use app\admin\model\NodAccountInfo;
use app\admin\model\NodCustomerManagement;
use app\admin\model\NodInventory;
use app\admin\model\NodSupplier;
use app\admin\model\NodWarehouse;


use app\admin\model\NodWarehouseInfo;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * @ControllerAnnotation(title="报表 总览")
 */
class Statistics extends AdminController
{

    use \app\admin\traits\Curd;


    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new NodCustomerManagement();
        $this->kc_model = new NodInventory();
        $this->zh_model = new NodAccount();
        $this->account_info_model = new NodAccountInfo();
        $this->gys_model = new NodSupplier();
        $this->wareehouse_info_model = new NodWarehouseInfo();


    }

    /**
     * @NodeAnotation(title="报表")
     */
    public function index()
    {
        //客户总数
        $customer_count = $this->model->count();
        //库存总数
        $kc_count = $this->kc_model->count();
        //账户数
        $zh_count = $this->zh_model->count();
        //供应商数
        $gys_count = $this->gys_model->count();

        //计算日销售额
        $day_sales = $this->wareehouse_info_model->where('type','=',2)->whereRaw('to_days(order_time) = to_days(now())')->sum('total_price');
        $day_profit_price = $this->wareehouse_info_model->where('type','=',2)->whereRaw('to_days(order_time) = to_days(now())')->sum('profit_price');
        //日订单数
        $day_count = $this->wareehouse_info_model->where('type','=',2)->whereRaw('to_days(order_time) = to_days(now())')->count();
        //日资金收入
        $day_shouru = $this->account_info_model->whereRaw('to_days(operate_time) = to_days(now())')->sum('price');

        //库存成本
        $kc_cb = $this->kc_model->sum('total_price');


        //总资金余额
        $balance = $this->zh_model->sum('balance_price');

        //获取每日入库数
        $every_rukun_data = $this->wareehouse_info_model->field('count(*) as count ,DATE_FORMAT(order_time, "%Y-%m-%d") as order_time')->where('type','=',1)
            ->group("DATE_FORMAT(order_time, '%Y-%m-%d')")
            ->select()->toArray();
        $every_ruku_list = ['time'=>[],'list'=>[]];
        foreach ($every_rukun_data as $item){
            $every_ruku_list['time'][] = $item['order_time'];
            $every_ruku_list['list'][] = $item['count'];
        }
        //获取每日出库数
        $every_chuku_data = $this->wareehouse_info_model->field('sum(profit_price) as profit_price,sum(total_price) as total_price,count(*) as count ,DATE_FORMAT(order_time, "%Y-%m-%d") as order_time')
            ->where('type','=',2)
            ->group("DATE_FORMAT(order_time, '%Y-%m-%d')")
            ->select()->toArray();

        $every_chuku_list = ['time'=>[],'list'=>[],'day_sales'=>[['time'=>[],'list'=>[]]],'day_profit'=>[['time'=>[],'list'=>[]]]];
        foreach ($every_chuku_data as $item){
            //日销售额
            $every_chuku_list['day_sales']['time'][] = $item['order_time'];
            $every_chuku_list['day_sales']['list'][] = $item['total_price'];
            //日毛利
            $every_chuku_list['day_profit']['time'][] = $item['order_time'];
            $every_chuku_list['day_profit']['list'][] = $item['profit_price'];
            //日出库
            $every_chuku_list['time'][] = $item['order_time'];
            $every_chuku_list['list'][] = $item['count'];
        }





        $this->assign('every_chuku_list',json_encode($every_chuku_list));
        $this->assign('every_ruku_list',json_encode($every_ruku_list));
        $this->assign('balance',$balance);
        $this->assign('kc_cb',$kc_cb);
        $this->assign('day_shouru',$day_shouru);
        $this->assign('day_count',$day_count);
        $this->assign('day_sales',$day_sales);
        $this->assign('customer_count',$customer_count);
        $this->assign('zh_count',$zh_count);
        $this->assign('kc_count',$kc_count);
        $this->assign('gys_count',$gys_count);
        $this->assign('day_profit_price',$day_profit_price);


        return $this->fetch();
    }



}
