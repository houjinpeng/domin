define(["jquery", "easy-admin","echarts"], function ($, ea,echarts) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'domain.attention_ym/index',
        edit_url: 'domain.attention_ym/edit',
        batch_edit_url: 'domain.attention_ym/batch_edit',
        export_url: 'domain.attention_ym/export',
        crawl_url: 'domain.attention_ym/crawl',
        cancel_like_url: 'domain.attention_ym/cancel_like',
        cancel_like_batch_url: 'domain.attention_ym/cancel_like_batch',
        attention_url: 'domain.attention_ym/attention',
        fx_data_url: 'domain.attention_ym/fx_data',

    };

    var fx_init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'domain.attention_ym/fx_data',


    };

    var Controller = {

        index: function () {

            var customer_select_list = ea.getSelectList('NodWarehouse', 'id,name')

            function bulid_select(select_list, field = 'name') {
                let se = {}
                select_list.forEach(function (item) {
                    se[item[field]] = item[field]
                })
                return se

            }

            ea.table.render({
                init: init,
                height: 'full-40',
                limit: 50,

                limits: [50, 100, 200, 500],
                toolbar: ['refresh','export' ,[
                    {
                        checkbox: true,
                        text: '批量取关',
                        title: '确定要更取消关注选择的域名么？',
                        url: init.cancel_like_batch_url,
                        method: 'request',
                        auth: 'cancel_like_batch',
                        field: 'ym_id',
                        class: 'layui-btn layui-btn-sm layui-btn-success',
                    },
                    {
                        text: '批量编辑',
                        url: init.batch_edit_url,
                        method: 'open',
                        auth: 'edit',
                        field: 'batch_edit',
                        class: 'layui-btn layui-btn-sm layui-btn-normal',
                    },
                    {
                        text: '更新关注',
                        title: '确定要更新所有账号下的关注域名么？',
                        url: init.crawl_url,
                        method: 'request',
                        auth: 'crawl',
                        class: 'layui-btn layui-btn-sm',

                    },
                    // {
                    //     text: '分析报表',
                    //     url: init.fx_data_url,
                    //     method: 'open',
                    //     auth: 'fx_data',
                    //     class: 'layui-btn layui-btn-warm layui-btn-sm',
                    // }
                ]],
                cols: [[
                    {type: "checkbox"},
                    {field: 'account', minWidth: 110, title: '账号', selectList: bulid_select(customer_select_list, 'name')},
                    {field: 'like_time', minWidth: 177, title: '关注日期', search: 'range'},
                    {field: 'ym', minWidth: 190, align: 'left', title: '域名', search: 'batch',templet:function (d) {
                            if (d.logs.length !== 0){
                                return '<font color="red">'+d.ym+'</font>'
                            }
                            return d.ym
                        }},
                    {field: 'get_time', minWidth: 177, title: '拿货日期', search: 'range',templet:function (d){
                        if (d.get_time){
                            return  d.get_time.split(' ')[0]
                        }
                       return  ''
                        }},
                    {field: 'update_time', minWidth: 177, title: '更新时间', search: 'range'},
                    {field: 'sale_price', minWidth: 200, title: '售价', search: false,templet:function (d) {
                        let sale_price_list = []
                        if (d.logs !== []){
                            d.logs.forEach(function (item) {
                                sale_price_list.push(item['sale_price'])
                            })


                        }
                        sale_price_list.push(d.sale_price)
                        return sale_price_list.join('->')

                        }},
                    {field: 'cost_price', minWidth: 120, title: '成本', search: false},
                    {
                        field: 'profit_cost', minWidth: 120, title: '利润', search: false, templet: function (d) {
                            return d.profit_cost??''
                            // if (d.cost_price) {
                            //     return (d.sale_price - d.cost_price).toFixed(2)
                            // }
                            // return ''

                        }
                    },
                    {
                        field: 'profit_cost_lv', minWidth: 120, title: '利润率', search: false, templet: function (d) {

                            return d.profit_cost_lv??''
                            // if (d.cost_price) {
                            //     return ((d.sale_price - d.cost_price) / d.sale_price * 100).toFixed(2) + "%"
                            // }
                            // return ''
                        }
                    },
                    {field: 'store_id', minWidth: 200, title: '卖家id', search: 'batch',templet:function (d) {
                            let list = []
                            if (d.logs !== []){
                                d.logs.forEach(function (item) {
                                    if (list[list.length - 1] !== item['store_id']) {
                                        list.push(item['store_id'])
                                    }
                                })
                            }
                            if (list[list.length - 1] !== d.store_id) {
                                list.push(d.store_id)
                            }
                            return list.join('->')
                        }},
                    {field: 'team', minWidth: 120, title: '团队介绍', search: false,templet:function (d) {
                            if ( d.getStore === null){
                                return ''
                            }
                            if ( d.getStore.team == null || d.getStore.team ===''){
                                return ''
                            } else{
                                return d.getStore.team
                            }
                    }},
                    {field: 'remark', minWidth: 120, title: '备注'},
                    {
                        field: 'sale_status',
                        width: 120,
                        title: '出售状态',
                        selectList: {'已删除': '已删除', '已下架': '已下架','出售中': '出售中', '已出售': '已出售',"未知":"未知","其他":"其他"}
                    },
                    {field: 'channel', minWidth: 120, title: '来源渠道', selectList: {"竞价":"竞价","注册":"注册","入库":"入库","其他":"其他","未知":"未知"},templet:function (d) {
                            // return d.channel?d.channel:''
                            return d.channel??''
                        }},
                    {field: 'cost_price', minWidth: 120, title: '成本价', selectList: {"0":"无"},hide:true},
                    {field: 'zcs', minWidth: 120, title: '注册商', search: false},
                    {
                        fixed: 'right',
                        width: 150,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [ 'edit',[

                            {
                            text: '取消关注',
                            url: init.cancel_like_url,
                            method: 'request',
                            field: 'ym_id',
                            auth: 'cancel_like',
                            class: 'layui-btn layui-btn-danger layui-btn-xs',
                        }],
                        ]
                    }
                ]],

                done:function (resp) {
                    let all_sale_price = resp['all_sale_price'];
                    let all_cost_price = resp['all_cost_price'];
                    let all_lirun_price = resp['all_lirun_price'];
                    let all_lirun_lv = resp['all_lirun_lv'];

                    $('#layui-table-page1').append('<font color="red">总销售额:'+all_sale_price+'  | 总成本:'+all_cost_price+'  | 总利润:'+all_lirun_price+' | 利润率:'+all_lirun_lv+'%</font>')




                }

            });


            ea.listen();
        },

        edit: function () {



            ea.listen()
        },

        batch_edit: function () {
            ea.listen()
        },

        attention: function () {
            var form = layui.form
            var table = layui.table
            $('#submit').click(function () {
                let ym = form.val("form")['ym']
                if ($.trim(ym) === '') {
                    layer.msg('请输入域名', {icon: 2})
                    return
                }
                console.log(ym)
                //第一个实例
                table.render({
                    elem: '#currentTable'
                    , height: 550
                    , url: 'search_ym?ym=' + ym.replaceAll('\n', ',') //数据接口
                    , page: true //开启分页
                    , limit: 30
                    , response: {
                        statusCode: 1 //规定成功的状态码，默认：0
                    }
                    , cols: [[ //表头
                        {type: "checkbox"}
                        , {field: 'id', title: 'ID', minWidth: 110}
                        , {field: 'ym', title: '域名', minWidth: 180}
                        , {field: 'jg', title: '售价', minWidth: 120}
                        , {field: 'cd', title: '长度', minWidth: 80}
                        , {field: 'ms', title: '备注', minWidth: 180}
                        , {field: 'zcsj', title: '注册时间', minWidth: 140}
                        , {field: 'sid', title: '店铺id', minWidth: 80}
                        , {field: 'lxtxt', title: '店铺类型', minWidth: 120}
                        , {field: 'zcs', title: '注册商', minWidth: 80}

                    ]]
                    , error: function () {
                        layer.msg('请再次查询~ 请求失败~', {icon: 2})
                    }
                });

            })

            //点击关注方法
            $('#submit_gz').click(function () {
                //获取选中数据
                var checkStatus = table.checkStatus('currentTable');
                if (checkStatus.data.length === 0) {
                    layer.msg('请选中要关注的域名~', {icon: 2})
                    return
                }
                //获取选中数据
                var selected_data = checkStatus.data
                var post_data = []
                selected_data.forEach(function (item) {
                    post_data.push({
                        id: item['id'],
                        ym: item['ym'],
                        jg: item['jg'],
                        ms: item['ms'],
                        zcsj: item['zcsj'],
                        sid: item['sid'],
                        lxtxt: item['lxtxt'],
                        zcs: item['zcs'],
                    })

                })

                console.log(post_data)

                ea.request.post({
                    url: 'attention',
                    // data:JSON.stringify({data:selected_data}),
                    data: {data: JSON.stringify(post_data)},
                }, function (resp) {
                    layer.msg('关注成功~', {icon: 1})
                }, function (resp) {
                    layer.msg('关注失败 ' + resp['msg'], {icon: 2})
                })


            })


            ea.listen()
        },


        fx_data:function () {
            console.log(123)
            ea.table.render({
                init: fx_init,
                height: 'full-40',
                limit:50,
                limits:[50,80,100],
                toolbar: ['refresh'],
                cols: [[
                    {type: "numbers"},
                    {field: 'ym', minWidth: 160, align: 'left', title: '域名', search: 'batch'},
                    {field: 'store_id', minWidth: 120, title: '卖家id', search: false},
                    {field: 'team', minWidth: 120, title: '团队简介', search: false},
                    {field: 'sale_price', minWidth: 120, title: '售价', search: false},

                ]],

            });
            ea.listen()
        }


    };
    return Controller;
});