<?php



namespace app\admin\controller\seo_search;


use app\admin\controller\Tool;
use app\admin\model\SearchResult;
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
 * @ControllerAnnotation(title="爱站查询")
 */
class SearchAizhan extends AdminController
{

    use \app\admin\traits\Curd;


    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new SearchResult();


    }

    /**
     * @NodeAnotation(title="页面")
     */
    public function index()
    {
        return $this->fetch();
    }


    /**
     * @NodeAnotation(title="查询爱站")
     */
    public function search(){

        $ym = $this->request->get('ym');


        $cmd = 'python3 ./python_script/search/search_aizhan.py '.$ym.'  2>&1';
        $out = exec($cmd);
        $result = json_decode($out,true);
        $data = [
            'code'=>0,
            'msg'=>'',
            'data'=>[$result]
        ];
        return json($data);


    }

}
