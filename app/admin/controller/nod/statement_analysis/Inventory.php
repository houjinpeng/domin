<?php



namespace app\admin\controller\nod\statement_analysis;


use app\admin\controller\Tool;
use app\admin\model\NodInventory;
use app\admin\model\NodWarehouse;


use app\admin\model\NodWarehouseInfo;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use EasyAdmin\tool\CommonTool;
use jianyan\excel\Excel;
use think\App;
use think\facade\Db;

/**
 * @ControllerAnnotation(title="报表 库存")
 */
class Inventory extends AdminController
{

    use \app\admin\traits\Curd;


    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new NodInventory();
        $this->tool = new Tool();

    }

    /**
     * @NodeAnotation(title="库存列表")
     */
    public function index()
    {

        if ($this->request->isAjax()){
            list($page, $limit, $where) = $this->buildTableParames();
            $w = $this->tool->build_select_where($where);

            $where = [];
            foreach ($w as $item){
                if ($item[0] == 'dqsj'){
                    //判断大于小于
                    if ($item[1] == '>=') {
                        $t = date("Y-m-d H:i:s", strtotime("+" . $item[2] . " Days"));
                        $where[] = ['expiration_time', '>=', $t];
                    } else {
                        $t = date("Y-m-d H:i:s", strtotime("+" . $item[2] . " Days"));
                        $where[] = ['expiration_time', '<=', $t];
                    }
                    continue;
                }elseif ($item[0] == 'register_time'){
                    $where[] = [$item[0],$item[1],date('Y-m-d H:i:s',$item[2])];
                    continue;
                }
                $where[] = $item;
            }
            $list = $this->model
                ->with(['getSupplier','getWarehouse'],'left')
                ->where($where)
                ->page($page,$limit)
                ->order('id','desc')->select()->toArray();
            $total_price = 0;
            foreach ($list as $item){
                $total_price += floatval($item['unit_price']);
            }


            $count = $this->model->where($where)->count();
            $data = [
                'code'=>0,
                'data'=>$list,
                'count'=>$count,
                'total_price'=>$total_price,
                'msg'=>''
            ];
            return json($data);

        }


        return $this->fetch();
    }


    /**
     * @NodeAnotation(title="导出域名")
     */
    public function export(){
        list($page, $limit, $where) = $this->buildTableParames();
//        $tableName = $this->model->getName();
//        $tableName = CommonTool::humpToLine(lcfirst($tableName));
//        $prefix = config('database.connections.mysql.prefix');
//        $dbList = Db::query("show full columns from {$prefix}{$tableName}");
//        $header = [];
//        foreach ($dbList as $vo) {
//            $comment = !empty($vo['Comment']) ? $vo['Comment'] : $vo['Field'];
//            if (!in_array($vo['Field'], $this->noExportFields)) {
//                $header[] = [$comment, $vo['Field']];
//            }
//        }
        $w = $this->tool->build_select_where($where);

        $where = [];
        foreach ($w as $item){
            if ($item[0] == 'dqsj'){
                //判断大于小于
                if ($item[1] == '>=') {
                    $t = date("Y-m-d H:i:s", strtotime("+" . $item[2] . " Days"));
                    $where[] = ['expiration_time', '>=', $t];
                } else {
                    $t = date("Y-m-d H:i:s", strtotime("+" . $item[2] . " Days"));
                    $where[] = ['expiration_time', '<=', $t];
                }
                continue;
            }elseif ($item[0] == 'register_time'){
                $where[] = [$item[0],$item[1],date('Y-m-d H:i:s',$item[2])];
                continue;
            }
            $where[] = $item;
        }
        $list = $this->model->where($where)->with(['getWarehouse','getSupplier'],'left')->select()->toArray();
        $now = date('Y-m-d');
        foreach ($list as &$item){
            if ($item['expiration_time']){
                $date=(strtotime($item['expiration_time'])-strtotime($now))/86400;
                $item['dqts'] = $date;
            }else{
                $item['dqts'] = '';
            }

        }

        $header = [
            ['商品名称','good_name'],
            ['成本价','unit_price'],
            ['注册时间','register_time'],
            ['过期时间','expiration_time'],
            ['到期天数','dqts'],
            ['渠道','supplier'],
            ['仓库','warehouse'],
            ['注册商','zcs'],
            ['备案','beian'],
            ['百度','baidu'],
            ['搜狗','sogou'],
            ['备注','remark'],
        ];
        $new_list = [];
        unset($item);
        foreach ($list as $item){
            $new_list[] = [
                'good_name'=>$item['good_name']? $item['good_name']:'',
                'unit_price'=>$item['unit_price']?$item['unit_price']:'',
                'register_time'=>$item['register_time']?$item['register_time']: '',
                'expiration_time'=>$item['expiration_time']?$item['expiration_time']: '',
                'warehouse'=>$item['getWarehouse']?$item['getWarehouse']['name']:'',
                'supplier'=>$item['getSupplier']?$item['getSupplier']['name']:'',
                'dqts'=>$item['dqts'],
                'zcs'=>$item['zcs'],
                'remark'=>$item['remark'],
                'beian'=>$item['beian']?$item['beian']: '',
                'baidu'=>$item['baidu']?$item['baidu']: '',
                'sogou'=>$item['sogou']?$item['sogou']: '',
            ];
        }
        $fileName = time();
        return Excel::exportData($new_list, $header, $fileName, 'xlsx');



    }


}
