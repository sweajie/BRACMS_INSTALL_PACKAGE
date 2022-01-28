// 布拉组件 个个超神 , "async!BMap"
define("bra_map", ["braui", 'async!amap', "async!BMap"], function (braui) {
    return {
        get_by_html5: function (callback) {
            function getLocation() {
                var options = {
                    enableHighAccuracy: true,
                    maximumAge: 1000
                };
                if (navigator.geolocation) {//浏览器支持geolocation
                    navigator.geolocation.getCurrentPosition(onSuccess, onError, options);
                } else {//浏览器不支持geolocation
                    alert("浏览器不支持geolocation");
                }
            }

            function onSuccess(position) {
                var lng = position.coords.longitude; // + 0.008774687519
                var lat = position.coords.latitude; // + 0.00374531687912
                console.log(position);
                callback({
                    code: 1,
                    data: [lng, lat]
                });
            }

            function onError(error) {
                switch (error.code) {
                    case 1:
                        callback({
                            code: 100,
                            msg: "位置服务被拒绝"
                        });
                        break;
                    case 2:
                        callback({
                            code: 200,
                            msg: "暂时获取不到位置信息,可能您的手机已经禁用位置信息!"
                        });
                        break;

                    case 3:
                        callback({
                            code: 300,
                            msg: "获取信息超时"
                        });
                        break;
                    case 4:
                        callback({
                            code: 400,
                            msg: "未知错误"
                        });
                        break;
                }
            }

            getLocation();
        },
        get_address_wgs84: function (lat, lng, callback) {
            var root = this;
            var pointArr = [];
            var ggPoint = new BMap.Point(lng, lat);
            pointArr.push(ggPoint);
            var convertor = new BMap.Convertor();
            convertor.translate(pointArr, 1, 5, function (data) {
                if (data.status === 0) {
                    root.get_address_bmap(data.points[0], function (rs) {
                        if (typeof callback === "function") {
                            callback(rs);
                        }
                    });
                }
            });

        },
        get_address_bmap: function (point, callback) {
            var gc = new BMap.Geocoder();
            gc.getLocation(point, function (rs) {
                console.log(rs);
                if (typeof callback === "function") {
                    callback(rs);
                }
            });

        },
        do_local_search: function (keyword, point, callback) {
            var map = new BMap.Map("allmap");
            map.centerAndZoom(new BMap.Point(point[0], point[1]), 11);
            var options = {
                onSearchComplete: function (results) {
                    // 判断状态是否正确
                    if (local.getStatus() == BMAP_STATUS_SUCCESS) {
                        callback(results);
                    }
                }
            };
            var local = new BMap.LocalSearch(map, options);
            local.search(keyword);
        },
        get_by_amap: function (callback) {
            var map = new AMap.Map('container', {
                center: [117.000923, 36.675807],
                zoom: 11
            });
            AMap.plugin('AMap.Geolocation', function () {
                var geolocation = new AMap.Geolocation({
                    enableHighAccuracy: true,// 是否使用高精度定位，默认：true
                    timeout: 10000,// 设置定位超时时间，默认：无穷大
                    buttonOffset: new AMap.Pixel(10, 20),// 定位按钮的停靠位置的偏移量，默认：Pixel(10, 20)
                    zoomToAccuracy: true,//  定位成功后调整地图视野范围使定位位置及精度范围视野内可见，默认：false
                    buttonPosition: 'RB'  //  定位按钮的排放位置,  RB表示右下
                });

                geolocation.getCurrentPosition();
                AMap.event.addListener(geolocation, 'complete', onComplete);
                AMap.event.addListener(geolocation, 'error', onError);

                function onComplete(data) {
                    var lng = data.position.lng;
                    var lat = data.position.lat;
                    if (callback && typeof callback === 'function') {
                        callback({
                            code: 1,
                            data: [lng, lat],
                            address: data.formattedAddress,
                            comp: data.addressComponent

                        });
                    }
                }

                function onError(data) {
                    // alert(JSON.stringify(data));
                }
            })
        },
        do_local_search_amap: function (city_name, keyword, callback) {
            console.log(city_name, keyword);
            AMap.plugin('AMap.PlaceSearch', function () {  // 实例化Autocomplete
                var O = {
                    city: city_name ? city_name : '全国',
                    pageSize: 15,
                    extensions: "all",
                    citylimit: true
                }, i = new AMap.PlaceSearch(O);
                i.search(keyword, function (status, result) {
                    console.log(status, result); // 搜索成功时，result即是对应的匹配数据
                    result.results = result.poiList.pois;
                    status === "complete" && callback(result);
                })
            })
        },
        /**
         * start_loc =  [lng , lat] , stop_loc = [lng , lat]
         */
        count_dis: function (start_loc, stop_loc) {
            dis = (new AMap.LngLat(start_loc[0], start_loc[1]).distance(new AMap.LngLat(stop_loc[0], stop_loc[1])) * .001).toFixed(1);
            return dis < 1 ? "<1" : dis < 100 ? dis.toString() : ">99"

        },
        map: {},
        choose_marker: {},
        config: {
            title: '',
            html_el: '',
            btn_el: '', //触发弹窗元素
            map_el: '', // map 元素 注意没有 #
            input_el: '', // 搜索元素
            search_el: '.do-map-search', // 搜索元素
            confirm_el: 'confirm-select', // 确认选择 元素
            success: {},
            point: {},
            default_zoom: 14,
            keyword: '',
            page_size: 30 ,
            mode : 'point'  // point | address
        },

        init_choose: function (config) {
            var root = this;
            this.config = $.extend(this.config, config);
            $(this.config.btn_el).on('click', function () {
                root.chooseLocation(root.config);
            });

            $(this.config.html_el + ' ' + this.config.search_el).on('click', function () {
                var keyword = $(root.config.html_el + ' .bra-search-input').val();
                if (!keyword || keyword == '') {
                    return layer.msg('opps , 请输入搜索关键字!');
                }
                root.do_local_search_amap(null, keyword, function (data) {
                    var poi0= data.poiList.pois[0].location;
                    root.choose_marker.setPosition([poi0.lng, poi0.lat]);
                    root.map.setCenter([poi0.lng, poi0.lat]);
                    root.config.success([poi0.lng, poi0.lat]);
                });
            });

            $(this.config.html_el + ' .bra-confirm-select').on('click', function () {
                var center = root.map.getCenter();

                if(typeof root.config.success === 'function'){
                    if(root.config.mode == 'point'){
                        root.config.success(center);
                    }else{
                        //todo select address
                    }

                    layer.close(root.opend_idx);
                }
            });

        },

        addMarker: function (lng, lat) {
            marker = new AMap.Marker({
                icon: "//a.amap.com/jsapi_demos/static/demo-center/icons/poi-marker-red.png",
                position: [lng, lat],
                offset: new AMap.Pixel(-13, -30)
            });
            marker.setMap(this.map);
        },
        chooseLocation: function (param) {
            var root = this;
            this.opend_idx = layer.open({
                type: 1,
                title: param.title,
                area: ['90%', '90%'],
                content: $(param.html_el),
                success: function (layero, dIndex) {
                    root.create_amap(param);
                }
            });
        },

        create_amap: function (param) {
            var root = this;

            var mapOption = {
                resizeEnable: true, // 监控地图容器尺寸变化
                zoom: param.default_zoom  // 初缩放级别
            };
            param.point && (mapOption.center = param.point);
            root.map = new AMap.Map(param.map_el, mapOption);
            console.log(param, mapOption)
            // 地图加载完成
            root.map.on("complete", function () {
                var center = root.map.getCenter();
                root.choose_marker = new AMap.Marker({
                    icon: "//a.amap.com/jsapi_demos/static/demo-center/icons/poi-marker-red.png",
                    position: center,
                    offset: new AMap.Pixel(-13, -30)
                });
                root.choose_marker.setMap(root.map);
            });
            // 地图移动结束事件
            root.map.on('moveend', function (e) {

            });
            root.map.on('click', function (e) {
                root.map.setCenter([e.lnglat.getLng(), e.lnglat.getLat()]);
                root.choose_marker.setPosition([e.lnglat.getLng(), e.lnglat.getLat()]);
            });
        }

    }
});

