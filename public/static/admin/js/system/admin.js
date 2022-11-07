define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'system.admin/index',
        add_url: 'system.admin/add',
        edit_url: 'system.admin/edit',
        delete_url: 'system.admin/delete',
        modify_url: 'system.admin/modify',
        export_url: 'system.admin/export',
        password_url: 'system.admin/password',
    };

    var Controller = {

        index: function () {

            ea.table.render({
                init: init,
                limit:15,
                limits:[15,30,50],
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', width: 80, title: 'ID'},
                    {field: 'username', minWidth: 80, title: '登录账户'},
                    {field: 'getGroupName.title', minWidth: 80, title: '所属分组',search: false,templet: function (d){
                        if (d.getGroupName){
                            return d.getGroupName.title
                        }else{
                            return '无'
                        }

                        }},
                    {field: 'login_num', minWidth: 80, title: '登录次数',sort:true},
                    {field: 'task_num', minWidth: 80, title: '添加任务数',sort:true,search: false},
                    {field: 'remark', minWidth: 80, title: '备注信息'},
                    {field: 'create_time', minWidth: 80, title: '创建时间', search: 'range'},
                    {
                        width: 250,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [
                            'edit',
                            [{
                                text: '设置密码',
                                url: init.password_url,
                                method: 'open',
                                auth: 'password',
                                class: 'layui-btn layui-btn-normal layui-btn-xs',
                            }],
                            'delete'
                        ]
                    }
                ]],
            });

            ea.listen();
        },
        add: function () {
            // var tree = layui.tree;
            //
            // ea.request.get(
            //     {
            //         url: 'get_group',
            //     }, function (res) {
            //         res.data = res.data || [];
            //         tree.render({
            //             elem: '#group_id',
            //             data: res.data,
            //             showCheckbox: false,
            //             onlyIconControl: true,
            //             accordion: true,
            //             id: 'nodeDataId',
            //         });
            //     }
            // );



            ea.listen();
        },
        edit: function () {
            ea.listen();
        },
        password: function () {
            ea.listen();
        }
    };
    return Controller;
});