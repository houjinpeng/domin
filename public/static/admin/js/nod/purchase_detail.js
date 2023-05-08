define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'nod.purchase_detail/index',
        export_url: 'nod.purchase_detail/export',


    };
    var sale_init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'nod.purchase_detail/index',
        export_url: 'nod.purchase_detail/export?type=sale',


    };

    function set_red_font(data){

        return '<font color="red">'+data+'</font>'

    }

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
                    init: sale_init,
                    url:'/admin/nod.purchase_detail/index?type=sale',
                    limit:30,
                    page:true,
                    height:'full-40',
                    toolbar:['refresh','export'],
                    limits:[30,50,100],
                    cols: [[
                        {field: 'operate_time', search:'range',minWidth: 168, fixed:'left', title: '操作时间'},
                        {field: 'good_name', search:'batch', searchOp:'in',minWidth: 168, title: '域名'},
                        {field: 'order_user_id', minWidth: 110, title: '经手人',selectList: bulid_select(user_select_list,'username'),templet:function (d) {
                                if (d.getOrderUser){
                                    return d.getOrderUser.username
                                } return ''
                            }},
                        {field: 'sale_user_id', minWidth: 180, title: '销售员',selectList: bulid_select(user_select_list,'username'),templet:function (d) {
                                if (d.getSaleUser){
                                    return d.getSaleUser.username
                                } return ''
                            }},
                        {field: 'customer_id', minWidth: 180, title: '客户',selectList: bulid_select(customer_select_list),templet:function (d) {
                                if (d.getCustomer){
                                    return d.getCustomer.name
                                } return ''
                            }},
                        {field: 'category', minWidth: 180, title: '类型',templet:function (d) {
                            if (d.type === 1){
                                return '采购单'
                            }else if(d.type===2){
                                return '采购退货单'
                            }else if(d.type===3){
                                return '销售单'
                            }else if(d.type===4){
                                return '付款单'
                            }else if(d.type===5){
                                return '收款单'
                            }else if(d.type===6){
                                return '销售退货单'
                            }else if(d.type===7){
                                return '调拨单'
                            }else if(d.type===7){
                                return '调拨单'
                            }else if(d.type===8){
                                return '费用单'
                            }else if(d.type===9){
                                return '其他收入单'
                            }
                            return d.category
                        }},
                        {field: 'sm', minWidth: 400, title: '说明',search:false,align:'left',templet:function (d) {
                                if (d.type === 3){
                                    return 'ID:'+ d.getAccount.name+' 出售域名:【'+d.good_name+'】 价格:'+d.practical_price+'元'
                                }else if (d.type === 6){
                                    return '退款 ID:'+ d.getAccount.name+' 出售域名:【'+d.good_name+'】 价格:'+d.practical_price+'元'
                                }else if (d.category === '费用单'){
                                    return 'ID:'+ d.getAccount.name+' 付款给客户【'+d.getCustomer.name+'】'+d.getCategory.name+'  '+-d.practical_price+'元 '
                                }


                            }},
                        {field: 'account_id', minWidth: 190,title: '账号',selectList: bulid_select(account_select_list),templet:function (d) {
                                if (d.category ==='应收款'){
                                    return '应收款->'+d.getCustomer.name
                                }
                                if ( d.getAccount){
                                    return d.getAccount.name
                                } return ''
                            }},
                        {field: 'price', minWidth: 152, title: '变动',search: false,templet:function (d) {
                                if (d.category === '采购单' || d.category === '付款单' || d.category === '销售退货单'|| d.category === '费用单'){
                                    return '<font color="red">'+(d.price)+'</font>'
                                }
                                return d.price
                            }},
                        {field: 'balance_price', minWidth: 152, title: '账号余额',search: false,templet:function (d) {
                                // if (d.category ==='应收款'){
                                //     return d.customer_receivable_price
                                // }
                                return d.balance_price
                            }},
                        {field: 'all_balance_price', minWidth: 152, title: '总资金剩额',search: false,templet:function (d) {
                                // if (d.category ==='应收款'){
                                //     return d.total_customer_receivable_price
                                // }
                                return d.all_balance_price
                            }},
                        {field: 'customer_receivable_price', minWidth: 152, title: '应收款',search: false,templet:function (d) {
                                if (d.customer_receivable_price < 0){
                                    return set_red_font(d.customer_receivable_price)
                                }
                                return d.customer_receivable_price
                            }},
                        {field: 'total_customer_receivable_price', minWidth: 152, title: '总应收款',search: false,templet:function (d) {
                                if (d.total_customer_receivable_price < 0){
                                    return set_red_font(d.total_customer_receivable_price)
                                }
                                return d.total_customer_receivable_price
                            }},
                        {field: 'remark', minWidth: 180, title: '备注'},
                        {field: 'is_compute_profit', minWidth: 180, title: '是否付款',templet:function (d) {
                                if (d.is_compute_profit === 1){
                                    return '是'
                                }
                                return '<font color="red">否</font>'
                            }},
                    ]],
                    done:function (data) {
                        $('#layui-table-page1').append('     <font color="red">销售总数量:'+$('#total_stock_count').val()+'  | 销售总金额:'+parseInt($('#total_stock_price').val())+'  | 退货总数量:'+$('#total_sale_count').val()+' |销售退货总金额:'+$('#total_sale_price').val()+'</font>')


                    }
                });
            }else{
                ea.table.render({
                    init: init,
                    limit:30,
                    height:'full-40',
                    toolbar:['refresh','export'],
                    limits:[30,50,100],
                    cols: [[
                        {field: 'operate_time', search:'range',minWidth: 168, fixed:'left', title: '操作时间'},
                        {field: 'good_name',hide:true, search:'batch', searchOp:'in',minWidth: 168, title: '域名'},

                        {field: 'order_user_id', minWidth: 110, title: '经手人',selectList: bulid_select(user_select_list,'username'),templet:function (d) {
                                if (d.getOrderUser){
                                    return d.getOrderUser.username
                                } return ''
                            }},
                        {field: 'type', minWidth: 180, title: '类型',templet:function (d) {
                                if (d.type === 1){
                                    return '采购单'
                                }else if(d.type===2){
                                    return '采购退货单'
                                }else if(d.type===3){
                                    return '销售单'
                                }else if(d.type===4){
                                    return '付款单'
                                }else if(d.type===5){
                                    return '收款单'
                                }else if(d.type===6){
                                    return '销售退货单'
                                }else if(d.type===7){
                                    return '调拨单'
                                }else if(d.type===7){
                                    return '调拨单'
                                }else if(d.type===8){
                                    return '费用单'
                                }else if(d.type===9){
                                    return '其他收入单'
                                }
                                return d.category
                            }},
                        {field: 'sm', minWidth: 400, title: '说明',align:'left',templet:function (d) {
                                if (d.type === 1){
                                    return 'ID:'+ d.getAccount.name+' 购买域名:【'+d.good_name+'】 价格:'+-d.price+'元'
                                }else if (d.type === 2){
                                    return '退款 ID:'+ d.getAccount.name+' 购买域名:【'+d.good_name+'】 价格:'+d.price+'元'
                                }

                            }},
                        {field: 'account_id', minWidth: 190,title: '账号',selectList: bulid_select(account_select_list),templet:function (d) {
                                if (d.category ==='应付款' || d.category ==='应收款'){
                                    return d.category +'->'+d.getSupplier.name
                                }
                                if (d.getAccount){
                                    return d.getAccount.name
                                }
                                return ''
                            }},
                        {field: 'price', minWidth: 152, title: '变动',search: false,templet:function (d) {
                                if (d.category === '采购单' || d.category === '付款单' || d.category === '销售退货单' || d.category === '应付款' ){
                                    return '<font color="red">'+(d.price)+'</font>'
                                }
                                return d.price
                            }},
                        {field: 'balance_price', minWidth: 152, title: '账号余额',search: false,templet:function (d) {
                                if (d.balance_price < 0){
                                    return set_red_font(d.balance_price)
                                }
                                return d.balance_price
                            }},
                        {field: 'all_balance_price', minWidth: 152, title: '总资金剩额',search: false,templet:function (d) {

                                return d.all_balance_price
                            }},
                        {field: 'customer_receivable_price', minWidth: 152, title: '应收款',search: false,templet:function (d) {
                                if (d.customer_receivable_price < 0){
                                    return set_red_font(d.customer_receivable_price)
                                }
                                return d.customer_receivable_price
                            }},
                        {field: 'total_customer_receivable_price', minWidth: 152, title: '总应收款',search: false,templet:function (d) {
                                if (d.total_customer_receivable_price < 0){
                                    return set_red_font(d.total_customer_receivable_price)
                                }
                                return d.total_customer_receivable_price
                            }},
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