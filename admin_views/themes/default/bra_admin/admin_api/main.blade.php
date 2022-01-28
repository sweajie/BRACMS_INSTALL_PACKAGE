@extends("themes." . $_W['theme'] . '.public.base_layout')

@section('top_header')
    <style>
        .sub-menu-top {
            display: none
        }

        .bra-btn {
            border: 0;
            padding: 20px 40px
        }

        .bg_green {
            background: #2dab78
        }

        .count .column {
            padding: 25px 0;
            transition: all .3s;
            cursor: pointer;
            background:rgb(86, 123, 94)
        }

        .count .column:hover {
            padding: 25px 30px;
            transition: all .3s;
        }

        .count .column:nth-child(1) {
            background: #52b78f
        }

        .count .column:nth-child(2) {
            background: #333d66
        }

        .count .column:nth-child(3) {
            background: #2dab78
        }

        .count .column:nth-child(4) {
            background: #da5268
        }

        .count .column:nth-child(5) {
            background: #854ad7
        }

        .count .column:nth-child(6) {
            background: #008acd
        }

        .bra-btn {
            transition: all .3s;
        }

        .bra-btn:hover {
            padding-left: 50px;
            padding-right: 50px;
            transition: all .3s;
        }

    </style>
@endsection
@section('main')
    @verbatim
        <div class="bra_cms_app" id="bra_cms_app" v-cloak="">
            <div class="columns is-mobile is-marginless is-multiline">
                <div class="column is-12">
                    <div class="has-bg-white is-padding p-a animated fadeInLeft">
                        <div
                                class="columns is-marginless is-mobile has-text-centered   count has-text-white   is-padding p-a">

                            <div class="column" v-for="item in todos" @click="open_menu_tab(item.menu_id)" :class="item.class">
                                <div class="is-size-4">{{item.amount}}</div>
                                <div>{{item.title}}</div>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="column is-6">

                    <div class="has-bg-white is-padding p-a" style="height: 350px">

                        <div id="pv_24_hour" style="width: 100%;height:100%;"></div>
                    </div>
                </div>

                <div class="column is-6">

                    <div class="has-bg-white is-padding p-a" style="height: 350px">

                        <div id="new_user_month" style="width: 100%;height:100%;"></div>
                    </div>
                </div>

                <div class="column is-6">

                    <div class="has-bg-white is-padding p-a" style="height: 350px">
                        <div id="pv_month" style="width: 100%;height:100%;"></div>
                    </div>
                </div>

                <div class="column is-6 is-hidden">
                    <div class="has-bg-white is-padding p-a" style="height: 350px">
                        <div id="myPie" style="width: 100%;height:100%;"></div>
                    </div>

                </div>
                <div class="column  is-6 is-hidden">
                    <div class="has-bg-white is-padding p-a" style="height: 350px">

                        <div id="myXian" style="width: 100%;height:100%;"></div>
                    </div>

                </div>
            </div>

        </div>
    @endverbatim

@endsection

