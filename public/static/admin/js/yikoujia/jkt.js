define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'yikoujia.jkt/index',
        delete_url: 'yikoujia.jkt/delete',
        edit_url: 'yikoujia.jkt/edit',
        add_url: 'yikoujia.jkt/add',
        add_zhi_url: 'yikoujia.jkt/add_zhi',
        stop_task_url: 'yikoujia.jkt/stop_task',
        show_buy_ym_url: 'yikoujia.jkt/show_buy_ym',
        show_zhi: 'yikoujia.jkt/show_zhi',
    };

    var fuhe_init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'yikoujia.jkt/show_fuhe_list',

    };

    var Controller = {

        index: function () {

            ea.table.render({
                init: init,
                height: 'full-40',
                limit: 50,
                limits: [50, 70, 200, 500],
                search: false,
                toolbar: ['refresh', [{
                    text: '添加',
                    url: init.add_url,
                    method: 'open',
                    auth: 'add',
                    icon: 'fa fa-plus ',
                    class: 'layui-btn  layui-btn-sm',
                    extend: 'data-full="true"',
                }], 'delete'],
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', title: 'ID'},
                    {field: 'title',width:100, title: '名称'},
                    {field: 'spider_status',width: 95, title: '状态',templet:function (d) {
                            if (d.spider_status === 1){
                                return '<button class="layui-btn layui-btn-xs layui-btn-primary"><i style="color: #d0544c;" class="layui-icon layui-icon-loading layui-anim layui-anim-rotate layui-anim-loop" style="display: inline-block"></i>进行中</button>'
                            }else if(d.spider_status === 2){
                                return '<button class="layui-btn layui-btn-xs layui-btn-primary"><i style="color: #1029a1;" class="layui-icon layui-icon-ok-circle" style="display: inline-block"></i>完成</button>'
                            }else if(d.spider_status === 3){
                                return '<button class="layui-btn layui-btn-xs layui-btn-primary"><i style="color: #bbb2b2;" class="layui-icon layui-icon-close-fill " style="display: inline-block"></i>停止</button>'
                            }else if(d.spider_status === 4){
                                // return '<i style="color: #ff2222;" class="layui-icon layui-icon-tips" style="display: inline-block"></i>异常'
                                return '<button class="layui-btn layui-btn-xs layui-btn-primary layui-border-red"><i style="color: #ff2222;" class="layui-icon layui-icon-tips" style="display: inline-block"></i>异常</button>'
                            }else if (d.spider_status === 0) {
                                return '<button class="layui-btn layui-btn-xs layui-btn-primary"><i class="layui-icon layui-icon-time" style="display: inline-block"></i>待运行</button>'
                            }
                        }},
                    {
                        field: 'title', title: '筛选条件', width: 160, templet: function (d) {
                            if (d.filter_count) {
                                return d.filter_count.toString() + ' 条 <button data-full="true" class="layui-btn layui-btn-xs" data-open="yikoujia.jkt/edit?id=' + d.id + '">修改</button>'
                            }
                            return '0 条 <button data-full="true" class="layui-btn layui-btn-xs" data-open="yikoujia.jkt/edit?id=' + d.id + '">修改</button>'
                        }
                    },
                    {
                        field: 'show', width:80,title: '查看', templet: function (d) {
                            return '<button data-full="true" class="layui-btn layui-btn-xs" data-open="yikoujia.jkt/show_fuhe_list?id=' + d.id + '">列表</button>'
                        }
                    },
                    {field: 'main_filter', title: '主条件'},

                    {
                        field: 'zhi',width:100, title: '支线任务1', templet: function (d) {
                            if (d.zhixian[0]) {
                                return '<button style="width: 70px" data-full="true" class="layui-btn layui-btn-xs" data-open="yikoujia.jkt/show_zhi?id=' + d.zhixian[0].id + '">' + d.zhixian[0]['title'] + '</button>'
                            }
                            return ''
                        }
                    },

                    {
                        field: 'zhi',width:100, title: '支线任务2', templet: function (d) {
                            if (d.zhixian[1]) {
                                return '<button style="width: 70px" data-full="true" class="layui-btn layui-btn-xs" data-open="yikoujia.jkt/show_zhi?id=' + d.zhixian[1].id + '">' + d.zhixian[1]['title'] + '</button>'
                            }
                            return ''
                        }
                    },
                    {
                        field: 'zhi',width:100, title: '支线任务3', templet: function (d) {
                            if (d.zhixian[2]) {
                                return '<button style="width: 70px" data-full="true" class="layui-btn layui-btn-xs" data-open="yikoujia.jkt/show_zhi?id=' + d.zhixian[2].id + '">' + d.zhixian[2]['title'] + '</button>'
                            }
                            return ''
                        }
                    },
                    {
                        field: 'zhi',width:100, title: '支线任务4', templet: function (d) {
                            if (d.zhixian[3]) {
                                return '<button style="width: 70px" data-full="true" class="layui-btn layui-btn-xs" data-open="yikoujia.jkt/show_zhi?id=' + d.zhixian[3].id + '">' + d.zhixian[3]['title'] + '</button>'

                            }
                            return ''
                        }
                    },
                    {
                        field: 'zhi',width:100, title: '支线任务5', templet: function (d) {
                            if (d.zhixian[4]) {
                                return '<button style="width: 70px" data-full="true" class="layui-btn layui-btn-xs" data-open="yikoujia.jkt/show_zhi?id=' + d.zhixian[4].id + '">' + d.zhixian[4]['title'] + '</button>'

                            }
                            return ''
                        }
                    },
                    {
                        field: 'zhi',width:100, title: '支线任务6', templet: function (d) {
                            if (d.zhixian[5]) {
                                return '<button style="width: 70px" data-full="true" class="layui-btn layui-btn-xs" data-open="yikoujia.jkt/show_zhi?id=' + d.zhixian[5].id + '">' + d.zhixian[5]['title'] + '</button>'

                            }
                            return ''
                        }
                    },
                    {
                        field: 'zhi',width:100, title: '支线任务7', templet: function (d) {
                            if (d.zhixian[6]) {
                                return '<button style="width: 70px" data-full="true" class="layui-btn layui-btn-xs" data-open="yikoujia.jkt/show_zhi?id=' + d.zhixian[6].id + '">' + d.zhixian[6]['title'] + '</button>'

                            }
                            return ''
                        }
                    },
                    {
                        field: 'zhi',width:100, title: '支线任务8', templet: function (d) {
                            if (d.zhixian[7]) {
                                return '<button style="width: 70px" data-full="true" class="layui-btn layui-btn-xs" data-open="yikoujia.jkt/show_zhi?id=' + d.zhixian[7].id + '">' + d.zhixian[7]['title'] + '</button>'

                            }
                            return ''
                        }
                    },
                    {
                        field: 'zhi',width:100, title: '支线任务9', templet: function (d) {
                            if (d.zhixian[8]) {
                                return '<button style="width: 70px" data-full="true" class="layui-btn layui-btn-xs" data-open="yikoujia.jkt/show_zhi?id=' + d.zhixian[8].id + '">' + d.zhixian[8]['title'] + '</button>'

                            }
                            return ''
                        }
                    },
                    {
                        fixed: 'right',
                        width: 380,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [[{
                            text: '日志',
                            url: 'yikoujia.jkt/logs?type=1',
                            method: 'open',
                            auth: 'edit',
                            class: 'layui-btn  layui-btn-xs layui-btn-primary',
                        },{
                            text: '检测',
                            title:'检测程序是否在运行中',
                            url: 'yikoujia.jkt/check_status?type=zhu',
                            method: 'request',
                            auth: 'edit',
                            class: 'layui-btn  layui-btn-xs layui-btn-warm',

                        },{
                            text: '运行主线',
                            title:'是否在开一个主线运行程序?如果主线正在运行还是会重新开一个哦~',
                            url: 'yikoujia.jkt/restart_task?type=zhu',
                            method: 'request',
                            auth: 'edit',
                            class: 'layui-btn  layui-btn-xs',

                        },{
                            text: '新增分支',
                            url: init.add_zhi_url,
                            method: 'open',
                            auth: 'edit',
                            class: 'layui-btn  layui-btn-xs',
                            extend: 'data-full="true"',
                        }, {
                            text: '全部停止',
                            title:'是否要停止全部任务？',
                            url: init.stop_task_url,
                            method: 'request',
                            auth: 'stop_task',
                            class: 'layui-btn  layui-btn-xs layui-btn-danger',
                        },{
                            text: '删除',
                            title:'删除前请确认所有任务已停止！！！<br>不可逆谨慎操作！',
                            url: init.delete_url,
                            method: 'request',
                            auth: 'delete',
                            class: 'layui-btn  layui-btn-xs layui-btn-danger',
                        }]]
                    }
                ]],

            });

            ea.listen();
        },

        add: function () {


            ea.listen()
        },

        add_zhi: function () {

            ea.listen()
        },

        edit_zhi: function () {
            ea.listen()
        },

        show_zhi: function () {
            let table = layui.table
            table.render({
                elem: '#show_zhi',

                search: false,
                toolbar: [],
                cols: [[
                    {field: 'id', title: 'ID'},
                    {field: 'title', title: '分支名称'},
                    {
                        field: 'fff', title: '分支配置', templet: function (d) {
                           return' <button class="layui-btn layui-btn-sm" title="修改分支" data-full="true"  data-open="yikoujia.jkt/edit_zhi?id='+d.id+'">修改</button>'
                        }
                    },
                    {
                        field: 'fff', title: '列表', templet: function (d) {
                            return' <button class="layui-btn layui-btn-sm" title="修改分支" data-full="true"  data-open="yikoujia.jkt/show_buy_ym?id='+d.id+'">查看列表</button>'
                        }
                    },
                    // {align:'center', toolbar: '#barDemo3',title:'是否购买'},
                    {
                        field: 'spider_status', title: '运行状态', templet: function (d) {
                            if (d.spider_status === 1){
                                return '<button class="layui-btn layui-btn-sm layui-btn-primary"><i style="color: #d0544c;" class="layui-icon layui-icon-loading layui-anim layui-anim-rotate layui-anim-loop" style="display: inline-block"></i>进行中</button>'
                            }else if(d.spider_status === 2){
                                return '<button class="layui-btn layui-btn-sm layui-btn-primary"><i style="color: #1029a1;" class="layui-icon layui-icon-ok-circle" style="display: inline-block"></i>完成</button>'
                            }else if(d.spider_status === 3){
                                return '<button class="layui-btn layui-btn-sm layui-btn-primary"><i style="color: #bbb2b2;" class="layui-icon layui-icon-close-fill " style="display: inline-block"></i>停止</button>'
                            }else if (d.spider_status === 0) {
                                return '<button class="layui-btn layui-btn-sm layui-btn-primary"><i class="layui-icon layui-icon-time" style="display: inline-block"></i>待运行</button>'
                            }else if(d.spider_status === 4){
                                return '<button class="layui-btn layui-btn-sm layui-btn-primary layui-border-red"><i style="color: #ff2222;" class="layui-icon layui-icon-tips" style="display: inline-block"></i>异常</button>'
                            }
                        }
                    },

                    {align: 'center', toolbar: '#barDemo7', title: '操作',width:380},
                ]],
                data: JSON.parse($('#filter_data').val())
            });


            //触发事件
            table.on('tool(show_zhi)', function (obj) {
                let data = obj.data
                if (obj.event === 'del') {
                    layer.confirm('删除任务之前要停止任务哦！确定要删除任务么 ', function (index) {
                        //do something
                        ea.request.post({
                            url: '/admin/yikoujia.jkt/delete_zhi?id=' +data['id'],
                            ok:function (resp) {


                            }
                        })
                        setTimeout(function (){
                                parent.layer.closeAll()
                                parent.layui.table.reload('currentTableRenderId')},500,
                            )
                    });
                } else if (obj.event === 'stop_task') {
                    layer.confirm('确定要停止任务么？重新开始任务会继续哦~ ', function (index) {
                        //do something
                        ea.request.get({
                            url: '/admin/yikoujia.jkt/stop_zhi_task?id=' +data['id'],
                            ok:function (resp) {
                                layer.close(index);
                            }
                        })
                        setTimeout(function (){ location.reload()},500)

                    });
                } else if (obj.event === 'start_task') {
                    layer.confirm('确定要重新启动任务么 ', function (index) {
                        //do something
                        ea.request.get({
                            url: '/admin/yikoujia.jkt/restart_task?type=zhi&id=' +data['id'],
                            ok:function (resp) {
                                layer.close(index);
                            }
                        })
                        setTimeout(function (){ location.reload()},500)
                    });

                }else if (obj.event === 'check_task') {
                    layer.confirm('是否要检测程序运行状态~ ', function (index) {
                        ea.request.get({
                            url:'check_status?type=zhi&id='+data['id']
                        },function (resp) {
                            if (resp.code===1){
                                layer.msg('正在运行中~',{icon: 1})
                            }
                        })
                    });
                }else if (obj.event === 'del_list') {
                    layer.confirm('是否要清空列表中数据~ ', function (index) {
                        ea.request.get({
                            url:'delete_buy_list?type=zhi&id='+data['id']
                        },function (resp) {
                            layer.msg('清除成功~',{icon: 1})

                        })
                    });
                }

            });

            ea.listen()
        },

        show_fuhe_list: function () {
            // ea.table.render({
            //     init: init,
            //     height: 'full-40',
            //     limit: 50,
            //     limits: [50, 70, 200, 500],
            //     search: false,
            //     toolbar: ['refresh'],
            //     cols: [[
            //         {type: "checkbox"},
            //         {field: 'title', title: '名称'},
            //
            //     ]],
            //
            // });
            ea.listen()
        },

        edit: function () {
            ea.listen()
        },
        logs: function () {
            ea.listen()
        }


    };
    return Controller;
});