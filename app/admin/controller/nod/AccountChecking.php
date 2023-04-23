<?php


namespace app\admin\controller\nod;

use app\admin\controller\JvMing;
use app\admin\model\NodAccount;
use app\admin\model\NodAccountInfo;
use app\admin\model\NodCategory;
use app\admin\model\NodChecking;
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
use think\Db;

/**
 * @ControllerAnnotation(title="一键对账")
 */
class AccountChecking extends AdminController
{

    use \app\admin\traits\Curd;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new NodAccount();
        $this->wareahouse_model = new NodWarehouse();
        $this->inventory_model = new NodInventory();
        $this->checking_model = new NodChecking();


    }

    /**
     * @NodeAnotation(title="一键对账 列表")
     */
    public function index()
    {
        if ($this->request->isAjax()) {

            $list = $this->checking_model->select();

            $data = [
                'code'=>0,
                'count'=>0,
                'data'=>$list
            ];
            return json($data);

        }

        return $this->fetch();
    }


    /**
     * @NodeAnotation(title="一键对账 抓取")
     */
    public function comp_account()
    {

        $all_warehouse_data = $this->wareahouse_model->select();
        $all_data = [];
        foreach ($all_warehouse_data as $warehouse_data) {
            $username = $warehouse_data['account'];
            $password = $warehouse_data['password'];
            $cookie = $warehouse_data['cookie'];
            $this->jm_api = new JvMing($username, $password, $cookie);

            //获取资金账户余额
            $jvming_account_info = $this->jm_api->get_account_info();

            $account = $this->model->where('name', '=', $warehouse_data['name'])->find();
            $zj = isset($jvming_account_info['qian']['zqian']) ? $jvming_account_info['qian']['zqian'] : 0;
            $djje = isset($jvming_account_info['qian']['kjsqian']) ? $jvming_account_info['qian']['kjsqian'] : 0;

            $jvming_total_price = $zj + $djje;

            //聚名库存
            $all_inventory = $this->jm_api->get_inventory();
            $my_total_inventory = [];
            $my_total_inventory_a = $this->inventory_model->where('warehouse_id', '=', $warehouse_data['id'])->select()->toArray();
            foreach ($my_total_inventory_a as $item) {
                $my_total_inventory[] = $item['good_name'];
            }

            //判断资金账户  总金额+待结算金额
            $data = [
                'name' => $warehouse_data['name'],
                'my_total_price' => $account['balance_price'],                          //我的金额
                'jvming_total_price' => $jvming_total_price,                            //聚名金额
                'cha_price' => $account['balance_price'] - $jvming_total_price,           //相差
                'my_total_inventory' => join(',', $my_total_inventory),         //我的库存
                'jvming_total_inventory' => join(',', $all_inventory),         //聚名库存
                'cha_inventory' => join(',', array_diff($all_inventory, $my_total_inventory)),      //相差库存
            ];

            //保存数据 判断是否存在 不存在保存  存在更新
            $row = $this->checking_model->where('name', '=', $data['name'])->find();
            if (empty($row)) {
                $this->checking_model->insert($data);
            } else {
                $row->save($data);
            }


            $all_data[] = $data;

        }
        $result = [
            'code' => 1,
            'msg' => '更新成功',
            'data' => $all_data
        ];

        return json($result);

    }


}
