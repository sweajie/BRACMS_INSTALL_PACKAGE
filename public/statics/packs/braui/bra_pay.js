// 布拉组件 个个超神
define("bra_pay", ["braui", 'wx'], function (braui, wx) {
    return {
        jssdk_obj: {},
        wx : {} ,
        init_bra_wx_pay: function (order_id) {
            var $this = this;
            $this.order_id = order_id;
            $this.isMiniProgram = false;
            //判断来源是否是小程序
            this.checkIsFromMiniProgram = function (callback) {
                wx.miniProgram.getEnv(function (res) {
                    $this.isMiniProgram = res.miniprogram;
                    $this.navigateToPay(callback);
                });
            };
            //跳转到小程序

            this.navigateToMiniProgram = function (payParam) {
                var url =
                    "/pages/wxpay/main?payParam=" + encodeURIComponent(payParam);
                //alert('url:' + url);
                wx.miniProgram.navigateTo({
                    url: url
                });
            };

            this.navigateToPay = function ( callback) {
                if ($this.isMiniProgram === true) {
                    var payParam = {};
                    payParam.order_id = $this.order_id;
                    $this.navigateToMiniProgram(JSON.stringify(payParam));
                } else {
                    //原先支付逻辑不用修改;
                    //todo get pay parmas
                    $.get(get_jsapi_pay_params_api_url, {
                        query: {
                            order_id: $this.order_id
                        }
                    }, function (resp) {
                        if (resp.code !== 1) {
                            alert(JSON.stringify(resp));
                        }
                        console.log(resp);
                        if (resp.code === 1) {
                            if (resp.data.return_code === "FAIL") {
                                alert(resp.data.return_msg);
                                return;
                            }
                            if (typeof WeixinJSBridge === "undefined") {
                                if (document.addEventListener) {
                                    document.addEventListener('WeixinJSBridgeReady', $this.jsApiCall(resp.data, callback), false);
                                } else if (document.attachEvent) {
                                    document.attachEvent('WeixinJSBridgeReady', $this.jsApiCall(resp.data, callback));
                                    document.attachEvent('onWeixinJSBridgeReady', $this.jsApiCall(resp.data, callback));
                                }
                            } else {
                                $this.jsApiCall(resp.data, callback);
                            }
                        } else {
                            callback && callback(false);
                        }
                    }, 'json');

                }
            };

            this.native_pay = function (callback) {
                $.get(get_native_pay_params_api_url, {
                    query: {
                        order_id: $this.order_id
                    }
                }, function (resp) {
                    console.log(resp);
                    if (resp.code === 1) {
                        callback && callback(resp.data);
                    } else {
                        alert('发生错误');
                    }

                }, 'json');
            };

            this.jsApiCall = function (params, callback) {
                console.log(params);
                WeixinJSBridge.invoke(
                    'getBrandWCPayRequest', params,
                    function (res) {
                        //----------
                        if (res.err_msg === "get_brand_wcpay_request:ok") {
                            callback && callback(true);
                        } else {
                            callback && callback(false);
                        }
                    }
                );
            }

        },
        init_wechat_share: function ($seo_data, $share_url, callback, direct_callback) {
            var root = this;
            this.get_sign(encodeURIComponent(location.href.split('#')[0]), function (jssdk_obj) {
                wx.config(jssdk_obj);
                root.wx = wx;
                wx.ready(function () {
                    wx.onMenuShareAppMessage({
                        'title': $seo_data.seo_title,
                        'link': $share_url,
                        'imgUrl': $seo_data.share_icon,
                        'desc': $seo_data.seo_desc,
                        'success': function (res) {
                            if (callback && typeof callback === "function") {
                                callback("AppMessage");
                            }
                        }
                    });
                    wx.onMenuShareQQ({
                        'title': $seo_data.seo_title,
                        'link': $share_url,
                        'imgUrl': $seo_data.share_icon,
                        'desc': $seo_data.seo_desc,
                        'success': function (res) {
                            if (callback && typeof callback === "function") {
                                callback("QQ");
                            }
                        }
                    });
                    wx.onMenuShareWeibo({
                        'title': $seo_data.seo_title,
                        'link': $share_url,
                        'imgUrl': $seo_data.share_icon,
                        'desc': $seo_data.seo_desc,
                        'success': function (res) {
                            if (callback && typeof callback === "function") {
                                callback("Weibo");
                            }
                        }
                    });
                    wx.onMenuShareTimeline({
                        'title': $seo_data.seo_title,
                        'link': $share_url,
                        'imgUrl': $seo_data.share_icon,
                        'desc': $seo_data.seo_desc,
                        'success': function (res) {
                            if (callback && typeof callback === "function") {
                                setTimeout(function () {
                                    //回调要执行的代码
                                    callback("Timeline");
                                }, 500);

                            }
                        }
                    });

                });

                if (direct_callback && typeof direct_callback === "function") {
                    direct_callback(wx)
                }
            })
        },
        get_sign: function (url, cb) {
            var root = this;
            var sdk_service = "/wechat/bra_wechat/get_current_ticket";
            $.get(sdk_service, {
                'url': url
            }, function ($signPackage) {
                root.jssdk_obj = {
                    debug: false,
                    appId: $signPackage.appId,
                    timestamp: $signPackage.timestamp,
                    nonceStr: $signPackage.nonceStr,
                    signature: $signPackage.signature,
                    jsApiList: js_api_list
                };

                if (cb && typeof cb === "function") {
                    cb(root.jssdk_obj)
                }
            }, 'json');
        },
        get_location: function ($url, callback) {

            if (this.jssdk_obj.appId) {
                wx.getLocation({
                    type: 'wgs84', // 默认为wgs84的gps坐标，如果要返回直接给openLocation用的火星坐标，可传入'gcj02'
                    success: function (res) {
                        if (typeof callback === "function") {
                            callback({
                                code: 1,
                                data: [res.longitude, res.latitude],
                                res: res
                            });
                        }
                    }
                })
            } else {
                this.get_sign(encodeURIComponent(location.href.split('#')[0]), function (jssdk_obj) {
                    wx.config(jssdk_obj);
                    wx.ready(function () {
                        wx.getLocation({
                            type: 'wgs84', // 默认为wgs84的gps坐标，如果要返回直接给openLocation用的火星坐标，可传入'gcj02'
                            success: function (res) {
                                if (typeof callback === "function") {
                                    callback({
                                        code: 1,
                                        data: [res.longitude, res.latitude],
                                        res: res
                                    });
                                }
                            }
                        })
                    });
                });
            }

        },
        bra_init_preview: function (container) {
            var root = this;
            var urls = [];
            var current = '';
            $(container + ' img').each(function (idx, item) {
                console.log(idx, item);
                urls.push($(this).attr('src'));
                $(this).click(function(){
                    current = $(this).attr('src');

                    root.wx.previewImage({
                        current : current ,
                        urls : urls
                    });
                });
            });
        },
        init_wechat_scan : function(needResult , callback ){
            this.wx.scanQRCode({
                needResult: needResult, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
                scanType: ["qrCode","barCode"], // 可以指定扫二维码还是一维码，默认二者都有
                success: function (res) {
                    if (callback && typeof callback === "function") {
                        callback(res.resultStr);
                    }
                }
            });
        },

        /**
         *  统一下单接口
         * @param callback
         */
        get_pay : function (way_id , order_id , callback) {
            $.get(get_pay_params_api_url, {
                query: {
                    order_id: order_id ,
                    way_id : way_id
                }
            }, function (resp) {
                if (resp.code !== 1) {
                    alert(JSON.stringify(resp));
                }else{
                    callback && callback(resp.data);
                }
            }, 'json');
        } ,

        micropay : function (params, callback) {
            console.log(params);
            WeixinJSBridge.invoke(
                'getBrandWCPayRequest', params,
                function (res) {
                    //----------
                    //alert(callback);
                    if (res.err_msg === "get_brand_wcpay_request:ok") {
                        callback && callback(true);
                    } else {
                        callback && callback(false);
                    }
                }
            );
        }
    }
});
