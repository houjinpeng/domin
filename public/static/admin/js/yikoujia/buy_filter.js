define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'yikoujia.buy_filter/index',
        delete_url: 'yikoujia.buy_filter/delete',
        edit_url: 'yikoujia.buy_filter/edit',
        add_url: 'yikoujia.buy_filter/add',
    };

    var Controller = {

        index: function () {

            ea.table.render({
                init: init,
                height: 'full-40',
                limit:50,
                limits:[50,100,200,500],
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
                    {field: 'id', title: 'ID'},
                    {field: 'title', title: '条件名称'},
                    {field: 'palce_num', title: '下单区间',templet: function (d){
                        return d.place_1+'-'+d.place_2
                        }},
                    {field: 'detail', title: '详情',templet: function (d){
                        return d.data

                        }},
                    {
                        fixed: 'right',
                        width: 150,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [[{
                            text:'编辑',
                            url: init.edit_url,
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
        
        edit:function (){
            ea.listen()
        }



    };
    return Controller;
});