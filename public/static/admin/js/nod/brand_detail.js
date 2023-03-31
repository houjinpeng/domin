define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'nod.brand_detail/index',


    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                limit:30,
                search:false,
                toolbar:['refresh','add'],
                limits:[30,50,100],
                cols: [[
                    {field: 'name', minWidth: 180, title: '账号名称'},
                    {field: 'sr', minWidth: 180, title: '收入'},
                    {field: 'zc', minWidth: 180, title: '支出'},
                    {field: 'balance_price', minWidth: 180, title: '余额'},

                ]],
            });

            ea.listen();
        },


    };
    return Controller;
});