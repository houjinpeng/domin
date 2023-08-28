define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'domain.attention_ym/index',
        edit_url: 'domain.attention_ym/edit',
        crawl_url: 'domain.attention_ym/crawl',
        cancel_like_url: 'domain.attention_ym/cancel_like',
        cancel_like_batch_url: 'domain.attention_ym/cancel_like_batch',
        attention_url: 'domain.attention_ym/attention',

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
                toolbar: ['refresh', [{
                    title: '关注域名',
                    text: "关注域名",
                    url: init.attention_url,
                    method: 'open',
                    auth: 'attention',
                    class: 'layui-btn layui-btn-sm layui-btn-warm',

                },
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
                        checkbox: true,
                        text: '批量编辑',
                        url: init.edit_url,
                        method: 'open',
                        auth: 'edit',
                        field: 'ym_id',
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
                ]],
                cols: [[
                    {type: "checkbox"},
                    {field: 'account', width: 110, title: '账号', selectList: bulid_select(customer_select_list, 'name')},
                    {field: 'like_time', width: 177, title: '关注日期', search: 'range'},
                    {field: 'ym', width: 160, align: 'left', title: '域名', search: 'batch'},
                    {field: 'get_time', width: 177, title: '拿货日期', search: 'range'},
                    {field: 'update_time', width: 177, title: '更新时间', search: 'range'},
                    {field: 'sale_price', width: 120, title: '售价', search: false},
                    {field: 'cost_price', width: 120, title: '成本', search: false},
                    {
                        field: 'profit_cost', width: 120, title: '利润', search: false, templet: function (d) {
                            if (d.cost_price) {
                                return (d.sale_price - d.cost_price).toFixed(2)
                            }
                            return ''

                        }
                    },
                    {
                        field: 'profit_cost_lv', width: 120, title: '利润率', search: false, templet: function (d) {
                            if (d.cost_price) {
                                return ((d.sale_price - d.cost_price) / d.sale_price * 100).toFixed(2) + "%"
                            }
                            return ''
                        }
                    },
                    {field: 'store_id', width: 120, title: '卖家id', search: 'batch'},
                    {field: 'remark', width: 120, title: '备注'},
                    {
                        field: 'sale_status',
                        width: 120,
                        title: '出售状态',
                        selectList: {'已删除': '已删除', '出售中': '出售中', '已出售': '已出售',"未知":"未知","其他":"其他"}
                    },
                    {field: 'channel', width: 120, title: '来源渠道', selectList: {"竞价":"竞价","注册":"注册","入库":"入库"}},
                    {field: 'zcs', width: 120, title: '注册商', search: false},
                    {
                        fixed: 'right',
                        width: 150,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [ 'edit',[{
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
            });


            ea.listen();
        },

        edit: function () {
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
        }


    };
    return Controller;
});