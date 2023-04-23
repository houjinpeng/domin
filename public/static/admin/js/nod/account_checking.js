define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'nod.account_checking/index',
        comp_account_url: 'nod.account_checking/comp_account',


    };



    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                page:false,
                search:false,
                toolbar:['refresh',[
                    {
                        title:'一键对账',
                        method:'request',
                        auth:'comp_account',
                        class: 'layui-btn layui-btn-normal layui-btn-sm',
                        url:init.comp_account_url

                    }
                ]],
                cols: [[
                    {field: 'name',minWidth: 168, title: '账号',fixed:'left'},
                    {field: 'my_total_inventory',minWidth: 168, title: '系统库存',templet:function (d) {
                            if (d.my_total_inventory){
                                let html = '<div account_name ="'+d.name+'" class="show_detail" goods_name = "'+d.my_total_inventory+'" >'+d.my_total_inventory.split(',').length+'</div>'
                                return html
                                
                            }
                            return 0
                        }},
                    {field: 'jvming_total_inventory',minWidth: 168, title: '聚名库存',templet:function (d) {
                            if (d.jvming_total_inventory){
                                let html = '<div account_name ="'+d.name+'"  class="show_detail" goods_name = "'+d.jvming_total_inventory+'" >'+d.jvming_total_inventory.split(',').length+'</div>'
                                return html
                                return 
                            }
                            return 0
                        }},
                    {field: 'cha_inventory',minWidth: 168, title: '相差库存',templet:function (d) {
                            if (d.cha_inventory){
                                let html = '<div account_name ="'+d.name+'"  class="show_detail" goods_name = "'+d.cha_inventory+'" ><font color="red">'+d.cha_inventory.split(',').length+'</font></div>'
                                return html
                              
                            }
                            return 0
                        }},
                    {field: 'my_total_price',minWidth: 168, title: '系统资金'},
                    {field: 'jvming_total_price',minWidth: 168, title: '聚名资金'},
                    {field: 'cha_price',minWidth: 168, title: '相差资金',templet:function (d) {
                            if (d.cha_price !== 0){
                                return '<font color="red">'+d.cha_price+'</font>'
                            }
                            return 0
                        }},
                    {field: 'create_time',minWidth: 168, title: '对比时间'},
                ]],
                done:function () {
                    $('[class=show_detail]').click(function () {
                        let data = this.getAttribute('goods_name')
                        let account_name = this.getAttribute('account_name')
                        layer.open({
                            title: '账号:'+account_name +'  共：'+data.split(',').length+'条记录'
                            ,area: ['500px', '300px']
                            ,btn: ['复制', '关闭']
                            , skin: 'demo-class'
                            ,content: data.split(',').join('<br>')
                            ,yes: function(index, layero){
                                //按钮【按钮一】的回调
                                ea.copyText(data.split(',').join("\n"))
                                // layer.msg('复制成功~',{icon:1})
                                return false
                            }
                            ,btn2: function(index, layero){

                            }
                        });
                    })
                }
            });



            ea.listen();
        },


    };
    return Controller;
});