define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'nod.config.customer_management/index',
        add_url: 'nod.config.customer_management/add',
        edit_url: 'nod.config.customer_management/edit',
        delete_url: 'nod.config.customer_management/delete',

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
                    {field: 'name', minWidth: 80, title: '客户名'},
                    // {field: 'receivable_price', minWidth: 80, title: '应收款'},
                    {field: 'phone', minWidth: 80, title: '联系方式'},
                    {field: 'remark', minWidth: 80, title: '备注信息'},
                    {
                        width: 250,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [
                            // [{
                            //     text: '交易记录',
                            //     url: init.edit_url,
                            //     method: 'open',
                            //     auth: 'edit',
                            //     class: 'layui-btn layui-btn-xs layui-btn-primary',
                            //     extend: 'data-full="true"',
                            // }],
                            'edit',
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