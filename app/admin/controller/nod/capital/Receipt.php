<?php


namespace app\admin\controller\nod\capital;

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
 * @ControllerAnnotation(title="资金-收款单")
 */
class Receipt extends AdminController
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
     * @NodeAnotation(title="收款列表")
     */
    public function index()
    {

        if ($this->request->isAjax()) {

            list($page, $limit, $where) = $this->buildTableParames();

            $where[] = ['type', '=', 4];
            $where = format_where_datetime($where, 'order_time');


            $list = $this->order_model
                ->with(['getCustomer', 'getAccount', 'getOrderUser'], 'left')
                ->where($where)->page($page, $limit)->order('id', 'desc')->select()->toArray();
            $count = $this->order_model->where($where)->order('id', 'desc')->count();
            $data = [
                'code' => 0,
                'data' => $list,
                'count' => $count,
            ];
            return json($data);

        }


        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="录入收款单数据")
     */
    public function add()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $post = htmlspecialchars_decode($post['data']);
            $post = (json_decode($post, true));
            $post['practical_price'] == '0' && $this->error('单据金额不能为0');
            $post['practical_price'] = floatval($post['practical_price']);
            $post['paid_price'] = $post['practical_price'];
            $order_info_rule = [
                'order_time|【单据日期】' => 'require|date',
                'customer|【客户名】' => 'require',
                'account_id|【账户】' => 'require|number',
                'practical_price|【单据金额】' => 'float|require',
                'paid_price|【实收金额】' => 'float|require',
            ];
            $this->validate($post, $order_info_rule);
            //检查单据金额是否与内容一样
            check_practical_price($post['practical_price'], $post['goods']) || $this->error('单据中的内容与单据金额不付~ 请重新计算');
            $rule = [
                'category_id|【收款类别】' => 'number|require',
                'unit_price|【收款金额】' => 'float|require',

            ];
            if (count($post['goods']) == 0 || count($post['goods']) > 1) {
                $this->error('只能录入一单哦~');
            }

            //验证
            foreach ($post['goods'] as $item) {
                intval($item['unit_price']) == 0 && $this->error('总金额不能为0');
                $this->validate($item, $rule);
            }
            if ($post['practical_price'] != floatval($post['goods'][0]['unit_price'])) {
                $this->error('单据金额和项目金额不相等');
            }

            //判断客户是否存在 不存在添加
            $customer = get_customer_data($post['customer']);
            $customer_id = $customer['id'];


            //单据编号自动生成   SKD+时间戳
            $order_batch_num = 'SKD' . date('YmdHis');

            $save_order = [
                'order_time' => $post['order_time'],
                'order_batch_num' => $order_batch_num,
                'order_user_id' => session('admin.id'),
                'remark' => $post['remark'],
                'customer_id' => $customer_id,
                'account_id' => $post['account_id'],
                'sale_user_id' => $post['sale_user_id'],
                'type' => 4, //收款单
                'practical_price' => $post['practical_price'],
                'paid_price' => $post['paid_price'],
                'audit_status' => 0,//审核状态
            ];
            //获取pid   保存商品详情

            $pid = $this->order_model->insertGetId($save_order);

            $insert_all = [];

            foreach ($post['goods'] as $item) {
                $save_info = [
                    'category_id' => $item['category_id'],
                    'category' => '收款单',
                    'unit_price' => $item['unit_price'],
                    'remark' => isset($item['remark']) ? $item['remark'] : '',
                    'pid' => $pid,
                    'customer_id' => $customer_id,
                    'account_id' => $post['account_id'],
                    'sale_user_id' => $post['sale_user_id'],
                    'order_user_id' => session('admin.id'),
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
     * @NodeAnotation(title="编辑收款单数据")
     */
    public function edit($id)
    {

        //查询为审核的订单
        $data = $this->order_model
            ->with(['getWarehouse', 'getAccount', 'getSupplier', 'getOrderUser'], 'left')
            ->find($id);
        if ($this->request->isAjax()) {
            $data['audit_status'] != 0 && $this->error('不是可编辑状态，不能再次编辑~');


            $post = $this->request->post();
            $post = htmlspecialchars_decode($post['data']);
            $post = (json_decode($post, true));
            $post['practical_price'] == '0' && $this->error('单据金额不能为0');
            $post['practical_price'] = floatval($post['practical_price']);
            $post['paid_price'] = $post['practical_price'];
            $order_info_rule = [
                'order_time|【单据日期】' => 'require|date',
                'customer|【客户名】' => 'require',
                'account_id|【账户】' => 'require|number',
                'practical_price|【单据金额】' => 'float|require',
                'paid_price|【实收金额】' => 'float|require',
            ];

            $this->validate($post, $order_info_rule);

            //检查单据金额是否与内容一样
            check_practical_price($post['practical_price'], $post['goods']) || $this->error('单据中的内容与单据金额不付~ 请重新计算');
            $rule = [
                'category_id|【收款类别】' => 'number|require',
                'unit_price|【收款金额】' => 'float|require',

            ];

            if (count($post['goods']) == 0 || count($post['goods']) > 1) {
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
                'paid_price' => $post['paid_price'],
                'sale_user_id' => $post['sale_user_id'],

            ];
            //获取pid   保存商品详情

            $data->save($save_order);


            $item = $post['goods'][0];
            $save_info = [
                'category_id' => $item['category_id'],
                'unit_price' => $item['unit_price'],
                'remark' => isset($item['remark']) ? $item['remark'] : '',
                'customer_id' => $customer_id,
                'account_id' => $post['account_id'],
                'sale_user_id' => $post['sale_user_id'],
            ];
            $this->order_info_model->where('id', '=', $item['id'])->update($save_info);

            $this->success('修改成功~');

        }
        $supplier_list = $this->supplier_model->field('id,name')->select()->toArray();
        $warehouse_list = $this->warehouse_model->field('id,name')->select()->toArray();
        $account_list = $this->account_model->field('id,name')->select()->toArray();

        //获取所有订单详情中的数据
        $all_goods = $this->order_info_model->where('pid', '=', $id)->select()->toArray();
        $this->assign('all_goods', json_encode($all_goods));
        $this->assign('data', $data);


        $this->assign('supplier_list', $supplier_list);
        $this->assign('warehouse_list', $warehouse_list);
        $this->assign('account_list', $account_list);
        return $this->fetch();

    }


    /**
     * @NodeAnotation(title="订单回滚")
     */
    public function rollback_order($id)
    {
        $row = $this->order_model->find($id);
        if ($this->request->isAjax()) {
            empty($row) && $this->error('无法找到此订单~');
            $row['audit_status'] != 1 && $this->error('此状态不能回滚订单');
            //判断订单类型
            $row['type'] != 4 && $this->error('订单类型不对，不能回滚订单');

            //将订单审核状态修改为回滚 3
            //获取收款数据   将所有数据退回

            //获取订单下的数据
            $all_order_info = $this->account_info_model->where('order_id','=',$id)->select()->toArray();
            count($all_order_info) == 0 && $this->error('没有订单内容，不能回滚订单');

            //计算回退金额
            $return_price = 0;
            foreach ($all_order_info as $item){
                $return_price += $item['price'];
            }

            //计算账户总余额
            $all_balance_price = $this->account_model->sum('balance_price')-$return_price;

            //获取单账户的余额
            $balance_price_data = $this->account_model->find($row['account_id']);

            //开启事务
            $this->model->startTrans();
            try {

                $balance_price = $balance_price_data['balance_price']-$return_price;
                //将金额减退款金额
                $balance_price_data->save(['balance_price'=>$balance_price]);

                //将客户应收款还原
                $customer_data = $this->kehu_model->find($row['customer_id']);

                $receivable_price = $customer_data['receivable_price'] + $return_price;
                $customer_data->save(['receivable_price'=>$receivable_price]);

                //账户记录收款
                $this->account_info_model->insert([
                    'account_id'        => $row['account_id'],
                    'customer_id'       => $row['customer_id'],
                    'sale_user_id'      => $row['sale_user_id'],
                    'order_user_id'     => $row['order_user_id'],
                    'order_id'          => $id,
                    'price'             => $return_price,
                    'profit_price'      => 0, //利润
                    'category'          => '收款单回滚',
                    'sz_type'           => 1,
                    'type'              => 4,
                    'operate_time'      => $row['order_time'],
                    'remark'            => $item['remark'],
                    'balance_price'     => $balance_price, //账户余额
                    'all_balance_price' => $all_balance_price,//总账户余额
                    'receivable_price'  => $receivable_price,//对方欠咱们的钱
                ]);


                $row->save(['audit_status'=>3,'user_id'=>session('admin.id')]);

                $this->model->commit();
            }catch (\Exception $e){
                $this->model->rollback();
                $this->error('第【'.$e->getLine().'】行 回滚错误：'.$e->getMessage());
            }

            $this->model->commit();

            $this->success('回滚成功~');


        }


    }


}
