define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'nod.config.warehouse/index',
        add_url: 'nod.config.warehouse/add',
        edit_url: 'nod.config.warehouse/edit',
        delete_url: 'nod.config.warehouse/delete',
        show_url: 'nod.config.warehouse/show',

    };

    var show_init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'nod.config.warehouse/show',

    };

    var Controller = {

        index: function () {

            ea.table.render({
                init: init,
                limit:15,
                limits:[15,30,50],
                toolbar:['refresh','add','delete'],
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', width: 80, title: '编号'},
                    {field: 'name', minWidth: 80, title: '仓库名'},
                    {field: 'account', minWidth: 80, title: '账号'},
                    // {field: 'password', minWidth: 80, title: '密码',sort:true,search: false},
                    {field: 'linkman', minWidth: 80, title: '联系人',search: false},
                    {field: 'remark', minWidth: 80, title: '备注信息'},
                    {field: 'create_time', minWidth: 200, title: '创建时间', search: 'range'},
                    {
                        width: 250,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [
                            // [{
                            //     text: '出入记录',
                            //     url: init.show_url,
                            //     method: 'open',
                            //     auth: 'edit',
                            //     class: 'layui-btn layui-btn-xs layui-btn-primary',
                            //     extend: 'data-full="true"',
                            // }],
                            'edit',
                            'delete'
                        ]
                    }
                ]],
            });

            ea.listen();
        },


        add: function () {

            ea.listen();
        },
        edit: function () {
            ea.listen();
        },
        show: function () {
            ea.table.render({
                init: show_init,
                url:'show?warehouse_id='+$('#warehouse_id').val(),
                limit:50,
                limits:[50,100,200],
                height:'full-40',
                toolbar:['refresh','add','delete'],
                cols: [[
                    {type: "checkbox"},
                    {field: 'type', minWidth: 80, title: '类型',selectList:{'1':'采购','2':'出库','3':'转移'},templet:function (d) {
                            if (d.type === 1){
                                return '采购'
                            }if (d.type === 2){
                                return '出库'
                            }if (d.type === 3){
                                return '转移'
                            }
                        }},
                    {field: 'pid', minWidth: 180, title: '单据编号',templet:function (d) {
                            return d.getOrder.order_batch_num
                        }},
                    {field: 'pid', minWidth: 182, title: '单据日期',templet:function (d) {
                            return d.getOrder.order_time
                        }},

                    {field: 'good_name', minWidth: 152, title: '商品名称'},
                    {field: 'expiration_time', search:false,minWidth: 130, title: '过期时间'},
                    {field: 'register_time', search:false, minWidth: 130, title: '注册时间'},
                    {field: 'unit_price', search:false, minWidth: 100, title: '成本价'},
                    {field: 'account_id', minWidth: 110, title: '账号',templet:function (d) {
                            return d.getAccount.name
                        }},
                    {field: 'supplier_id', minWidth: 110, title: '来源渠道',templet:function (d) {
                            return d.getSupplier.name
                        }},
                    {field: 'remark', minWidth: 100, title: '备注信息'},
                    // {field: 'order_time', width: 80, title: '单据时间'},

                ]],
            });




            ea.listen();
        }
    };
    return Controller;
});