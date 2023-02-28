<?php



namespace app\admin\controller\yikoujia;


use app\admin\controller\Tool;
use app\admin\model\SystemAdmin;
use app\admin\model\SystemConfig;
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
 * @ControllerAnnotation(title="一口价黑名单")
 */
class Hmd extends AdminController
{

    use \app\admin\traits\Curd;


    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new SystemConfig();


    }

    /**
     * @NodeAnotation(title="列表")
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->post();

            $hmd_data = $this->model->where('name','=','hmd')->find();
            if (empty($hmd_data)){
                $this->model->insert(['name'=>'hmd','value'=>$post['hmd'],'remark'=>'一口价店铺黑名单']);
            }else{
                $hmd_data->save(['value'=>$post['hmd']]);
            }
            $this->success('保存成功');
        }
        $row = $this->model->where('name','=','hmd')->find();
        empty($row) ? $this->assign('row',['value'=>'']):$this->assign('row',$row);

        return $this->fetch();
    }

}
