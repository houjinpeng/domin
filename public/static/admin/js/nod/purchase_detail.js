define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'nod.purchase_detail/index',


    };

    var Controller = {

        index: function () {
            var sale_select_list = ea.getSelectList('NodSaleUser','id,name')
            var user_select_list = ea.getSelectList('SystemAdmin','id,username')
            var account_select_list = ea.getSelectList('NodAccount','id,name')
            var customer_select_list = ea.getSelectList('NodCustomerManagement','id,name')

            function bulid_select(select_list,field='name'){
                let se = {}
                select_list.forEach(function (item) {
                    se[item['id']] = item[field]
                })
                return se

            }
            if ($('#type').val() === 'sale'){
                ea.table.render({
                    init: init,
                    url:'/admin/nod.purchase_detail/index?type=sale',
                    limit:15,
                    toolbar:['refresh','add'],
                    limits:[15,30,50],
                    cols: [[
                        {field: 'operate_time', search:false,minWidth: 168, fixed:'left', title: '操作时间'},
                        {field: 'order_user_id', minWidth: 110, title: '经手人',selectList: bulid_select(user_select_list,'username'),templet:function (d) {
                                if (d.getOrderUser){
                                    return d.getOrderUser.username
                                } return ''
                            }},
                        {field: 'sale_user_id', minWidth: 180, title: '销售员',selectList: bulid_select(sale_select_list),templet:function (d) {
                                if (d.getSaleUser){
                                    return d.getSaleUser.name
                                } return ''
                            }},
                        {field: 'customer_id', minWidth: 180, title: '客户',selectList: bulid_select(customer_select_list),templet:function (d) {
                                if (d.getCustomer){
                                    return d.getCustomer.name
                                } return ''
                            }},
                        {field: 'category', minWidth: 180, title: '类型'},
                        {field: 'sm', minWidth: 400, title: '说明',search:false,align:'left',templet:function (d) {
                                if (d.category === '采购单'){
                                    return 'ID:'+ d.getAccount.name+' 购买域名:【'+d.good_name+'】 价格:'+-d.price+'元'
                                }else if (d.category === '销售单'){
                                    return 'ID:'+ d.getAccount.name+' 出售域名:【'+d.good_name+'】 价格:'+d.price+'元'
                                }else if (d.category === '付款单'){
                                    return 'ID:'+ d.getAccount.name+' 付款给客户【'+d.getCustomer.name+'】'+d.getCategory.name+'  '+-d.price+'元 '
                                }else if (d.category === '收款单'){
                                    return 'ID:'+ d.getAccount.name+' 收到客户【'+d.getCustomer.name+'】 '+d.getCategory.name+'  '+d.price+'元 '
                                }else if (d.category === '采购退货单'){
                                    return '退款 ID:'+ d.getAccount.name+' 购买域名:【'+d.good_name+'】 价格:'+d.price+'元'
                                }else if (d.category === '销售退货单'){
                                    return '退款 ID:'+ d.getAccount.name+' 出售域名:【'+d.good_name+'】 价格:'+-d.price+'元'
                                }
                            }},
                        {field: 'price', minWidth: 152, title: '变动',search: false,templet:function (d) {
                                if (d.category === '采购单' || d.category === '付款单' || d.category === '销售退货单'){
                                    return '<font color="red">'+(d.price)+'</font>'
                                }
                                return d.price
                            }},
                        {field: 'account_id', minWidth: 110,title: '账号',selectList: bulid_select(account_select_list),templet:function (d) {
                                if ( d.getAccount){
                                    return d.getAccount.name
                                } return ''
                            }},
                        {field: 'balance_price', minWidth: 152, title: '账号余额',search: false},
                        {field: 'all_balance_price', minWidth: 152, title: '总资金剩额',search: false},
                        {field: 'remark', minWidth: 180, title: '备注'},
                    ]],
                    done:function (data) {
                        $('#layui-table-page1').append('     <font color="red">销售总数量:'+$('#total_stock_count').val()+'  | 销售总金额:'+parseInt($('#total_stock_price').val())+'  | 退货总数量:'+$('#total_sale_count').val()+' |销售退货总金额:'+$('#total_sale_price').val()+'</font>')


                    }
                });
            }else{
                ea.table.render({
                    init: init,
                    limit:15,
                    toolbar:['refresh','add'],
                    limits:[15,30,50],
                    cols: [[
                        {field: 'operate_time', search:false,minWidth: 168, fixed:'left', title: '操作时间'},
                        {field: 'order_user_id', minWidth: 110, title: '经手人',selectList: bulid_select(user_select_list,'username'),templet:function (d) {
                                if (d.getOrderUser){
                                    return d.getOrderUser.username
                                } return ''
                            }},
                        {field: 'category', minWidth: 180, title: '类型'},
                        {field: 'sm', minWidth: 400, title: '说明',align:'left',templet:function (d) {
                                if (d.category === '采购单'){
                                    return 'ID:'+ d.getAccount.name+' 购买域名:【'+d.good_name+'】 价格:'+-d.price+'元'
                                }else if (d.category === '采购退货单'){
                                    return '退款 ID:'+ d.getAccount.name+' 购买域名:【'+d.good_name+'】 价格:'+d.price+'元'
                                }
                            }},
                        {field: 'price', minWidth: 152, title: '变动',search: false,templet:function (d) {
                                if (d.category === '采购单' || d.category === '付款单' || d.category === '销售退货单'){
                                    return '<font color="red">'+(d.price)+'</font>'
                                }
                                return d.price
                            }},
                        {field: 'account_id', minWidth: 110,title: '账号',selectList: bulid_select(account_select_list),templet:function (d) {
                                if ( d.getAccount){
                                    return d.getAccount.name
                                } return ''
                            }},
                        {field: 'balance_price', minWidth: 152, title: '账号余额',search: false},
                        {field: 'all_balance_price', minWidth: 152, title: '总资金剩额',search: false},
                        {field: 'remark', minWidth: 180, title: '备注'},

                    ]],
                    done:function (data) {
                        $('#layui-table-page1').append('     <font color="red">采购总数量:'+$('#total_stock_count').val()+'  | 采购总金额:'+-parseInt($('#total_stock_price').val())+'  | 退货总数量:'+$('#total_sale_count').val()+' |采购退货总金额:'+$('#total_sale_price').val()+'</font>')

                    }
                });
            }



            ea.listen();
        },


    };
    return Controller;
});