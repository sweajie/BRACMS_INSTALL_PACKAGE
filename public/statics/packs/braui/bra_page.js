// 布拉组件 个个超神
define("bra_page", ["braui", "Vue"], function (braui, Vue) {
    return {
        vm: {},
        config: {
            list_url : '' ,
            has_list : true ,
            page_sign : '' ,
            bra_token : '' ,
            init_page_data : {} ,
            callbacks: {
                init_callback: {},
                before_load: {},
                load_more_callback: {}
            }
        },
        init_bra_page: function ( params) {
            var bra_page = this;
            var page_sign = bra_page.config.page_sign;
            this.vm = new Vue({
                el: "#Bra_App",
                data: {
                    params: bra_page.config.params,
                    page_data: {},
                    init_page_data: bra_page.config.init_page_data,

                    items: [],

                    loader : {
                        page: 1,
                        is_loading: false,
                        has_more: true,
                    } ,
                    pager: {
                        currentPage: 0,
                        totalPages: 15,
                        pages: 0
                    } ,

                    startY: '',    //保存touch时的Y坐标
                    moveDistance: 0,    //保存向下滑动的距离
                    moveState: 0,        //开始滑动到结束后状态的变化 0:下拉即可刷新 1:释放即可刷新 2:加载中
                    duration: 0       //动画持续时间，0就是没有动画


                },
                methods: {

                    touchStart(e) {
                        this.duration = 0; // 关闭动画
                        this.moveDistance = 0; // 滑动距离归0
                        this.startY = e.targetTouches[0].clientY  // 获得开始Y坐标
                    },
                    touchMove(e) {    //这里是整个下拉刷新的核心
                        let scrollTop = $('#scroll_content').offset().top;
                        if (scrollTop < 0) return; //首先判断我们有没有滚动条，如果有，我们下拉刷新就不能启用。
                        let move = e.targetTouches[0].clientY - this.startY;
                        if (move > 0) { //判断手指滑动的距离，只有为正数才代表用户下拉了。
                            e.preventDefault();//阻止默认事件，在微信浏览器中尤为有用，至于为什么，你去试就知道了。
                            this.moveDistance = Math.pow(move, 0.8); //增加滑动阻力的感觉
                            if (this.moveDistance > 50) { //如果滑动距离大于50 那我就告诉你，释放即可刷新
                                if (this.moveState === 1) return;
                                this.moveState = 1
                            } else {
                                this.moveState = 0
                            }
                            console.log(this.moveDistance);
                        }
                    },
                    touchEnd(e) {
                        var root = this;
                        this.duration = 300;//只要手指拿开，我都需要加上结束时的动画，这里为300ms
                        if (this.moveDistance > 50) {
                            this.moveState = 2;//moveState变为2 所以加载动画在这出现
                            this.moveDistance = 50;//因为还没加载完，我得让加载动画显示着，所以这里移动距离为50
                            console.log('refresh');

                            this.page_init();
                            root.moveState = 0
                        } else {
                            //否则 给我老老实实恢复原样
                            this.moveDistance = 0
                        }
                    },

                    page_init: function (callback) {
                        var root = this;
                        this.items = [];
                        this.page = 1;

                        if (bra_page.config.list_url) {
                            root.init_flow();
                        }else{
                            console.log('page no url  to load')
                        }
                    },

                    init_flow: function () {
                        var root = this;
                        layui.use('flow', function () {
                            var flow = layui.flow;
                            flow.load({
                                elem: '#bra-inf' //指定列表容器
                                , isAuto: true //设置是否自动加载
                                , isLazyimg: true
                                , done: function (page, next) {
                                    console.log(page);
                                    root.load_lists(page, function (res) {
                                        flow.lazyimg();
                                        next('', res.data.length > 0);
                                    });
                                }
                            }, 0);
                        });
                    },
                    load_lists(page, call_back , init) {
                        var root = this, params = {};

                        if(!root.loader.has_more){
                            return [];
                        }

                        //todo test if loading or has more
                        params = this.params;

                        params.page = this.loader.page = page;
                        root.is_loading = true;
                        $.post(bra_page.config.list_url, {
                            bra_action: 'post',
                            _token : bra_page.config.bra_token,
                            page: params.page,
                            query: params
                        }, function (res) {
                            console.log(res);
                            if(init === 1){
                                root.items = [];
                            }
                            root.page_data = res.data;

                            var list_data = res.list;

                            layui.each(list_data.data, function (index, item) {
                                root.items.push(item);
                            });

                            root.total_count = list_data.total;
                            root.total_pages = list_data.last_page;

                            sessionStorage.setItem(page_sign + "_data", JSON.stringify(root.items));

                            if (list_data.data.length > 0) {
                                root.loader.has_more = true;
                                sessionStorage.setItem(page_sign + "_page", page);// rember page num
                                var t = $(window).scrollTop();
                                $('body,html').animate({'scrollTop': t + 1}, 100).animate({'scrollTop': t}, 100)
                            }else{
                                root.loader.has_more = false;
                            }
                            call_back(list_data);

                            if (typeof bra_page.config.callbacks.load_more_callback === "function") {
                                bra_page.config.callbacks.load_more_callback();
                            }

                            root.pager = root.paginate(list_data.total , root.page , 15 , root.total_pages);
                        }, 'json').always(function () {
                            root.loader.is_loading = false;
                        });
                    },
                    change_opt: function (name, val) {
                        if (this.params.query) {
                            this.params.query[name] = val;
                        } else {
                            this.params[name] = val;
                        }

                        this.page_init();
                        $('.filter_panel , .bra-filter .mask').hide();
                    },
                    reset_opt: function () {
                        this.params.query = {};
                        this.page_init();
                    },
                    change_opt_from_dom: function (name, val) {
                        if (this.params.query) {
                            this.params.query[name] = val;
                        } else {
                            this.params[name] = val;
                        }
                        this.page_init();
                    },
                    paginate: function (totalItems, currentPage, pageSize, maxPages) {
                        // calculate total pages
                        let totalPages = Math.ceil(totalItems / pageSize);

                        // ensure current page isn't out of range
                        if (currentPage < 1) {
                            currentPage = 1;
                        } else if (currentPage > totalPages) {
                            currentPage = totalPages;
                        }

                        let startPage, endPage;
                        if (totalPages <= maxPages) {
                            // total pages less than max so show all pages
                            startPage = 1;
                            endPage = totalPages;
                        } else {
                            // total pages more than max so calculate start and end pages
                            let maxPagesBeforeCurrentPage = Math.floor(maxPages / 2);
                            let maxPagesAfterCurrentPage = Math.ceil(maxPages / 2) - 1;
                            if (currentPage <= maxPagesBeforeCurrentPage) {
                                // current page near the start
                                startPage = 1;
                                endPage = maxPages;
                            } else if (currentPage + maxPagesAfterCurrentPage >= totalPages) {
                                // current page near the end
                                startPage = totalPages - maxPages + 1;
                                endPage = totalPages;
                            } else {
                                // current page somewhere in the middle
                                startPage = currentPage - maxPagesBeforeCurrentPage;
                                endPage = currentPage + maxPagesAfterCurrentPage;
                            }
                        }

                        // calculate start and end item indexes
                        let startIndex = (currentPage - 1) * pageSize;
                        let endIndex = Math.min(startIndex + pageSize - 1, totalItems - 1);

                        // create an array of pages to ng-repeat in the pager control
                        let pages = Array.from(Array((endPage + 1) - startPage).keys()).map(i => startPage + i);

                        // return object with all pager properties required by the view
                        return {
                            totalItems: totalItems,
                            currentPage: currentPage,
                            pageSize: pageSize,
                            totalPages: totalPages,
                            startPage: startPage,
                            endPage: endPage,
                            startIndex: startIndex,
                            endIndex: endIndex,
                            pages: pages
                        };
                    }

                },
                mounted: function () {
                    var init_page = sessionStorage.getItem(page_sign + "_page");
                    var bra_last_page_sign = sessionStorage.getItem("bra_last_page");
                    if (bra_last_page_sign !== page_sign + '_detail') {
                        console.log('reset');
                        sessionStorage.setItem(page_sign + "_data", JSON.stringify([]));
                        this.items = [];
                        init_page = 0;
                    } else {
                        this.items = JSON.parse(sessionStorage.getItem(page_sign + "_data"));
                    }



                    this.page_init();
                },
                computed: {
                    style: function () {
                        return {
                            transition: `${this.duration}ms`,
                            transform: `translate3d(0,${this.moveDistance}px, 0)`
                        }
                    }
                },
                watch: {
                    total_count: function (total_count) {
                        this.pager = this.paginate(total_count , this.page , 15 , this.total_pages)
                    },
                    moveState: function (state) {
                        //oveState 0意味着开始也意味着结束，这里是结束，并且只有动画生效我们才能 moveDistance 设为0，
                        //为什么动画生效才行，因为动画生效意味着手指离开了屏幕，如果不懂去看touchEnd方法，这时
                        //我们让距离变为0才会有动画效果。
                        if (state === 0 && this.duration === 300) {
                            this.moveDistance = 0
                        }
                    }
                }
            });

            if (typeof this.config.callbacks.init_callback === 'function') {
                this.config.callbacks.init_callback();
            }
            return this.vm;
        },

        set_config: function (config) {
            this.config = $.extend(this.config, config);
            return this;
        }
    }
});
