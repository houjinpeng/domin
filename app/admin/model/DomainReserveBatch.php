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

class DomainReserveBatch extends TimeModel
{

    protected $name = "domain_reserve_batch";

    public function admin(){
        return $this->belongsTo(SystemAdmin::class, 'user_id', 'id');

    }

    public function warehouse(){
        return $this->belongsTo(NodWarehouse::class, 'warehouse_id', 'id');

    }

}