define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'domain_reserve.aliyun_yd/index',
        add_url: 'domain_reserve.aliyun_yd/add',
        edit_url: 'domain_reserve.aliyun_yd/edit',
        delete_url: 'domain_reserve.aliyun_yd/delete',
        info_url: 'domain_reserve.aliyun_yd/info',

    };

    var info_init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'domain_reserve.aliyun_yd/info',
    };


    var Controller = {

        index: function () {

            ea.table.render({
                init: init,
                limit:30,
                toolbar:['refresh','add','delete'],
                limits:[30,50,100],
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', width: 80, title: '编号'},
                    {field: 'title', minWidth: 123, title: '名称'},
                    {field: 'admin', minWidth: 123, title: '操作人',search:false,templet: function (d) {
                            return d.admin ? d.admin.username: ''
                        }},
                    {
                        width: 170,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [
                            [{
                                text: '详情',
                                url: init.info_url,
                                method: 'open',
                                auth: 'info_url',
                                class: 'layui-btn layui-btn-xs layui-btn-primary',
                                extend: 'data-full="true"',
                            }],
                            'edit','delete'
                        ]
                    }
                ]],
            });

            ea.listen();
        },


        add: function () {
            var laydate = layui.laydate;

            //执行一个laydate实例
            laydate.render({
                elem: '#test1' //指定元素
                ,type: 'datetime'
            });

            ea.listen();
        },
        edit: function () {
            var laydate = layui.laydate;

            var test1_value = $('test1').attr('value')

            //执行一个laydate实例
            laydate.render({
                elem: '#test1' //指定元素
                ,type: 'datetime'
                ,value:test1_value
            });

            ea.listen();
        },


        info: function () {

            var batch_id = $('#batch_id').val()
            info_init.index_url += '?batch_id='+batch_id

            ea.table.render({
                init: info_init,
                index_url: '1231233',
                limit:100000,
                toolbar:['refresh'],
                // limits:[30,50,100],
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', width: 80, title: '编号'},
                    {field: 'ym', minWidth: 123, title: '域名'},
                    {field: 'remark', minWidth: 123, title: '备注'},
                    {field: 'status', minWidth: 123, title: '状态',selectList:{0:'待运行',1:'运行中',2:'已添加',3:'失败'},templet:function (d) {
                            if (d.status==0){
                                return '待运行'
                            }
                            else if (d.status==1){
                                return '运行中'
                            }
                            else if (d.status==2){
                                return '<font color="green">已添加</font>'
                            }else if (d.status==3){
                                return '<font color="red">失败</font>'
                            }

                        }},
                    {field: 'error_msg', minWidth: 123, title: '失败信息',templet:function (d) {
                        if (d.error_msg == null) return ''
                            return '<font color="red">'+d.error_msg+'</font>'
                        }},
                    //
                    // {
                    //     width: 170,
                    //     title: '操作',
                    //     templet: ea.table.tool,
                    //     operat: [
                    //         [{
                    //             text: '详情',
                    //             url: init.info_url,
                    //             method: 'open',
                    //             auth: 'info_url',
                    //             class: 'layui-btn layui-btn-xs layui-btn-primary',
                    //             extend: 'data-full="true"',
                    //         }],
                    //         'edit','delete'
                    //     ]
                    // }
                ]],
            });
            ea.listen();
        }
    };
    return Controller;
});