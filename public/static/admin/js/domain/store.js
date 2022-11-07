define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'domain.store/index',
        delete_url: 'domain.store/delete',
        edit_url: 'domain.store/edit',
        add_url: 'domain.store/add',
        export_url: 'domain.store/export',
        add_like_url: 'domain.store/add_like',

    };

    var Controller = {

        index: function () {

            ea.table.render({
                init: init,
                height: 'full-40',
                limit:50,
                limits:[50,100,200,500],
                toolbar:['refresh','add','delete','export',[
                    {
                        checkbox:true,
                        text:'关注',
                        title:'是否要关注选中的所有店铺！',
                        icon: 'fa fa-plus ',
                        url: init.add_like_url,
                        method: 'request',
                        auth: 'add_like',
                        class: 'layui-btn layui-btn-normal layui-btn-sm',
                    }
                ]],
                cols: [[
                    {type: "checkbox"},
                    {field: 'store_id', width: 80, title: '店铺ID'},
                    {
                        field: 'name', minWidth: 80, title: '店铺名称', templet: function (d) {
                            return '<a target="_blank" href="' + d.url + '">' + d.name + '</a>'
                        }
                    },
                    {field: 'register_time', minWidth: 80, title: '注册时间'},
                    {field: 'yunying_num', minWidth: 80, title: '运营天数'},
                    {field: 'brief_introduction', minWidth: 80, title: '简介'},
                    {field: 'sales', minWidth: 80, title: '销量'},
                    {field: 'repertory', minWidth: 80, title: '库存'},
                    {field: 'store_cate_analyse', minWidth: 80, title: '店铺品类分析'},
                    {field: 'phone', minWidth: 80, title: '联系方式'},
                    {field: 'team', minWidth: 80, title: '所属团队'},
                    {field: 'individual_opinion', minWidth: 80, title: '个人意见'},

                   {fixed: 'right', width:250,title:'操作', toolbar: '#barDemo'} //这里的toolbar值是模板元素的选择器

                    //
                    // {
                    //     width: 250,
                    //     title: '操作',
                    //     templet: ea.table.tool,
                    //     operat: [[{
                    //         text: '关注',
                    //         url: init.add_jk_url,
                    //         method: 'request',
                    //         auth: 'show',
                    //         class: 'layui-btn layui-btn-normal layui-btn-xs',
                    //     }],
                    //         'edit',
                    //         'delete'
                    //     ]
                    // }
                ]],
            });




            ea.listen();
        },

        add: function () {
            var upload = layui.upload;

            //执行实例
            var uploadInst = upload.render({
                elem: '#upload_store' //绑定元素
                , url: '/admin/ajax/upload' //上传接口
                , accept: 'file' //允许上传的文件类型
                , exts: 'xlsx' //允许上传的文件类型
                , done: function (res) {
                    console.log(res)


                    let file_path = 'upload'+res['data']['url'].split('upload')[1]
                    ea.request.post({
                        url: '/admin/domain.store/add',
                        data:{'file_path':file_path}
                    },function (res) {
                        layer.msg('导入成功',{icon:1})
                        parent.layer.closeAll()
                        parent.layui.table.reload('currentTableRenderId');
                    })

                }
                , error: function () {
                    //请求异常回调
                }
            });
            ea.listen()
        },


        edit:function (){
            ea.listen()
        }



    };
    return Controller;
});