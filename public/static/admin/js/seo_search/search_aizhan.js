define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',

    };

    var Controller = {

        index: function () {
            var table = layui.table

            //第一个实例
            table.render({
                elem: '#resultTable'
                ,height: 'full-40'
                ,limit: 10000
                ,data:[]
                ,toolbar:true
                ,page: false //开启分页
                ,cols: [[ //表头
                    {type: "checkbox"}
                    ,{field: 'ym', title: '域名', minWidth:200,templet:function (d) {
                            return '<a href="https://www.aizhan.com/cha/'+d.ym+'/" target="_blank">'+d.ym+'</a>'
                        }}
                    ,{field: 'baidu_pr', title: '百度权重', minWidth:200}
                    ,{field: 'yidong_pr', title: '移动权重', minWidth:200}
                    ,{field: 'so_pr', title: '360权重', minWidth:200}
                    ,{field: 'shenma_pr', title: '神马权重', minWidth:200}
                    ,{field: 'sogou_pr', title: '搜狗权重', minWidth:200}
                ]]
            });


            $('#search').click(function () {
                let index = layer.msg('正在努力查询',{icon: 16})
                table.reload('resultTable',{data:[],limit:100000})
                let all_ym = $('#yms').val()
                let yms = all_ym.split('\n')
                //去掉不可用的域名和重复的域名
                let search_list = []
                for (let i in yms){
                    if ($.trim(yms[i]) === '')continue
                    if (search_list.indexOf($.trim(yms[i])) === -1){
                        search_list.push($.trim(yms[i]))
                    }
                }
                let count_ym = search_list.length
                $('#sy').text(count_ym)
                for (let i in search_list){
                    $.ajax({
                        url:'search?ym='+search_list[i],
                        method:'get',
                        success:function (resp) {
                            let sy = parseInt($('#sy').text())
                            $('#sy').text(sy-1)
                            if (resp.code === 0){
                                let old_data = table.cache['resultTable']
                                resp.data.forEach(function (item) {
                                    item['ym'] = search_list[i]
                                    old_data.push(item)
                                })
                                table.reload('resultTable',{data:old_data,limit:100000})
                                layer.close(index)
                            }

                        }

                    })
                }

            })


            ea.listen();
        },
        


    };
    return Controller;
});