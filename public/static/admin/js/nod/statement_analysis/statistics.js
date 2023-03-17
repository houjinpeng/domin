define(["jquery", "easy-admin","echarts"], function ($, ea,echarts) {


    var show_init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'nod.statement_analysis.inventory/index',

    };

    function getOpt(data,title){
        // 指定图表的配置项和数据
        let option = {
            title: {
                text: title
            },
            tooltip: { trigger: 'axis'},
            legend: {
                data: ['数量']
            },
            xAxis: {
                data: data['time']
            },
            yAxis: {},
            series: [
                {
                    name: '数量',
                    type: 'line',
                    data: data['list']
                }
            ]
        };
        return option
    }


    var Controller = {

        index: function () {
            var every_ruku_list = JSON.parse($('#every_ruku_list').val())
            var every_chuku_list = JSON.parse($('#every_chuku_list').val())

            console.log(every_chuku_list)
            console.log(every_ruku_list)
            var myChart1 = echarts.init(document.getElementById('main1'));
            let opt1 = getOpt(every_ruku_list,'每日入库数量')
            // 使用刚指定的配置项和数据显示图表。
            myChart1.setOption(opt1);


            var myChart2 = echarts.init(document.getElementById('main2'));
            let opt2 = getOpt(every_chuku_list,'每日出库数量')
            // 使用刚指定的配置项和数据显示图表。
            myChart2.setOption(opt2);


            let opt3 = {
                title: {
                    text: '日销售额及毛利'
                },
                tooltip: {
                    trigger: 'axis'
                },
                legend: {
                    data: ['销售额', '毛利润']
                },
                xAxis: {
                    type: 'category',
                    boundaryGap: false,
                    data: every_chuku_list['time']
                },
                yAxis: {
                    type: 'value'
                },
                series: [

                    {
                        name: '毛利润',
                        type: 'line',
                        stack: 'Total',
                        data: every_chuku_list['day_profit']['list']
                    },
                    {
                        name: '销售额',
                        type: 'line',
                        stack: 'Total',
                        data:  every_chuku_list['day_sales']['list']
                    }
                ]
            };
            var myChart3 = echarts.init(document.getElementById('main3'));
            // 使用刚指定的配置项和数据显示图表。
            myChart3.setOption(opt3);


            var carousel = layui.carousel;
            //建造实例
            carousel.render({
                elem: '#test1'
                ,width: '100%' //设置容器宽度
                ,arrow: 'always' //始终显示箭头
                //,anim: 'updown' //切换动画方式
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





            ea.listen();
        }
    };
    return Controller;
});