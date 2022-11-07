<?php

namespace app\admin\controller\system;


use app\admin\model\SystemLog;
use app\admin\model\SystemAdmin;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * @ControllerAnnotation(title="操作日志管理")
 * Class Auth
 * @package app\admin\controller\system
 */
class Log extends AdminController
{

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new SystemLog();
        $this->model_admin = new SystemAdmin();
    }

    /**
     * @NodeAnotation(title="列表")
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
            [$page, $limit, $where, $excludeFields] = $this->buildTableParames(['month']);

            $month = (isset($excludeFields['month']) && !empty($excludeFields['month']))
                ? date('Ym', strtotime($excludeFields['month']))
                : date('Ym');

            // todo TP6框架有一个BUG，非模型名与表名不对应时（name属性自定义），withJoin生成的sql有问题
            foreach ($where as $k => $v) {
                if (!empty($v[0]) && $v[0] == 'admin.username') {
                    $admin_id = $this->model_admin->field('id')->where('username','like',$v[2])->select()->toArray();
                    $admin_id_array = [];
                    foreach ($admin_id as $i =>$items){
                        array_push($admin_id_array,$items['id']);
                    }
                    $where[$k][0] = "ea_" . 'system_log_' . $month . ".admin_id";
                    $where[$k][1] = "in";
                    $where[$k][2] = $admin_id_array;
                }
            }
            try {
                $count = $this->model
                    ->setMonth($month)
                    ->withJoin('admin','LEFT')
                    ->where($where)
                    ->count();
                $list = $this->model
                    ->setMonth($month)
                    ->with('admin')
                    ->where($where)
                    ->page($page, $limit)
                    ->order($this->sort)
                    ->select();
                $data = [
                    'code' => 0,
                    'msg' => '',
                    'count' => $count,
                    'data' => $list,
                ];
            }catch (\Exception $e){
                $data = [
                    'code' => 0,
                    'msg' => '',
                    'count' => 0,
                    'data' => [],
                ];
            }
            return json($data);
        }
        return $this->fetch();
    }

}