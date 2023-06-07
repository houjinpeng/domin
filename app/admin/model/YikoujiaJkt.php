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

class YikoujiaJkt extends TimeModel
{

    protected $name = "yikoujia_jkt";

    public function getGroup(){
        return $this->belongsTo(DomainGroup::class, 'group_id', 'id');

    }
}