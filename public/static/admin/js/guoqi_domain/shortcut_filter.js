define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'guoqi_domain.shortcut_filter/index',
        delete_url: 'guoqi_domain.shortcut_filter/delete',
        edit_url: 'guoqi_domain.shortcut_filter/edit',
        add_url: 'guoqi_domain.shortcut_filter/add',
        add_zhi_url: 'guoqi_domain.shortcut_filter/add_zhi',
        stop_task_url: 'guoqi_domain.shortcut_filter/stop_task',
        show_buy_ym_url: 'guoqi_domain.shortcut_filter/show_buy_ym',
        show_zhi: 'guoqi_domain.shortcut_filter/show_zhi',
        delete_buy_list_list: 'guoqi_domain.shortcut_filter/delete_buy_list?type=main',
        clear_result: 'guoqi_domain.shortcut_filter/clear_result',
        set_time_url: 'guoqi_domain.shortcut_filter/set_time',
    };


    var Controller = {

        index: function () {

            var group_list = ea.getSelectList('DomainGroup','id,name')
            function bulid_select(select_list,field='name'){
                let se = {}
                select_list.forEach(function (item) {
                    se[item['id']] = item[field]
                })
                return se

            }
            ea.table.render({
                init: init,
                height: 'full-40',
                limit: 50,
                limits: [50, 70, 200, 500],
                search: true,
                toolbar: ['refresh', [{
                    text: '添加',
                    url: init.add_url,
                    method: 'open',
                    auth: 'add',
                    icon: 'fa fa-plus ',
                    class: 'layui-btn  layui-btn-sm',
                    extend: 'data-full="true"',
                },{
                    text: '设置筛选日期',
                    url: init.set_time_url,
                    method: 'open',
                    auth: 'set_time',
                    class: 'layui-btn  layui-btn-sm',
                }], 'delete'],

                cols: [[
                    {type: "checkbox"},
                    {field: 'id', title: 'ID'},
                    {field: 'title',minWidth:180, title: '名称',align:'left'},
                    {field: 'group_id',minWidth:100, title: '分组名',hide:true,selectOp:'=',selectList:bulid_select(group_list)},
                    {field: 'group_name',minWidth:100, title: '分组名',search: false,templet:function (d) {
                            if (d.getGroup){
                                return d.getGroup.name
                            }
                            return ''
                        }},
                    {
                        field: 'title',search:false,align:'left', title: '筛选条件', minWidth: 160, templet: function (d) {
                            if (d.filter_count) {
                                return d.filter_count.toString() + ' 条 <button data-full="true" class="layui-btn layui-btn-xs" data-open="guoqi_domain.shortcut_filter/edit?id=' + d.id + '">修改</button>'
                            }
                            return '0 条 <button data-full="true" class="layui-btn layui-btn-xs" data-open="ym.shortcut_filter/edit?id=' + d.id + '">修改</button>'
                        }
                    },
                    {
                        field: 'show',search:false, minWidth:80,title: '查看', templet: function (d) {
                            return '<button data-full="true" class="layui-btn layui-btn-xs" data-open="guoqi_domain.shortcut_filter/show_fuhe_list?id=' + d.id + '">列表</button>'
                        }
                    },
                    {field: 'main_filter',search:false, title: '主条件'},

                    {
                        field: 'zhi',minWidth:100,search:false, title: '支线任务1', templet: function (d) {
                            if (d.zhixian[0]) {
                                if (d.zhixian[0].is_buy ===1){
                                    return '<button style="width: 70px" data-full="true" class="layui-btn layui-btn-warm layui-btn-xs" data-open="guoqi_domain.shortcut_filter/show_zhi?id=' + d.zhixian[0].id + '">' + d.zhixian[0]['title'] + '</button>'

                                }


                                return '<button style="width: 70px" data-full="true" class="layui-btn layui-btn-xs" data-open="guoqi_domain.shortcut_filter/show_zhi?id=' + d.zhixian[0].id + '">' + d.zhixian[0]['title'] + '</button>'
                            }
                            return ''
                        }
                    },

                    {
                        field: 'zhi',minWidth:100,search:false, title: '支线任务2', templet: function (d) {
                            if (d.zhixian[1]) {
                                if (d.zhixian[1].is_buy ===1){
                                    return '<button style="width: 70px" data-full="true" class="layui-btn layui-btn-warm layui-btn-xs" data-open="guoqi_domain.shortcut_filter/show_zhi?id=' + d.zhixian[1].id + '">' + d.zhixian[1]['title'] + '</button>'

                                }
                                return '<button style="width: 70px" data-full="true" class="layui-btn layui-btn-xs" data-open="guoqi_domain.shortcut_filter/show_zhi?id=' + d.zhixian[1].id + '">' + d.zhixian[1]['title'] + '</button>'
                            }
                            return ''
                        }
                    },
                    {
                        field: 'zhi',minWidth:100,search:false, title: '支线任务3', templet: function (d) {
                            if (d.zhixian[2]) {

                                if (d.zhixian[2].is_buy ===1){
                                    return '<button style="width: 70px" data-full="true" class="layui-btn layui-btn-warm layui-btn-xs" data-open="guoqi_domain.shortcut_filter/show_zhi?id=' + d.zhixian[2].id + '">' + d.zhixian[2]['title'] + '</button>'

                                }
                                return '<button style="width: 70px" data-full="true" class="layui-btn layui-btn-xs" data-open="guoqi_domain.shortcut_filter/show_zhi?id=' + d.zhixian[2].id + '">' + d.zhixian[2]['title'] + '</button>'
                            }
                            return ''
                        }
                    },
                    {
                        field: 'zhi',minWidth:100,search:false, title: '支线任务4', templet: function (d) {
                            if (d.zhixian[3]) {
                                if (d.zhixian[3].is_buy ===1){
                                    return '<button style="width: 70px" data-full="true" class="layui-btn layui-btn-warm layui-btn-xs" data-open="guoqi_domain.shortcut_filter/show_zhi?id=' + d.zhixian[3].id + '">' + d.zhixian[3]['title'] + '</button>'

                                }
                                return '<button style="width: 70px" data-full="true" class="layui-btn layui-btn-xs" data-open="guoqi_domain.shortcut_filter/show_zhi?id=' + d.zhixian[3].id + '">' + d.zhixian[3]['title'] + '</button>'

                            }
                            return ''
                        }
                    },
                    {
                        field: 'zhi',minWidth:100,search:false, title: '支线任务5', templet: function (d) {
                            if (d.zhixian[4]) {
                                if (d.zhixian[4].is_buy ===1){
                                    return '<button style="width: 70px" data-full="true" class="layui-btn layui-btn-warm layui-btn-xs" data-open="guoqi_domain.shortcut_filter/show_zhi?id=' + d.zhixian[4].id + '">' + d.zhixian[4]['title'] + '</button>'

                                }
                                return '<button style="width: 70px" data-full="true" class="layui-btn layui-btn-xs" data-open="guoqi_domain.shortcut_filter/show_zhi?id=' + d.zhixian[4].id + '">' + d.zhixian[4]['title'] + '</button>'

                            }
                            return ''
                        }
                    },
                    {
                        field: 'zhi',minWidth:100,search:false, title: '支线任务6', templet: function (d) {
                            if (d.zhixian[5]) {
                                if (d.zhixian[5].is_buy ===1){
                                    return '<button style="width: 70px" data-full="true" class="layui-btn layui-btn-warm layui-btn-xs" data-open="guoqi_domain.shortcut_filter/show_zhi?id=' + d.zhixian[5].id + '">' + d.zhixian[5]['title'] + '</button>'

                                }
                                return '<button style="width: 70px" data-full="true" class="layui-btn layui-btn-xs" data-open="guoqi_domain.shortcut_filter/show_zhi?id=' + d.zhixian[5].id + '">' + d.zhixian[5]['title'] + '</button>'

                            }
                            return ''
                        }
                    },
                    {
                        field: 'zhi',minWidth:100,search:false, title: '支线任务7', templet: function (d) {
                            if (d.zhixian[6]) {
                                if (d.zhixian[6].is_buy ===1){
                                    return '<button style="width: 70px" data-full="true" class="layui-btn layui-btn-warm layui-btn-xs" data-open="guoqi_domain.shortcut_filter/show_zhi?id=' + d.zhixian[6].id + '">' + d.zhixian[6]['title'] + '</button>'

                                }
                                return '<button style="width: 70px" data-full="true" class="layui-btn layui-btn-xs" data-open="guoqi_domain.shortcut_filter/show_zhi?id=' + d.zhixian[6].id + '">' + d.zhixian[6]['title'] + '</button>'

                            }
                            return ''
                        }
                    },
                    {
                        field: 'zhi',minWidth:100,search:false, title: '支线任务8', templet: function (d) {

                            if (d.zhixian[7]) {
                                if (d.zhixian[7].is_buy ===1){
                                    return '<button style="width: 70px" data-full="true" class="layui-btn layui-btn-warm layui-btn-xs" data-open="guoqi_domain.shortcut_filter/show_zhi?id=' + d.zhixian[7].id + '">' + d.zhixian[7]['title'] + '</button>'

                                }
                                return '<button style="width: 70px" data-full="true" class="layui-btn layui-btn-xs" data-open="guoqi_domain.shortcut_filter/show_zhi?id=' + d.zhixian[7].id + '">' + d.zhixian[7]['title'] + '</button>'

                            }
                            return ''
                        }
                    },
                    {
                        field: 'zhi',minWidth:100,search:false, title: '支线任务9', templet: function (d) {
                            if (d.zhixian[8]) {
                                if (d.zhixian[8].is_buy ===1){
                                    return '<button style="width: 70px" data-full="true" class="layui-btn layui-btn-warm layui-btn-xs" data-open="guoqi_domain.shortcut_filter/show_zhi?id=' + d.zhixian[8].id + '">' + d.zhixian[8]['title'] + '</button>'

                                }
                                return '<button style="width: 70px" data-full="true" class="layui-btn layui-btn-xs" data-open="guoqi_domain.shortcut_filter/show_zhi?id=' + d.zhixian[8].id + '">' + d.zhixian[8]['title'] + '</button>'

                            }
                            return ''
                        }
                    },
                    {
                        fixed: 'right',
                        width: 200,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [[{
                            text: '复制',
                            title:'是否复制模型？',
                            url: 'ym.shortcut_filter/copy_model',
                            method: 'request',
                            auth: 'copy_model',
                            class: 'layui-btn  layui-btn-xs layui-btn-warm',

                        },{
                            text: '新增分支',
                            url: init.add_zhi_url,
                            method: 'open',
                            auth: 'add_zhi',
                            class: 'layui-btn  layui-btn-xs',
                            extend: 'data-full="true"',
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
            var demo2 = xmSelect.render({
                el: '#demo2',
                toolbar:{
                    show: true,
                },
                name:'hz',
                filterable: true,
                height: '500px',
                data: [
                    {name: '.com', value: '.com'},
                    {name: '.net', value: '.net'},
                    {name: '.cn', value: '.cn'},
                    {name: '.cc', value: '.cc'},
                    {name: '.top', value: '.top'},
                    {name: '.vip', value: '.vip'},
                    {name: '.xyz', value: '.xyz'},
                    {name: '.com.cn', value: '.com.cn'},
                    {name: '.net.cn', value: '.net.cn'},
                    {name: '.org.cn', value: '.org.cn'},
                ]
            })
            ea.listen()
        },

        edit_zhi: function () {
            try {
                var hz_list = JSON.parse($('#filter_data').val())['hz'].split(',')
            }catch (e){
                hz_list = []
            }


            var demo2 = xmSelect.render({
                el: '#demo2',
                toolbar:{
                    show: true,
                },
                name:'hz',
                filterable: true,
                height: '500px',
                data: [
                    {name: '.com', value: '.com',selected: hz_list.indexOf('.com') !== -1},
                    {name: '.net', value: '.net',selected: hz_list.indexOf('.net') !== -1},
                    {name: '.cn', value: '.cn',selected: hz_list.indexOf('.cn') !== -1},
                    {name: '.cc', value: '.cc',selected: hz_list.indexOf('.cc') !== -1},
                    {name: '.top', value: '.top',selected: hz_list.indexOf('.top') !== -1},
                    {name: '.vip', value: '.vip',selected: hz_list.indexOf('.vip') !== -1},
                    {name: '.xyz', value: '.xyz',selected: hz_list.indexOf('.xyz') !== -1},
                    {name: '.com.cn', value: '.com.cn',selected: hz_list.indexOf('.com.cn') !== -1},
                    {name: '.net.cn', value: '.net.cn',selected: hz_list.indexOf('.net.cn') !== -1},
                    {name: '.org.cn', value: '.org.cn',selected: hz_list.indexOf('.org.cn') !== -1},
                ]
            })
            ea.listen()
        },

        show_zhi: function () {
            let table = layui.table
            table.render({
                elem: '#show_zhi',
                limit: 10000,
                search: false,
                toolbar: '#toolbarDemo',
                cols: [[
                    {field: 'id', title: 'ID'},
                    {field: 'title', title: '分支名称'},
                    {
                        field: 'fff', title: '分支配置', templet: function (d) {
                            return' <button class="layui-btn layui-btn-sm" title="修改分支" data-full="true"  data-open="ym.shortcut_filter/edit_zhi?id='+d.id+'">修改</button>'
                        }
                    },
                    {
                        field: 'fff', title: '列表', templet: function (d) {
                            if (d.is_buy===1){
                                return' <button class="layui-btn layui-btn-warm layui-btn-sm" title="修改分支" data-full="true"  data-open="guoqi_domain.shortcut_filter/show_buy_ym?id='+d.id+'">查看列表</button>'
                            }
                            return' <button class="layui-btn layui-btn-sm" title="修改分支" data-full="true"  data-open="guoqi_domain.shortcut_filter/show_buy_ym?id='+d.id+'">查看列表</button>'
                        }
                    },

                    {align: 'center', toolbar: '#barDemo7', title: '操作',width:200},
                ]],
                data: JSON.parse($('#filter_data').val())
            });

            //头部触发事件
            table.on('toolbar(show_zhi)', function(obj){
                var checkStatus = table.checkStatus(obj.config.id);
                switch(obj.event){
                    case 'refresh':
                        location.reload()
                }
            });
            //触发事件
            table.on('tool(show_zhi)', function (obj) {
                let data = obj.data
                if (obj.event === 'del') {
                    layer.confirm('确定要删除任务么 ', function (index) {
                        //do something
                        ea.request.post({
                            url: '/admin/ym.shortcut_filter/delete_zhi?id=' +data['id'],
                            ok:function (resp) {
                                layer.msg('删除成功')
                            }
                        })
                    });
                }
                else if (obj.event === 'copy') {
                    layer.confirm('确定要复制此条支线任务么 ', function (index) {
                        //do something
                        ea.request.post({
                            url: '/admin/ym.shortcut_filter/copy_model?id=' +data['id']+'&type=zhi',
                            ok:function (resp) {
                                layer.msg('复制成功')
                            }
                        })

                    });
                }
            });

            ea.listen()
        },

        show_fuhe_list: function () {
            ea.listen()
        },

        edit: function () {
            ea.listen()
        },
        set_time: function () {
            var laydate = layui.laydate;

            //执行一个laydate实例
            laydate.render({
                elem: '#test1' //指定元素
            });

            ea.listen()
        }


    };
    return Controller;
});