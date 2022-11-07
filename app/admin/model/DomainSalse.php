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

class DomainSalse extends TimeModel
{

    protected $name = "domain_sales";

    public function getSalesData(){
        return $this->belongsTo(DomainStore::class, 'store_id', 'store_id');

    }
}