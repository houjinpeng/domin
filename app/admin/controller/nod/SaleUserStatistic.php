<?php


namespace app\admin\controller\nod;

use app\admin\controller\JvMing;
use app\admin\model\NodAccount;
use app\admin\model\NodAccountInfo;
use app\admin\model\NodCategory;
use app\admin\model\NodCustomerManagement;
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
        $this->warehouse_model = new NodWarehouse();
        $this->supplier_model = new NodSupplier();
        $this->customer_model = new NodCustomerManagement();


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
        $t = [date('y-m') .'-01 00:00:00',date('y-m') .'-31 23:59:59'];
//        $t = ['2021-01-01 00:00:00',date('y-m') .'-31 23:59:59'];
        foreach ($all_sale_user as $user){
            $sale_user_list[] = $user['username'];
            //获取销售员的销售条数及利润
            $count = $this->account_info_model
                ->where('operate_time','BETWEEN',[$t[0],$t[1]])
                ->where('sale_user_id','=',$user['id'])->where('type','=','3')->count();//销售单
            $th_count = $this->account_info_model
                ->where('operate_time','BETWEEN',[$t[0],$t[1]])
                ->where('sale_user_id','=',$user['id'])->where('type','=','6')->count();//退货单销售单
            $sale_count_list[] = $count-$th_count;

            $cate = $this->category_model->where('name','=','销售费用')->find();
            if (!empty($cate)){

                //计算利润
                $profit_price = $this->account_info_model   ->where('operate_time','BETWEEN',[$t[0],$t[1]])->where('sale_user_id','=',$user['id'])->whereRaw('(type=3 or type=6 or type=9  or type=8 or category_id='.$cate['id'].')')->sum('profit_price');//销售单
            }else{
                //计算利润
                $profit_price = $this->account_info_model   ->where('operate_time','BETWEEN',[$t[0],$t[1]])->where('sale_user_id','=',$user['id'])->whereRaw('(type=3 or type=6 or type=9  or type=8)')->sum('profit_price');//销售单
            }


            $profit_price_list[] = $profit_price;

        }

        $this->assign('user_list',json_encode($sale_user_list));
        $this->assign('sale_count_list',json_encode($sale_count_list));
        $this->assign('profit_price_list',json_encode($profit_price_list));
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="销售员利润数据")
     */
    public function get_sale_user_profit($time){

        $t = explode(' - ',$time);

        //获取所有销售员
        $all_sale_user = $this->model->select()->toArray();
        $profit_price_list = [];
        $sale_count_list = [];
        $sale_user_list = [];
        $t[1] = explode(' ',$t[1])[0].' 23:59:59';
        foreach ($all_sale_user as $user){
            $sale_user_list[] = $user['username'];
            //获取销售员的销售条数及利润
            $count = $this->account_info_model
                ->where('operate_time','BETWEEN',[$t[0],$t[1]])
                ->where('sale_user_id','=',$user['id'])->where('type','=','3')->count();//销售单
            $th_count = $this->account_info_model
                ->where('operate_time','BETWEEN',[$t[0],$t[1]])
                ->where('sale_user_id','=',$user['id'])
                ->where('type','=','6')->count();//退货单销售单


            $sale_count_list[] = $count-$th_count;

            $cate = $this->category_model->where('name','=','销售费用')->find();
            if (!empty($cate)){

                //计算利润
                $profit_price = $this->account_info_model
                    ->where('operate_time','BETWEEN',[$t[0],$t[1]])
                    ->where('sale_user_id','=',$user['id'])->whereRaw('(type=3 or type=6 or type=9  or type=8 or category_id='.$cate['id'].')')->sum('profit_price');//销售单
            }else{
                //计算利润
                $profit_price = $this->account_info_model
                    ->where('operate_time','BETWEEN',[$t[0],$t[1]])
                    ->where('sale_user_id','=',$user['id'])->whereRaw('(type=3 or type=6 or type=9  or type=8)')->sum('profit_price');//销售单
            }


            $profit_price_list[] = $profit_price;

        }

        $data = [
            'code'=>1,
            'data'=>['profit_price_list'=>$profit_price_list,
                'sale_count_list'=>$sale_count_list,
                'sale_user_list'=>$sale_user_list,]
        ];
        return json($data);
    }

    /**
     * @NodeAnotation(title="仓库利润数据")
     */
    public function get_store_profit($time){

        $t = explode(' - ',$time);

        //获取所有仓库信息
        $all_warehouse_data = $this->warehouse_model->select()->toArray();
        $profit_price_list = [];
        $warehouse_count_list = [];
        $warehouse_name_list = [];
        $t[1] = explode(' ',$t[1])[0].' 23:59:59';
        foreach ($all_warehouse_data as $warehouse){
            $warehouse_name_list[] = $warehouse['name'];


            //获取销售员的销售条数及利润
            $count = $this->account_info_model
                ->where('operate_time','BETWEEN',[$t[0],$t[1]])
                ->where('warehouse_id','=',$warehouse['id'])
                ->where('type','=','3')->count();//销售单

            $th_count = $this->account_info_model
                ->where('operate_time','BETWEEN',[$t[0],$t[1]])
                ->where('warehouse_id','=',$warehouse['id'])
                ->where('type','=','6')->count();//退货单销售单


            $warehouse_count_list[] = $count-$th_count;

            $cate = $this->category_model->where('name','=','销售费用')->find();
            if (!empty($cate)){

                //计算利润
                $profit_price = $this->account_info_model
                    ->where('operate_time','BETWEEN',[$t[0],$t[1]])
                    ->where('warehouse_id','=',$warehouse['id'])
                    ->whereRaw('(type=3 or type=6 or type=9  or type=8 or category_id='.$cate['id'].')')->sum('profit_price');//销售单
            }else{
                //计算利润
                $profit_price = $this->account_info_model
                    ->where('operate_time','BETWEEN',[$t[0],$t[1]])
                    ->where('warehouse_id','=',$warehouse['id'])
                    ->whereRaw('(type=3 or type=6 or type=9  or type=8)')->sum('profit_price');//销售单
            }


            $profit_price_list[] = $profit_price;

        }

        $data = [
            'code'=>1,
            'data'=>['profit_price_list'=>$profit_price_list,
                'sale_count_list'=>$warehouse_count_list,
                'name_list'=>$warehouse_name_list,]
        ];
        return json($data);
    }

    /**
     * @NodeAnotation(title="渠道利润数据")
     */
    public function get_supplier_profit($time){

        $t = explode(' - ',$time);

        //获取所有仓库信息
        $all_data = $this->supplier_model->select()->toArray();
        $profit_price_list = [];
        $count_list = [];
        $name_list = [];
        $t[1] = explode(' ',$t[1])[0].' 23:59:59';
        foreach ($all_data as $item){
            $name_list[] = $item['name'];


            //获取销售员的销售条数及利润
            $count = $this->account_info_model
                ->where('operate_time','BETWEEN',[$t[0],$t[1]])
                ->where('supplier_id','=',$item['id'])
                ->where('type','=','3')->count();//销售单

            $th_count = $this->account_info_model
                ->where('operate_time','BETWEEN',[$t[0],$t[1]])
                ->where('supplier_id','=',$item['id'])
                ->where('type','=','6')->count();//退货单销售单


            $count_list[] = $count-$th_count;

            $cate = $this->category_model->where('name','=','销售费用')->find();
            if (!empty($cate)){

                //计算利润
                $profit_price = $this->account_info_model
                    ->where('operate_time','BETWEEN',[$t[0],$t[1]])
                    ->where('supplier_id','=',$item['id'])
                    ->whereRaw('(type=3 or type=6 or type=9  or type=8 or category_id='.$cate['id'].')')->sum('profit_price');//销售单
            }else{
                //计算利润
                $profit_price = $this->account_info_model
                    ->where('operate_time','BETWEEN',[$t[0],$t[1]])
                    ->where('supplier_id','=',$item['id'])
                    ->whereRaw('(type=3 or type=6 or type=9  or type=8)')->sum('profit_price');//销售单
            }


            $profit_price_list[] = $profit_price;

        }

        $data = [
            'code'=>1,
            'data'=>['profit_price_list'=>$profit_price_list,
                'sale_count_list'=>$count_list,
                'name_list'=>$name_list]
        ];
        return json($data);
    }

    /**
     * @NodeAnotation(title="客户利润数据")
     */
    public function get_customer_profit($time){

        $t = explode(' - ',$time);

        //获取所有仓库信息
        $all_data = $this->customer_model->select()->toArray();
        $profit_price_list = [];
        $count_list = [];
        $name_list = [];
        $t[1] = explode(' ',$t[1])[0].' 23:59:59';
        foreach ($all_data as $item){


            //获取销售员的销售条数及利润
            $count = $this->account_info_model
                ->where('operate_time','BETWEEN',[$t[0],$t[1]])
                ->where('customer_id','=',$item['id'])
                ->where('type','=','3')->count();//销售单

            $th_count = $this->account_info_model
                ->where('operate_time','BETWEEN',[$t[0],$t[1]])
                ->where('customer_id','=',$item['id'])
                ->where('type','=','6')->count();//退货单销售单

            if ($count-$th_count == 0){
                continue;
            }
            $count_list[] = $count-$th_count;
            $name_list[] = $item['name'];

            $cate = $this->category_model->where('name','=','销售费用')->find();
            if (!empty($cate)){

                //计算利润
                $profit_price = $this->account_info_model
                    ->where('operate_time','BETWEEN',[$t[0],$t[1]])
                    ->where('customer_id','=',$item['id'])
                    ->whereRaw('(type=3 or type=6 or type=9  or type=8 or category_id='.$cate['id'].')')->sum('profit_price');//销售单
            }else{
                //计算利润
                $profit_price = $this->account_info_model
                    ->where('operate_time','BETWEEN',[$t[0],$t[1]])
                    ->where('customer_id','=',$item['id'])
                    ->whereRaw('(type=3 or type=6 or type=9  or type=8)')->sum('profit_price');//销售单
            }
            $profit_price_list[] = $profit_price;

        }

        $data = [
            'code'=>1,
            'data'=>['profit_price_list'=>$profit_price_list,
                'sale_count_list'=>$count_list,
                'name_list'=>$name_list]
        ];
        return json($data);
    }


}
