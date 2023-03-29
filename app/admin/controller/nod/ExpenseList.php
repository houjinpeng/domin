<?php


namespace app\admin\controller\nod;

use app\admin\controller\JvMing;
use app\admin\model\NodAccount;
use app\admin\model\NodAccountInfo;
use app\admin\model\NodCategory;
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
 * @ControllerAnnotation(title="资金-费用单")
 */
class ExpenseList extends AdminController
{

    use \app\admin\traits\Curd;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new NodAccount();
        $this->supplier_model = new NodSupplier();
        $this->warehouse_model = new NodWarehouse();
        $this->account_model = new NodAccount();
        $this->account_info_model = new NodAccountInfo();
        $this->order_model = new NodOrder();
        $this->order_info_model = new NodOrderInfo();
        $this->inventory_model = new NodInventory();
        $this->kehu_model = new NodCustomerManagement();
        $this->category_model = new NodCategory();

    }

    /**
     * @NodeAnotation(title="费用列表")
     */
    public function index()
    {

        if ($this->request->isAjax()){

            list($page, $limit, $where) = $this->buildTableParames();

            $where[] = ['type','=',8];


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
                'customer|【客户名】' => 'require',
                'account_id|【账户】' => 'require|number',
                'practical_price|【单据金额】' => 'number|require',
                'paid_price|【实付金额】' => 'number|require',
            ];

            $this->validate($post, $order_info_rule);

            $rule = [
                'category_id|【付款类别】' => 'number|require',
                'unit_price|【付款金额】' => 'number|require',

            ];

            if (count($post['goods']) == 0 || count($post['goods']) >1 ) {
                $this->error('只能录入一单哦~');
            }
            //验证
            foreach ($post['goods'] as $item) {
                $item['unit_price'] = intval($item['unit_price']);
                $item['unit_price'] == 0 && $this->error('总金额不能为0');
                $this->validate($item, $rule);
            }
            if ($post['practical_price'] != intval($post['goods'][0]['unit_price'])) {
                $this->error('单据金额和项目金额不相等');
            }
            //判断客户是否存在 不存在添加
            $customer = $this->kehu_model->where('user_id','=',session('admin.id'))->where('name','=',$post['customer'])->find();
            if (empty($customer)){
                $customer_id = $this->kehu_model->insertGetId(['name'=>$post['customer'],'user_id'=>session('admin.id')]);
            }else{
                $customer_id = $customer['id'];
            }

            //单据编号自动生成   FYD+时间戳
            $order_batch_num = 'FYD' . date('YmdHis');

            $save_order = [
                'order_time' => $post['order_time'],
                'order_batch_num' => $order_batch_num,
                'order_user_id' => session('admin.id'),
                'remark' => $post['remark'],
                'customer_id'=>$customer_id,
                'account_id'=>$post['account_id'],
                'type'=>8, //付款单
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
                    'category' => '费用单',
                    'unit_price' => $item['unit_price'],
                    'remark' => isset($item['remark']) ? $item['remark'] : '',
                    'pid' => $pid,
                    'customer_id'=>$customer_id,
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
            $post['practical_price'] = intval($post['practical_price'] );
            $post['paid_price'] = $post['practical_price'];
            $order_info_rule = [
                'order_time|【单据日期】' => 'require|date',
                'customer|【客户名】' => 'require',
                'account_id|【账户】' => 'require|number',
                'practical_price|【单据金额】' => 'number|require',
                'paid_price|【实付金额】' => 'number|require',
            ];

            $this->validate($post, $order_info_rule);


            $rule = [
                'category_id|【付款类别】' => 'require',
                'unit_price|【付款金额】' => 'number|require',

            ];

            if (count($post['goods']) == 0 || count($post['goods']) >1 ) {
                $this->error('只能录入一单哦~');
            }
            //验证
            foreach ($post['goods'] as $item) {
                $item['unit_price'] = intval($item['unit_price']);

                $item['unit_price'] == 0 && $this->error('总金额不能为0');
                $this->validate($item, $rule);
            }
            if ($post['practical_price'] != intval($post['goods'][0]['unit_price'])) {
                $this->error('单据金额和项目金额不相等');
            }

            //判断客户是否存在 不存在添加
            $customer = $this->kehu_model->where('user_id','=',session('admin.id'))->where('name','=',$post['customer'])->find();
            if (empty($customer)){
                $customer_id = $this->kehu_model->insertGetId(['name'=>$post['customer'],'user_id'=>session('admin.id')]);
            }else{
                $customer_id = $customer['id'];
            }


            $save_order = [
                'order_time' => $post['order_time'],
                'order_user_id' => session('admin.id'),
                'remark' => $post['remark'],
                'customer_id' => $customer_id,
                'account_id' => $post['account_id'],
                'practical_price' => $post['practical_price'],
                'paid_price' => $post['paid_price'],

            ];
            //获取pid   保存商品详情

            $data->save($save_order);


            foreach ($post['goods'] as $item) {
                $save_info = [
                    'category_id' => $item['category_id'],
                    'unit_price' => $item['unit_price'],
                    'remark' => isset($item['remark']) ? $item['remark'] : '',
                    'customer_id'=>$customer_id,
                    'account_id' => $post['account_id'],
                ];
                $this->order_info_model->where('id','=',$item['id'])->update($save_info);

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
     * @NodeAnotation(title="撤销单数据")
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

    /**
     * @NodeAnotation(title="费用单审核")
     */
    public function audit($id){
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
            $post['practical_price'] = intval($post['practical_price'] );
            $order_info_rule = [
                'order_time|【单据日期】' => 'require|date',
                'customer|【客户名】' => 'require',
                'account_id|【账户】' => 'require|number',
                'practical_price|【单据金额】' => 'number|require',

            ];

            $this->validate($post, $order_info_rule);



            $rule = [
                'category_id|【付款类别】' => 'require',
                'unit_price|【付款金额】' => 'number|require',

            ];

            if (count($post['goods']) == 0 || count($post['goods']) >1 ) {
                $this->error('只能录入一单哦~');
            }
            $order_info = $post['goods'] [0];
            //验证
            $order_info['unit_price'] = intval($order_info['unit_price']);
            $order_info['unit_price'] == 0 && $this->error('总金额不能为0');
            $this->validate($order_info, $rule);


            if ($post['practical_price'] != $order_info['unit_price']) {
                $this->error('单据金额和项目金额不相等');
            }


            //判断客户是否存在 不存在添加
            $customer = $this->kehu_model->where('user_id','=',session('admin.id'))->where('name','=',$post['customer'])->find();
            if (empty($customer)){
                $customer_id = $this->kehu_model->insertGetId(['name'=>$post['customer'],'user_id'=>session('admin.id')]);
            }else{
                $customer_id = $customer['id'];
            }

            $save_order = [
                'remark' => $post['remark'],
                'practical_price' => $post['practical_price'],
                'paid_price' => $post['practical_price'],
                'audit_status' => 1,//审核状态
                'audit_user_id'=>session('admin.id')

            ];
            //保存订单详情
            $update = $data->save($save_order);
            $update || $this->error('审核失败~');



            $save_info = [
                'category_id' => $order_info['category_id'],
                'unit_price' => $order_info['unit_price'],
                'remark' => isset($order_info['remark']) ? $order_info['remark'] : '',
                'customer_id'=>$customer_id,
                'account_id' => $post['account_id'],
            ];
            $this->order_info_model->where('id','=',$order_info['id'])->update($save_info);

            //减少账户钱
            $account_data = $this->account_model->find($data['account_id']);

            //获取总账户余额
            $all_balance_price = $this->account_model->sum('balance_price');
            $balance_price = $account_data['balance_price'];

            $balance_price -= intval($order_info['unit_price']);
            $all_balance_price -= intval($order_info['unit_price']);
            //利润
            $profit_price = 0;
            //费用如果带上销售员就扣利润
            if ($post['sale_user_id']){
//                判断是否是销售费用 如果是要在销售员利润中扣除
//                $cate_info = $this->category_model->find($order_info['category_id']);
//                if ($cate_info['name'] == '销售费用'){
//                    $profit_price = -$order_info['unit_price'];
//                }
                $profit_price = -$order_info['unit_price'];
                //获取销售员的总利润
                $total_profit_price = $this->account_info_model->where('sale_user_id','=',$post['sale_user_id'])->sum('profit_price');
                $total_profit_price = $total_profit_price +$profit_price;

            }

            //查询客户欠咱们的钱
            $customer_row = $this->kehu_model->find($data['customer_id']);
            $receivable_price = $customer_row['receivable_price'];
            //保存交易明细表中
            $this->account_info_model->insert([
                'sale_user_id'      => $data['sale_user_id'],
                'order_user_id'     => $data['order_user_id'],
                'account_id'        => $data['account_id'],
                'customer_id'       => $data['customer_id'],
                'category_id'       => $order_info['category_id'],
                'order_id'          => $id,
                'price'             =>-$order_info['unit_price'],
                'profit_price'      => $profit_price, //利润
                'total_profit_price'=> $total_profit_price, //总利润
                'category'          => '费用单',
                'sz_type'           => 2,
                'type'              => 8,
                'operate_time'      => $data['order_time'],
                'remark'            => $order_info['remark'],
                'balance_price'     => $balance_price, //账户余额
                'all_balance_price' => $all_balance_price,//总账户余额
                'receivable_price'  => $receivable_price,//对方欠咱们的钱
            ]);



            $this->success('审核成功~');



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

}
