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

return [

    // 不需要验证登录的控制器
    'no_login_controller' => [
        'login',
        'api'
    ],

    // 不需要验证登录的节点
    'no_login_node'       => [
        'login/index',
        'login/out',
        'ajax/send_fs_msg',
        'ajax/upload_file',
        'website.task/index',
        'website.refresh/index',
        'website.refresh/refresh',
        'website.refresh_visitor/index',
        'website.charts_data/index',
        'domain.store/crawl_store',
        'domain.attention_ym/crawl_attention_channel',
        'domain.attention_ym/crawl_attention_data',
    ],

    // 不需要验证权限的控制器
    'no_auth_controller'  => [
        'ajax',
        'login',
        'index',
        'api',
        'website.task',
        'website.refresh',
        'website.refresh_visitor',
        'website.charts_data',
        'domain.store/crawl_store',
        'domain.attention_ym/crawl_attention_channel',
        'domain.attention_ym/crawl_attention_data',
    ],

    // 不需要验证权限的节点
    'no_auth_node'        => [
        'login/index',
        'login/out',
        'tool/get_group_user_id',
        'tool/get_current_group_id',
        'website.task/index',
        'website.refresh/index',
        'website.refresh/refresh',
        'website.refresh_visitor/index',
        'website.charts_data/index',
        'domain.store/crawl_store',
        'domain.attention_ym/crawl_attention_channel',
        'domain.attention_ym/crawl_attention_data',
    ],
];