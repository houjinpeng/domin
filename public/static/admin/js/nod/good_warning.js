define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'nod.good_warning/index',


    };

    var Controller = {

        index: function () {
            var warehouse_select_list = ea.getSelectList('NodWarehouse','id,name')
            var supplier_select_list = ea.getSelectList('NodSupplier','id,name')
            function bulid_select(select_list,field='name'){
                let se = {}
                select_list.forEach(function (item) {
                    se[item['id']] = item[field]
                })
                return se

            }
            // 验证输入是否是数字
            layui.form.verify({
                number: function (val) {
                    if (val === "" || val == null) {
                        return false;
                    }
                    if (!isNaN(val)) {
                    } else {
                        return '请填写数字'
                    }
                }
            })

            ea.table.render({
                init: init,
                limit:50,
                height:'full-40',
                limits:[50,100,200,500,1000],
                toolbar:['refresh'],
                cols: [[
                    {type: "checkbox"},
                    {field: 'good_name', minWidth: 152, title: '商品名称',search: 'batch'},
                    {field: 'unit_price', search:'section', minWidth: 100, title: '成本价'},
                    {field: 'dqsj', search:false, minWidth: 100, title: '到期天数',hide:true},
                    {field: 'register_time', search:false, minWidth: 140, title: '注册时间'},
                    {field: 'expiration_time', search:false, minWidth: 140, title: '到期时间',templet:function (d) {
                            if (!d['expiration_time']) return ''
                            var date1=new Date();
                            var date2=new Date(d['expiration_time']);
                            return parseInt((date2.getTime() - date1.getTime())/(1000*60*60*24))
                        }},
                    {field: 'zcs', minWidth: 140, title: '注册商'},
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
                    {field: 'beian', minWidth: 180, title: '备案'},
                    {field: 'baidu', minWidth: 80, title: '百度'},
                    {field: 'sogou', minWidth: 80, title: '搜狗'},
                    {field: 'remark', minWidth: 100, title: '备注信息'},
                    // {field: 'create_time', minWidth: 180, title: '操作时间',search: 'range'},

                ]],
                done:function () {
                    $('#layui-table-page1').append('     <font color="red">当前库存数量: '+$('#total_count').val()+'条  库存总金额:'+$('#total_price').val()+'元</font>')
                }
            });

            ea.listen();
        },


    };
    return Controller;
});