@section('footer_js')
	<?php
	use Illuminate\Support\Carbon;use Illuminate\Support\Facades\DB;
	$lists = Db::table('users')->selectRaw("DATE_FORMAT( create_at, '%Y-%m-%d' ) AS 'ct' , count(id) as ids")->groupByRaw("DATE_FORMAT( create_at, '%Y-%m-%d' )")->get();

	$pv_month = Db::table('log')->selectRaw("DATE_FORMAT( create_at, '%Y-%m-%d' ) AS 'ct' , count(id) as ids")->groupByRaw("DATE_FORMAT( create_at, '%Y-%m-%d' )")->get();

	$pv_24_hour = Db::table('log')->where('create_at' , '>' , Carbon::yesterday())->selectRaw("DATE_FORMAT( create_at, '%d %H' ) AS 'ct' , count(id) as ids")->groupByRaw("DATE_FORMAT( create_at, '%d %H' )")->get();


	$donde['create_at'] = ['DAY', Carbon::today()];
	?>
    <script>

        require(['Vue', 'echarts', 'braui'], function (Vue, echarts, braui) {

            new Vue({
                el: "#bra_cms_app",
                data: {
                    echarts: echarts,
                    todos: {},
                    in_order_price: "{{$in_order_price}}",
                    total_order_price: "{{$total_price}}",
                    today_order_price: "{{$out_order_price + $in_order_price}}",
                    out_order_price: "{{$out_order_price}}"
                }
                ,
                methods: {
                    open_tab: function () {
                        parent.vm.open_diy_tab('https://www.baidu.com/', 'https://www.baidu.com/', '百度', {
                            sandbox: 'allow-same-origin allow-scripts allow-popups allow-forms'
                        });
                    },
                    open_menu_tab: function (menu_id) {
                        parent.vm.open_from_sub(menu_id);
                    },
                    load_todo: function () {
                        var $this = this;
                        $.post("{{$_W['current_url']}}", {
                            bra_action: 'post',
                            _token: '{{csrf_token()}}',
                        }, function (data) {
                            if (data.total > 0) {
                                braui.playSound('7.ogg');
                            }
                            $this.todos = data.todos;
                        }, 'json');
                    },

                    pv_24_hour() {
                        let myChart = this.echarts.init(document.getElementById('pv_24_hour'));

                        var opt = {
                            dataset: {
                                source: <?php
								echo json_encode($pv_24_hour);
								?>
                            },
                            title: {
                                text: '近一24小时APP访问'
                            },
                            tooltip: {},
                            xAxis: {
                                type: 'category'
                            },
                            yAxis: {
                                type: 'value'
                            },
                            grid: {

                                left: '1%',
                                right: '1%',
                                bottom: '1%',
                                containLabel: true
                            },
                            series: {
                                type: 'bar',
                                encode: {
                                    x: 'ct',
                                    y: 'ids'
                                },
                                barGap: '-10%',
                                barCategoryGap: '40%',

                                animation: true,
                                itemStyle: {
                                    color: new echarts.graphic.LinearGradient(
                                        0, 0, 0, 1,
                                        [
                                            {offset: 0, color: '#49ab8a'},
                                            {offset: 0.5, color: '#2dab78'},
                                            {offset: 1, color: '#2dab78'}
                                        ]
                                    )
                                },
                                showBackground: true,
                                backgroundStyle: {
                                    color: 'rgba(220, 220, 220, 0.8)'
                                }
                            },

                        };
                        console.log(opt);

                        myChart.setOption(opt);
                    },
                    pv_month() {
                        let myChart = this.echarts.init(document.getElementById('pv_month'));

                        var opt = {
                            dataset: {
                                source: <?php
								echo json_encode($pv_month);
								?>
                            },
                            title: {
                                text: '近一个月日PV'
                            },
                            tooltip: {},
                            xAxis: {
                                type: 'category'
                            },
                            yAxis: {
                                type: 'value'
                            },
                            grid: {

                                left: '1%',
                                right: '1%',
                                bottom: '1%',
                                containLabel: true
                            },
                            series: {
                                type: 'bar',
                                encode: {
                                    x: 'ct',
                                    y: 'ids'
                                },
                                barGap: '-10%',
                                barCategoryGap: '40%',

                                animation: true,
                                itemStyle: {
                                    color: new echarts.graphic.LinearGradient(
                                        0, 0, 0, 1,
                                        [
                                            {offset: 0, color: '#49ab8a'},
                                            {offset: 0.5, color: '#2dab78'},
                                            {offset: 1, color: '#2dab78'}
                                        ]
                                    )
                                },
                                showBackground: true,
                                backgroundStyle: {
                                    color: 'rgba(220, 220, 220, 0.8)'
                                }
                            },

                        };
                        console.log(opt);

                        myChart.setOption(opt);
                    },
                    drawPie() {
                        let myPie = this.echarts.init(document.getElementById('myPie'));

                        var opt = {
                            title: {
                                text: '订单分布'
                            },
                            tooltip: {
                                trigger: 'axis',
                                showContent: false
                            },
                            dataset: {
                                source: [
                                    ['外卖订单', {{$out_order}}],
                                    ['店内消费', {{$in_order}}]
                                ]
                            },
                            legend: {
                                orient: 'vertical',
                                left: 'left',
                                top: '30%'
                            },
                            series:
                                {
                                    type: 'pie',
                                    id: 'pie',
                                    radius: ["35%", "65%"],
                                    center: ['50%', '50%'],
                                    emphasis: {focus: 'data'},
                                    label: {
                                        formatter: '{b}: ({d}%)'
                                    },

                                    "itemStyle": {
                                        "borderWidth": 0,
                                        "borderColor": "#ccc"
                                    },
                                    "lineStyle": {
                                        "width": "1",
                                        "color": "#cccccc"
                                    },
                                    "symbolSize": "8",
                                    "symbol": "emptyCircle",
                                    "smooth": false,
                                    "color": [
                                        "#49ab8a",
                                        "#626c91"
                                    ],

                                }

                        };
                        console.log(opt);

                        myPie.setOption(opt);
                    },
                    drawXian() {
                        let myXian = this.echarts.init(document.getElementById('myXian'));

                        var opt = {

                            title: {
                                text: '用户统计图'
                            },
                            tooltip: {
                                trigger: 'axis'
                            },
                            legend: {
                                data: ['扫码用户', '公众号用户', '链接用户']
                            },
                            grid: {

                                left: '3%',
                                right: '4%',
                                bottom: '3%',
                                containLabel: true
                            },
                            toolbox: {
                                feature: {
                                    saveAsImage: {}
                                }
                            },
                            xAxis: {
                                type: 'category',
                                boundaryGap: false,
                                data: ['周一', '周二', '周三', '周四', '周五', '周六', '周日']
                            },
                            yAxis: {
                                type: 'value'
                            },
                            series: [
                                {
                                    name: '扫码用户',
                                    type: 'line',
                                    stack: '总量',
                                    "color": [
                                        "#49ab8a",

                                    ],
                                    data: [220, 132, 101, 134, 90, 210, 290]
                                },
                                {
                                    name: '公众号用户',
                                    type: 'line',
                                    stack: '总量',
                                    "color": [
                                        "#da5268",

                                    ],
                                    data: [10, 132, 101, 13, 180, 330, 100]
                                },
                                {
                                    name: '链接用户',
                                    type: 'line',
                                    stack: '总量',
                                    "color": [
                                        "#626c91",

                                    ],
                                    data: [100, 182, 91, 234, 290, 330, 310]
                                }

                            ]

                        };
                        console.log(opt);

                        myXian.setOption(opt);
                    },

                    new_user_month() {
                        let myTop = this.echarts.init(document.getElementById('new_user_month'));

                        var opt = {

                            dataset: {
                                source: <?php
								echo json_encode($lists);
								?>
                            },

                            title: {
                                text: '近一个月新用户'
                            },
                            grid: {
                                top: '5%',
                                left: '0%',
                                right: '3%',
                                bottom: '1%',
                                containLabel: true
                            },
                            xAxis: [
                                {
                                    type: 'value',
                                    boundaryGap: false,

                                }
                            ],
                            yAxis: [
                                {
                                    type: 'category',
                                    axisLabel: {interval: 0, rotate: 0},
                                }
                            ],

                            label: {
                                show: true,
                                position: 'inside'
                            },
                            series: {
                                type: 'bar',
                                encode: {
                                    x: 'ids',
                                    y: 'title'
                                },
                                barGap: '-10%',
                                barCategoryGap: '40%',

                                animation: true,
                                itemStyle: {
                                    color: '#49ab8a'
                                },

                            },

                        };
                        console.log(opt);

                        myTop.setOption(opt);
                    }

                },
                mounted() {
                    var $this = this;
                    this.pv_month();
                    this.pv_24_hour();
                    this.drawPie();
                    this.drawXian();

                    this.new_user_month();

                    $this.load_todo();
                    setInterval(function () {
                        $this.load_todo();
                    }, 20000);
                }
            });

        });
    </script>
@endsection
