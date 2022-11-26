<?php



namespace app\admin\controller\yikoujia;


use app\admin\controller\Tool;
use app\admin\model\SystemAdmin;
use app\admin\model\YikoujiaAccountPool;
use app\admin\model\YikoujiaBuyFilter;
use app\admin\model\YikoujiaJkt;

use app\admin\model\YikoujiaSpiderStatus;
use app\admin\service\TriggerService;
use app\common\constants\AdminConstant;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use EasyAdmin\tool\CommonTool;
use jianyan\excel\Excel;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use think\App;
use think\facade\Db;

/**
 * @ControllerAnnotation(title="账号池")
 */
class AccountPool extends AdminController
{

    use \app\admin\traits\Curd;


    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new YikoujiaAccountPool();


    }

    /**
     * @NodeAnotation(title="列表")
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($page, $limit, $where) = $this->buildTableParames();
            $list = $this->model->where($where)->page($page,$limit)->select()->toArray();
            $count = $this->model->where($where)->count();
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
     * @NodeAnotation(title="添加")
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            if ($post['username']==''||$post['password'] == ''){
                $this->error('聚名账号密码不能为空');
            }


            $save = $this->model->save($post);
            $save ?$this->success('保存成功'):$this->error('保存失败');

        }

        return $this->fetch();
    }






}
