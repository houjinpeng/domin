<?php


namespace app\admin\controller\nod;

use app\admin\controller\JvMing;
use app\admin\model\NodAccount;
use app\admin\model\NodAccountInfo;
use app\admin\model\NodCustomerManagement;
use app\admin\model\NodInventory;
use app\admin\model\NodOrder;
use app\admin\model\NodOrderInfo;
use app\admin\model\NodSupplier;
use app\admin\model\NodWarehouse;
use app\admin\model\NodWarehouseInfo;
use app\admin\model\SystemAdmin;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * @ControllerAnnotation(title="报表 应收应付款")
 */
class ReceiveAndPay extends AdminController
{

    use \app\admin\traits\Curd;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new NodCustomerManagement();
        $this->supplier_model = new NodSupplier();
        $this->admin_model = new SystemAdmin();
        $this->account_info_model = new NodAccountInfo();


    }

    /**
     * @NodeAnotation(title="应收应付款")
     */
    public function index()
    {
        if ($this->request->isAjax()){
            list($page, $limit, $where) = $this->buildTableParames();



            $list1 = $this->model
                ->where($where)
                ->page($page, $limit)
                ->order($this->sort)
                ->select()->toArray();

            foreach ($list1 as &$item){
                $c = $this->account_info_model->where('customer_id','=',$item['id'])->find();
                if (!empty($c)){
                    $item['sale_user'] = $this->admin_model->field('username')->where('id','=',$c['sale_user_id'])->find();
                }

            }

            $list2 = $this->supplier_model
                ->where($where)
                ->page($page, $limit)
                ->order($this->sort)
                ->select()->toArray();


            $list = [];
            foreach ($list1 as $item){
                if ($item['receivable_price'] == 0) continue;
                $list[] = [
                    'sale_user'=>empty($item['sale_user'])?'':$item['sale_user']['username'],
                    'name'=>$item['name'],
                    'receivable_price'=>$item['receivable_price'],

                ];
            }
            foreach ($list2 as $item){
                if ($item['receivable_price'] == 0) continue;
                $list[] = [
                    'name'=>'渠道 '.$item['name'],
                    'receivable_price'=>$item['receivable_price'],

                ];
            }


            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => count($list),
                'data'  => $list,
            ];

            return json($data);
        }


        return $this->fetch();
    }


}
