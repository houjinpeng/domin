define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'nod.capital.receipt/index',
        add_url: 'nod.capital.receipt/add',
        audit_url: 'nod.audit.receipt_and_payment/audit?type=receipt',
        edit_url: 'nod.capital.receipt/edit',
        delete_url: 'nod.purchase.return_order/delete',
        rollback_order_url: 'nod.capital.receipt/rollback_order',

    };
    function check_number(value) {
        return !isNaN(parseFloat(value)) && isFinite(value);

    }

    var Controller = {



        index: function () {
            var account_select_list = ea.getSelectList('NodAccount','id,name')
            var supplier_select_list = ea.getSelectList('NodSupplier','id,name')
            var user_select_list = ea.getSelectList('SystemAdmin','id,username')

            function bulid_select(select_list,field='name'){
                let se = {}
                select_list.forEach(function (item) {
                    se[item['id']] = item[field]
                })
                return se

            }


            ea.table.render({
                init: init,

                height:'full-40',
                toolbar: ['refresh', 'add'],
                limit: 30,
                limits: [30, 50, 100],
                cols: [[
                    // {type: "checkbox"},
                    {field: 'order_batch_num', minWidth: 180, title: '单据编号'},
                    {field: 'order_time', minWidth: 180, title: '单据时间',search: 'range'},
                    {field: 'order_user_id', minWidth: 90, title: '制单人',selectList: bulid_select(user_select_list,'username'), templet: function (d) {
                            if ( d.getOrderUser){
                                return d.getOrderUser['username']
                            }return ''

                        }
                    },
                    {
                        field: 'customer_id', minWidth: 100, title: '客户',selectList: bulid_select(supplier_select_list), templet: function (d) {
                            if ( d.getCustomer){
                                return d.getCustomer['name']
                            }return ''

                        }
                    },
                    {
                        field: 'account_id', minWidth: 120, title: '收款账户',selectList: bulid_select(account_select_list), templet: function (d) {
                            if ( d.getAccount){
                                return d.getAccount['name']
                            }return ''

                        }
                    },
                    {field: 'practical_price', minWidth: 100, title: '单据金额',search:false},
                    {field: 'remark', minWidth: 180, title: '备注'},
                    {field: 'audit_status', minWidth: 100, title: '状态',selectList:{'1':'已审核','2':'撤销','0':'未审核'},templet:function (d) {
                            if (d.audit_status === 1){
                                return'已审核'
                            }if (d.audit_status === 0){
                                return'待审核'
                            }if (d.audit_status === 2){
                                return'已撤销'
                            }

                        }},
                    {
                        fixed: 'right',
                        width: 240,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [
                            [ {
                                text: '编辑查看',
                                title:'编辑查看',
                                url: init.edit_url,
                                method: 'open',
                                auth: 'edit',
                                class: 'layui-btn layui-btn-xs',
                                extend: 'data-full="true"',
                            }, {
                                text: '审核',
                                url: init.audit_url,
                                method: 'open',
                                auth: 'audit',
                                class: 'layui-btn layui-btn-xs',
                                extend: 'data-full="true"',
                            },
                                // {
                                //     text: '撤回',
                                //     title:'是否要回滚当前单据？ 慎重选择',
                                //     url: init.rollback_order_url,
                                //     method: 'request',
                                //     auth: 'rollback_order_url',
                                //     class: 'layui-btn layui-btn-xs layui-btn-warm',
                                //     extend: 'data-full="true"',
                                // },
                                {
                                text: '删除',
                                title:'是否要删除当前单据？',
                                url: init.delete_url,
                                method: 'request',
                                auth: 'delete',
                                class: 'layui-btn layui-btn-xs layui-btn-danger',
                                extend: 'data-full="true"',
                            }]
                        ]
                    }
                ]],
                done:function (data) {
                    //将抓取过的抓取按钮变灰色
                    $.each(data.data,function (k,v){

                        if (v.audit_status === 0){
                            $('div[lay-id="currentTableRenderId"]').find('tr[data-index="'+k+'"]').find('a[data-title="是否要回滚当前单据？ 慎重选择"]').removeClass('layui-btn-danger').addClass('layui-btn-disabled').removeAttr('data-request')

                        }
                        if (v.audit_status === 1){
                            $('div[lay-id="currentTableRenderId"]').find('tr[data-index="'+k+'"]').find('a[data-title="是否要删除当前单据？"]').removeClass('layui-btn-danger').addClass('layui-btn-disabled').removeAttr('data-request')
                            $('div[lay-id="currentTableRenderId"]').find('tr[data-index="'+k+'"]').find('a[data-title="审核"]').removeClass('layui-btn-danger').addClass('layui-btn-disabled').removeAttr('data-open')

                        }

                        if (v.audit_status === 3){
                            $('div[lay-id="currentTableRenderId"]').find('tr[data-index="'+k+'"]').find('a[data-title="是否要删除当前单据？"]').removeClass('layui-btn-danger').addClass('layui-btn-disabled').removeAttr('data-request')
                            $('div[lay-id="currentTableRenderId"]').find('tr[data-index="'+k+'"]').find('a[data-title="是否要回滚当前单据？ 慎重选择"]').removeClass('layui-btn-danger').addClass('layui-btn-disabled').removeAttr('data-request')
                            $('div[lay-id="currentTableRenderId"]').find('tr[data-index="'+k+'"]').find('a[data-title="审核"]').removeClass('layui-btn-danger').addClass('layui-btn-disabled').removeAttr('data-open')

                        }

                        if ( v.audit_status === 2){
                            $('div[lay-id="currentTableRenderId"]').find('tr[data-index="'+k+'"]').find('a[data-title="是否要回滚当前单据？ 慎重选择"]').removeClass('layui-btn-danger').addClass('layui-btn-disabled').removeAttr('data-open')

                        }

                    })
                }
            });
            ea.listen();
        },

        add: function () {
            var laydate = layui.laydate;
            var table = layui.table;
            var form = layui.form;
            var all_data = null;



            var category_select_list = ea.getSelectList('NodCategory','id,name')

            let html_select = '<select name="category_id" lay-verify="required">'
            var select_dict = {}


            for (let index in category_select_list){

                let v = category_select_list[index]['id']
                let name = category_select_list[index]['name']
                select_dict[name] = v
                html_select +='<option value="'+v+'">'+name+'</option>'
            }

            html_select +=   '</select>'

            // html_select = get_select('')

            function get_select(select_name){
                let h = ' <select name="category_id" lay-verify="required">'
                for (let index in category_select_list){

                    let v = category_select_list[index]['id']
                    let name = category_select_list[index]['name']
                    select_dict[name] = v
                    if (select_name === name){
                        h +='<option value="'+v+'" selected>'+name+'</option>'
                    }else{
                        h +='<option value="'+v+'" >'+name+'</option>'
                    }

                }
                h +=   '</select>'
                return h
            }


            laydate.render({
                elem: '#order_time' //指定元素
                , type: 'datetime'
                ,value: new Date()
            });

            //初始化表格
            table.render({
                elem: '#order_table',
                height: 'full-300',
                limit: 10000,
                page: false ,//开启分页,
                cols: [[ //表头
                    {field: 'index', title: '列', width: 70}
                    // , {field: 'category_id', title: '收款类别', minWidth: 180,templet:'#selectCategory'}
                    , {field: 'category_id', title: '收款类别', minWidth: 180}
                    , {field: 'unit_price', title: '收款金额', minWidth: 110, edit: true}
                    , {field: 'remark', title: '备注信息', minWidth: 110, edit: true}

                ]]
                ,
                data: [{
                    index: 1,
                    unit_price: '',
                    category_id:html_select ,
                    remark: '',
                }]
                ,done:function (data) {

                    $(".layui-form").parent().css('overflow', 'visible');//sel_action为下拉框class

                    form.render();
                }
            });


            //工具条事件
            table.on('tool(order_table)', function (obj) { //注：tool 是工具条事件名，test 是 table 原始容器的属性 lay-filter="对应的值"
                var layEvent = obj.event; //获得 lay-event 对应的值（也可以是表头的 event 参数对应的值）
                all_data = table.cache['order_table']

                if (layEvent === 'del') { //删除
                    if (all_data.length === 1) {
                        layer.msg('不能再删除了，就剩下一行了~~', {icon: 2})
                        return
                    }
                    obj.del(); //删除对应行（tr）的DOM结构，并更新缓存
                    let ls_data = table.cache['order_table']
                    let new_table_data = [];
                    let index = 0
                    ls_data.forEach(function (item,index_c) {
                        if (item !== [] && item['LAY_TABLE_INDEX'] !== undefined) {
                            let category = $($($('.layui-table tr').eq(index_c+1)).find('td')[1]).find('.layui-this').html()
                            let cate_select = get_select(category)

                            item['index'] = index + 1
                            item['category_id'] = cate_select
                            index += 1

                            new_table_data.push(item)
                        }
                    })

                    table.reload('order_table', {data: new_table_data, limit: 10000})

                } else if (layEvent === 'add') {
                    let ls_data = table.cache['order_table']
                    let index = 0
                    let new_table_data = [];
                    ls_data.forEach(function (item,index_c) {
                        let category = $($($('.layui-table tr').eq(index_c+1)).find('td')[1]).find('.layui-this').html()
                        let cate_select = get_select(category)
                        item['index'] = index + 1
                        item['category_id'] = cate_select
                        new_table_data.push(item)
                        index +=1
                    })

                    new_table_data.push({
                        index: index + 1,
                        unit_price: '',
                        remark: '',
                        category_id: html_select,
                    })


                    table.reload('order_table', {data: new_table_data, limit: 10000})


                }
            });


            $('#reset').click(function () {
                $('input').val('')
                table.reload('order_table',{data:[{
                        index: 1,
                        category: html_select,
                        remark: '',
                        total_price: '',
                    }],limit:100000})

            })


            table.on('edit(order_table)', function(obj){
                $('#practical_price').val(obj.value)

            });

            ea.listen(function (data) {
                let d = table.cache['order_table']
                let new_data = []
                d.forEach(function (item,index_c) {
                    let category = $($($('.layui-table tr').eq(index_c+1)).find('td')[1]).find('.layui-this').html()

                    item['category_id'] = select_dict[category]
                    new_data.push(item)
                })



                data['goods'] = new_data
                console.log(data)
                return {data: JSON.stringify(data)}

            });
        },


        edit: function () {
            var laydate = layui.laydate;
            var table = layui.table;
            var all_data = null;
            var form = layui.form;

            var category_select_list = ea.getSelectList('NodCategory','id,name')

            var select_dict_v = {}
            let html_select = '<select name="category_id">'
            var select_dict = {}


            for (let index in category_select_list){

                let v = category_select_list[index]['id']
                let name =  category_select_list[index]['name']
                select_dict[v] = name
                select_dict_v[name] = v
                html_select +='<option value="'+v+'">'+name+'</option>'
            }
            html_select +=   '</select>'
            function get_select_id(select_id){
                let h = ' <select name="category_id" lay-verify="required">'
                for (let index in category_select_list){
                    let v = category_select_list[index]['id']
                    let name = category_select_list[index]['name']

                    if (select_id === v){
                        h +='<option value="'+v+'" selected>'+name+'</option>'
                    }else{
                        h +='<option value="'+v+'" >'+name+'</option>'
                    }

                }
                h +=   '</select>'
                return h
            }
            function get_select(select_name){
                let h = ' <select name="category_id" lay-verify="required">'
                for (let index in category_select_list){

                    let v = category_select_list[index]['id']
                    let name = category_select_list[index]['name']
                    select_dict[name] = v
                    if (select_name === name){
                        h +='<option value="'+v+'" selected>'+name+'</option>'
                    }else{
                        h +='<option value="'+v+'" >'+name+'</option>'
                    }

                }
                h +=   '</select>'
                return h
            }
            laydate.render({
                elem: '#order_time' //指定元素
                ,type: 'datetime'
            });

            var good_list = JSON.parse($('#all_good').val())


            var good_l = []
            good_list.forEach(function (item,index) {
                good_l.push({
                    index:index+1,
                    id:item['id'],
                    unit_price:item['unit_price'],
                    remark:item['remark'],
                    category_id:get_select_id(item['category_id']),
                })
            })


            //初始化表格
            table.render({
                elem: '#order_table'
                ,height: 'full-300'
                ,limit:10000
                ,page: false //开启分页
                ,cols: [[ //表头
                    {field: 'index', title: '列', width:70}
                    ,{field: 'id', title: 'ID', width:70}
                    , {field: 'category_id', title: '收款类别', minWidth: 180}
                    , {field: 'unit_price', title: '收款金额', minWidth: 110, edit: true}
                    , {field: 'remark', title: '备注信息', minWidth: 110, edit: true}
                    // , {field: '#', title: '操作', width: 70, toolbar: '#barDemo'}

                ]]
                ,data:good_l
                ,done:function (data) {

                    $(".layui-form").parent().css('overflow', 'visible');//sel_action为下拉框class

                    form.render('select');
                }
            });
            //工具条事件
            table.on('tool(order_table)', function (obj) { //注：tool 是工具条事件名，test 是 table 原始容器的属性 lay-filter="对应的值"
                var layEvent = obj.event; //获得 lay-event 对应的值（也可以是表头的 event 参数对应的值）
                all_data = table.cache['order_table']

                if (layEvent === 'del') { //删除
                    if (all_data.length === 1) {
                        layer.msg('不能再删除了，就剩下一行了~~', {icon: 2})
                        return
                    }
                    obj.del(); //删除对应行（tr）的DOM结构，并更新缓存
                    let ls_data = table.cache['order_table']
                    let new_table_data = [];
                    let index = 0
                    ls_data.forEach(function (item,index_c) {
                        if (item !== [] && item['LAY_TABLE_INDEX'] !== undefined) {
                            let category = $($($('.layui-table tr').eq(index_c+1)).find('td')[2]).find('.layui-this').html()
                            let cate_select = get_select(category)

                            item['index'] = index + 1
                            item['category_id'] = cate_select
                            index += 1

                            new_table_data.push(item)
                        }
                    })

                    table.reload('order_table', {data: new_table_data, limit: 10000})

                } else if (layEvent === 'add') {
                    let ls_data = table.cache['order_table']
                    let index = 0
                    let new_table_data = [];
                    ls_data.forEach(function (item,index_c) {
                        let category = $($($('.layui-table tr').eq(index_c+1)).find('td')[2]).find('.layui-this').html()
                        let cate_select = get_select(category)
                        item['index'] = index + 1
                        item['category_id'] = cate_select
                        new_table_data.push(item)
                        index +=1
                    })

                    new_table_data.push({
                        index: index + 1,
                        unit_price: '',
                        remark: '',
                        category_id: html_select,
                    })


                    table.reload('order_table', {data: new_table_data, limit: 10000})


                }
            });

            //快捷录入单据金额
            table.on('edit(order_table)', function(obj){
                $('#practical_price').val(obj.value)

            });


            ea.listen(function (data) {
                let d = table.cache['order_table']

                let new_data = []
                d.forEach(function (item,index_c) {
                    let category = $($($('.layui-table tr').eq(index_c+1)).find('td')[2]).find('.layui-this').html()
                    item['category_id'] = select_dict_v[category]
                    new_data.push(item)
                })

                data['goods'] = new_data

                return {data: JSON.stringify(data)}

            });
        },

    };
    return Controller;
});