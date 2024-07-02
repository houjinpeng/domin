<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        'curd'      => 'app\common\command\Curd',
        'node'      => 'app\common\command\Node',
        'OssStatic' => 'app\common\command\OssStatic',
        'yuding_ym' => 'app\command\YudingYm',
        'yuding_ym_aliyun' => 'app\command\YudingYmAliyun',
    ],
];
