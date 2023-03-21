<?php



namespace app\admin\controller\nod\config;

use app\admin\model\NodAccount;
use app\admin\model\NodSaleUser;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * @ControllerAnnotation(title="财务-销售员管理")
 */
class SaleUser extends AdminController
{

    use \app\admin\traits\Curd;


    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new NodSaleUser();


    }

    /**
     * @NodeAnotation(title="销售员列表")
     */
    public function index()
    {

        if ($this->request->isAjax()){
            list($page, $limit, $where) = $this->buildTableParames();
            $count = $this->model
                ->where($where)
                ->count();
            $list = $this->model
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

    /**
     * @NodeAnotation(title="创建销售员")
     */
    public function add()
    {
        if ($this->request->isAjax()){
            $post = $this->request->post();
            $save = $this->model->save($post);
            $save?$this->success('创建成功'):$this->error('创建失败');

        }
        return $this->fetch();
    }

}
