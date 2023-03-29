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

            $list2 = $this->supplier_model
                ->where($where)
                ->page($page, $limit)
                ->order($this->sort)
                ->select()->toArray();


            $list = [];
            foreach ($list1 as $item){
                if ($item['receivable_price'] == 0) continue;
                $list[] = [
                    'name'=>'客户 '.$item['name'],
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
