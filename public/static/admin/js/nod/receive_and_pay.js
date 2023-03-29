define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'nod.receive_and_pay/index',


    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                limit:15,
                search:false,
                toolbar:['refresh'],
                limits:[15,30,50],
                cols: [[
                    {field: 'name', minWidth: 180, title: '渠道|名字'},
                    {field: 'receivable_price', minWidth: 180, title: '应收款',templet:function (d) {
                            if (d.receivable_price < 0){
                                return '<font color="red">'+d.receivable_price+'</font>'
                            }
                            return d.receivable_price
                        }},
                    {field: 'remark', minWidth: 180, title: '备注'},

                ]],
            });

            ea.listen();
        },


    };
    return Controller;
});