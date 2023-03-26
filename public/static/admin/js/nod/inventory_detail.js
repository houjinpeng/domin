define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'nod.inventory_detail/index',


    };

    var Controller = {

        index: function () {
            var table = layui.table
            console.log(123)
            table.render({
                elem: '#currentTable',
                url:'index',
                limit:15,
                toolbar:['refresh'],
                limits:[15,30,50],
                cols: [[

                    {field: 'name', minWidth: 180, title: '仓库名'},
                    {field: 'count', minWidth: 180, title: '库存总数量'},
                    {field: 'price', minWidth: 180, title: '库存总金额'},
                    {field: 'info', minWidth: 180, title: '库存详情',event:'show',templet:function (d) {
                        var data = [];
                        d.info.forEach(function (item) {
                            data.push(item['good_name'])
                            })
                        return data.join(',')
                        }},

                ]],
            });

            //工具条事件
            table.on('tool(currentTable)', function(obj){ //注：tool 是工具条事件名，test 是 table 原始容器的属性 lay-filter="对应的值"
                var data = obj.data; //获得当前行数据
                var layEvent = obj.event; //获得 lay-event 对应的值（也可以是表头的 event 参数对应的值）
                var tr = obj.tr; //获得当前行 tr 的 DOM 对象（如果有的话）

                if(layEvent === 'show'){ //查看
                    let all_data = [];
                    data.info.forEach(function (item) {
                        all_data.push(item['good_name'])
                    })
                    //do somehing
                    layer.open({
                        title: data['name'] +' 详情'
                        ,area: ['500px', '300px']
                        , skin: 'demo-class'
                        ,content: all_data.join('<br>')
                    });




                }
            });


            ea.listen();
        },


    };
    return Controller;
});