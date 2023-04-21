define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'nod.audit.purchase/index',
        audit_url: 'nod.audit.purchase/audit',


    };

    var Controller = {
        index:function () {
            ea.table.render({
                init: init,
                limit:30,
                toolbar:['refresh'],
                limits:[30,50,100],
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
                        ,{field: 'sale_time', title: '出售时间', minWidth:180,edit:true}
                        ,{field: 'unit_price', title: '购货单价', minWidth:110,edit:true}
                        ,{field: 'remark', title: '备注信息', minWidth: 110,edit:true}

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
                        ,{field: 'good_name', title: '商品信息', minWidth:180,edit:true}
                        ,{field: 'unit_price', title: '购货单价', minWidth:110,edit:true}
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

            //批量修改单价
            $('#batch_set_price').click(function () {
                layer.open({
                    title: '批量修改金额',
                    skin: 'demo-class',
                    type: 1,
                    area: ['260px', '132px'],
                    content: '<div class="layuimini-container">请输入要修改的价格：<input autocomplete="off"  id="price" value="" ><button id="sub_price">确定</button></div> ',
                    success:function (layero, index) {
                        //点击导入 导入表单
                        $('#sub_price').click(function () {


                            let price = $('#price').val()

                            let import_data = []
                            let all_data = table.cache['order_table']
                            all_data.forEach(function (item) {
                                item['unit_price'] = price
                                import_data.push(item)
                            })




                            table.reload('order_table', {data: import_data, limit: 100000})
                            layer.msg('修改成功',{icon:1})
                            layer.close(index)
                            return false
                        })
                    }


                });
            })


            ea.listen(function (data) {
                data['goods'] = table.cache['order_table']
                console.log(data)
                return {data:JSON.stringify(data)}

            });
        },

    };
    return Controller;
});