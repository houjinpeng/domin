<?php


namespace app\admin\controller\domain_reserve;


use app\admin\model\DomainReserveBatch;
use app\admin\model\DomainReserveDomain;

use app\admin\model\NodWarehouse;

use app\common\controller\AdminController;
use think\App;


/**
 * @ControllerAnnotation(title="阿里云域名预定")
 */
class AliyunYd extends AdminController
{

    use \app\admin\traits\Curd;


    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new DomainReserveBatch();
        $this->model_domain = new DomainReserveDomain();

    }

    /**
     * @NodeAnotation(title="列表")
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($page, $limit, $where) = $this->buildTableParames();
            $count = $this->model
                ->where('type', '=', 2)
                ->where($where)
                ->count();
            $list = $this->model
                ->with(['admin'=>function($qurey){
                    $qurey->field('id,username');
                }])
                ->where($where)
                ->where('type', '=', 2)
                ->page($page, $limit)
                ->order('id','desc')
                ->select()->toArray();

            $data = [
                'code' => 0,
                'msg' => '',
                'count' => $count,
                'data' => $list,
            ];
            return json($data);
        }



        //获取仓库数据
        $warehouse = NodWarehouse::select()->toArray();
        $this->assign('warehouse',$warehouse);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="添加")
     */
    public function add()
    {
        if ($this->request->isAjax()) {

            $post = $this->request->post();
            if ( htmlspecialchars_decode($post['start_script_time']) == '') $this->error('执行时间不能为空！');
            $remark = htmlspecialchars_decode($post['remark']);
            $ym_list = explode("\n", htmlspecialchars_decode($post['ym']));
            $batch_data = [
                'title' => $post['title'],
                'ym' => htmlspecialchars_decode($post['ym']),
                'fs' => 4,
                'start_script_time' => htmlspecialchars_decode($post['start_script_time']),
                'remark' => $remark,
                'user_id' => session('admin.id'),
                'type'=>2
            ];
            $batch_id = $this->model->insertGetId($batch_data);

            $save_list = [];
            foreach ($ym_list as $ym) {
                $ym = trim($ym);
                if ($ym == '') {
                    continue;
                }
                $save_list[] = [
                    'ym'=>$ym,
                    'remark'=>$remark,
                    'fs'=>4,
                    'batch_id'=>$batch_id,
                ];

            }
            $this->model_domain->insertAll($save_list);
            $this->success('添加成功');

        }

        return $this->fetch();
    }


    /**
     * @NodeAnotation(title="编辑")
     */
    public function edit($id)
    {
        $row = $this->model->find($id);
        $this->assign('row',$row);
        if ($this->request->isAjax()) {

            $post = $this->request->post();
            if ( htmlspecialchars_decode($post['start_script_time']) == '') $this->error('执行时间不能为空！');
            $remark = htmlspecialchars_decode($post['remark']);
            $ym_list = explode("\n", htmlspecialchars_decode($post['ym']));
            $batch_data = [
                'title' => $post['title'],
                'ym' => htmlspecialchars_decode($post['ym']),
                'fs' => 4,
                'start_script_time' => htmlspecialchars_decode($post['start_script_time']),
                'remark' => $remark,
                'user_id' => session('admin.id'),
                'type'=>2
            ];
            $row->save($batch_data);

            $ym_ll = [];
            foreach ($ym_list as $ym) {
                $ym = trim($ym);
                if ($ym == '') {
                    continue;
                }
                $ym_ll[] = $ym;
                //判断是否存在，存在更新，不存在插入
                $ym_row = $this->model_domain->where('ym','=',$ym)
                    ->where('batch_id','=',$id)->find();
                if (empty($ym_row)){

                    $this->model_domain->insert(['ym'=>$ym,'remark'=>$remark,'fs'=>4,'batch_id'=>$id]);
                }else{
                    //判断 如果是提交成功的 不允许修改
                    if ($ym_row['status'] == 2){
                        continue;
                    }
                    $ym_row->save(['remark'=>$remark,'fs'=>4,'status'=>0]);
                }


            }
            //删除不在域名列表的此批次数据  但是成功的不允许删除
            $this->model_domain->where('ym','not in',$ym_ll)->where('batch_id','=',$id)->where('status','<>',2)->delete();
            $this->success('编辑成功');

        }
        //获取仓库数据
        $warehouse = NodWarehouse::select()->toArray();
        $this->assign('warehouse',$warehouse);
        return $this->fetch();
    }


    /**
     * @NodeAnotation(title="删除")
     */
    public function delete($id)
    {
        $row = $this->model->whereIn('id', $id)->select();
        try {
            $this->model_domain->where('batch_id','in',$id)->delete();
            $save = $row->delete();
        } catch (\Exception $e) {
            $this->error('删除失败');
        }
        $save ? $this->success('删除成功') : $this->error('删除失败');
    }

    /**
     * @NodeAnotation(title="查看详情")
     */
    public function info()
    {

        if ($this->request->isAjax()) {
            list($page, $limit, $where) = $this->buildTableParames();
            $id = $this->request->get('batch_id');

            $where[] = ['batch_id','=',$id];
            $count = $this->model_domain
                ->where($where)
                ->count();
            $list = $this->model_domain
                ->where($where)
                ->page($page, $limit)
                ->order('id','desc')
                ->select()->toArray();

            $data = [
                'code' => 0,
                'msg' => '',
                'count' => $count,
                'data' => $list,
            ];
            return json($data);
        }


        $id = $this->request->get('id');

        $this->assign('batch_id',$id);
        $domain_list = $this->model_domain->where('batch_id','=',$id)->select()->toArray();
        $this->assign('domain_list',json_encode($domain_list));
        return $this->fetch();
    }




}
