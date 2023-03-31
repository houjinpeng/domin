<?php


namespace app\admin\controller\nod;

use app\admin\controller\JvMing;
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
 * @ControllerAnnotation(title="报表 现金银行报表")
 */
class BrandDetail extends AdminController
{

    use \app\admin\traits\Curd;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new NodAccount();
        $this->account_info_model = new NodAccountInfo();


    }

    /**
     * @NodeAnotation(title="现金银行报表列表")
     */
    public function index()
    {
        if ($this->request->isAjax()){

            $all_account = $this->model->select()->toArray();
            $list = [];
            foreach ($all_account as $item){
                $data = [];
                $data['name'] = $item['name'];
                //计算收入  收款或销售  采购退货
                $data['sr'] = $this->account_info_model->where(function ($query){
                    $query->whereOr([['type','=',3],['type','=',4],['type','=',2],['type','=',9]]);
                })->where('account_id','=',$item['id'])->sum('price');

                //计算支出  付款 采购 或退货
                $data['zc'] = $this->account_info_model->where(function ($query){
                    $query->whereOr([['type','=',5],['type','=',1],['type','=',6],['type','=',8]]);
                })->where('account_id','=',$item['id'])->sum('price');
                $data['balance_price'] = $item['balance_price'];
                $list[] = $data;
            }

            $data = [
                'code'=>0,
                'data'=>$list,
                'count'=>count($all_account),
                'msg'=>''
            ];


            return json($data);

        }


        return $this->fetch();
    }


}
