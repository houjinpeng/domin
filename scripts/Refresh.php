<?php
namespace app\admin\controller\website;
use think\App;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;

/**
 * @ControllerAnnotation(title="刷新功能")
 */
class Refresh extends AdminController
{
    use \app\admin\traits\Curd;
    public function __construct()
    {
        $this->model = new \app\admin\model\WebsiteSite();
        $this->model_task = new \app\admin\model\WebsiteTask();
        $this->model_cate = new \app\admin\model\WebsiteCate();

    }
    /**
     * @NodeAnotation(title="刷新")
     */
    public function index()
    {
        $list = $this->model
            ->select()->toArray();

        foreach ($list as $k=>$v){
            $where =['id','=',$v['id']] ;
            $web_url = $v['web_url'];
            $web_url = htmlspecialchars_decode($web_url);
            $web_url = '"'.str_replace("\n",',',$web_url).'"';

//          获取到task_id  更新到mysql中
            $wherem = [];
            $wherem[] = ['param', '=', $web_url];
            $mongo = \think\facade\Db::connect('mongo')
                ->table('tasks')->where($wherem)
                ->order('create_ts', 'desc')
                ->find();
            if (empty($mongo)){
//                echo '无结果';
                continue;
            }
            $cate = $this->model_cate ->where('id','=',$v['cate_id'])->find();
            if (strstr($cate['remark'],'亚马逊')){
                $table_name = '亚马逊';
            }else{
                $table_name = 'etsy';
            }
            //查task id是否存在
            $count = \think\facade\Db::connect('mongo')
                ->table($table_name)->where('task_id','=',$mongo['_id'])
                ->count();
            if($count == 0 ){
                continue;
            }

            $task_id = $mongo['_id'];
            $status = $mongo['status'];

            if ($status == 'running') {
                $data['status'] = 0;
            } elseif ($status == 'finished') {
                $data['status'] = 1;
            } elseif ($status == 'cancelled') {
                $data['status'] = 3;
            } elseif ($status == 'error') {
                $data['status'] = 3;
            }
            #更改site数据库的状态
            $data['task_id'] = $task_id;
            $row_site = $this->model->find($v['id']);
            $row_site->save($data);
            $d['site_id'] = $v['id'];
            $d['task_id'] = $task_id;
//          根据site_id查找  存入之前要判断创建时间是否大于1天或者18个小时  大于创建时间就添加 小于就覆盖
            $l = $this->model_task
                ->where('site_id','=',$v['id'])
                ->order('create_time','desc')
                ->limit(1)
                ->select()->toArray();

            if ($l==[]){
                $row = $this->model_task->insert($d);
            }
            foreach ($l as $kk=>$vv){
                //如果当前时间大于创建时间19个小时就添加 否则更新
                if (strtotime(date("Y-m-d H:i:s"))-strtotime($vv['create_time'])> 68400 ){
                    $row = $this->model_task->insert($d);
                }elseif ($d['task_id']==$vv['task_id']){ //如果task_id一样的话就跳过
                    continue;
                }else{
//                    echo $vv['id'].'<br>';
                    $row = $this->model_task;
                    $row->where('id',$vv['id']) ->save($d);
                }
            }
        }
        return 'ok';
    }
}




