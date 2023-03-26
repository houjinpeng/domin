define(["jquery", "easy-admin","echarts"], function ($, ea,echarts) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'nod.inventory_detail/index',


    };

    var Controller = {

        index: function () {

            var myChart = echarts.init(document.getElementById('main'));
            var user_list = JSON.parse($('#user_list').val())
            var sale_count_list = JSON.parse($('#sale_count_list').val())
            var profit_price_list = JSON.parse($('#profit_price_list').val())



            var option = {
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
                        dataView: { show: true, readOnly: false },
                        magicType: { show: true, type: ['line', 'bar'] },
                        restore: { show: true },
                        saveAsImage: { show: true }
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

                        interval: 50,
                        axisLabel: {
                            formatter: '{value} 条'
                        }
                    },
                    {
                        type: 'value',
                        name: '利润',
                        min: 0,

                        interval: 5,
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
            };

            myChart.setOption(option);
            ea.listen();
        },


    };
    return Controller;
});