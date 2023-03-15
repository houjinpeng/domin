<?php



namespace app\admin\controller\nod\config;

use app\admin\model\NodAccount;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * @ControllerAnnotation(title="财务-账户管理 资金明细")
 */
class Account extends AdminController
{

    use \app\admin\traits\Curd;


    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new NodAccount();


    }

    /**
     * @NodeAnotation(title="账户列表")
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

    /**
     * @NodeAnotation(title="创建账户")
     */
    public function add()
    {
        if ($this->request->isAjax()){
            $post = $this->request->post();
            $post['balance_price'] = $post['init_price'];
            $save = $this->model->save($post);
            $save?$this->success('创建成功'):$this->error('创建失败');

        }
        return $this->fetch();
    }

}
