define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'domain.sales/index1',
        delete_url: 'domain.sales/delete',
        edit_url: 'domain.sales/edit',
        add_url: 'yikoujia.config/add',
        export_url: 'domain.sales/export',
        add_jk_url: 'domain.sales/add_jk',
        add_like_url: 'domain.sales/add_like',
        reset_data_url: 'domain.sales/reset_data',
    };

    var Controller = {

        index: function () {
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
                }]],
                cols: [[
                    {type: "checkbox"},
                    {field: 'ym', width: 180, title: '域名',search:'batch'},
                    {field: 'len', minWidth: 80, title: '长度',sort:true},
                    {field: 'hz', minWidth: 80, title: '后缀',hide:true,search: 'xmSelect',xm_data:[{'name':'.com','value':'com'},{'name':'.net','value':'.net'},{'name':'.cc','value':'.cc'}
                            ,{'name':'.top','value':'.top'},{'name':'.vip','value':'.vip'},{'name':'.cn','value':'.cn'},{'name':'.com.cn','value':'.com.cn'},{'name':'.net.cn','value':'.net.cn'},{'name':'.org.cn','value':'.prg.cn'}]},
                    {field: 'jj', minWidth: 80, title: '域名简介'},
                    {field: 'mj_jj', minWidth: 80, title: '卖家简介'},
                    {field: 'store_id', minWidth: 80, title: '卖家ID',search:'batch',templet: function (d) {
                            if (d.store_id){
                                return d.store_id
                            }
                            return d.store_id_hide
                        }},
                    {field: 'fixture_date', minWidth: 80, title: '成交日期',sort:true,search: 'range'},
                    {field: 'price', minWidth: 80, title: '价格',search: 'section',sort:true},
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
                    {field: 'is_get_store', minWidth: 80, title: '匹配店铺ID',selectList:{0: '未匹配', 1: '已匹配',2: '匹配失败'},hide:true},
                    // {fixed: 'right', width:250,title:'操作', toolbar: '#barDemo'}, //这里的toolbar值是模板元素的选择器

                    {
                        fixed: 'right',
                        width: 150,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [[{
                            text: '关注',
                            url: init.add_like_url,
                            method: 'request',
                            auth: 'show',
                            class: 'layui-btn  layui-btn-xs layui-btn-warm',
                            extend: 'data-full="true"',
                        }],'edit',
                        ]
                    }
                ]],
                done:function (res) {

                    $.each(res.data,function (k,v){
                        if (v.getSalesData){
                            if (v.getSalesData.is_like === 1){
                                // 复用按钮是否可用
                                $('div[lay-id="'+init.table_render_id+'"]').find('tr[data-index="' + k + '"]').find('a[data-title="关注"]').addClass('layui-btn-disabled').attr('disabled',true).css('pointer-events','none');
                            }
                        }
                    })

                }
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