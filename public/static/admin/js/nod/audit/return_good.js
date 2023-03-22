define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'nod.audit.purchase/index',
        audit_url: 'nod.audit.purchase/audit',


    };

    var Controller = {

        audit: function () {
            var laydate = layui.laydate;
            var table = layui.table;
            var type = $('#type').val()

            laydate.render({
                elem: '#order_time' //指定元素
                ,type: 'datetime'
            });

            var good_list = JSON.parse($('#all_good').val())
            var good_l = []
            good_list.forEach(function (item,index) {
                item['index'] = index+1
                good_l.push(item)
            })

            if (type === 'sale'){
                //初始化表格
                table.render({
                    elem: '#order_table'
                    ,height: 'full-300'
                    ,limit:10000
                    ,page: false //开启分页
                    ,cols: [[ //表头
                        {field: 'index', title: '列', width:70}
                        ,{field: 'id', title: 'ID', width:70}
                        ,{field: 'good_name', title: '商品信息', minWidth:180,edit:true}
                        ,{field: 'unit_price', title: '退货单价', minWidth:110,edit:true}
                        ,{field: 'remark', title: '备注信息', minWidth: 110,edit:true}

                    ]]
                    ,data:good_l

                });

            }
            else{
                //初始化表格
                table.render({
                    elem: '#order_table'
                    ,height: 'full-300'
                    ,limit:10000
                    ,page: false //开启分页
                    ,cols: [[ //表头
                        {field: 'index', title: '列', width:70}
                        ,{field: 'id', title: 'ID', width:70}
                        ,{field: 'good_name', title: '商品信息', minWidth:180,edit:true}
                        ,{field: 'unit_price', title: '退货单价', minWidth:110,edit:true}
                        ,{field: 'remark', title: '备注信息', minWidth: 110,edit:true}
                    ]]
                    ,data:good_l

                });

            }

            //快捷录入单据金额
            $('#jk_price').click(function () {
                let all_data = table.cache['order_table']
                let total_pirce = 0
                all_data.forEach(function (item) {
                    total_pirce += parseInt(item['unit_price'])
                })

                $('#practical_price').val(total_pirce)

            })


            ea.listen(function (data) {
                data['goods'] = table.cache['order_table']

                return {data:JSON.stringify(data)}

            });
        },


    };
    return Controller;
});