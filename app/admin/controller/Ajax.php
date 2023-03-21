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

namespace app\admin\controller;

use app\admin\model\CommentCenter;
use app\admin\model\CommentPro;
use app\admin\model\SystemGroup;
use app\admin\model\SystemUploadfile;
use app\admin\model\WebsiteShopCategory;
use app\common\controller\AdminController;
use app\common\service\MenuService;
use EasyAdmin\upload\Uploadfile;
use think\db\Query;
use think\facade\Cache;
use think\facade\Db;

class Ajax extends AdminController
{

    /**
     * 初始化后台接口地址
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function initAdmin()
    {
//        $cacheData = Cache::get('initAdmin_' . session('admin.id'));
//        if (!empty($cacheData)) {
//            return json($cacheData);
//        }
        $menuService = new MenuService(session('admin.id'));
        $data = [
            'logoInfo' => [
                'title' => sysconfig('site', 'logo_title'),
                'image' => sysconfig('site', 'logo_image'),
                'href'  => __url('index/index'),
            ],
            'homeInfo' => $menuService->getHomeInfo(),
            'menuInfo' => $menuService->getMenuTree(),
        ];
        Cache::tag('initAdmin')->set('initAdmin_' . session('admin.id'), $data);
        return json($data);
    }

    /**
     * 清理缓存接口
     */
    public function clearCache()
    {
        Cache::clear();
        $this->success('清理缓存成功');
    }

