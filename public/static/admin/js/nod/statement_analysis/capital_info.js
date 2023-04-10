define(["jquery", "easy-admin"], function ($, ea) {


    var show_init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'nod.statement_analysis.capital_info/index',

    };

    var Controller = {

        index: function () {
            var account_select_list = ea.getSelectList('NodAccount','id,name')
            var admin_list = ea.getSelectList('SystemAdmin','id,username')
            function bulid_select(select_list,field='name'){
                let se = {}
                select_list.forEach(function (item) {
                    se[item['id']] = item[field]
                })
                return se

            }


            ea.table.render({
                init: show_init,
                limit:50,
                align:'left',
                height:'full-40',
                limits:[50,100,200],
                toolbar:['refresh'],
                cols: [[
                    {field: 'operate_time', search:'range',minWidth: 168, fixed:'left', title: '操作时间'},

                    {field: 'order_user_id', minWidth: 110, title: '经手人',selectList: bulid_select(admin_list,'username'),templet:function (d) {
                            if ( d.getOrderUser){
                                return d.getOrderUser.username
                            } return ''
                        }},
                    {field: 'type', minWidth: 120, title: '类型',selectList:{'1':'采购单','2':'采购退货单','3':'销售单','4':'付款单','5':'收款单','6':'销售退货单','9':'其他收入单'
                            ,'8':'费用单','10':'提现转移单'}},
                    {field: 'sm', minWidth: 400, title: '说明',search: false,align:'left',templet:function (d) {
                            if (d.type === 1){
                                return 'ID:'+ d.getAccount.name+' 购买域名:【'+d.good_name+'】 价格:'+-d.price+'元'
                            }else if (d.type === 3){
                                return 'ID:'+ d.getAccount.name+' 出售域名:【'+d.good_name+'】 价格:'+d.price+'元'
                            }else if (d.type === 5){
                                return 'ID:'+ d.getAccount.name+' 付款给客户【'+d.getCustomer.name+'】'+d.getCategory.name+'  '+-d.price+'元 '
                            }else if (d.type === 4){
                                return 'ID:'+ d.getAccount.name+' 收到客户【'+d.getCustomer.name+'】 '+d.getCategory.name+'  '+d.price+'元 '
                            }else if (d.type === 2){
                                return '退款 ID:'+ d.getAccount.name+' 购买域名:【'+d.good_name+'】 价格:'+d.price+'元'
                            }else if (d.type === 6){
                                return '退款 ID:'+ d.getAccount.name+' 出售域名:【'+d.good_name+'】 价格:'+-d.price+'元'
                            }else if (d.type === 8){
                                return 'ID:'+ d.getAccount.name+' 付款给客户【'+d.getCustomer.name+'】'+d.getCategory.name+'  '+-d.price+'元 '
                            }else if (d.type === 9){
                                return 'ID:'+ d.getAccount.name+' 收到客户【'+d.getCustomer.name+'】 '+d.getCategory.name+'  '+d.price+'元 '
                            }else if (d.type === 10){
                                return 'ID:'+ d.getAccount.name+' 收到转移 '+d.price+'元 '
                            }
                        }},
                    {field: 'price', minWidth: 152, title: '变动',search: false,templet:function (d) {
                            if (d.category === '采购单' || d.category === '付款单' || d.category === '销售退货单'|| d.category === '费用单'){
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
                    {field: 'remark', minWidth: 152,align:'left', title: '备注信息'},
                    // {field: 'pid', minWidth: 180, title: '单据编号',search: false,templet:function (d) {
                    //         if (d.getOrder){
                    //             return d.getOrder.order_batch_num
                    //         } return ''
                    //
                    //     }},
                    // {field: 'warehouse_id', minWidth: 110, title: '仓库',selectList: bulid_select(warehouse_select_list),templet:function (d) {
                    //     if ( d.getWarehouse){
                    //         return d.getWarehouse.name
                    //     } return ''
                    // }},
                    // {field: 'supplier_id', minWidth: 110, title: '来源渠道',selectList: bulid_select(supplier_select_list),templet:function (d) {
                    //         if ( d.getSupplier){
                    //             return d.getSupplier.name
                    //         }
                    //         return ''
                    //     }},
                    // {field: 'customer_id', minWidth: 110, title: '客户',selectList: bulid_select(customer_select_list),templet:function (d) {
                    //         if ( d.getCustomer){
                    //             return d.getCustomer.name
                    //         }
                    //         return ''
                    //     }},

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
        show: function () {





            ea.listen();
        }
    };
    return Controller;
});