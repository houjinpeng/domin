<?php



namespace app\admin\controller\domain;


use app\admin\controller\Tool;
use app\admin\model\DomainConfig;
use app\admin\model\DomainJk;
use app\admin\model\DomainSalse;
use app\admin\model\DomainStore;
use app\admin\model\SystemAdmin;
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
 * @ControllerAnnotation(title="配置管理")
 */
class Config extends AdminController
{

    use \app\admin\traits\Curd;


    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new DomainConfig();

    }

    /**
     * @NodeAnotation(title="列表")
     */
    public function index()
    {
        $row = $this->model->find(1);
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $this->model->where('id','=',1)->update($post);
            $this->success('保存成功');


        }
        $this->assign('row',$row);
        return $this->fetch();
    }



}
