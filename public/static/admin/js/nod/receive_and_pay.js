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
                limit:30,
                search:false,
                toolbar:['refresh'],
                limits:[30,50,100],
                cols: [[
                    {field: 'sale_user', minWidth: 180, title: '销售员'},
                    {field: 'name', minWidth: 180, title: '渠道|名字'},
                    {field: 'receivable_price', minWidth: 180, title: '应收款',templet:function (d) {
                            if (d.receivable_price < 0){
                                return ''
                            }
                            return d.receivable_price
                        }},
                    {field: 'receivable_price', minWidth: 180, title: '应付款',templet:function (d) {
                            if (d.receivable_price < 0){
                                return '<font color="red">'+-d.receivable_price+'</font>'
                            }
                            return ''
                        }},
                    {field: 'remark', minWidth: 180, title: '备注'},

                ]],
                done:function (data) {
                    let s = 0;
                    let f = 0;
                    data.data.forEach(function (item) {
                        if (parseInt(item['receivable_price'])>0){
                            s += parseInt(item['receivable_price'])
                        }
                        if (parseInt(item['receivable_price']) < 0){
                            f += parseInt(-item['receivable_price'])
                        }
                    })


                    $('#layui-table-page1').append(' <font color="red">总应收款余额:'+s+'  | 总应付款余额:'+f+'</font>')


                }

            });

            ea.listen();
        },


    };
    return Controller;
});