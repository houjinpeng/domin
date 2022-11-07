define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'domain.jk/index',

    };

    var Controller = {

        index: function () {

            ea.listen();
        },

        add: function () {
            ea.listen()
        },


        edit:function (){
            ea.listen()
        }



    };
    return Controller;
});