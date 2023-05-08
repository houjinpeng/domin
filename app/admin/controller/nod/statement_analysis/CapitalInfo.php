<?php



namespace app\admin\controller\nod\statement_analysis;


use app\admin\controller\Tool;
use app\admin\model\NodAccountInfo;
use app\admin\model\NodInventory;
use app\admin\model\NodWarehouse;


use app\admin\model\NodWarehouseInfo;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use jianyan\excel\Excel;
use think\App;

/**
 * @ControllerAnnotation(title="报表 资金总明细")
 */
class CapitalInfo extends AdminController
{

    use \app\admin\traits\Curd;


    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new NodAccountInfo();
        $this->tool = new Tool();


    }

    /**
     * @NodeAnotation(title="资金总明细列表")
     */
    public function index()
    {

        if ($this->request->isAjax()){
            list($page, $limit, $where) = $this->buildTableParames();
            $where = format_where_datetime($where,'operate_time');

            $list = $this->model
                ->with(['getOrder','getAccount','getSupplier','getWarehouse','getOrderUser','getCustomer','getCategory'],'left')
                ->where($where)
                ->page($page,$limit)
                ->order('id','desc')->select()->toArray();

            $count = $this->model->where($where)->count();
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

    /**
     * @NodeAnotation(title="资金总明细导出")
     */
    public function export()
    {
        list($page, $limit, $where) = $this->buildTableParames();

        $where = format_where_datetime($where,'operate_time');


        $header = [
            ['操作时间', 'operate_time'],
            ['经手人', 'getOrderUser.username'],
            ['类型', 'category'],
            ['说明', 'sm'],
            ['变动', 'price'],
            ['账号', 'getAccount.name'],
            ['账号余额', 'balance_price'],
            ['总资金剩额', 'all_balance_price'],
            ['备注信息', 'remark'],
        ];

        $list = $this->model
            ->with(['getOrder','getAccount','getSupplier','getWarehouse','getOrderUser','getCustomer','getCategory'],'left')
            ->where($where)
            ->order('id','desc')->select()->toArray();
        foreach ($list as &$item){$item['sm'] = $this->tool->bulid_remark($item);}


        $fileName = '总资金明细'.time();
        return Excel::exportData($list, $header, $fileName, 'xlsx');
    }


}
