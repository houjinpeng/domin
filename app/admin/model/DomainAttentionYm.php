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

class DomainAttentionYm extends TimeModel
{

    protected $name = "domain_attention_ym";

    public function getLog(){
        return $this->belongsTo(DomainAttentionYmLog::class, 'ym', 'ym');

    }
}