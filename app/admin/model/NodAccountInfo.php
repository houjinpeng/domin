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

namespace app\admin\model;


use app\common\model\TimeModel;

class NodAccountInfo extends TimeModel
{

    protected $name = "nod_account_info";
    //关联单据
    public function getOrder()
    {
        return $this->belongsTo(NodOrder::class, 'order_id', 'id');
    }
    //关联单据
    public function getWarehouse()
    {
        return $this->belongsTo(NodWarehouse::class, 'warehouse_id', 'id');
    }
    //关联账户
    public function getAccount()
    {
        return $this->belongsTo(NodAccount::class, 'account_id', 'id');
    }

    //关联来源渠道
    public function getSupplier()
    {
        return $this->belongsTo(NodSupplier::class, 'supplier_id', 'id');
    }

    //关联客户
    public function getCustomer()
    {
        return $this->belongsTo(NodCustomerManagement::class, 'customer_id', 'id');
    }
    //关联制单人
    public function getOrderUser()
    {
        return $this->belongsTo(SystemAdmin::class, 'order_user_id', 'id');
    }
    //关联销售人
    public function getSaleUser()
    {
        return $this->belongsTo(SystemAdmin::class, 'sale_user_id', 'id');
    }

    //关联收付款类型
    public function getCategory()
    {
        return $this->belongsTo(NodCategory::class, 'category_id', 'id');
    }
}