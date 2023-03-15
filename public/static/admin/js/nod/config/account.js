define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'nod.config.account/index',
        add_url: 'nod.config.account/add',
        edit_url: 'nod.config.account/edit',
        delete_url: 'nod.config.account/delete',

    };

    var Controller = {

        index: function () {

            ea.table.render({
                init: init,
                limit:15,
                toolbar:['refresh','add','delete'],
                limits:[15,30,50],
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', width: 80, title: '编号'},
                    {field: 'name', minWidth: 80, title: '账户名'},
                    {field: 'init_price', minWidth: 80, title: '初始余额'},
                    {field: 'balance_price', minWidth: 80, title: '余额'},
                    {field: 'remark', minWidth: 80, title: '备注信息'},
                    {field: 'create_time', minWidth: 200, title: '开户时间', search: 'range'},
                    {
                        width: 250,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [
                            [{
                                text: '收支明细',
                                url: init.edit_url,
                                method: 'open',
                                auth: 'edit',
                                class: 'layui-btn layui-btn-xs layui-btn-primary',
                                extend: 'data-full="true"',
                            }],'edit',
                            'delete'
                        ]
                    }
                ]],
            });

            ea.listen();
        },


        add: function () {

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