define(["jquery", "easy-admin"], function ($, ea) {


    var show_init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'nod.statement_analysis.inventory/index',

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
                limits:[50,100,200,500,1000],
                toolbar:['refresh'],
                cols: [[
                    {type: "checkbox"},
                    {field: 'good_name', minWidth: 152, title: '商品名称'},
                    // {field: 'expiration_time', search:false,minWidth: 130, title: '过期时间'},
                    // {field: 'register_time', search:false, minWidth: 130, title: '注册时间'},
                    {field: 'unit_price', search:false, minWidth: 100, title: '成本价'},
                    {field: 'register_time', search:false, minWidth: 140, title: '注册时间'},
                    {field: 'expiration_time', search:false, minWidth: 140, title: '到期时间'},
                    {field: 'unit_price', search:false, minWidth: 140, title: '注册商'},
                    {field: 'warehouse_id', minWidth: 110, title: '仓库',selectList: bulid_select(warehouse_select_list),templet:function (d) {
                        if ( d.getWarehouse){
                            return d.getWarehouse.name
                        } return ''

                        }},

                    {field: 'supplier_id', minWidth: 110, title: '来源渠道',selectList: bulid_select(supplier_select_list),templet:function (d) {
                            if ( d.getSupplier){
                                return d.getSupplier.name
                            }
                            return ''
                        }},
                    {field: 'remark', minWidth: 180, title: '备案'},
                    {field: 'remark', minWidth: 80, title: '百度'},
                    {field: 'remark', minWidth: 80, title: '搜狗'},
                    {field: 'remark', minWidth: 100, title: '备注信息'},
                    // {field: 'create_time', minWidth: 180, title: '操作时间',search: 'range'},

                ]],
                done:function () {
                    $('#layui-table-page1').append('     <font color="red">当前库存数量: '+$('#total_count').val()+'条  库存总金额:'+$('#total_price').val()+'元</font>')
                }
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