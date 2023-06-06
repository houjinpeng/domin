define(["jquery", "easy-admin", "echarts"], function ($, ea, echarts) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'nod.inventory_detail/index',


    };

    var Controller = {

        index: function () {
            var laydate = layui.laydate


            var element = layui.element;

            //获取hash来切换选项卡，假设当前地址的hash为lay-id对应的值
            var layid = location.hash.replace(/^#test1=/, '');
            element.tabChange('test1', layid); //假设当前地址为：http://a.com#test1=222，那么选项卡会自动切换到“发送消息”这一项

            //监听Tab切换，以改变地址hash值
            element.on('tab(test1)', function(){
                location.hash = 'test1='+ this.getAttribute('lay-id');
            });


            var myChart = echarts.init(document.getElementById('main'));
            var changku = echarts.init(document.getElementById('changku'));
            var qvdao = echarts.init(document.getElementById('qvdao'));
            var kehu = echarts.init(document.getElementById('kehu'));
            var user_list = JSON.parse($('#user_list').val())
            var sale_count_list = JSON.parse($('#sale_count_list').val())
            var profit_price_list = JSON.parse($('#profit_price_list').val())

            function set_chart(user_list, sale_count_list, profit_price_list) {
                return {
                    tooltip: {
                        trigger: 'axis',
                        axisPointer: {
                            type: 'cross',
                            crossStyle: {
                                color: '#999'
                            }
                        }
                    },
                    toolbox: {
                        feature: {
                            dataView: {show: true, readOnly: false},
                            magicType: {show: true, type: ['line', 'bar']},
                            restore: {show: true},
                            saveAsImage: {show: true}
                        }
                    },
                    legend: {
                        data: ['销售数', '利润']
                    },
                    xAxis: [
                        {
                            type: 'category',
                            data: user_list,
                            axisPointer: {
                                type: 'shadow'
                            }
                        }
                    ],
                    yAxis: [
                        {
                            type: 'value',
                            name: '销售数',
                            min: 0,
                            axisLabel: {
                                formatter: '{value} 条'
                            }
                        },
                        {
                            type: 'value',
                            name: '利润',
                            min: 0,

                            axisLabel: {
                                formatter: '{value} 元'
                            }
                        }
                    ],
                    series: [
                        {
                            name: '销售数',
                            type: 'bar',
                            tooltip: {
                                valueFormatter: function (value) {
                                    return value + ' 条';
                                }
                            },
                            data: sale_count_list
                        },
                        {
                            name: '利润',
                            type: 'bar',
                            tooltip: {
                                valueFormatter: function (value) {
                                    return value + ' 元';
                                }
                            },
                            data: profit_price_list
                        },

                    ]
                }
            }

            $('#main_t').empty()
            $('#main_t').append('总利润:'+jisuan(profit_price_list)+'元   总销售数:'+jisuan(sale_count_list)+'条')

            function jisuan(data_list){
                let total = 0;
                data_list.forEach(function (item) {
                    total+= item
                })
                return total
            }


            var date = new Date();
            var year = date.getFullYear();
            var month = date.getMonth() + 1;


            laydate.render({
                elem: '#test1' //指定元素
                , max: 1 //7天后
                , type: 'datetime'
                , range: true
                , value: year + '-' + month + '-01 00:00:00 - ' + ea.GetDateStr(0) + ' 23:59:59'
            });


            myChart.setOption(set_chart(user_list, sale_count_list, profit_price_list));

            $('#search').click(function () {
                //获取时间
                let t = $('#test1').val()
                if (t === '') {
                    layer.msg('时间范围不能为空~', {icon: 2})
                    return false
                }
                ea.request.get({
                    url: 'get_sale_user_profit?time=' + t,
                }, function (resp) {
                    myChart.setOption(set_chart(resp.data.sale_user_list, resp.data.sale_count_list, resp.data.profit_price_list));
                    $('#main_t').empty()
                    $('#main_t').append('总利润:'+jisuan(resp.data.profit_price_list)+'元   总销售数:'+jisuan(resp.data.sale_count_list)+'条')


                })
                ea.request.get({
                    url: 'get_store_profit?time=' + t,
                }, function (resp) {
                    $('#changku_t').empty()
                    $('#changku_t').append('总利润:'+jisuan(resp.data.profit_price_list)+'元   总销售数:'+jisuan(resp.data.sale_count_list)+'条')
                    changku.setOption(set_chart(resp.data.name_list, resp.data.sale_count_list, resp.data.profit_price_list));
                })
                ea.request.get({
                    url: 'get_supplier_profit?time=' + t,
                }, function (resp) {
                    $('#qvdao_t').empty()
                    $('#qvdao_t').append('总利润:'+jisuan(resp.data.profit_price_list)+'元   总销售数:'+jisuan(resp.data.sale_count_list)+'条')
                    qvdao.setOption(set_chart(resp.data.name_list, resp.data.sale_count_list, resp.data.profit_price_list));
                })
                ea.request.get({
                    url: 'get_customer_profit?time=' + t,
                }, function (resp) {
                    $('#kehu_t').empty()
                    $('#kehu_t').append('总利润:'+jisuan(resp.data.profit_price_list)+'元   总销售数:'+jisuan(resp.data.sale_count_list)+'条')
                    kehu.setOption(set_chart(resp.data.name_list, resp.data.sale_count_list, resp.data.profit_price_list));
                })


            })


            ea.listen();
        },


    };
    return Controller;
});