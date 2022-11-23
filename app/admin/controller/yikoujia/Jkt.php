<?php



namespace app\admin\controller\yikoujia;


use app\admin\controller\Tool;
use app\admin\model\SystemAdmin;
use app\admin\model\YikoujiaBuyFilter;
use app\admin\model\YikoujiaJkt;
use app\admin\model\YikoujiaSearchConfig;

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
 * @ControllerAnnotation(title="一口价监控台")
 */
class Jkt extends AdminController
{

    use \app\admin\traits\Curd;


    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new YikoujiaSearchConfig();
        $this->filter_model = new YikoujiaBuyFilter();
        $this->jkt_model = new YikoujiaJkt();

    }

    /**
     * @NodeAnotation(title="列表")
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($page, $limit, $where) = $this->buildTableParames();
            $list = $this->jkt_model
                ->with('getMainFilter')
                ->where($where)->page($page,$limit)->select()->toArray();
            foreach ($list as $index=>&$item){
                foreach (explode(',',$item['fu_filter_id']) as $idx=>&$fu_id){
                    $fu = $this->filter_model->where('id',$fu_id)->find();
                    $item['fu_title_'.$idx+1] = $fu['title'];
                    $item['fu_id_'.$idx+1] = $fu_id;
                    $item['fu_is_buy_'.$idx+1] = $fu['is_buy'];
                }

            }
            $count = $this->jkt_model->where($where)->count();
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
            $save = $this->jkt_model->save($post);
            $save ?$this->success('保存成功'):$this->error('保存失败');
        }
        $filters = $this->filter_model->field('id,title')->select()->toArray();
        $this->assign('filters',$filters);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="增加支线")
     */
    public function add_zhi($id){
        $row = $this->jkt_model->with('getMainFilter')->find($id);
        if ($this->request->isAjax()){
            $post = $this->request->post();
            $save = $this->jkt_model->where('id',$id)->update($post);
            $save? $this->success('添加成功'):$this->error('添加失败');

        }
        $this->assign('row',$row);
        //获取支线数据
        $z_list = explode(',',$row['fu_filter_id']);

        $zhi = $this->filter_model->where('id','<>',$row['main_filter_id'])->select()->toArray();
        $z = [];
        foreach ($zhi as $item){
            if (in_array($item['id'],$z_list)){
                $z[] = ['name'=>$item['title'],'value'=>$item['id'],'selected'=>true];
            }else{
                $z[] = ['name'=>$item['title'],'value'=>$item['id']];
            }

        }
        $this->assign('z',json_encode($z));
        return $this->fetch();
    }


    /**
     * @NodeAnotation(title="编辑")
     */
    public function edit($id)
    {
        $row = $this->model->find($id);
        empty($row) && $this->error('数据不存在');

        if ($this->request->isPost()) {
//            $post = $this->request->post();
//            $save = $this->model_store->where('store_id','=',$row['store_id'])->update($post);
//            $save?$this->success('修改成功'):$this->error('修改失败,没有匹配到数据');
        }
        $this->assign('row', $row);
        return $this->fetch();
    }

    

}
