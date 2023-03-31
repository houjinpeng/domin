define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'nod.config.sz_category/index',
        add_url: 'nod.config.sz_category/add',
        edit_url: 'nod.config.sz_category/edit',
        delete_url: 'nod.config.sz_category/delete',

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
                    {field: 'name', minWidth: 80, title: '分类名称'},
                    {field: 'remark', minWidth: 80, title: '备注信息'},
                    {
                        width: 250,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [
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