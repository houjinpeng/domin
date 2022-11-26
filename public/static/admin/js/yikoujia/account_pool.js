define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'yikoujia.account_pool/index',
        delete_url: 'yikoujia.account_pool/delete',
        edit_url: 'yikoujia.account_pool/edit',
        add_url: 'yikoujia.account_pool/add',

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
                    extend: 'data-full="false"',
                }],'delete'],
                cols: [[
                    {type: "checkbox"},
                    {field: 'username', title: '账户名'},
                    {field: 'password', title: '密码'},
                    {field: 'is_daili', title: '账户类型',templet: function (d){
                        if (d.is_daili === 1) return '代理'
                        if (d.is_daili === 0) return '普通用户'
                        }},
                    {field: 'remark', title: '备注'},
                    {field: 'cookie', title: 'Cookie'},
                    {
                        fixed: 'right',
                        width: 200,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: ['edit','delete']
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