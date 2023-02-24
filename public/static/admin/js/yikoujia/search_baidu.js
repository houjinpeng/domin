define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'yikoujia.account_pool/index',


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
                ,page: true //开启分页
                ,cols: [[ //表头
                    {type: "checkbox"}
                    ,{field: 'ym', title: '域名', minWidth:120,templet:function (d) {
                            return '<a target="_blank" href="https://www.baidu.com/s?wd=site:'+d.ym+'">'+d.ym+'</a>'
                        }}
                    ,{field: 'sl', title: '收录', minWidth:80,}
                    ,{field: 'is_chinese', title: '是否是中文', minWidth:80,templet:function (d) {
                        return d.is_chinese === true? '是':"否"
                        }}
                    ,{field: 'mgc', title: '敏感词', minWidth:180,templet:function (d) {
                            return d.mgc === '无结果'?'无':d.mgc
                        }}
                    ,{field: 'jg', title: '结构', minWidth:180,}

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
                        url:'search_baidu?ym='+yms[i],
                        method:'get',
                        success:function (resp) {
                            let sy = parseInt($('#sy').text())
                            $('#sy').text(sy-1)
                            if (resp.code === 0){
                                let old_data = table.cache['resultTable']
                                resp.data.forEach(function (item) {
                                    old_data.push({
                                        ym:item['ym'],
                                        jg:item['data']['jg'],
                                        mgc:item['data']['mgc'],
                                        sl:item['data']['sl'],
                                        is_chinese:item['data']['is_chinese'],
                                    })
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