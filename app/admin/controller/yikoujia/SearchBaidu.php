<?php



namespace app\admin\controller\yikoujia;


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
 * @ControllerAnnotation(title="百度查询")
 */
class SearchBaidu extends AdminController
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
     * @NodeAnotation(title="列表")
     */
    public function search_baidu(){

        $ym = $this->request->get('ym');





//        dd($out,$cmd);

        $list = $this->model->where('ym','=',$ym)->where('type','=','baidu')->select()->toArray();
        if (empty($list)){
            $cmd = 'python3 ./python_script/search/search_baidu.py '.$ym.'  2>&1';
            $out = exec($cmd);
        }

        $list = $this->model->where('ym','=',$ym)->where('type','=','baidu')->select()->toArray();
        foreach ($list as &$item){
            $item['data'] = json_decode($item['data'],true);
        }
        if (empty($list)){
            $data = [
                'code'=>1,
                'msg'=>'未查询到',
                'data'=>$list
            ];
            return json($data);
        }


        $data = [
            'code'=>0,
            'msg'=>'',
            'data'=>$list
        ];
        return json($data);


    }

}
