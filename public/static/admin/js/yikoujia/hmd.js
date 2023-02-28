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

            ea.listen();
        },

    };
    return Controller;
});