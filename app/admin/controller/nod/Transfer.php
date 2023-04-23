<?php


namespace app\admin\controller\nod;

use app\admin\controller\JvMing;
use app\admin\model\NodAccount;
use app\admin\model\NodAccountInfo;
use app\admin\model\NodInventory;
use app\admin\model\NodOrder;
use app\admin\model\NodOrderInfo;
use app\admin\model\NodSupplier;
use app\admin\model\NodWarehouse;
use app\admin\model\NodWarehouseInfo;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * @ControllerAnnotation(title="资金 提现转存")
 */
class Transfer extends AdminController
{

    use \app\admin\traits\Curd;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new NodAccount();
        $this->account_info_model = new NodAccountInfo();
        $this->order_model = new NodOrder();
    }

    /**
     * @NodeAnotation(title="提现转存列表")
     */
    public function index()
    {
        if ($this->request->isAjax()){
            list($page, $limit, $where) = $this->buildTableParames();

            $where[] = ['type','=',10];

            $where = format_where_datetime($where,'order_time');
            $list = $this->order_model
                ->with(['getFromAccount','getToAccount','getOrderUser'],'left')
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
     * @NodeAnotation(title="提现转存数据添加")
     */
    public function add()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->post();


            $order_info_rule = [
                'from_account|【转移账户】' => 'require|number',
                'to_account|【目标账户】' => 'require|number',
                'price|【金额】' => 'require|number',
            ];

            $this->validate($post, $order_info_rule);


            $post['from_account'] == $post['to_account'] && $this->error('不能选择相同的账户');
            //判断转移的账户是否有钱
            $data = $this->model->where('id','=',$post['from_account'])->find();


            //单据编号自动生成   ZCTX+时间戳
            $order_batch_num = 'ZCTX' . date('YmdHis');

            $save_order = [
                'from_account' => $post['from_account'],
                'to_account' => $post['to_account'],
                'order_batch_num' => $order_batch_num,
                'order_time' => date('Y-m-d H:i:s'),
                'order_user_id' => session('admin.id'),
                'practical_price' => $post['price'],
                'paid_price' =>  $post['price'],
                'remark' => $post['remark'],
                'type' => 10, //转存提现
                'audit_status' => 0,//审核状态
            ];
            $this->order_model->save($save_order);

            $this->success('保存成功~');


        }

        $account_list = $this->model->field('id,name,balance_price')->select()->toArray();
        $this->assign('account_list', $account_list);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="提现转存数据修改")
     */
    public function edit($id)
    {
        $row = $this->order_model->find($id);
        empty($row) && $this->error('次单据不存在');
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            if ($row['audit_status'] != 0){
                $this->error('此状态不能再次修改！');
            }


            $order_info_rule = [
                'from_account|【转移账户】' => 'require|number',
                'to_account|【目标账户】' => 'require|number',
                'price|【金额】' => 'require',
            ];

            $this->validate($post, $order_info_rule);


            $post['from_account'] == $post['to_account'] && $this->error('不能选择相同的账户');
            //判断转移的账户是否有钱
            $data = $this->model->where('id','=',$post['from_account'])->find();


            //单据编号自动生成   ZCTX+时间戳
            $order_batch_num = 'ZCTX' . date('YmdHis');

            $save_order = [
                'from_account' => $post['from_account'],
                'to_account' => $post['to_account'],
                'order_batch_num' => $order_batch_num,
                'order_user_id' => session('admin.id'),
                'practical_price' => $post['price'],
                'paid_price' =>  $post['price'],
                'remark' => $post['remark'],
                'type' => 10, //转存提现
                'audit_status' => 0,//审核状态
            ];
            $row->save($save_order);
            $this->success('保存成功~');


        }

        $account_list = $this->model->field('id,name,balance_price')->select()->toArray();
        $this->assign('account_list', $account_list);
        $this->assign('row', $row);
        return $this->fetch();
    }


    /**
     * @NodeAnotation(title="撤销提现转存数据")
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
     * @NodeAnotation(title="审核提现转存数据")
     */
    public function audit($id)
    {

        $row = $this->order_model->find($id);
        empty($row) && $this->error('次单据不存在');
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            //判断转移的账户是否有钱
            $from_data = $this->model->where('id','=',$post['from_account'])->find();
            $to_data = $this->model->where('id','=',$post['to_account'])->find();
            $post['from_account'] == $post['to_account'] && $this->error('不能选择相同的账户');

            if ($row['audit_status'] !=0){
                $this->error('此状态不能再次审核！');
            }
            $this->model->startTrans();
            try{
                //账号扣钱 加钱
                $row->save([
                    'audit_status'=>1,
                    'from_account' => $post['from_account'],
                    'to_account' => $post['to_account'],
                    'practical_price' => $post['price'],
                    'paid_price' =>  $post['price'],
                    'audit_user_id'=>session('admin.id')]);


                $from_data_balance = $from_data['balance_price']-$row['paid_price'];
                $to_data_balance = $to_data['balance_price']+$row['paid_price'];

                $from_data->save(['balance_price'=>$from_data_balance]);
                $to_data->save(['balance_price'=>$to_data_balance]);

                //保存到详情记录中
                $this->account_info_model->insert([
                    'sale_user_id'      => session('admin.id'),
                    'order_user_id'     => $row['order_user_id'],
                    'account_id'        => $post['from_account'],
                    'order_id'          => $row['id'],
                    'price'             =>-$post['price'],
                    'category'          => '提现转移单',
                    'sz_type'           => 1,
                    'type'              => 10,
                    'operate_time'      => $row['order_time'],
                    'remark'            => $row['remark'],
                    'balance_price'     => $from_data_balance, //账户余额
                    'all_balance_price' => get_total_account_price(),//总账户余额
                ]);

                $this->account_info_model->insert([
                    'sale_user_id'      => session('admin.id'),
                    'order_user_id'     => $row['order_user_id'],
                    'account_id'        => $post['to_account'],
                    'order_id'          => $row['id'],
                    'price'             => $post['price'],
                    'category'          => '提现转移单',
                    'sz_type'           => 1,
                    'type'              => 10,
                    'operate_time'      => $row['order_time'],
                    'remark'            => $row['remark'],
                    'balance_price'     => $to_data_balance, //账户余额
                    'all_balance_price' => get_total_account_price(),//总账户余额
                ]);
                $this->model->commit();
            }catch (\Exception $e) {
                // 回滚事务
                $this->model->rollback();
                $this->error('第【'.$e->getLine().'】行 审核错误：'.$e->getMessage() .'错误文件：'.$e->getFile());
            }







            $this->success('审核成功~');







        }

        $account_list = $this->model->field('id,name,balance_price')->select()->toArray();
        $this->assign('account_list', $account_list);
        $this->assign('row', $row);
        return $this->fetch();

    }


}
