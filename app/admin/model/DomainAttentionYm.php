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

    public function getLogs(){
        return $this->hasMany(DomainAttentionYmLog::class, 'ym','ym');

    }

    public function getStore(){
        return $this->belongsTo(DomainStore::class, 'store_id', 'store_id');

    }
}