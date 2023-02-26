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
                    ,{field: 'wz', title: '域名', minWidth:200,templet:function (d) {
                            let url = 'http://www.jucha.com/lishi-info?ym='+btoa(encodeURI(d.wz))+'&token='+d.token
                            return '<a href="'+url+'" target="_blank">'+d.wz+'</a>'
                        }}
                    ,{field: 'bt', title: '网站标题', minWidth:180}
                    ,{field: 'pf', title: '评分', minWidth:180}
                    ,{field: 'nl', title: '使用年龄', minWidth:180}
                    ,{field: 'yy', title: '主要语言', minWidth:180}
                    ,{field: 'jls', title: '记录数', minWidth:180}
                    ,{field: 'sj_min', title: '最老记录时间', minWidth:180}
                    ,{field: 'sj_max', title: '最新记录时间', minWidth:180}

                ]]
            });


            $('#search').click(function () {

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
                let index = layer.msg('正在努力查询',{icon: 16})
                let count_ym = search_list.length
                $('#sy').text(count_ym)
                $.ajax({
                    url: 'get_token',
                    method: 'post',
                    data:{data:search_list},
                    success:function (resp) {
                        if (resp.code === 0){
                            layer.msg('获取token错误',{icon: 2})
                            return
                        }

                        get_data(resp)

                    }
                })

                function get_data(resp) {
                    let all_data = resp['data']
                    all_data.forEach(function (item) {
                        $.ajax({
                            url:'search',
                            method: 'post',
                            data:{'token':item['token'],ym:item['ym']},
                            success:function (resp,) {
                                // console.log(resp)
                                let old_data = table.cache['resultTable']
                                let data = resp['data']
                                data['token'] = item['token']
                                old_data.push(data)
                                table.reload('resultTable',{data:old_data,limit:100000})
                                layer.close(index)
                            }
                        })

                    })
                }


                // let count_ym = search_list.length
                // $('#sy').text(count_ym)
                // for (let i in search_list){
                //     $.ajax({
                //         url:'search?ym='+search_list[i],
                //         method:'get',
                //         success:function (resp) {
                //             let sy = parseInt($('#sy').text())
                //             $('#sy').text(sy-1)
                //             if (resp.code === 0){
                //                 let old_data = table.cache['resultTable']
                //                 resp.data.forEach(function (item) {
                //                     let d = false
                //                     try{
                //                         d =  item['data']['params']['list'][0]
                //                     }catch (e) {
                //
                //                     }
                //
                //
                //                     old_data.push({
                //                         ym:item['ym'],
                //                         data:d,
                //                     })
                //                 })
                //                 table.reload('resultTable',{data:old_data,limit:100000})
                //                 layer.close(index)
                //             }
                //
                //         }
                //
                //     })
                // }

            })


            ea.listen();
        },
        


    };
    return Controller;
});