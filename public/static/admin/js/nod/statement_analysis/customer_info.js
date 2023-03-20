define(["jquery", "easy-admin"], function ($, ea) {


    var show_init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'nod.statement_analysis.customer_info/index',

    };

    var Controller = {

        index: function () {
            var warehouse_select_list = ea.getSelectList('NodWarehouse','id,name')
            var account_select_list = ea.getSelectList('NodAccount','id,name')
            var supplier_select_list = ea.getSelectList('NodSupplier','id,name')
            var customer_select_list = ea.getSelectList('NodCustomerManagement','id,name')
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
                height:'full-40',
                limits:[50,100,200],
                toolbar:['refresh'],
                cols: [[
                    {field: 'customer_id', minWidth: 110, fixed:'left',title: '客户',selectList: bulid_select(customer_select_list),templet:function (d) {
                            if ( d.getCustomer){
                                return d.getCustomer.name
                            } return ''
                        }},
                    // {field: 'unit_price', minWidth: 152, title: '交易金额', fixed:'left',search: false},
                    {field: 'total_price', minWidth: 152, title: '交易金额', fixed:'left',search: false},
                    {field: 'sale_user_id', minWidth: 100, fixed:'left', title: '销售人',templet:function (d) {
                            if (d.getSaleUser){
                                return d.getSaleUser.username
                            }
                            return d.category
                        }},
                    {field: 'category', minWidth: 80, title: '类型',templet:function (d) {
                            if (d.getCategory){
                                return d.getCategory.name
                            }
                            return d.category
                        }},

                    {field: 'pid', minWidth: 180, title: '单据编号',search: false,templet:function (d) {
                            if (d.getOrder){
                                return d.getOrder.order_batch_num
                            } return ''

                        }},
                    {field: 'pid', minWidth: 182, title: '单据日期',search: 'range',templet:function (d) {
                            if (d.getOrder){
                                return d.getOrder.order_time
                            } return ''

                        }},
                    {field: 'warehouse_id', minWidth: 110, title: '仓库',selectList: bulid_select(warehouse_select_list),templet:function (d) {
                        if ( d.getWarehouse){
                            return d.getWarehouse.name
                        } return ''

                        }},
                    {field: 'supplier_id', minWidth: 110, title: '供应商',selectList: bulid_select(supplier_select_list),templet:function (d) {
                            if ( d.getSupplier){
                                return d.getSupplier.name
                            }
                            return ''
                        }},
                    // {field: 'customer_id', minWidth: 110, title: '客户',selectList: bulid_select(customer_select_list),templet:function (d) {
                    //         if ( d.getCustomer){
                    //             return d.getCustomer.name
                    //         }
                    //         return ''
                    //     }},
                    {field: 'remark', minWidth: 100, title: '备注信息',search: false},

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