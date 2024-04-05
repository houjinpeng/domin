define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'domain.jk/index',
        delete_url: 'domain.jk/delete',
        edit_url: 'domain.store/edit',
        add_url: 'domain.jk/add',
        show_url: 'domain.jk/show',
        add_like_url: 'domain.store/add_like',
    };


    var show_init = {
        table_elem: '#showTable',
        table_render_id: 'currentTableRenderId',
        index_url: '/admin/domain.jk/show',
        export_url: '/admin/domain.jk/export',
        refresh_store_url: 'domain.jk/refresh_store',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                height: 'full-40',
                limit:50,
                limits:[50,100,200,500],
                toolbar:['refresh',[
                    {
                        checkbox:true,
                        text:'刷新',
                        title:'是否要刷新选中的所有店铺数据！',
                        icon: 'fa fa-refresh',
                        url: show_init.refresh_store_url,
                        method: 'request',
                        auth: 'refresh_store',
                        class: 'layui-btn layui-btn-warm layui-btn-sm',
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
                    {field: 'get_sales_data_count', title: '销量',search: false},
                    {
                        width: 250,
                        fixed: 'right',
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [[{
                            text: '刷新',
                            url: show_init.refresh_store_url,
                            method: 'request',
                            auth: 'show',
                            class: 'layui-btn  layui-btn-xs layui-btn-warm',
                            extend: 'data-full="true"',
                        },{
                            text: '查看数据',
                            url: init.show_url,
                            method: 'open',
                            auth: 'show',
                            class: 'layui-btn  layui-btn-xs',
                            extend: 'data-full="true"',
                        },{
                            text: '取消关注',
                            url: init.add_like_url+'?type=0',
                            method: 'request',
                            auth: 'show',
                            class: 'layui-btn layui-btn-primary layui-btn-xs',
                        }],
                            'edit',
                        ]
                    }

                ]],


            });

            ea.listen();
        },

        add: function () {
            ea.listen()
        },


        edit:function (){
            ea.listen()
        },
        show: function () {
            show_init.export_url = 'domain.jk/export'+'?store_id='+$('#store_id').val(),
            ea.table.render({
                init: show_init,
                elem: '#showTable',
                url:show_init.index_url+'?store_id='+$('#store_id').val(),
                height: 'full-40',
                limit:50,
                limits:[50,100,200,500],
                toolbar:['refresh','export'],
                cols: [[
                    {field: 'ym', width: 180, title: '域名'},
                    {field: 'len', minWidth: 80, title: '长度',sort:true},
                    {field: 'hz', minWidth: 80, title: '后缀',hide:true,search: false},
                    {field: 'jj', minWidth: 80, title: '域名简介',search: false},
                    {field: 'mj_jj', minWidth: 80, title: '卖家简介',search: false},
                    {field: 'store_id', minWidth: 80, title: '卖家ID',search: false},
                    {field: 'fixture_date', minWidth: 80, title: '成交日期',sort:true,search:'range'},
                    {field: 'price', minWidth: 80, title: '价格',sort:true,searchOp:'='},
                    {field: 'team', minWidth: 80, title: '所属团队',search: false,templet:function (d) {
                            if (d.getSalesData){
                                return d.getSalesData.team
                            }
                            return ''
                        }},
                    {field: 'yj', minWidth: 80, title: '个人意见',search: false,templet:function (d) {
                            if (d.getSalesData){
                                return d.getSalesData.individual_opinion
                            }
                            return ''
                        }},

                ]],
            });




            // layui.table.render({
            //     elem: '#currentTable',
            //     table_render_id: 'currentTableRenderId',
            //     height: 'full-40',
            //     limit:50,
            //     search:false,
            //     toolbar:[],
            //     limits:[50,100,200,500],
            //     cols: [[
            //         {field: 'ym', width: 180, title: '域名',search:'batch'},
            //         {field: 'len', minWidth: 80, title: '长度',sort:true},
            //         {field: 'jj', minWidth: 80, title: '域名简介'},
            //         {field: 'store_id', minWidth: 80, title: '卖家ID',search:'batch',templet: function (d) {
            //                 if (d.store_id){
            //                     return d.store_id
            //                 }
            //                 return d.store_id_hide
            //             }},
            //         {field: 'fixture_date', minWidth: 80, title: '成交日期',sort:true,search: 'range'},
            //         {field: 'price', minWidth: 80, title: '价格',search: 'section',sort:true},
            //
            //     ]],
            //     data:JSON.parse($('#all_data').val())
            // });

            ea.listen();
        },



    };
    return Controller;
});