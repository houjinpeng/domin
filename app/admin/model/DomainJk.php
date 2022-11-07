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

class DomainJk extends TimeModel
{

    protected $name = "domain_jk_data";

    public function getSalesData(){
        return $this->belongsTo(DomainHistory::class, 'store_id', 'store_id');

    }

}