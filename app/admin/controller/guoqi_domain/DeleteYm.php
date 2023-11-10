<?php

// +----------------------------------------------------------------------
// | EasyAdmin
// +----------------------------------------------------------------------
// | PHP交流群: 763822524
// +----------------------------------------------------------------------
// | 开源协议  https://mit-license.org
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/zhongshaofa/EasyAdmin
// +----------------------------------------------------------------------

namespace app\admin\controller\guoqi_domain;


use app\admin\controller\api\JvMing;
use app\admin\model\SystemAdmin;
use app\admin\model\NodWarehouse;
use app\admin\model\YmAllDoamin;
use app\admin\service\TriggerService;
use app\common\constants\AdminConstant;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use EasyAdmin\tool\CommonTool;
use GuzzleHttp\Client;
use jianyan\excel\Excel;
use think\App;
use think\facade\Db;

/**
 * Class Admin
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="删除域名")
 */
class DeleteYm extends AdminController
{

    use \app\admin\traits\Curd;

    protected $sort = [
        'delete_time'   => 'desc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new YmAllDoamin();
        $this->client = new Client();

    }

    /**
     * @NodeAnotation(title="列表")
     */
    public function index()

    {
        if ($this->request->isAjax()) {
            list($page, $limit, $where,$excludeFields) = $this->buildTableParames(['month']);
            $month = (isset($excludeFields['month']) && !empty($excludeFields['month']))
                ? date('Ym', strtotime($excludeFields['month']))
                : date('Ym');
            //整理搜索条件
            $where = build_select_where($where);
            $count = $this->model
                ->setMonth($month)
                ->where($where)
                ->count();
            $list = $this->model
                ->setMonth($month)
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
     * @NodeAnotation(title="下载过期域名")
     */
    public function download_delete_ym(){

        $scsj = $this->request->get('scsj');
        $account = NodWarehouse::where('status','=',1)->find();
        $api = new JvMing($account['account'],$account['password'],$account['cookie']);
        $resp = $api->download_delete_ym($scsj);
        if ($resp != true){
            $this->error($resp);
        }
        //打开文件 读取文件
        $file = fopen( 'delete_ym_'.$scsj.'.txt' ,'r'); // 打开文件句柄，'r' 表示以只读方式打开
        if ($file) {
            $content = fread($file, filesize('delete_ym_'.$scsj.'.txt')); // 读取文件内容
            fclose($file); // 关闭文件句柄

            $all_ym = explode("\r\n",$content);
            $all_ym_mysql = $this->model->field('ym')->where('delete_time','=',$scsj)->select();
            $ym_list = [];
            foreach ($all_ym_mysql as $item){
                $ym_list[] = $item['ym'];
            }

            $difference = array_diff($all_ym, $ym_list);

            $insert_list = [];
            foreach ($difference as $ym){
                if ($ym=='')continue;
                $hz =  array_slice(explode('.',$ym), 1,100);
                $insert_list[] = ['ym'=>$ym,'delete_time'=>$scsj,'cd'=>strlen($ym),'hz'=>'.'.join('.',$hz)];
            }
            $this->model->insertAll($insert_list);
            $this->success('导入完成');
        } else {
            $this->error('无法打开文件');
        }



    }

    /**
     * @NodeAnotation(title="导出")
     */
    public function export()
    {
        ini_set('max_execution_time',7200);
        ini_set('memory_limit',-1);
        list($page, $limit, $where,$excludeFields) = $this->buildTableParames(['month']);
        $month = (isset($excludeFields['month']) && !empty($excludeFields['month']))
            ? date('Ym', strtotime($excludeFields['month']))
            : date('Ym');
        $where = build_select_where($where);
        $where = delete_where_filter($where,'month');
        $header= [
            ['域名','ym'],
            ['长度','cd'],
            ['后缀','hz'],
            ['百度收录','baidu_num'],
            ['搜狗收录','sogou_num'],
            ['360收录','so_num'],
            ['备案性质','beian'],
            ['注册商','zcs'],
            ['历史年龄','history_data'],
            ['pr','pr'],
            ['权重','qz'],
            ['外链数','wl'],
            ['反链数','fl'],
            ['V认证','v_rz'],
            ['删除时间','delete_time'],
        ];
        $list = $this->model
            ->setMonth($month)
            ->where($where)
            ->limit(100000)
            ->select()
            ->toArray();
        $fileName = '删除域名'.time();
        return Excel::exportData($list, $header, $fileName, 'xlsx');

    }

    /**
     * @NodeAnotation(title="导出txt")
     */
    public function export_txt()
    {
        ini_set('max_execution_time',7200);
        ini_set('memory_limit',-1);
        list($page, $limit, $where,$excludeFields) = $this->buildTableParames(['month']);
        $month = (isset($excludeFields['month']) && !empty($excludeFields['month']))
            ? date('Ym', strtotime($excludeFields['month']))
            : date('Ym');
        $where = build_select_where($where);
        $where = delete_where_filter($where,'month');

        $list = $this->model
            ->setMonth($month)
            ->where($where)
            ->select()
            ->toArray();
        $data = [];
        foreach ($list as $ym){
            $data[] = $ym['ym'];
        }
        $data = join("\n",$data);
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="删除域名.txt"');
        header('Content-Length: ' . strlen($data));

        echo $data;



    }

}
