define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'nod.purchase.stock_order/index',
        add_url: 'nod.purchase.stock_order/add',
        audit_url: 'nod.audit.purchase/audit?type=stock',
        edit_url: 'nod.purchase.stock_order/edit',
        chexiao_url: 'nod.purchase.stock_order/chexiao',


    };

    var Controller = {

        index: function () {
            var table = layui.table
            var warehouse_select_list = ea.getSelectList('NodWarehouse','id,name')
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

                toolbar: ['refresh', 'add',[{
                    title:'采集单据',
                    url:init.add_url,
                    auth:'crawl_order',
                    method:'open',
                    icon:'fa fa-android',
                    class: 'layui-btn layui-btn-sm',
                }]],
                limit: 30,
                limits: [30, 50, 100],
                cols: [[
                    // {type: "checkbox"},
                    {field: 'order_batch_num', minWidth: 180, title: '单据编号'},
                    {field: 'order_time', minWidth: 180, title: '单据时间',search: 'range'},
                    {field: 'order_info', minWidth: 120, title: '域名',searchOp:'=' ,templet:function (d) {
                        if (d.order_info.length !== 0){
                            return d.order_info[0].good_name
                        }
                          return  ''
                        }},
                    {field: 'order_count', minWidth: 100, title: '数量',search: false,templet:function (d) {
                            let all_data = [];
                            d.order_info.forEach(function (item) {
                                all_data.push(item['good_name'])
                            })
                            let html = '<div batch_num="'+d.order_batch_num+'" class="show_detail" goods_name = "'+all_data.join(',')+'" >'+d.order_count+'</div>'
                            return html

                        }},
                    {
                        field: 'order_user_id', minWidth: 90, title: '制单人',selectList: bulid_select(user_select_list,'username'), templet: function (d) {
                            if ( d.getOrderUser){
                                return d.getOrderUser['username']
                            }return ''

                        }
                    },
                    {
                        field: 'supplier_id', minWidth: 100, title: '来源渠道',selectList: bulid_select(supplier_select_list), templet: function (d) {
                            if ( d.getSupplier){
                                return d.getSupplier['name']
                            }return ''

                        }
                    },
                    {
                        field: 'warehouse_id', minWidth: 100, title: '仓库',selectList: bulid_select(warehouse_select_list), templet: function (d) {
                            if (d.getWarehouse){
                                return d.getWarehouse['name']
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
                            [ {
                                text: '编辑查看',
                                title:'编辑查看',
                                url: init.edit_url,
                                method: 'open',
                                auth: 'edit',
                                class: 'layui-btn layui-btn-xs',
                                extend: 'data-full="true"',
                            },
                                {
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
                            // $('div[lay-id="currentTableRenderId"]').find('tr[data-index="'+k+'"]').find('a[data-title="编辑查看"]').removeClass('layui-btn-success').addClass('layui-btn-disabled').removeAttr('data-open')
                            $('div[lay-id="currentTableRenderId"]').find('tr[data-index="'+k+'"]').find('a[data-title="是否要撤销当前单据？"]').removeClass('layui-btn-danger').addClass('layui-btn-disabled').removeAttr('data-request')
                            $('div[lay-id="currentTableRenderId"]').find('tr[data-index="'+k+'"]').find('a[data-title="审核"]').removeClass('layui-btn-danger').addClass('layui-btn-disabled').removeAttr('data-open')
                        }

                    })


                    $('[class=show_detail]').click(function () {
                        let data = this.getAttribute('goods_name')
                        let batch_num = this.getAttribute('batch_num')
                        layer.open({
                            title: '编号:'+batch_num +'  共：'+data.split(',').length+'条记录'
                            ,area: ['500px', '300px']
                            ,  btn: ['复制', '关闭'] //可以无限个按钮
                            , skin: 'demo-class'
                            ,content: data.split(',').join('<br>')
                            ,yes: function(index, layero){
                                //按钮【按钮一】的回调
                                ea.copyText(data.split(',').join("\n"))
                                // layer.msg('复制成功~',{icon:1})
                                return false
                            }
                            ,btn2: function(index, layero){

                            }
                        });
                    })


                    $('[data-title=采集单据]').click(function (d) {


                        layer.open({
                            title: '自动采集订单'
                            ,area: ['500px', '300px']
                            ,btn: ['开始采集', '关闭'] //可以无限个按钮
                            ,skin: 'demo-class'
                            ,content: ' 请选择采集日期：<input autocomplete="off" required name="carwl_time" type="text" class="layui-input" id="carwl_time">'
                            ,success:function () {
                                var laydate = layui.laydate;
                                //执行一个laydate实例
                                laydate.render({
                                    elem: '#carwl_time'
                                    ,format: 'yyyy-MM-dd'
                                    ,value: ''
                                    ,max: 0
                                });

                            }
                            ,yes: function(index, layero){
                                let carwl_time = $('#carwl_time').val()
                                if (carwl_time ===''){
                                    alert('请选择采集日期')
                                    return false
                                }

                                // $.ajax({
                                //     url:'crawl_all_order?crawl_time='+carwl_time,
                                //     success:function (resp) {
                                //         console.log(resp)
                                //     }
                                // })
                                ea.request.post({
                                    url:'crawl_all_order?crawl_time='+carwl_time,

                                },function (resp) {
                                    layer.msg(resp.msg,{icon:1})

                                    layer.open({
                                        title: '自动采集订单结果'
                                        ,area: ['500px', '300px']
                                        ,skin: 'demo-class'
                                        ,'content':resp.msg +'<br> 请刷新页面'
                                    })


                                    // parent.table.reload('currentTableRenderId')
                                })


                                return false
                            }
                        })

                        console.log(123)
                        return false
                    })


                }
            });



            table.on('tool(currentTable)', function(obj){
                var data = obj.data; //获得当前行数据
                var layEvent = obj.event;
                var tr = obj.tr;
                console.log(data)
                if(layEvent === 'detail'){ //查看
                    console.log(data)
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
                    , {field: 'good_name', title: '商品信息', minWidth: 180, edit: true}
                    , {field: 'unit_price', title: '购货单价', minWidth: 110, edit: true}
                    , {field: 'remark', title: '备注信息', minWidth: 110, edit: true}
                    , {field: '#', title: '操作', width: 70, toolbar: '#barDemo'}

                ]]
                ,
                data: [{
                    index: '1',
                    remark: '',
                    unit_price: '',
                    good_name: '',
                }]
                ,
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
                            item['index'] = index + 1
                            index += 1
                            new_table_data.push(item)
                        }
                    })

                    table.reload('order_table', {data: new_table_data, limit: 10000})

                } else if (layEvent === 'add') {

                    all_data.push({
                        index: parseInt(all_data[all_data.length - 1]['index']) + 1,
                        remark: '',
                        unit_price: '',
                        good_name: '',

                    })
                    table.reload('order_table', {data: all_data, limit: 10000})


                }
            });


            //快捷录入单据金额
            $('#jk_price').click(function () {
                all_data = table.cache['order_table']
                let total_pirce = 0
                all_data.forEach(function (item) {
                    total_pirce += parseInt(item['unit_price'])
                })

                $('#practical_price').val(total_pirce)

            })

            $('#reset').click(function () {
                $('input').val('')
                table.reload('order_table',{data:[{
                        index: '1',
                        remark: '',
                        unit_price: '',
                        good_name: '',
                    }],limit:100000})

            })
            //点击导入单据
            $('#crawl_order').click(function () {
                let data = form.val("order_form");
                if (!data['warehouse_id']) {
                    layer.msg('请选择仓库~', {icon: 2});
                    return
                }


                ea.request.get({
                    url: 'crawl_order_data?warehouse_id=' + data['warehouse_id'],
                }, function (resp) {
                    all_data = resp.data
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
                        '      <textarea rows="10" name="data" placeholder="输入格式:域名|单价|备注   如：baidu.com|100|我是一个搬运工" class="layui-textarea"></textarea>\n' +
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

                                let unit_price = $.trim(detail[1])
                                let remark = $.trim(detail[2])

                                import_data.push({
                                    remark: remark,
                                    unit_price: unit_price,
                                    good_name:ym,
                                    index: parseInt(i)+1,

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


            $('#clear_zero').click(function () {
                let all_data = table.cache['order_table']
                let new_data = []
                let index = 1
                all_data.forEach(function (item) {
                    if (item['unit_price'] !== 0) {
                        item['index'] = index
                        new_data.push(item)
                        index += 1
                    }
                })
                table.reload('order_table', {data: new_data, limit: 100000})

            })

            ea.listen(function (data) {
                data['goods'] = table.cache['order_table']

                return {data: JSON.stringify(data)}

            });
        },


        edit: function () {
            var laydate = layui.laydate;
            var table = layui.table;
            var all_data = null;
            var form = layui.form;
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
                    , {field: '#', title: '操作', width: 70, toolbar: '#barDemo'}

                ]]
                ,data:good_l

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
                        index: parseInt(all_data[all_data.length - 1]['index']) + 1,
                        remark: '',
                        unit_price: '',
                        good_name: '',


                    })
                    table.reload('order_table', {data: all_data, limit: 10000})


                }
            });


            //快捷录入单据金额
            $('#jk_price').click(function () {
                all_data = table.cache['order_table']
                let total_pirce = 0
                all_data.forEach(function (item) {
                    total_pirce += parseInt(item['unit_price'])
                })

                $('#practical_price').val(total_pirce)

            })

            ea.listen(function (data) {
                data['goods'] = table.cache['order_table']

                return {data: JSON.stringify(data)}

            });
        },
        password: function () {
            ea.listen();
        }
    };
    return Controller;
});