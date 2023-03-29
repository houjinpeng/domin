define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'nod.audit.receipt_and_payment/index',
        audit_url: 'nod.audit.receipt_and_payment/audit',


    };

    var Controller = {
        index:function () {
            ea.table.render({
                init: init,
                limit:15,
                toolbar:['refresh'],
                limits:[15,30,50],
                cols: [[
                    {type: "checkbox"},
                    {field: 'order_batch_num', minWidth: 180, title: '单据编号'},
                    {field: 'order_time', minWidth: 180, title: '单据时间'},
                    {field: 'order_user_id', minWidth: 90, title: '制单人',templet: function (d) {
                            return d.getOrderUser['username']
                        }},
                    {field: 'supplier_id', minWidth: 100, title: '来源渠道',templet: function (d) {
                            return d.getSupplier['name']
                        }},
                    {field: 'warehouse_id', minWidth: 100, title: '仓库',templet: function (d) {
                            return d.getWarehouse['name']
                        }},
                    {field: 'account_id', minWidth: 120, title: '结算账户',templet: function (d) {
                            return d.getAccount['name']
                        }},
                    {field: 'practical_price', minWidth: 100, title: '单据金额'},
                    {field: 'paid_price', minWidth: 100, title: '实付金额'},
                    {field: 'remark', minWidth: 180, title: '备注'},

                    {
                        fixed:'right',
                        width: 80,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [
                            [{
                                text: '审核',
                                url: init.audit_url,
                                method: 'open',
                                auth: 'edit',
                                class: 'layui-btn layui-btn-xs',
                                extend: 'data-full="true"',
                            }]

                        ]
                    }
                ]],
            });

            ea.listen()
        },

        audit: function () {
            var laydate = layui.laydate;
            var table = layui.table;
            var type = $('#type').val()
            function check_number(value) {
                return !isNaN(parseFloat(value)) && isFinite(value);

            }

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

            if (type === 'receipt'){
                //初始化表格
                table.render({
                    elem: '#order_table'
                    ,height: 'full-300'
                    ,limit:10000
                    ,page: false //开启分页
                    ,cols: [[ //表头
                        {field: 'index', title: '列', width:70}
                        ,{field: 'id', title: 'ID', width:70}
                        , {field: 'category', title: '收款类别', minWidth: 180, edit: true}
                        , {field: 'unit_price', title: '收款金额', minWidth: 110, edit: true}
                        , {field: 'remark', title: '备注信息', minWidth: 110, edit: true}

                    ]]
                    ,data:good_l

                });

            }else{
                //初始化表格
                table.render({
                    elem: '#order_table'
                    ,height: 'full-300'
                    ,limit:10000
                    ,page: false //开启分页
                    ,cols: [[ //表头
                        {field: 'index', title: '列', width:70}
                        ,{field: 'id', title: 'ID', width:70}
                        , {field: 'category', title: '付款类别', minWidth: 180, edit: true}
                        , {field: 'unit_price', title: '付款金额', minWidth: 110, edit: true}
                        , {field: 'remark', title: '备注信息', minWidth: 110, edit: true}

                    ]]
                    ,data:good_l

                });

            }



            table.on('edit(order_table)', function(obj){
                $('#practical_price').val(obj.value)

            });


            ea.listen(function (data) {
                data['goods'] = table.cache['order_table']

                return {data:JSON.stringify(data)}

            });
        },



    };
    return Controller;
});