    /**
     * 上传文件
     */
    public function upload()
    {
        $data = [
            'upload_type' => $this->request->post('upload_type'),
            'file'        => $this->request->file('file'),
        ];
        $uploadConfig = sysconfig('upload');
        empty($data['upload_type']) && $data['upload_type'] = $uploadConfig['upload_type'];
        $rule = [
            'upload_type|指定上传类型有误' => "in:{$uploadConfig['upload_allow_type']}",
            'file|文件'              => "require|file|fileExt:{$uploadConfig['upload_allow_ext']}|fileSize:{$uploadConfig['upload_allow_size']}",
        ];
        $this->validate($data, $rule);
        try {
            $upload = Uploadfile::instance()
                ->setUploadType($data['upload_type'])
                ->setUploadConfig($uploadConfig)
                ->setFile($data['file'])
                ->save();
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        if ($upload['save'] == true) {
            $this->success($upload['msg'], ['url' => $upload['url']]);
        } else {
            $this->error($upload['msg']);
        }
    }

    /**
     * 上传图片至编辑器
     * @return \think\response\Json
     */
    public function uploadEditor()
    {
        $data = [
            'upload_type' => $this->request->post('upload_type'),
            'file'        => $this->request->file('upload'),
        ];
        $uploadConfig = sysconfig('upload');
        empty($data['upload_type']) && $data['upload_type'] = $uploadConfig['upload_type'];
        $rule = [
            'upload_type|指定上传类型有误' => "in:{$uploadConfig['upload_allow_type']}",
            'file|文件'              => "require|file|fileExt:{$uploadConfig['upload_allow_ext']}|fileSize:{$uploadConfig['upload_allow_size']}",
        ];
        $this->validate($data, $rule);
        try {
            $upload = Uploadfile::instance()
                ->setUploadType($data['upload_type'])
                ->setUploadConfig($uploadConfig)
                ->setFile($data['file'])
                ->save();
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        if ($upload['save'] == true) {
            return json([
                'error'    => [
                    'message' => '上传成功',
                    'number'  => 201,
                ],
                'fileName' => '',
                'uploaded' => 1,
                'url'      => $upload['url'],
            ]);
        } else {
            $this->error($upload['msg']);
        }
    }

    /**
     * 获取上传文件列表
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUploadFiles()
    {
        $get = $this->request->get();
        $page = isset($get['page']) && !empty($get['page']) ? $get['page'] : 1;
        $limit = isset($get['limit']) && !empty($get['limit']) ? $get['limit'] : 10;
        $title = isset($get['title']) && !empty($get['title']) ? $get['title'] : null;
        $this->model = new SystemUploadfile();
        $count = $this->model
            ->where(function (Query $query) use ($title) {
                !empty($title) && $query->where('original_name', 'like', "%{$title}%");
            })
            ->count();
        $list = $this->model
            ->where(function (Query $query) use ($title) {
                !empty($title) && $query->where('original_name', 'like', "%{$title}%");
            })
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

    
    /**
     * 获得筛选下拉数据-shine
     * @return \think\response\Json
     */
    public function source()
    {
        $json = cache('search_source');
        if ($json==null){
            $post = $this->request->post();
            $className = '\app\admin\model\\'.$post['table'];
            $list = $className::column($post['field'], $post['field']);
            $json = json($list);
            // cache('search_source', $json);
        }
        return $json;
    }

    /**
     * 获得关联查询数据
     * @return \think\response\Json
     */
    public function cateSiteList()
    {
        $post = $this->request->post();
        $model_site = new \app\admin\model\WebsiteSite();
        if (session('admin')['id'] == 1){
            $where = [];
        }else{
            $where[] = ['user_id','=',session('admin')['id']];
        }

        $row = $model_site->where($where)->select()->toArray();
        $arr = [];
        foreach ($row as $index => $item) {
            if ($item['c_id'] == $post['name']){
                $arr[$item['title']] = [$item['title']];
            }
        }
        return json($arr);
    }


    /**
     * 获得海选user查询数据
     * @return \think\response\Json
     */
    public function userList()
    {
        $post = $this->request->post();
        $model = new \app\admin\model\NodSaleUser();

        $row = $model->field(['id','name'])->select()->toArray();
        $arr = [];
        foreach ($row as $index => $item) {
            $arr[] = ['id'=>$item['id'],'title'=>$item['name']];
        }
        $data = [
          'code'=>1,
          'data'=>$arr
        ];
        return json($data);
    }

    /**
     * 获得所有user查询数据
     * @return \think\response\Json
     */
    public function userListAll()
    {
        $post = $this->request->post();
        $model = new \app\admin\model\SystemAdmin();

        $row = $model->field(['id','username'])->select()->toArray();
        $arr = [];
        $arr['0'] = '所有人';
        foreach ($row as $index => $item) {
            $arr[$item['id']] = $item['username'];
        }
        return json($arr);
    }


    /**
     * 获得海选user查询数据
     * @return \think\response\Json
     */
    public function getShopCategorySelect($id,$type='shop',$is_add=0)
    {
        $arr = [];
        if ($type =='shop'){
            $list = WebsiteShopCategory::where('shop_id','=',$id)->where('is_add','=',$is_add)->select();
        }else{
            $list = WebsiteShopCategory::where('keyword_id','=',$id)->where('is_add','=',$is_add)->select();
        }

        foreach ($list as $index => $item) {
            $arr[$item['id']] = $item['name'];
        }
        return json($arr);
    }


    /**
     * 获取评论中心所有产品列表
     */
    public function getCommentSelectList(){
        $type = $this->request->post('type');
        $arr = [];
        if ($type == 'pro'){
            $list = CommentPro::select()->toArray();
            foreach ($list as $index=>$item){
                $arr[$item['id']] = $item['title'].' - '.$item['platform'];
            }
        }elseif ($type == 'collect'){

        }elseif ($type == 'shop'){
            $list = CommentCenter::select()->where('crawl_type','=','店铺')->toArray();
            foreach ($list as $index=>$item){
                $arr[$item['id']] = $item['title'].' - '.$item['platform'];
            }
        }elseif ($type == 'keyword'){
            $list = CommentCenter::select()->where('crawl_type','=','关键词')->toArray();
            foreach ($list as $index=>$item){
                $arr[$item['id']] = $item['title'].' - '.$item['platform'];
            }
        }

        return json($arr);

    }

    /**
     * 飞书机器人卡片消息
     * @param string $user_id  飞书 id
     * @param string $username  用户名
     * @param int $num    数量
     * @param int $msg    发送的消息
     */
    public function send_fs_msg(){

        $post = $this->request->post();
        $user_id = $post['user_id'];
        $msg = $post['msg'];

        $card_msg = [
            "config" => [
                "wide_screen_mode" => true
            ],
            "header" => [
                "title" => [
                    "tag" => "plain_text",
                    "content" => "🔊 号外！号外！以下链接无法打开，快去看看吧～"
                ],
                "template"=>"turquoise"//卡片标题的主题色
            ],
            "elements" => [
                [
                    "tag" => "div",
                    "fields" => [

                        [
                            "is_short" => false,
                            "text" => [
                                "tag" => "lark_md",
                                "content" => $msg
                            ]
                        ],
                    ]

                ]
                ,
                [
                    "tag" => "hr"
                ],
                [
                    "tag" => "div",
                    "fields" => [
                        [
                            "is_short" => true,
                            "text" => [
                                "tag" => "lark_md",
                                "content"=> " **蜘蛛侠** - [SpiderMan](https://myspider-manager.maiyuan.online/admin/) 通知"

                            ]
                        ],
                    ]
                ],

            ]
        ];
        //消息   interactive     post   text
        app('feishu')->im->sendMessage($user_id,$card_msg,'interactive');
        return 'success';
    }


    public function getGroupTree(){
        //获取组信息
        $all_group = SystemGroup::select()->toArray();
        $tool = new Tool;
        $tree_data = $tool->get_tree_data_add($all_group,0);
        return json($tree_data);
    }

}