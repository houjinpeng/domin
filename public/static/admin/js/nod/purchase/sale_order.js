define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'nod.purchase.sale_order/index',
        add_url: 'nod.purchase.sale_order/add',
        audit_url: 'nod.audit.purchase/audit?type=sale',
        edit_url: 'nod.purchase.sale_order/edit',
        chexiao_url: 'nod.purchase.sale_order/chexiao',


    };

    var Controller = {

        index: function () {
            var customer_select_list = ea.getSelectList('NodCustomerManagement','id,name')
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
                limit: 15,
                toolbar: ['refresh', 'add'],
                limits: [15, 30, 50],
                cols: [[
                    // {type: "checkbox"},
                    {field: 'order_batch_num', minWidth: 180, title: '单据编号'},
                    {field: 'order_time', minWidth: 180, title: '单据时间'},
                    {
                        field: 'order_user_id', minWidth: 90, title: '制单人',selectList: bulid_select(user_select_list,'username'), templet: function (d) {
                            if ( d.getOrderUser){
                                return d.getOrderUser['username']
                            }return ''

                        }
                    },
                    {
                        field: 'customer_id', minWidth: 100, title: '客户',selectList: bulid_select(customer_select_list), templet: function (d) {
                            if ( d.getCustomer){
                                return d.getCustomer['name']
                            }return ''

                        }
                    },
                    {
                        field: 'account_id', minWidth: 120, title: '结算账户',selectList: bulid_select(account_select_list), templet: function (d) {
                            if ( d.getAccount){
                                return d.getAccount['name']
                            }return ''

                        }
                    },
                    {field: 'practical_price', minWidth: 100, title: '单据金额',search:false},
                    {field: 'paid_price', minWidth: 100, title: '实付金额',search:false},
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
                        width: 180,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [

                            [
                                {
                                    text: '编辑查看',
                                    title:'编辑查看',
                                    url: init.edit_url,
                                    method: 'open',
                                    auth: 'edit',
                                    class: 'layui-btn layui-btn-xs',
                                    extend: 'data-full="true"',
                                },{
                                    text: '撤销',
                                    title:'是否要撤销当前单据？',
                                    url: init.chexiao_url,
                                    method: 'request',
                                    auth: 'chexiao',
                                    class: 'layui-btn layui-btn-xs layui-btn-danger',
                                    extend: 'data-full="true"',
                                }, {
                                text: '审核',
                                url: init.audit_url,
                                method: 'open',
                                auth: 'audit',
                                class: 'layui-btn layui-btn-xs',
                                extend: 'data-full="true"',
                            }]
                        ]
                    }
                ]],
                done:function (data) {
                    //将抓取过的抓取按钮变灰色
                    $.each(data.data,function (k,v){
                        if (v.audit_status === 1 || v.audit_status === 2){
                            // $('div[lay-id="currentTableRenderId"]').find('tr[data-index="'+k+'"]').find('a[data-title="编辑信息"]').removeClass('layui-btn-success').addClass('layui-btn-disabled').removeAttr('data-open')
                            $('div[lay-id="currentTableRenderId"]').find('tr[data-index="'+k+'"]').find('a[data-title="是否要撤销当前单据？"]').removeClass('layui-btn-danger').addClass('layui-btn-disabled').removeAttr('data-request')
                            $('div[lay-id="currentTableRenderId"]').find('tr[data-index="'+k+'"]').find('a[data-title="审核"]').removeClass('layui-btn-danger').addClass('layui-btn-disabled').removeAttr('data-open')
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
            function check_number(value) {
                return !isNaN(parseFloat(value)) && isFinite(value);

            }

            laydate.render({
                elem: '#order_time' //指定元素
                , type: 'datetime'
            });

            //初始化表格
            table.render({
                elem: '#order_table'
                ,
                height: 'full-300'
                ,
                limit: 10000
                ,
                page: false //开启分页
                ,
                cols: [[ //表头
                    {field: 'index', title: '列', width: 70}
                    , {field: 'good_name', title: '商品信息', minWidth: 180, edit: true}
                    , {field: 'sale_time', title: '出售时间', minWidth: 180, edit: true}
                    , {field: 'unit_price', title: '售货单价', minWidth: 110, edit: true}
                    , {field: 'num', title: '售货数量', minWidth: 110}
                    , {field: 'total_price', title: '售货金额', minWidth: 110}
                    , {field: 'remark', title: '备注信息', minWidth: 110, edit: true}
                    , {field: '#', title: '操作', width: 70, toolbar: '#barDemo'}

                ]]
                ,
                data: [{
                    'index': '1',
                    'total_price': '',
                    remark: '',
                    unit_price: '',
                    good_name: '',
                    num: '1',
                    register_time: '',
                    expiration_time: ''
                }]
                ,
                done: function () {


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
                    var ls_data = table.cache['order_table']
                    let new_table_data = [];
                    let index = 0
                    ls_data.forEach(function (item) {
                        if (item !== [] && item['LAY_TABLE_INDEX'] !== undefined) {
                            console.log(index)
                            item['index'] = index + 1
                            index += 1
                            new_table_data.push(item)
                        }
                    })

                    table.reload('order_table', {data: new_table_data, limit: 10000})

                } else if (layEvent === 'add') {

                    all_data.push({
                        'index': parseInt(all_data[all_data.length - 1]['index']) + 1,
                        'total_price': '',
                        remark: '',
                        unit_price: '',
                        good_name: '',
                        num: '1',
                        register_time: '',
                        expiration_time: ''
                    })
                    table.reload('order_table', {data: all_data, limit: 10000})


                }
            });

            //单元格编辑事件
            table.on('edit(order_table)', function (obj) {
                let data = obj.data
                // obj.update({good_name:'asdasdasdasd'})
                let filed = obj.field
                if (filed === 'good_name' || filed === 'remark') return;

                if (check_number(obj.value) === false) {
                    layer.msg('重要提醒：请输入数字类型', {icon: 2})
                    return
                }
                try {
                    //购货金额自动计算
                    let total_price = data['num'] * data['unit_price']
                    obj.update({total_price: total_price})
                    console.log(obj.data)
                } catch (e) {
                    layer.msg('无法计算购货金额~ 请仔细核对！')

                }
            });

            //快捷录入单据金额
            $('#jk_price').click(function () {
                all_data = table.cache['order_table']
                let total_pirce = 0
                all_data.forEach(function (item) {
                    total_pirce += parseInt(item['total_price'])
                })

                $('#practical_price').val(total_pirce)

            })

            //点击导入单据
            $('#crawl_order').click(function () {

                let data = form.val("order_form");
                if (!data['order_time']) {
                    layer.msg('请选择单据时间 根据单据时间导入成交数据~', {icon: 2});
                    return
                }

                let crawl_time = data['order_time'].split(' ')[0]
                ea.request.get({
                    url: 'crawl_order_data?crawl_time='+crawl_time,
                }, function (resp) {
                    console.log(resp)
                    table.reload('order_table', {data: resp.data, limit: 100000})
                })


            })

            //点击表单导入
            $('#import_order').click(function () {
                layer.open({
                    title: '采购单-表单导入单据',
                    skin: 'demo-class',
                    type: 1,
                    area: ['800px', '500px'],
                    content: '<div class="layuimini-container">\n' +
                        '\t    <div class="layuimini-main">\n' +
                        '<form class="layui-form" action="" lay-filter="import_form">\n' +
                        '  <div class="layui-form-item layui-form-text">\n' +
                        '    <label class="layui-form-label">导入表单</label>\n' +
                        '    <div class="layui-input-block">\n' +
                        '      <textarea rows="10" name="data" placeholder="输入格式:域名|出售时间|单价|备注   如：baidu.com|2022-12-02|100|我是一个搬运工" class="layui-textarea"></textarea>\n' +
                        '    </div>\n' +
                        '  </div>\n' +
                        '  <div class="layui-form-item">\n' +
                        '    <div class="layui-input-block">\n' +
                        '      <a id="import_form" class="layui-btn layui-btn-sm" >立即导入</a>\n' +
                        '    </div>\n' +
                        '  </div>\n' +
                        '</form>\n' +
                        '</div></div>',
                    success:function (layero, index) {
                        //点击导入 导入表单
                        $('#import_form').click(function () {


                            let data = form.val("import_form")['data'].split('\n');
                            if (data.length ===0) {
                                layer.msg('不能一个也不导入吧~',{icon:2})
                            }
                            let import_data = []
                            for (let i in data){
                                let d = $.trim(data[i])
                                if (d === '')continue
                                let detail = d.split('|')
                                let ym = $.trim(detail[0])
                                let sale_time = $.trim(detail[1])
                                let unit_price = $.trim(detail[2])
                                let total_price = $.trim(detail[2])
                                let remark = $.trim(detail[3])

                                import_data.push({
                                    remark: remark,
                                    unit_price: unit_price,
                                    total_price: total_price,
                                    good_name:ym,
                                    num: 1,
                                    index: parseInt(i)+1,
                                    sale_time: sale_time,
                                })

                            }

                            let l = import_data.length
                            for (let i in all_data){
                                let d= all_data[i]
                                d['index'] =parseInt(l)+parseInt(i)+1
                                import_data.push(d)
                            }


                            table.reload('order_table', {data: import_data, limit: 100000})
                            layer.msg('导入成功',{icon:1})
                            layer.close(index)
                            return false
                        })
                    }


                });


            })

            ea.listen(function (data) {
                data['goods'] = table.cache['order_table']

                return {data: JSON.stringify(data)}

            });
        },


        edit: function () {
            var laydate = layui.laydate;
            var table = layui.table;
            var form = layui.form;

            function check_number(value) {
                return !isNaN(parseFloat(value)) && isFinite(value);

            }

            laydate.render({
                elem: '#order_time' //指定元素
                , type: 'datetime'
            });

            var good_list = JSON.parse($('#all_good').val())
            var good_l = []
            good_list.forEach(function (item,index) {
                item['index'] = index+1
                good_l.push(item)
            })


            //初始化表格
            table.render({
                elem: '#order_table',
                height: 'full-300',
                limit: 10000,
                page: false ,
                cols: [[
                    {field: 'index', title: '列', width: 70}
                    , {field: 'id', title: 'ID', minWidth: 80, edit: true}
                    , {field: 'good_name', title: '商品信息', minWidth: 180, edit: true}
                    , {field: 'sale_time', title: '出售时间', minWidth: 180, edit: true}
                    , {field: 'unit_price', title: '售货单价', minWidth: 110, edit: true}
                    , {field: 'num', title: '售货数量', minWidth: 110}
                    , {field: 'total_price', title: '售货金额', minWidth: 110}
                    , {field: 'remark', title: '备注信息', minWidth: 110, edit: true}
                    , {field: '#', title: '操作', width: 70, toolbar: '#barDemo'}

                ]],
                data:good_l,

            });


            //工具条事件
            table.on('tool(order_table)', function (obj) { //注：tool 是工具条事件名，test 是 table 原始容器的属性 lay-filter="对应的值"
                var layEvent = obj.event; //获得 lay-event 对应的值（也可以是表头的 event 参数对应的值）
                var all_data = table.cache['order_table']
                console.log(all_data)


                if (layEvent === 'del') { //删除
                    if (all_data.length === 1) {
                        layer.msg('不能再删除了，就剩下一行了~~', {icon: 2})
                        return
                    }
                    obj.del(); //删除对应行（tr）的DOM结构，并更新缓存
                    var ls_data = table.cache['order_table']
                    let new_table_data = [];
                    let index = 0
                    ls_data.forEach(function (item) {
                        if (item !== [] && item['LAY_TABLE_INDEX'] !== undefined) {
                            console.log(index)
                            item['index'] = index + 1
                            index += 1
                            new_table_data.push(item)
                        }
                    })

                    table.reload('order_table', {data: new_table_data, limit: 10000})

                } else if (layEvent === 'add') {

                    all_data.push({
                        'index': parseInt(all_data[all_data.length - 1]['index']) + 1,
                        'total_price': '',
                        remark: '',
                        unit_price: '',
                        good_name: '',
                        num: '1',
                        register_time: '',
                        expiration_time: ''
                    })
                    table.reload('order_table', {data: all_data, limit: 10000})


                }
            });

            //单元格编辑事件
            table.on('edit(order_table)', function (obj) {
                let data = obj.data
                // obj.update({good_name:'asdasdasdasd'})
                let filed = obj.field
                if (filed === 'good_name' || filed === 'remark') return;

                if (check_number(obj.value) === false) {
                    layer.msg('重要提醒：请输入数字类型', {icon: 2})
                    return
                }
                try {
                    //购货金额自动计算
                    let total_price = data['num'] * data['unit_price']
                    obj.update({total_price: total_price})
                    console.log(obj.data)
                } catch (e) {
                    layer.msg('无法计算购货金额~ 请仔细核对！')

                }
            });

            //快捷录入单据金额
            $('#jk_price').click(function () {
                let all_data = table.cache['order_table']
                let total_pirce = 0
                all_data.forEach(function (item) {
                    total_pirce += item['total_price']
                })

                $('#practical_price').val(total_pirce)

            })

            //点击导入单据
            $('#crawl_order').click(function () {

                ea.request.get({
                    url: 'crawl_order_data',
                }, function (resp) {
                    console.log(resp)
                    table.reload('order_table', {data: resp.data, limit: 100000})
                })


            })



            ea.listen(function (data) {
                data['goods'] = table.cache['order_table']

                return {data: JSON.stringify(data)}

            });
        },



    };
    return Controller;
});