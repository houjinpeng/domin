<?php



namespace app\admin\controller\nod\config;


use app\admin\model\NodSupplier;
use app\admin\model\NodWarehouse;


use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * @ControllerAnnotation(title="财务-来源渠道管理")
 */
class Supplier extends AdminController
{

    use \app\admin\traits\Curd;


    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new NodSupplier();


    }

    /**
     * @NodeAnotation(title="仓库列表")
     */
    public function index()
    {

        if ($this->request->isAjax()){
            list($page, $limit, $where) = $this->buildTableParames();
            $count = $this->model
                ->where($where)
                ->count();
            $list = $this->model
                ->withoutField('password')
                ->where($where)
                ->page($page, $limit)
                ->order($this->sort)
                ->select();
            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => $count,
                'data'  => $list,
            ];

            return json($data);
        }


        return $this->fetch();
    }

//    /**
//     * @NodeAnotation(title="添加仓库")
//     */
//    public function add()
//    {
//        if ($this->request->isAjax()){
//
//        }
//        return $this->fetch();
//
//    }


}
