define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'guoqi_domain.delete_ym/index',
        export_url: 'guoqi_domain.delete_ym/export',
        export_txt_url: 'guoqi_domain.delete_ym/export_txt',
    };

    var Controller = {

        index: function () {
            var util = layui.util;
            var laydate = layui.laydate

            // 验证输入是否是数字
            layui.form.verify({
                number: function (val) {
                    if (val === "" || val == null) {
                        return false;
                    }
                    if (!isNaN(val)) {
                    } else {
                        return '请填写数字'
                    }
                }
            })


            ea.table.render({
                init: init,
                limit: 50,
                limits: [50, 100, 500],
                height:'full-40',
                toolbar: ['refresh', 'export','export_txt', [
                    // {
                    //     title: '下载聚名删除文件',
                    //     url: '',
                    //     class: 'layui-btn layui-btn-normal layui-btn-sm',
                    //     method: 'open',
                    // }
                ]],
                cols: [[
                    // {type: "checkbox"},
                    {field: 'month', title: '删除月份', hide: true, search: 'time', timeType: 'month', searchValue: util.toDateString(new Date(), 'yyyy-MM')},
                    {field: 'source', title: '类型', hide: true, selectList:{'每日删除':'每日删除',外链:'外链',内链:"内链"}},
                    {field: 'ym', minWidth: 200, title: '域名', search: 'batch', align: 'left',fixed:'left'},
                    {field: 'cd', minWidth: 80, title: '长度', search: 'section', align: 'left'},
                    {field: 'hz', minWidth: 80, title: '后缀', align: 'left'},
                    {field: 'beian', minWidth: 90, title: '备案性质', align: 'left'},
                    {field: 'beian_num', minWidth: 200, title: '备案号', align: 'left'},
                    {field: 'beian_pcts', minWidth: 120, title: '备案审核时间', align: 'left'},
                    {field: 'beian_title', minWidth: 200, title: '备案名称', align: 'left'},
                    {field: 'so_num', minWidth: 80, title: '360', search: 'section', align: 'left'},
                    {field: 'baidu_num', minWidth: 80, title: '百度', search: 'section', align: 'left'},
                    {field: 'baidu_mingan', minWidth: 120, title: '百度敏感词', align: 'left'},
                    {field: 'baidu_jg', minWidth: 90, title: '百度结构', align: 'left'},
                    {field: 'baidu_url_lang', minWidth: 90, title: '百度语言', align: 'left'},
                    {field: 'sogou_num', minWidth: 80, title: '搜狗', search: 'section', align: 'left'},
                    {field: 'zcs', minWidth: 80, title: '注册商', align: 'left'},
                    {field: 'history_jl', minWidth: 100, search: 'section', title: '历史记录', align: 'left'},
                    {field: 'history_age', minWidth: 100, search: 'section', title: '历史年龄', align: 'left'},
                    {field: 'history_score', minWidth: 100, search: 'section', title: '历史分数', align: 'left'},
                    {field: 'history_tongyidu', minWidth: 100, search: 'section', title: '统一度', align: 'left'},
                    {field: 'history_max_lianxu', minWidth: 100, search: 'section', title: '最长连续', align: 'left'},
                    {field: 'history_five_lianxu', minWidth: 100, search: 'section', title: '五年连续', align: 'left'},
                    {field: 'history_five_num', minWidth: 100, search: 'section', title: '近五年历史', align: 'left'},
                    {field: 'history_chinese_num', minWidth: 100, search: 'section', title: '中文条数', align: 'left'},
                    {field: 'history_mingan', minWidth: 100, search: 'section', title: '敏感词', align: 'left'},
                    {field: 'yd_pr', minWidth: 100, title: '移动权重', search: 'section', align: 'left'},
                    {field: 'bd_pr', minWidth: 100, title: '百度权重', search: 'section', align: 'left'},
                    {field: 'sogou_pr', minWidth: 100, title: '搜狗权重', search: 'section', align: 'left'},
                    {field: 'sm_pr', minWidth: 100, title: '神马权重', search: 'section', align: 'left'},
                    {field: 'google_pr', minWidth: 120, title: 'google权重', search: 'section', align: 'left'},
                    {field: 'so_pr', minWidth: 100, title: '360权重', search: 'section', align: 'left'},
                    // {field: 'wl', minWidth: 80, title: '外链', search: 'section', align: 'left'},
                    // {field: 'fl', minWidth: 80, title: '反链', search: 'section', align: 'left'},
                    {field: 'v_rz', minWidth: 80, title: 'V认证', align: 'left'},
                    {
                        field: 'delete_time',
                        search: 'time',
                        timeType: 'date',
                        // searchValue: util.toDateString(new Date(), 'yyyy-MM-dd'),
                        minWidth: 200,
                        title: '删除日期',
                        align: 'left'
                    },

                    {
                        width: 250,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [

                            // [{
                            //     text: '设置密码',
                            //     url: init.index_url,
                            //     method: 'open',
                            //     auth: 'password',
                            //     class: 'layui-btn layui-btn-normal layui-btn-xs',
                            // }],

                        ]
                    }
                ]],
                done: function () {
                    $("[data-title='下载聚名删除文件']").click(function () {
                        console.log(123)
                        layer.open({
                            title: '下载删除列表',
                            btn: [],
                            area: ['500px', '300px'],
                            content: '<div class="layuimini-container">\n' +
                                '    <form id="app-form" class="layui-form layuimini-form">\n' +
                                '\n' +
                                '        <div class="layui-form-item">\n' +
                                '            <label class="layui-form-label required">选择日期</label>\n' +
                                '            <div class="layui-input-block layuimini-upload">\n' +
                                '                <input autocomplete="off" type="text" class="layui-input" id="download_date">\n' +
                                '               \n' +
                                '            </div>\n' +
                                '        </div>\n' +
                                '\n' +
                                '\n' +
                                '        <div class="layui-form-item text-center">\n' +
                                '            <button id="download" class="layui-btn layui-btn-normal layui-btn-sm">下载</button>\n' +
                                '        </div>\n' +
                                '\n' +
                                '    </form>\n' +
                                '</div>',
                            success: function () {
                                laydate.render({
                                    elem: '#download_date', //指定元素
                                    max: +4
                                });

                                $('#download').click(function () {
                                    var index = layer.load('正在下载删除文件...');

                                    let scsj = $('#download_date').val()
                                    $.ajax({
                                        url: 'download_delete_ym?scsj=' + scsj,
                                        method: 'get',
                                        success: function (resp) {
                                            layer.msg(resp['msg'])
                                            layer.close(index);
                                        }
                                    })

                                    return false

                                })

                            }
                        })

                        return false
                    })


                }
            });

            ea.listen();
        },
        add: function () {
            // var tree = layui.tree;
            //
            // ea.request.get(
            //     {
            //         url: 'get_group',
            //     }, function (res) {
            //         res.data = res.data || [];
            //         tree.render({
            //             elem: '#group_id',
            //             data: res.data,
            //             showCheckbox: false,
            //             onlyIconControl: true,
            //             accordion: true,
            //             id: 'nodeDataId',
            //         });
            //     }
            // );


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