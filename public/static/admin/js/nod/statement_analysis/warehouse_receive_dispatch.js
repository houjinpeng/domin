define(["jquery", "easy-admin"], function ($, ea) {


    var show_init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'nod.statement_analysis.warehouse_receive_dispatch/index',

    };

    var Controller = {

        index: function () {
            var warehouse_select_list = ea.getSelectList('NodWarehouse','id,name')
            var account_select_list = ea.getSelectList('NodAccount','id,name')
            var supplier_select_list = ea.getSelectList('NodSupplier','id,name')
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
                    {type: "checkbox"},
                    {field: 'warehouse_id', minWidth: 110, title: '仓库',selectList: bulid_select(warehouse_select_list),templet:function (d) {
                        if ( d.getWarehouse){
                            return d.getWarehouse.name
                        } return ''

                        }},
                    {field: 'type', minWidth: 120, title: '类型',selectList:{'1':'采购单','2':'采购退货单','3':'销货单','6':'销售退货单','7':'调拨单'},templet:function (d) {
                            if (d.type === 1){
                                return '采购单'
                            }if (d.type === 2){
                                return '采购退货单'
                            }if (d.type === 3){
                                return '销货单'
                            }if (d.type === 6){
                                return '销售退货单'
                            }if (d.type === 7){
                                return '调拨单'
                            }
                        }},
                    {field: 'pid', minWidth: 180,search: false, title: '单据编号',templet:function (d) {
                            if (d.getOrder){
                                return d.getOrder.order_batch_num
                            } return ''

                        }},
                    {field: 'pid', minWidth: 182, title: '单据日期',search: false,templet:function (d) {
                            if (d.getOrder){
                                return d.getOrder.order_time
                            } return ''

                        }},

                    {field: 'good_name', minWidth: 152, title: '商品名称'},
                    {field: 'expiration_time', search:false,minWidth: 130, title: '过期时间'},
                    {field: 'register_time', search:false, minWidth: 130, title: '注册时间'},
                    {field: 'sale_time', search:false, minWidth: 130, title: '出售时间'},
                    {field: 'unit_price', search:false, minWidth: 100, title: '价格'},
                    {field: 'account_id', minWidth: 110, title: '账号',selectList: bulid_select(account_select_list),templet:function (d) {
                            if ( d.getAccount){
                                return d.getAccount.name
                            } return ''
                        }},
                    {field: 'supplier_id', minWidth: 110, title: '来源渠道',selectList: bulid_select(supplier_select_list),templet:function (d) {
                            if ( d.getSupplier){
                                return d.getSupplier.name
                            }
                            return ''
                        }},

                    {field: 'remark', minWidth: 100, title: '备注信息'},
                    {field: 'create_time', minWidth: 180, title: '操作时间',search: 'range'},

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