define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'nod.transfer/index',
        add_url: 'nod.transfer/add',
        edit_url: 'nod.transfer/edit',
        audit_url: 'nod.transfer/audit',


    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                limit:30,
                search:false,
                toolbar:['refresh','add'],
                limits:[30,50,100],
                cols: [[
                    {field: 'order_batch_num', minWidth: 180, title: '订单编号'},
                    {field: 'from_account', minWidth: 180, title: '转移账号',templet: function (d) {
                            return d.getFromAccount.name
                        }},
                    {field: 'to_account', minWidth: 180, title: '目标账号',templet: function (d) {
                            return d.getToAccount.name
                        }},
                    {field: 'practical_price', minWidth: 180, title: '转移金额'},



                    {field: 'order_user_id', minWidth: 180, title: '操作人',templet:function (d) {
                            return d.getOrderUser.username
                        }},
                    {field: 'order_time', minWidth: 180, title: '订单时间'},

                    {
                        fixed: 'right',
                        width: 180,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [
                            [ {
                                text: '编辑查看',
                                title:'编辑查看',
                                url: init.edit_url,
                                method: 'open',
                                auth: 'edit',
                                class: 'layui-btn layui-btn-xs',
                                extend: 'data-full="true"',
                            },
                                {
                                    text: '撤销',
                                    title:'是否要撤销当前单据？',
                                    url: init.chexiao_url,
                                    method: 'request',
                                    auth: 'chexiao',
                                    class: 'layui-btn layui-btn-xs layui-btn-danger',
                                    extend: 'data-full="true"',
                                }, {
                                text: '审核',
                                url: init.audit_url,
                                method: 'open',
                                auth: 'audit',
                                class: 'layui-btn layui-btn-xs',
                                extend: 'data-full="true"',
                            }]
                        ]
                    }
                ]],
            });

            ea.listen();
        },

        add:function () {

            ea.listen()
        },
        edit:function () {

            ea.listen()
        },
        audit:function () {

            ea.listen()
        }


    };
    return Controller;
});