define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'nod.sale_profit/index',


    };

    var Controller = {

        index: function () {
            var table = layui.table
            var admin_list = ea.getSelectList('SystemAdmin','id,username')
            function bulid_select(select_list,field='name'){
                let se = {}
                select_list.forEach(function (item) {
                    se[item['id']] = item[field]
                })
                return se

            }


            ea.table.render({
                init:init,
                elem: '#currentTable',
                limit:30,
                toolbar:['refresh'],
                limits:[30,50,100],
                cols: [[

                    {field: 'operate_time', search:false,minWidth: 168, fixed:'left', title: '操作时间'},

                    {field: 'order_user_id', minWidth: 110, title: '经手人',selectList: bulid_select(admin_list,'username'),templet:function (d) {
                            if ( d.getOrderUser){
                                return d.getOrderUser.username
                            } return ''
                        }},

                    {field: 'sale_user_id',selectList: bulid_select(admin_list,'username'),minWidth: 168,  title: '销售员',templet:function (d) {
                            if (d.getSaleUser){
                                return d.getSaleUser.username
                            }
                            return ''
                        }},
                    {field: 'customer_id', search:false,minWidth: 168,  title: '客户',templet:function (d) {
                            if (d.getCustomer){
                                return d.getCustomer.name
                            }
                            return ''
                        }},

                    {field: 'category', minWidth: 120, title: '类型',selectList:{'销售单':'销售单','销售退货单':'销售退货单'},templet:function (d) {
                            if (d.category === '应收款'){
                                return '销售单'
                            }
                            return d.category
                        }},
                    {field: 'sm', minWidth: 400, title: '说明',search: false,align:'left',templet:function (d) {
                            if (d.category === '采购单'  || d.category === '应付款'){
                                return 'ID:'+ d.getAccount.name+' 购买域名:【'+d.good_name+'】 价格:'+-d.practical_price+'元'
                            }else if (d.category === '销售单'  || d.category === '应收款'){
                                return 'ID:'+ d.getAccount.name+' 出售域名:【'+d.good_name+'】 价格:'+d.practical_price+'元'
                            }else if (d.category === '付款单'){
                                return 'ID:'+ d.getAccount.name+' 付款给客户【'+d.getCustomer.name+'】'+d.getCategory.name+'  '+-d.practical_price+'元 '
                            }else if (d.category === '收款单'){
                                return 'ID:'+ d.getAccount.name+' 收到客户【'+d.getCustomer.name+'】 '+d.getCategory.name+'  '+d.practical_price+'元 '
                            }else if (d.category === '采购退货单'){
                                return '退款 ID:'+ d.getAccount.name+' 购买域名:【'+d.good_name+'】 价格:'+d.price+'元'
                            }else if (d.category === '销售退货单'){
                                return '退款 ID:'+ d.getAccount.name+' 出售域名:【'+d.good_name+'】 价格:'+-d.price+'元'
                            }else if (d.category === '费用单'){
                                return 'ID:'+ d.getAccount.name+' 付款给客户【'+d.getCustomer.name+'】'+d.getCategory.name+'  '+-d.practical_price+'元 '
                            }else if (d.category === '其他收入单'){
                                return 'ID:'+ d.getAccount.name+' 收到客户【'+d.getCustomer.name+'】 '+d.getCategory.name+'  '+d.practical_price+'元 '
                            }
                        }},

                    {field: 'cost_price', search:false,minWidth: 168,  title: '成本价'},
                    {field: 'practical_price', search:false,minWidth: 168,  title: '销售价',templet:function (d) {
                            if (d.category === '销售退货单'){
                                return '<font color="red">'+d.practical_price+'</font>'
                            }
                            return  d.practical_price
                        }},
                    {field: 'profit_price', search:false,minWidth: 168,  title: '利润',templet:function (d) {
                            if (d.category === '销售退货单'){
                                return '<font color="red">'+d.profit_price+'</font>'
                            }
                            return  d.profit_price
                        }},
                    {field: 'total_profit_price', search:false,minWidth: 168,  title: '总利润'},

                    {field: 'remark', minWidth: 152,align:'left', title: '备注信息'},

                ]],
            });

            //工具条事件
            table.on('tool(currentTable)', function(obj){ //注：tool 是工具条事件名，test 是 table 原始容器的属性 lay-filter="对应的值"
                var data = obj.data; //获得当前行数据
                var layEvent = obj.event; //获得 lay-event 对应的值（也可以是表头的 event 参数对应的值）
                var tr = obj.tr; //获得当前行 tr 的 DOM 对象（如果有的话）

                if(layEvent === 'show'){ //查看
                    let all_data = [];
                    data.info.forEach(function (item) {
                        all_data.push(item['good_name'])
                    })
                    //do somehing
                    layer.open({
                        title: data['name'] +' 详情'
                        ,area: ['500px', '300px']
                        , skin: 'demo-class'
                        ,content: all_data.join('<br>')
                    });




                }
            });


            ea.listen();
        },


    };
    return Controller;
});