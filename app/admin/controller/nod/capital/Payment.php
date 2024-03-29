<?php


namespace app\admin\controller\nod\capital;

use app\admin\controller\JvMing;
use app\admin\model\NodAccount;
use app\admin\model\NodCustomerManagement;
use app\admin\model\NodInventory;
use app\admin\model\NodOrder;
use app\admin\model\NodOrderInfo;
use app\admin\model\NodSupplier;
use app\admin\model\NodWarehouse;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * @ControllerAnnotation(title="资金-付款单")
 */
class Payment extends AdminController
{

    use \app\admin\traits\Curd;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new NodAccount();
        $this->supplier_model = new NodSupplier();
        $this->warehouse_model = new NodWarehouse();
        $this->account_model = new NodAccount();
        $this->order_model = new NodOrder();
        $this->order_info_model = new NodOrderInfo();
        $this->inventory_model = new NodInventory();
        $this->kehu_model = new NodCustomerManagement();

    }

    /**
     * @NodeAnotation(title="付款列表")
     */
    public function index()
    {

        if ($this->request->isAjax()){

            list($page, $limit, $where) = $this->buildTableParames();

            $where[] = ['type','=',5];

            $where = format_where_datetime($where,'order_time');

            $list = $this->order_model
                ->with(['getCustomer','getAccount','getOrderUser'],'left')
                ->where($where)->page($page,$limit)->order('id','desc')->select()->toArray();
            $count = $this->order_model->where($where)->order('id','desc')->count();
            $data = [
                'code'=>0,
                'data'=>$list,
                'count'=>$count,
            ];
            return json($data);

        }



        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="录入付款单数据")
     */
    public function add()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $post = htmlspecialchars_decode($post['data']);
            $post = (json_decode($post,true));
            $post['practical_price'] == '0'&& $this->error('单据金额不能为0');
            $post['paid_price'] = $post['practical_price'];
            $order_info_rule = [
                'order_time|【单据日期】' => 'require|date',
                'supplier_id|【渠道名】' => 'require',
                'account_id|【账户】' => 'require|number',
                'practical_price|【单据金额】' => 'number|require',
                'paid_price|【实付金额】' => 'number|require',
            ];

            $this->validate($post, $order_info_rule);
            //检查单据金额是否与内容一样
            check_practical_price($post['practical_price'],$post['goods'])|| $this->error('单据中的内容与单据金额不付~ 请重新计算');
            $rule = [
                'category_id|【付款类别】' => 'number|require',
                'unit_price|【付款金额】' => 'number|require',

            ];

            if (count($post['goods']) == 0 || count($post['goods']) >1 ) {
                $this->error('只能录入一单哦~');
            }
            //验证
            foreach ($post['goods'] as $item) {
                $item['unit_price'] = floatval($item['unit_price']);
                $item['unit_price'] == 0 && $this->error('总金额不能为0');
                $this->validate($item, $rule);
            }
            if ($post['practical_price'] != floatval($post['goods'][0]['unit_price'])) {
                $this->error('单据金额和项目金额不相等');
            }


            //单据编号自动生成   FKD+时间戳
            $order_batch_num = 'FKD' . date('YmdHis');

            $save_order = [
                'order_time' => $post['order_time'],
                'order_batch_num' => $order_batch_num,
                'order_user_id' => session('admin.id'),
                'remark' => $post['remark'],
                'supplier_id'=>$post['supplier_id'],
                'account_id'=>$post['account_id'],
                'type'=>5, //付款单
                'practical_price' => $post['practical_price'],
                'paid_price' => $post['paid_price'],
                'audit_status' => 0,//审核状态
                'sale_user_id' => $post['sale_user_id'],//审核状态
            ];
            //获取pid   保存商品详情

            $pid = $this->order_model->insertGetId($save_order);

            $insert_all = [];

            foreach ($post['goods'] as $item) {
                $save_info = [
                    'category_id' => $item['category_id'],
                    'category' => '付款单',
                    'unit_price' => $item['unit_price'],
                    'remark' => isset($item['remark']) ? $item['remark'] : '',
                    'pid' => $pid,
                    'supplier_id'=>$post['supplier_id'],
                    'sale_user_id'=>$post['sale_user_id'],
                    'order_user_id' => session('admin.id'),

                    'account_id' => $post['account_id'],
                ];
                $insert_all[] = $save_info;

            }
            $this->order_info_model->insertAll($insert_all);
            $this->success('保存成功~');


        }
        $account_list = $this->account_model->field('id,name')->select()->toArray();
        $supplier_list = $this->supplier_model->field('id,name')->select()->toArray();
        $this->assign('supplier_list', $supplier_list);

        $this->assign('account_list', $account_list);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="编辑付款单数据")
     */
    public function edit($id)
    {

        //查询为审核的订单
        $data = $this->order_model
            ->with(['getWarehouse','getAccount','getSupplier','getOrderUser'],'left')
            ->find($id);
        if ($this->request->isAjax()){
            $data['audit_status'] != 0 && $this->error('不是可编辑状态，不能再次编辑~');


            $post = $this->request->post();
            $post = htmlspecialchars_decode($post['data']);
            $post = (json_decode($post,true));
            $post['practical_price'] == '0'&& $this->error('单据金额不能为0');
            $post['practical_price'] = floatval($post['practical_price'] );
            $post['paid_price'] = $post['practical_price'];
            $order_info_rule = [
                'order_time|【单据日期】' => 'require|date',
                'supplier_id|【渠道名称】' => 'require',
                'account_id|【账户】' => 'require|number',
                'practical_price|【单据金额】' => 'number|require',
                'paid_price|【实付金额】' => 'number|require',
            ];

            $this->validate($post, $order_info_rule);
            //检查单据金额是否与内容一样
            check_practical_price($post['practical_price'],$post['goods'])|| $this->error('单据中的内容与单据金额不付~ 请重新计算');

            $rule = [
                'category_id|【付款类别】' => 'require',
                'unit_price|【付款金额】' => 'number|require',

            ];

            if (count($post['goods']) == 0 || count($post['goods']) >1 ) {
                $this->error('只能录入一单哦~');
            }
            //验证
            foreach ($post['goods'] as $item) {
                $item['unit_price'] = floatval($item['unit_price']);

                $item['unit_price'] == 0 && $this->error('总金额不能为0');
                $this->validate($item, $rule);
            }
            if ($post['practical_price'] != floatval($post['goods'][0]['unit_price'])) {
                $this->error('单据金额和项目金额不相等');
            }




            $save_order = [
                'order_time' => $post['order_time'],
                'order_user_id' => session('admin.id'),
                'remark' => $post['remark'],
                'supplier_id'=>$post['supplier_id'],
                'account_id' => $post['account_id'],
                'practical_price' => $post['practical_price'],
                'paid_price' => $post['paid_price'],

            ];
            //获取pid   保存商品详情

            $data->save($save_order);


            foreach ($post['goods'] as $item) {
                if (isset($item['id'])){
                    $save_info = [
                        'category_id' => $item['category_id'],
                        'unit_price' => $item['unit_price'],
                        'remark' => isset($item['remark']) ? $item['remark'] : '',
                        'supplier_id'=>$post['supplier_id'],
                        'account_id' => $post['account_id'],
                    ];
                    $this->order_info_model->where('id','=',$item['id'])->update($save_info);
                }else{
                    $save_info = [
                        'category_id' => $item['category_id'],
                        'category' => '付款',
                        'unit_price' => $item['unit_price'],
                        'remark' => isset($item['remark']) ? $item['remark'] : '',
                        'pid' => $id,
//                        'customer_id'=>$customer_id,
                        'sale_user_id'=>$post['sale_user_id'],
                        'account_id' => $post['account_id'],
                        'order_user_id' => session('admin.id'),

                    ];
                    $this->order_info_model->save($save_info);
                }

            }
            $this->success('修改成功~');

        }
        $supplier_list = $this->supplier_model->field('id,name')->select()->toArray();
        $warehouse_list = $this->warehouse_model->field('id,name')->select()->toArray();
        $account_list = $this->account_model->field('id,name')->select()->toArray();

        //获取所有订单详情中的数据
        $all_goods= $this->order_info_model->where('pid','=',$id)->select()->toArray();
        $this->assign('all_goods',json_encode($all_goods));
        $this->assign('data',$data);


        $this->assign('supplier_list', $supplier_list);
        $this->assign('warehouse_list', $warehouse_list);
        $this->assign('account_list', $account_list);
        return $this->fetch();

    }

    /**
     * @NodeAnotation(title="撤销付款单数据")
     */
    public function chexiao($id){
        if ($this->request->isAjax()){
            $row = $this->order_model->find($id);
            if ($row['audit_status'] !==0){
                $this->error('当前状态不能撤销');
            }
            $row->save(['audit_status'=>2]);
            $this->success('撤销成功~ 请重新提交采购数据！');
        }
    }




}
