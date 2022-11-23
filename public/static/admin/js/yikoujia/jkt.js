define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'yikoujia.jkt/index',
        delete_url: 'yikoujia.jkt/delete',
        edit_url: 'yikoujia.jkt/edit',
        add_url: 'yikoujia.jkt/add',
        add_zhi_url: 'yikoujia.jkt/add_zhi',
    };

    var Controller = {

        index: function () {

            ea.table.render({
                init: init,
                height: 'full-40',
                limit:50,
                limits:[50,100,200,500],
                search:false,
                toolbar:['refresh',[{
                    text:'添加',
                    url: init.add_url,
                    method: 'open',
                    auth: 'add',
                    icon: 'fa fa-plus ',
                    class: 'layui-btn  layui-btn-sm',
                    extend: 'data-full="true"',
                }],'delete'],
                cols: [[
                    {type: "checkbox"},
                    {field: 'title', title: '筛选条件'},
                    {field: 'getMainFilter.title', title: '购买主条件'},
                    {field: 'fenzhi1', title: '分支配置',templet:function (d){
                            if (d.fu_id_1 !== undefined){
                                return ' <button data-full="true" class="layui-btn layui-btn-xs" data-open="yikoujia.buy_filter/edit?id='+ d.fu_id_1+'">修改</button>'
                            }
                            return ''
                        }},
                    {field: 'list1', title: '查看列表',templet:function (d){
                        if (d.fu_id_1 !== undefined){
                            return ' <button data-full="true" class="layui-btn layui-btn-xs" data-open="yikoujia.buy_filter/edit?id='+ d.fu_id_1+'">查看</button>'
                        }
                            return ''
                        }},
                    {field: 'fu_is_buy_1', title: '是否购买',templet: function (d){
                        if (d.fu_is_buy_1 !== undefined){
                            if (d.fu_is_buy_1 ===0) return '否'
                            if (d.fu_is_buy_1 ===1) return '是'

                        }
                        return ''
                        }},
                    {field: 'status_1', title: '状态'},


                    {field: 'fenzhi2', title: '分支配置',templet:function (d){
                            if (d.fu_id_2 !== undefined){
                                return ' <button data-full="true" class="layui-btn layui-btn-xs" data-open="yikoujia.buy_filter/edit?id='+ d.fu_id_2+'">修改</button>'
                            }
                            return ''
                        }},
                    {field: 'list2', title: '查看列表',templet:function (d){
                            if (d.fu_id_2 !== undefined){
                                return ' <button data-full="true" class="layui-btn layui-btn-xs" data-open="yikoujia.buy_filter/edit?id='+ d.fu_id_2+'">查看</button>'
                            }
                            return ''
                        }},
                    {field: 'fu_is_buy_2', title: '是否购买',templet: function (d){
                            if (d.fu_is_buy_2 !== undefined){
                                if (d.fu_is_buy_2 ===0) return '否'
                                if (d.fu_is_buy_2 ===1) return '是'

                            }
                            return ''
                        }},
                    {field: 'status_2', title: '状态'},

                    {field: 'fenzhi3', title: '分支配置',templet:function (d){
                            if (d.fu_id_3 !== undefined){
                                return ' <button data-full="true" class="layui-btn layui-btn-xs" data-open="yikoujia.buy_filter/edit?id='+ d.fu_id_3+'">修改</button>'
                            }
                            return ''
                        }},
                    {field: 'list3', title: '查看列表',templet:function (d){
                            if (d.fu_id_3 !== undefined){
                                return ' <button data-full="true" class="layui-btn layui-btn-xs" data-open="yikoujia.buy_filter/edit?id='+ d.fu_id_3+'">查看</button>'
                            }
                            return ''
                        }},
                    {field: 'fu_is_buy_3', title: '是否购买',templet: function (d){
                            if (d.fu_is_buy_3 !== undefined){
                                if (d.fu_is_buy_3 ===0) return '否'
                                if (d.fu_is_buy_3 ===1) return '是'
                            }
                            return ''
                        }},
                    {field: 'status_3', title: '状态'},

                    {
                        fixed: 'right',
                        width: 150,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [[{
                            text:'新增分支',
                            url: init.add_zhi_url,
                            method: 'open',
                            auth: 'edit',
                            class: 'layui-btn  layui-btn-xs',
                            extend: 'data-full="true"',
                        }],'delete']
                    }
                ]],

            });

            ea.listen();
        },
        
        add:function (){
          
            
            ea.listen()
        },

        add_zhi:function (){
            var demo1 = xmSelect.render({
                el: '#demo1',
                max: 2,
                layVerify: 'required',
                name:'fu_filter_id',
                data: JSON.parse($('#z').val())
            })

            ea.listen()
        },
        
        edit:function (){
            ea.listen()
        }



    };
    return Controller;
});