<?php

namespace app\admin\controller\system;

use app\admin\model\SystemAuth;
use app\admin\model\SystemAuthNode;
use app\admin\model\SystemGroup;
use app\admin\model\SystemMenu;
use app\admin\model\SystemNode;
use app\admin\service\TriggerService;
use app\common\constants\MenuConstant;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use think\App;

/**
 * Class Menu
 * @package app\admin\controller\system
 * @ControllerAnnotation(title="组织架构",auth=true)
 */
class Group extends AdminController
{

    use \app\admin\traits\Curd;

    protected $sort = [
        'sort' => 'desc',
        'id'   => 'asc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new SystemGroup();
        $this->modelAuth = new SystemAuth();

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
            $count = $this->model->count();
            $list = $this->model->order($this->sort)->select();
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
    public function add($id = null)
    {

        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $rule = [
                'pid|上级'   => 'require',
                'title|架构名称' => 'require',
                'node|权限名称' => 'require',
            ];

            $save = $this->model->save($post);
            $save ? $this->success('添加成功'):$this->error('添加失败');
//            if(!isset($post['node'])){
//                $this->error('请选择权限');
//            }
            //判断当前权限是否存在
//            $is_exits = $this->modelAuth->where('title','=',$post['title'])->find();
//            if (!empty($is_exits)){ $this->error('请重新输入组名~当前组名权限已存在 不能再次添加！');}
//
//            //先插入组名
//            $this->model->save(['pid'=>$post['pid'],'title'=>$post['title'],'remark'=>$post['remark']]);
//
//            //插入和组名一样的角色名称 获取id
//
//
//            $id = $this->modelAuth->insertGetId(['title'=>$post['title'],'remark'=>$post['remark']]);
//
//            $node = $this->request->post('node', "[]");
//
//            try {
//                $authNode = new SystemAuthNode();
//                $authNode->where('auth_id', $id)->delete();
//                if (!empty($node)) {
//                    $saveAll = [];
//                    foreach ($node as $vo) {
//                        $saveAll[] = [
//                            'auth_id' => $id,
//                            'node_id' => $vo,
//                        ];
//                    }
//                    $authNode->saveAll($saveAll);
//                }
//                TriggerService::updateMenu();
//            } catch (\Exception $e) {
//                $this->error('保存失败'.$e->getMessage());
//            }
//            $this->success('保存成功');

        }


        $pidMenuList = $this->model->getPidMenuList();
        $this->assign('id', $id);
        $this->assign('pidMenuList', $pidMenuList);
        return $this->fetch();
    }


    /**
     * @NodeAnotation(title="授权")
     */
    public function authorize($id=99999999)
    {
        $row = $this->modelAuth->find($id);
        if ($this->request->isAjax()) {
            $list = $this->modelAuth->getAuthorizeNodeListByAdminId($id);
            $this->success('获取成功', $list);
        }
        $this->assign('row', $row);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="编辑")
     */
    public function edit($id)
    {
        $row = $this->model->find($id);
        empty($row) && $this->error('数据不存在');
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $rule = [
                'pid|上级菜单'   => 'require',
                'title|菜单名称' => 'require',
            ];
            $this->validate($post, $rule);
            try {
                $save = $row->save($post);
            } catch (\Exception $e) {
                $this->error('保存失败');
            }
            if ($save) {
                TriggerService::updateMenu();
                $this->success('保存成功');
            } else {
                $this->error('保存失败');
            }
        }
        $pidMenuList = $this->model->getPidMenuList();
        if ($row['pid'] == 0){
            $top_title='顶级架构';
        }else{
            $top_title = $this->model->where('id','=',$row['pid'])->find();
            $top_title = $top_title['title'];
        }
        $this->assign([
            'id'          => $id,
            'pidMenuList' => $pidMenuList,
            'row'         => $row,
            'top_title'         => $top_title,
        ]);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="删除")
     */
    public function delete($id)
    {
        $row = $this->model->whereIn('id', $id)->select();
        empty($row) && $this->error('数据不存在');
        try {
            $save = $row->delete();
        } catch (\Exception $e) {
            $this->error('删除失败');
        }
        if ($save) {
            TriggerService::updateMenu();
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * @NodeAnotation(title="属性修改")
     */
    public function modify()
    {
        $post = $this->request->post();
        $rule = [
            'id|ID'    => 'require',
            'field|字段' => 'require',
            'value|值'  => 'require',
        ];
        $this->validate($post, $rule);
        $row = $this->model->find($post['id']);
        if (!$row) {
            $this->error('数据不存在');
        }
        if (!in_array($post['field'], $this->allowModifyFields)) {
            $this->error('该字段不允许修改：' . $post['field']);
        }
        $homeId = $this->model
            ->where([
                'pid' => MenuConstant::HOME_PID,
            ])
            ->value('id');
        if ($post['id'] == $homeId && $post['field'] == 'status') {
            $this->error('首页状态不允许关闭');
        }
        try {
            $row->save([
                $post['field'] => $post['value'],
            ]);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        TriggerService::updateMenu();
        $this->success('保存成功');
    }

    /**
     * @NodeAnotation(title="添加菜单提示")
     */
    public function getMenuTips()
    {
        $node = input('get.keywords');
        $list = SystemNode::whereLike('node', "%{$node}%")
            ->field('node,title')
            ->limit(10)
            ->select();
        return json([
            'code'    => 0,
            'content' => $list,
            'type'    => 'success',
        ]);
    }

}