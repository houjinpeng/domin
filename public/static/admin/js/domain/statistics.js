define(["jquery", "easy-admin","echarts"], function ($, ea,echarts) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'domain.statistics/index',

    };

    var Controller = {

        index: function () {
            var table = layui.table;

            var laydate = layui.laydate,
                form = layui.form,
                element = layui.element,
                sales1 = echarts.init(document.getElementById('sales1')),
                sales2 = echarts.init(document.getElementById('sales2')),
                sales3 = echarts.init(document.getElementById('sales3')),
                sales4 = echarts.init(document.getElementById('sales4')),
                sales6 = echarts.init(document.getElementById('sales6'))


            //初始化日期选项
            laydate.render({
                elem: '#test1',
                range: '~',
                max: 0,
            });

            //初始化属性
            var sx = xmSelect.render({
                el: '#sx',
                name:'attr',
                filterable: true,
                data: [{'name': 'Bd', 'value': 'Bd'},
                    {'name': 'Sg', 'value': 'Sg'},
                    {'name': '反链', 'value': '反链'},
                    {'name': '外链', 'value': '外链'},
                    {'name': '建站', 'value': '建站'},
                    {'name': '个人建站', 'value': '个人建站'},
                    {'name': '企业建站', 'value': '企业建站'},
                    {'name': 'Sg+Bd', 'value': 'Sg+Bd'},
                    {'name': '反链+建站', 'value': '反链+建站'},
                    {'name': '外链+个人建站', 'value': '外链+个人建站'},
                    {'name': 'Sg+企业建站', 'value': 'Sg+企业建站'},
                    {'name': 'Bd+建站', 'value': 'Bd+建站'},
                    {'name': '反链+个人建站', 'value': '反链+个人建站'},
                    {'name': '外链+企业建站', 'value': '外链+企业建站'},
                    {'name': 'Bd+个人建站', 'value': 'Bd+个人建站'},
                    {'name': '反链+企业建站', 'value': '反链+企业建站'},
                    {'name': 'Bd+企业建站', 'value': 'Bd+企业建站'},
                    {'name': 'Bd+外链+建站+企业建站', 'value': 'Bd+外链+建站+企业建站'},
                    {'name': '反链+Sg+个人建站', 'value': '反链+Sg+个人建站'},
                    {'name': '外链+建站+企业建站', 'value': '外链+建站+企业建站'},
                    {'name': 'Sg+个人建站', 'value': 'Sg+个人建站'},
                    {'name': '建站+企业建站', 'value': '建站+企业建站'},
                    {'name': 'Bd+Sg+企业建站', 'value': 'Bd+Sg+企业建站'},

                ]
            })


            //获取销量排名
            function set_sales_id_charts(resp){
                let x= []
                let y = []
                let total = 0
                for (let i in resp['data']){
                    x.push(resp['data'][i]['store_id'])
                    y.push(resp['data'][i]['count'])
                    total += parseInt(resp['data'][i]['count'])
                }

                let option = {
                    legend: {
                        left: 'right',
                        orient: 'vertical',
                        show: true
                    },
                    title: {
                        text: '当前统计总数量：'+total.toString()
                    },
                    tooltip: {
                        confine: true,
                        trigger: 'axis',
                        enterable: true, // 防止tooltip浮层在折线或柱体等上时，触发mouseover事件
                        backgroundColor: 'rgba(32, 33, 36,0.85)',
                        borderColor: 'rgba(32, 33, 36,0.20)',
                        textStyle: { // 文字提示样式
                            color: '#fff',
                            fontSize: '12'
                        },
                    },

                    yAxis: { minInterval:1,},
                    xAxis: {
                        data: x,
                        // axisLabel: { interval: 0,
                        //     show:true,
                        //     margin:20,
                        //     rotate: -60 }
                    },
                    dataZoom:{
                        type:'inside'
                    },
                    series: [
                        {
                            stillShowZeroSum: false,
                            type: 'bar',
                            data: y,
                            labelLine: {show: true}
                        }
                    ],

                };
                // 使用刚指定的配置项和数据显示图表。
                sales1.setOption(option, true);
                sales1.resize();
            }

            //设置销售金额排名图
            function set_sales_id_price_charts(resp){
                let x= []
                let y = []
                let total = 0
                for (let i in resp['data']){
                    x.push(resp['data'][i]['store_id'])
                    y.push(resp['data'][i]['price'])
                    total += parseInt(resp['data'][i]['price'])
                }

                let option = {
                    legend: {
                        left: 'right',
                        orient: 'vertical',
                        show: true
                    },
                    title: {
                        text: '当前统计总金额：'+total.toString()
                    },
                    tooltip: {
                        confine: true,
                        trigger: 'axis',
                        enterable: true, // 防止tooltip浮层在折线或柱体等上时，触发mouseover事件
                        backgroundColor: 'rgba(32, 33, 36,0.85)',
                        borderColor: 'rgba(32, 33, 36,0.20)',
                        textStyle: { // 文字提示样式
                            color: '#fff',
                            fontSize: '12'
                        },
                    },

                    yAxis: { minInterval:1,},
                    xAxis: {
                        data: x,
                        // axisLabel: { interval: 0,
                        //     show:true,
                        //     margin:20,
                        //     rotate: -60 }
                    },
                    dataZoom:{
                        type:'inside'
                    },
                    series: [
                        {
                            stillShowZeroSum: false,
                            type: 'bar',
                            data: y,
                            labelLine: {show: true}
                        }
                    ],

                };
                // 使用刚指定的配置项和数据显示图表。
                sales6.setOption(option, true);
                sales6.resize();
            }

            //每日销量走势
            function set_sales_zs_charts(resp){
                let x= []
                let y = []
                for (let i in resp['data']){
                    x.push(resp['data'][i]['fixture_date'])
                    y.push(resp['data'][i]['count'])
                }

                let option = {
                    legend: {
                        left: 'right',
                        orient: 'vertical',
                        show: true
                    },
                    tooltip: {
                        confine: true,
                        trigger: 'axis',
                        enterable: true, // 防止tooltip浮层在折线或柱体等上时，触发mouseover事件
                        backgroundColor: 'rgba(32, 33, 36,0.85)',
                        borderColor: 'rgba(32, 33, 36,0.20)',
                        textStyle: { // 文字提示样式
                            color: '#fff',
                            fontSize: '12'
                        },
                    },
                    dataZoom:{
                        type:'inside'
                    },
                    yAxis: { minInterval:1,},
                    xAxis: {
                        data: x,
                        axisLabel: { interval: 0,
                            show:true,
                            margin:6,
                            rotate: -60 }
                    },
                    series: [
                        {
                            stillShowZeroSum: false,
                            type: 'line',
                            data: y,
                            labelLine: {show: true}
                        }
                    ],

                };
                // 使用刚指定的配置项和数据显示图表。
                sales3.setOption(option, true);
                sales3.resize();
            }

            //价格段位销量排名
            function set_sales_price_charts(resp){
                let x= []
                let y = []
                for (let key in resp['data']){
                    x.push(key)
                    y.push(resp['data'][key])
                }

                let option = {
                    legend: {
                        left: 'right',
                        orient: 'vertical',
                        show: true
                    },
                    tooltip: {
                        confine: true,
                        trigger: 'axis',
                        enterable: true, // 防止tooltip浮层在折线或柱体等上时，触发mouseover事件
                        backgroundColor: 'rgba(32, 33, 36,0.85)',
                        borderColor: 'rgba(32, 33, 36,0.20)',
                        textStyle: { // 文字提示样式
                            color: '#fff',
                            fontSize: '12'
                        },
                    },
                    dataZoom:{
                        type:'inside'
                    },
                    yAxis: { minInterval:1,},
                    xAxis: {
                        data: x
                    },
                    series: [
                        {
                            stillShowZeroSum: false,
                            type: 'bar',
                            data: y,
                            labelLine: {show: true}
                        }
                    ],

                };
                // 使用刚指定的配置项和数据显示图表。
                sales2.setOption(option, true);
                sales2.resize();
            }

            //属性占比
            function set_attr_proportion_charts(resp){
                option = {
                    // title: {
                    //     text: 'Referer of a Website',
                    //     subtext: 'Fake Data',
                    //     left: 'center'
                    // },
                    tooltip: {
                        trigger: 'item'
                    },
                    legend: {
                        orient: 'vertical',
                        left: 'left'
                    },
                    series: [
                        {
                            name: '',
                            type: 'pie',
                            radius: '70%',
                            data: resp.data,
                            emphasis: {
                                itemStyle: {
                                    shadowBlur: 10,
                                    shadowOffsetX: 0,
                                    shadowColor: 'rgba(0, 0, 0, 0.5)'
                                }
                            }
                        }
                    ]
                };

                // 使用刚指定的配置项和数据显示图表。
                sales4.setOption(option, true);
                sales4.resize();

            }


            //点击统计
            $('#search_btn').click(function (d) {
                var all_data = form.val("searchForm");
                let fixture_date = all_data['fixture_date']
                if (fixture_date==='') {layer.msg('请选择统计时间~',{icon:2}) ;return}

                //获取id销量排名
                ea.request.get({
                    url:'get_sales_rank?fixture_date='+fixture_date
                },function (resp) {
                    set_sales_id_charts(resp)
                })
                //获取销售金额排名
                ea.request.get({
                    url:'get_sales_price_rank?fixture_date='+fixture_date
                },function (resp) {
                    set_sales_id_price_charts(resp)
                })

                //获取价格段位销量排名
                ea.request.post({
                    url:'get_sales_price',
                    data:{
                        'fixture_date':fixture_date,
                        'jg':all_data['jg']
                    }
                },function (resp) {
                    set_sales_price_charts(resp)
                })



                //获取每日销量走势
                ea.request.get({
                    url:'get_sales_zs?fixture_date='+fixture_date
                },function (resp) {
                    set_sales_zs_charts(resp)
                })

                //获取属性占比
                ea.request.post({
                    url:'attr_proportion',
                    data:{
                        'fixture_date':fixture_date,
                        'attr':all_data['attr']
                    }
                },function (resp) {
                    set_attr_proportion_charts(resp)
                })

                ea.table.render({
                    toolbar:[],
                    elem: '#ym_rank'
                    ,url: 'get_ym_rank?fixture_date='+fixture_date //数据接口
                    ,page: true //开启分页
                    ,cols: [[ //表头
                        {field: 'ym', title: '域名'},
                        {field: 'price', title: '价格',templet:function (d) {
                                return d.price.join(',')
                            }}
                        ,{field: 'count', title: '出现次数',search:false}
                    ]]
                });

            })


            $('#reset_btn').click(function () {
                $('input').val('')
                $('textarea').val('')
                sx.setValue([])

            })

            //监听Tab切换，以改变地址hash值
            element.on('tab(test1)', function () {
                sales1.resize();
                sales2.resize();
                sales3.resize();
                sales4.resize();
                sales6.resize();
            });

            ea.listen();
        },



    };
    return Controller;
});