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
                limits: [50, 100, 200, 500],
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
                    {field: 'title', title: '名称'},

                    {field: 'title', title: '筛选条件',width:160,templet:function (d) {
                        if (d.filter_count){
                            return d.filter_count.toString() +' 条 <button data-full="true" class="layui-btn layui-btn-xs" data-open="yikoujia.jkt/edit?id='+d.id+'">修改</button>'
                        }
                        return '0 条 <button data-full="true" class="layui-btn layui-btn-xs" data-open="yikoujia.jkt/edit?id='+d.id+'">修改</button>'
                        }},
                    {field: 'show', title: '查看',templet:function (d){
                            return '<button data-full="true" class="layui-btn layui-btn-xs" data-open="yikoujia.jkt/show_fuhe_list?id='+d.id+'">列表</button>'
                        }},
                    {field: 'main_filter', title: '主条件'},

                    {field: 'zhi', title: '支线任务1',templet:function (d){
                        if (d.zhixian[0]){
                            return  '<button style="width: 100px" data-full="false" class="layui-btn layui-btn-xs" data-open="yikoujia.jkt/show_zhi?id='+d.id+'">'+d.zhixian[0]['title']+'</button>'
                        }return ''}},

                    {field: 'zhi', title: '支线任务2',templet:function (d){
                            if (d.zhixian[1]){
                                return  '<button style="width: 100px" data-full="true" class="layui-btn layui-btn-xs" data-open="yikoujia.jkt/show_zhi?id='+d.id+'">'+d.zhixian[1]['title']+'</button>'
                            }return ''}},
                    {field: 'zhi', title: '支线任务3',templet:function (d){
                            if (d.zhixian[2]){
                                return  '<button style="width: 100px" data-full="true" class="layui-btn layui-btn-xs" data-open="yikoujia.jkt/show_zhi?id='+d.id+'">'+d.zhixian[2]['title']+'</button>'
                            }return ''}},
                    {field: 'zhi', title: '支线任务4',templet:function (d){
                            if (d.zhixian[3]){
                                return  '<button style="width: 100px" data-full="true" class="layui-btn layui-btn-xs" data-open="yikoujia.jkt/show_zhi?id='+d.id+'">'+d.zhixian[3]['title']+'</button>'

                            }return ''}},
                    {field: 'zhi', title: '支线任务5',templet:function (d){
                            if (d.zhixian[4]){
                                return  '<button style="width: 100px" data-full="true" class="layui-btn layui-btn-xs" data-open="yikoujia.jkt/show_zhi?id='+d.id+'">'+d.zhixian[4]['title']+'</button>'

                            }return ''}},
                    {field: 'zhi', title: '支线任务6',templet:function (d){
                            if (d.zhixian[5]){
                                return  '<button style="width: 100px" data-full="true" class="layui-btn layui-btn-xs" data-open="yikoujia.jkt/show_zhi?id='+d.id+'">'+d.zhixian[5]['title']+'</button>'

                            }return ''}},
                    {field: 'zhi', title: '支线任务7',templet:function (d){
                            if (d.zhixian[6]){
                                return  '<button style="width: 100px" data-full="true" class="layui-btn layui-btn-xs" data-open="yikoujia.jkt/show_zhi?id='+d.id+'">'+d.zhixian[6]['title']+'</button>'

                            }return ''}},
                    {field: 'zhi', title: '支线任务8',templet:function (d){
                            if (d.zhixian[7]){
                                return  '<button style="width: 100px" data-full="true" class="layui-btn layui-btn-xs" data-open="yikoujia.jkt/show_zhi?id='+d.id+'">'+d.zhixian[7]['title']+'</button>'

                            }return ''}},
                    {field: 'zhi', title: '支线任务9',templet:function (d){
                            if (d.zhixian[8]){
                                return  '<button style="width: 100px" data-full="true" class="layui-btn layui-btn-xs" data-open="yikoujia.jkt/show_zhi?id='+d.id+'">'+d.zhixian[8]['title']+'</button>'

                            }return ''}},
                    // {field: 'fenzhi1', title: '分支', templet: function (d) {
                    //         if (d.zhixian[0]) {
                    //             return '<button data-full="true" class="layui-btn layui-btn-xs" data-open="yikoujia.jkt/edit_zhi?id=' + d.zhixian[0].id + '">修改</button>'
                    //         }
                    //         return ''
                    //     }
                    // },
                    // {field: 'list1', title: '列表', templet: function (d) {
                    //         if (d.zhixian[0]) {
                    //             return ' <button data-full="true" class="layui-btn layui-btn-xs" data-open="'+init.show_buy_ym_url+'?id=' + d.zhixian[0].id + '">查看</button>'
                    //         }
                    //         return ''
                    //     }
                    // },
                    // {field: 'fu_is_buy_1', title: '购买', templet: function (d) {
                    //         if (d.zhixian[0]) {
                    //             if (d.zhixian[0].is_buy === 0) return '否'
                    //             if (d.zhixian[0].is_buy === 1) return '是'
                    //         }
                    //         return ''
                    //     }
                    // },
                    // {field: 'status_1', title: '状态', templet: function (d) {
                    //         if (d.zhixian[0]) {
                    //             if (d.zhixian[0].spider_status === null || d.zhixian[0].spider_status === 0) return '待运行'
                    //             if (d.zhixian[0].spider_status === 1) return '进行中'
                    //             if (d.zhixian[0].spider_status === 2) return '已完成'
                    //             if (d.zhixian[0].spider_status === 3) return '停止'
                    //         }
                    //         return ''
                    //     }
                    // },
                    //
                    // {field: 'fenzhi2', title: '分支', templet: function (d) {
                    //         if (d.zhixian[1]) {
                    //             return ' <button data-full="true" class="layui-btn layui-btn-xs" data-open="yikoujia.jkt/edit_zhi?id=' + d.zhixian[1].id + '">修改</button>'
                    //         }
                    //         return ''
                    //     }
                    // },
                    // {field: 'list2', title: '列表', templet: function (d) {
                    //         if (d.zhixian[1]) {
                    //             return ' <button data-full="true" class="layui-btn layui-btn-xs" data-open="'+init.show_buy_ym_url+'?id=' + d.zhixian[1].id + '">查看</button>'
                    //         }
                    //         return ''
                    //     }
                    // },
                    // {field: 'fu_is_buy_2', title: '购买', templet: function (d) {
                    //         if (d.zhixian[1]) {
                    //             if (d.zhixian[1].is_buy === 0) return '否'
                    //             if (d.zhixian[1].is_buy === 1) return '是'
                    //         }
                    //         return ''
                    //     }
                    //
                    // },
                    // {field: 'status_2', title: '状态', templet: function (d) {
                    //         if (d.zhixian[1]) {
                    //             if (d.zhixian[1].spider_status === null || d.zhixian[1].spider_status === 0) return '待运行'
                    //             if (d.zhixian[1].spider_status === 1) return '进行中'
                    //             if (d.zhixian[1].spider_status === 2) return '已完成'
                    //         }
                    //         return ''
                    //
                    //     }
                    // },
                    //
                    // {
                    //     field: 'fenzhi3', title: '分支', templet: function (d) {
                    //         if (d.zhixian[2]) {
                    //             return ' <button data-full="true" class="layui-btn layui-btn-xs" data-open="yikoujia.jkt/edit_zhi?id=' + d.zhixian[2].id + '">修改</button>'
                    //         }
                    //         return ''
                    //     }
                    // },
                    // {
                    //     field: 'list3', title: '列表', templet: function (d) {
                    //         if (d.zhixian[2]) {
                    //             return ' <button data-full="true" class="layui-btn layui-btn-xs" data-open="'+init.show_buy_ym_url+'?id=' + d.zhixian[2].id + '">查看</button>'
                    //         }
                    //         return ''
                    //     }
                    // },
                    // {
                    //     field: 'fu_is_buy_3', title: '购买', templet: function (d) {
                    //         if (d.zhixian[2]) {
                    //             if (d.zhixian[2].is_buy === 0) return '否'
                    //             if (d.zhixian[2].is_buy === 1) return '是'
                    //         }
                    //         return ''
                    //     }
                    //
                    // },
                    // {
                    //     field: 'status_3', title: '状态', templet: function (d) {
                    //         if (d.zhixian[2]) {
                    //             if (d.zhixian[2].spider_status === null || d.zhixian[2].spider_status === 0) return '待运行'
                    //             if (d.zhixian[2].spider_status === 1) return '进行中'
                    //             if (d.zhixian[2].spider_status === 2) return '已完成'
                    //         }
                    //         return ''
                    //
                    //     }
                    // },
                    // {
                    //     field: 'fenzhi3', title: '分支', templet: function (d) {
                    //         if (d.zhixian[2]) {
                    //             return ' <button data-full="true" class="layui-btn layui-btn-xs" data-open="yikoujia.jkt/edit_zhi?id=' + d.zhixian[2].id + '">修改</button>'
                    //         }
                    //         return ''
                    //     }
                    // },
                    // {
                    //     field: 'list3', title: '列表', templet: function (d) {
                    //         if (d.zhixian[2]) {
                    //             return ' <button data-full="true" class="layui-btn layui-btn-xs" data-open="'+init.show_buy_ym_url+'?id=' + d.zhixian[2].id + '">查看</button>'
                    //         }
                    //         return ''
                    //     }
                    // },
                    // {
                    //     field: 'fu_is_buy_3', title: '购买', templet: function (d) {
                    //         if (d.zhixian[2]) {
                    //             if (d.zhixian[2].is_buy === 0) return '否'
                    //             if (d.zhixian[2].is_buy === 1) return '是'
                    //         }
                    //         return ''
                    //     }
                    //
                    // },
                    // {
                    //     field: 'status_3', title: '状态', templet: function (d) {
                    //         if (d.zhixian[2]) {
                    //             if (d.zhixian[2].spider_status === null || d.zhixian[2].spider_status === 0) return '待运行'
                    //             if (d.zhixian[2].spider_status === 1) return '进行中'
                    //             if (d.zhixian[2].spider_status === 2) return '已完成'
                    //         }
                    //         return ''
                    //
                    //     }
                    // },
                    {
                        fixed: 'right',
                        width: 200,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [[{
                            text: '新增分支',
                            url: init.add_zhi_url,
                            method: 'open',
                            auth: 'edit',
                            class: 'layui-btn  layui-btn-xs',
                            extend: 'data-full="true"',
                        }, {
                            text: '全部停止',
                            url: init.stop_task_url,
                            method: 'request',
                            auth: 'stop_task',
                            class: 'layui-btn  layui-btn-xs layui-btn-normal',
                        }], 'delete']
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

        show_zhi:function (){
            layui.table.render({
                elem: '#currentTable',
                // init: zhi_init,
                height: 'full-40',
                search: false,
                toolbar: [],
                cols: [[
                    {align:'center', toolbar: '#barDemo1',title:'分支配置'},
                    {align:'center', toolbar: '#barDemo2',title:'查看列表'},
                    {align:'center', toolbar: '#barDemo3',title:'是否购买'},
                    {align:'center', toolbar: '#barDemo4',title:'运行状态'},
                ]],
                data:[JSON.parse($('#filter_data').val())]

            });
            ea.listen()
        },

        show_fuhe_list:function (){
            // ea.table.render({
            //     init: init,
            //     height: 'full-40',
            //     limit: 50,
            //     limits: [50, 100, 200, 500],
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
        }


    };
    return Controller;
});