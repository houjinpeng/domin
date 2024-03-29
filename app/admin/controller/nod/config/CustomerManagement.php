<?php



namespace app\admin\controller\nod\config;

use app\admin\model\NodCustomerManagement;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * @ControllerAnnotation(title="财务-客户管理")
 */
class CustomerManagement extends AdminController
{

    use \app\admin\traits\Curd;


    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new NodCustomerManagement();

    }

    /**
     * @NodeAnotation(title="客户列表")
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
     * @NodeAnotation(title="添加客户")
     */
    public function add(){
        if ($this->request->isAjax()){
            $post = $this->request->post();
            $post['user_id'] = session('admin.id');
            $save= $this->model->save($post);

            $save? $this->success('保存成功'):$this->error('保存失败');


        }
        return $this->fetch();
    }
}
