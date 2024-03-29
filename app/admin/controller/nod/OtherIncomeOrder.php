<?php


namespace app\admin\controller\nod;

use app\admin\controller\JvMing;
use app\admin\model\NodAccount;
use app\admin\model\NodAccountInfo;
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
use think\facade\Db;

/**
 * @ControllerAnnotation(title="资金-其他收入单")
 */
class OtherIncomeOrder extends AdminController
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

    }

    /**
     * @NodeAnotation(title="其他收入列表")
     */
    public function index()
    {

        if ($this->request->isAjax()){

            list($page, $limit, $where) = $this->buildTableParames();

            $where[] = ['type','=',9];

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
     * @NodeAnotation(title="其他收入录入数据")
     */
    public function add()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $post = htmlspecialchars_decode($post['data']);
            $post = (json_decode($post,true));
            $post['practical_price'] == '0'&& $this->error('单据金额不能为0');
            $order_info_rule = [
                'order_time|【单据日期】' => 'require|date',
                'customer|【客户名】' => 'require',
                'account_id|【账户】' => 'require|number',
                'practical_price|【单据金额】' => 'float|require',
            ];

            $this->validate($post, $order_info_rule);

            $rule = [
                'category_id|【付款类别】' => 'number|require',
                'unit_price|【付款金额】' => 'float|require',
            ];

            if (count($post['goods']) == 0 ) {
                $this->error('不能不录入单据哦~');
            }
            $total_price = 0;
            //验证
            foreach ($post['goods'] as $item) {
                $item['unit_price'] = floatval($item['unit_price']);
                $item['unit_price'] == 0 && $this->error('总金额不能为0');
                $this->validate($item, $rule);
                $total_price += $item['unit_price'] ;
            }

            if ($post['practical_price'] != $total_price ) {
                $this->error('单据金额和项目金额总和不相等');
            }
            //判断客户是否存在 不存在添加
            $customer = get_customer_data($post['customer']);
            $customer_id = $customer['id'];


            //单据编号自动生成   FYD+时间戳
            $order_batch_num = 'QTSRD' . date('YmdHis');

            $save_order = [
                'order_time' => $post['order_time'],
                'order_batch_num' => $order_batch_num,
                'order_user_id' => session('admin.id'),
                'remark' => $post['remark'],
                'customer_id'=>$customer_id,
                'account_id'=>$post['account_id'],
                'type'=>9, //其他收入单
                'practical_price' => $post['practical_price'],
                'paid_price' => $post['practical_price'],
                'audit_status' => 0,//审核状态
                'sale_user_id' => $post['sale_user_id'],//审核状态
            ];
            //获取pid   保存商品详情

            $pid = $this->order_model->insertGetId($save_order);

            $insert_all = [];

            foreach ($post['goods'] as $item) {
                $save_info = [
                    'category_id' => $item['category_id'],
                    'category' => '其他收入单',
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
     * @NodeAnotation(title="编辑其他收入单数据")
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
            $order_info_rule = [
                'order_time|【单据日期】' => 'require|date',
                'customer|【客户名】' => 'require',
                'account_id|【账户】' => 'require|number',
                'practical_price|【单据金额】' => 'float|require',
            ];

            $this->validate($post, $order_info_rule);


            $rule = [
                'category_id|【付款类别】' => 'require',
                'unit_price|【付款金额】' => 'float|require',

            ];

            if (count($post['goods']) == 0) {
                $this->error('不能不录入单据哦~');
            }
            //验证
            $total_price = 0;
            foreach ($post['goods'] as $item) {
                $item['unit_price'] = intval($item['unit_price']);
                $item['unit_price'] == 0 && $this->error('总金额不能为0');
                $this->validate($item, $rule);
                $total_price += $item['unit_price'];
            }
            if ($post['practical_price'] != $total_price ) {
                $this->error('单据金额和项目金额总和不相等');
            }

            //判断客户是否存在 不存在添加
            $customer = get_customer_data($post['customer']);
            $customer_id = $customer['id'];


            $save_order = [
                'order_time' => $post['order_time'],
                'order_user_id' => session('admin.id'),
                'remark' => $post['remark'],
                'customer_id' => $customer_id,
                'account_id' => $post['account_id'],
                'practical_price' => $post['practical_price'],
                'paid_price' => $post['practical_price'],
                'sale_user_id' => $post['sale_user_id'],

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
     * @NodeAnotation(title="撤销数据")
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
     * @NodeAnotation(title="其他收入单审核")
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
            $post['practical_price'] = floatval($post['practical_price'] );
            $order_info_rule = [
                'order_time|【单据日期】' => 'require|date',
                'customer|【客户名】' => 'require',
                'account_id|【账户】' => 'require|number',
                'practical_price|【单据金额】' => 'float|require',

            ];

            $this->validate($post, $order_info_rule);



            $rule = [
                'category_id|【付款类别】' => 'require',
                'unit_price|【付款金额】' => 'float|require',

            ];

            if (count($post['goods']) == 0) {
                $this->error('不能不录入单据哦~');
            }


            //验证
            $total_price = 0;
            foreach ($post['goods'] as $item) {
                $item['unit_price'] = intval($item['unit_price']);
                $item['unit_price'] == 0 && $this->error('总金额不能为0');
                $this->validate($item, $rule);
                $total_price += $item['unit_price'];
            }

            if ($post['practical_price'] != $total_price ) {
                $this->error('单据金额和项目金额总和不相等');
            }

            //判断客户是否存在 不存在添加
            $customer = get_customer_data($post['customer']);
            $customer_id = $customer['id'];


            $this->model->startTrans();
            try {
                $save_order = [
                    'remark' => $post['remark'],
                    'customer_id' => $customer_id,#客户
                    'practical_price' => $post['practical_price'],
                    'paid_price' => $post['practical_price'],
                    'audit_status' => 1,//审核状态
                    'audit_user_id'=>session('admin.id'),

                ];
                //保存订单详情
                $data->save($save_order);
                $data = $this->order_model
                    ->with(['getWarehouse','getAccount','getSupplier','getOrderUser'],'left')
                    ->find($id);

                //增加账户钱
                $account_data = $this->account_model->find($data['account_id']);
                $balance_price = $account_data['balance_price'];
                //利润
                $profit_price = 0;
                $total_profit_price = 0;
                //获取总账户余额
                $all_balance_price = $this->account_model->sum('balance_price');
                foreach ($post['goods'] as $order_info){
                    $save_info = [
                        'category_id' => $order_info['category_id'],
                        'unit_price' => $order_info['unit_price'],
                        'remark' => isset($order_info['remark']) ? $order_info['remark'] : '',
                        'customer_id'=>$customer_id,
                        'account_id' => $post['account_id'],
                    ];
                    $this->order_info_model->where('id','=',$order_info['id'])->update($save_info);





                    $balance_price += intval($order_info['unit_price']);
                    $all_balance_price += intval($order_info['unit_price']);

                    if ($post['sale_user_id']){
                        $profit_price = $order_info['unit_price'];
                        //获取销售员的总利润
                        $total_profit_price = $this->account_info_model->where('sale_user_id','=',$post['sale_user_id'])->sum('profit_price');
                        $total_profit_price = $total_profit_price + $profit_price;
                    }

                    //查询客户欠咱们的钱
                    $customer_row = $this->kehu_model->find($data['customer_id']);

                    $receivable_price = empty($customer_row)? 0: $customer_row['receivable_price'];
                    //保存交易明细表中
                    $this->account_info_model->insert([
                        'sale_user_id'      => $data['sale_user_id'],
                        'order_user_id'     => $data['order_user_id'],
                        'account_id'        => $data['account_id'],
                        'customer_id'       => $data['customer_id'],
                        'category_id'       => $order_info['category_id'],
                        'order_id'          => $id,
                        'price'             => $order_info['unit_price'],
                        'profit_price'      => $profit_price, //利润
                        'total_profit_price'=> $total_profit_price, //总利润
                        'category'          => '其他收入单',
                        'sz_type'           => 1,
                        'type'              => 9,
                        'operate_time'      => $data['order_time'],
                        'remark'            => $order_info['remark'],
                        'balance_price'     => $balance_price, //账户余额
                        'all_balance_price' => $all_balance_price,//总账户余额
                        'receivable_price'  => $receivable_price,//对方欠咱们的钱
                    ]);

                }

                //修改余额
                $account_data->save(['balance_price'=>$balance_price]);
                $this->model->commit();
            } catch (\Exception $e) {
                // 回滚事务
                $this->model->rollback();
                $this->error('第【'.$e->getLine().'】行 审核错误：'.$e->getMessage() .'错误文件：'.$e->getFile());
            }
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
