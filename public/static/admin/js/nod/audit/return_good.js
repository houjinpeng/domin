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
                        ,{field: 'num', title: '购货数量', minWidth:110,edit:true}
                        ,{field: 'total_price', title: '购货金额', minWidth: 110}
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
                        ,{field: 'num', title: '退货数量', minWidth:110,edit:true}
                        ,{field: 'total_price', title: '退货金额', minWidth: 110}
                        ,{field: 'remark', title: '备注信息', minWidth: 110,edit:true}
                    ]]
                    ,data:good_l

                });

            }




            //单元格编辑事件
            table.on('edit(order_table)', function(obj){
                let data = obj.data
                // obj.update({good_name:'asdasdasdasd'})
                let filed = obj.field
                if (filed === 'good_name' || filed === 'remark')return;

                if (check_number(obj.value) === false){
                    layer.msg('重要提醒：请输入数字类型',{icon: 2})
                    return
                }
                try{
                    //购货金额自动计算
                    let total_price = data['num']*data['unit_price']
                    obj.update({total_price:total_price})
                    console.log(obj.data)
                }catch (e){
                    layer.msg('无法计算购货金额~ 请仔细核对！')

                }
            });

            //快捷录入单据金额
            $('#jk_price').click(function () {
                let all_data = table.cache['order_table']
                let total_pirce = 0
                all_data.forEach(function (item) {
                    total_pirce += parseInt(item['total_price'])
                })

                $('#practical_price').val(total_pirce)

            })


            ea.listen(function (data) {
                data['goods'] = table.cache['order_table']

                return {data:JSON.stringify(data)}

            });
        },


        add: function () {

            ea.listen();
        },
        edit: function () {
            ea.listen();
        },
        password: function () {
            ea.listen();
        }
    };
    return Controller;
});