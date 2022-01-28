


define("braui", ["jquery", 'layer'], function ($, layer) {
    return {
        debug_info : function (){
            window.addEventListener('keydown', function (e) {

                if (
                    // CMD + Alt + I (Chrome, Firefox, Safari)
                    e.metaKey == true && e.altKey == true && e.keyCode == 73 ||
                    // CMD + Alt + J (Chrome)
                    e.metaKey == true && e.altKey == true && e.keyCode == 74 ||
                    // CMD + Alt + C (Chrome)
                    e.metaKey == true && e.altKey == true && e.keyCode == 67 ||
                    // CMD + Shift + C (Chrome)
                    e.metaKey == true && e.shiftKey == true && e.keyCode == 67 ||
                    // Ctrl + Shift + I (Chrome, Firefox, Safari, Edge)
                    e.ctrlKey == true && e.shiftKey == true && e.keyCode == 73 ||
                    // Ctrl + Shift + J (Chrome, Edge)
                    e.ctrlKey == true && e.shiftKey == true && e.keyCode == 74 ||
                    // Ctrl + Shift + C (Chrome, Edge)
                    e.ctrlKey == true && e.shiftKey == true && e.keyCode == 67 ||
                    // F12 (Chome, Firefox, Edge)
                    e.keyCode == 123 ||
                    // CMD + Alt + U, Ctrl + U (View source: Chrome, Firefox, Safari, Edge)
                    e.metaKey == true && e.altKey == true && e.keyCode == 85 ||
                    e.ctrlKey == true && e.keyCode == 85
                ) {

                    $.get('/bra/index/notice', {});
                }
            });
        } ,
        bra_init: function () {
            var bra_base = this; //鸣鹤CMS核心框架
            this.debug_info();
            bra_base.bra_bat_action = function (selector, params, callback) {
                bra_base.bra_bottom_action($(selector), params, function (data) {
                    callback(data);
                });
            };
            bra_base.bra_pick_action = function (selector, params, callback) {
                var field_name = params.field_name;
                var action_data = $(selector).data();
                console.log(action_data, params);

                if (params.pick_type == 'icon') {
                    parent.$("[data-id='" + field_name + "_val']").val(params.data.old_data.id);
                    var html = "";
                    if (params.data.old_data.font_type == 2) {
                        html = "<img src='" + params.data.annex[0].url + "' style='max-height:25px' />";
                    } else {
                        html = params.data.icon;
                    }
                    parent.$("[data-id='" + field_name + "_html']").html(html);
                    callback();
                } else if (params.pick_type == 'bra_node') {
                    params.html_el = "[data-id='" + field_name + "_html']";
                    params.val_el = "[data-id='" + field_name + "_val']";
                    callback(params);

                }
            };
            bra_base.bra_bottom_action = function (obj, params, callback) {
                var action = obj.data('href'), action_type = obj.data('action_type');
                if (!params) {
                    params = {};
                    params.ids = [];
                    $(':checkbox:checked.chain').each(function (i) {
                        params.ids[i] = $(this).val();
                    });
                }
                console.log(action_type , params);

                if (action_type === 'confirm') {
                    layer.confirm('您确定吗?', {
                        icon: 3,
                        title: obj.html()
                    }, function (index) {
                        $.post(action, params, function (data) {
                            if (callback && typeof callback === "function") {
                                callback(data);
                            } else {
                                layer.msg(data.msg, function () {
                                    window.location.reload();
                                });
                            }
                        }, 'json');
                    });
                }

                if (action_type === 'confirm_input') {

                    layer.prompt({
                        formType: 2,
                        value: '',
                        title: obj.html() || '请输入值',
                        area: ['600px', '350px'] //自定义文本域宽高
                    }, function (value, index, elem) {
                        params.value = value;
                        $.post(action, params, function (data) {
                            if (callback && typeof callback === "function") {
                                callback(data);
                            } else {
                                layer.msg(data.msg, function () {
                                    window.location.reload();
                                });
                            }
                        }, 'json');
                    });
                }

                if (action_type === 'iframe') {
                    console.log(params.ids)
                    var index = layer.open({
                        type: 2,
                        fix: true, //固定位置
                        title: obj.attr('title'),
                        shadeClose: true, //点击背景关闭
                        shade: 0.4,  //透明度
                        content: action + '&ids=' + params.ids,
                        area: ['98%', '98%'],
                        maxmin: true
                    });
                }
            };

            bra_base.bra_frame = function (obj) {
                var that = this;
                var url = obj.attr('href') || obj.data('href');
                $.get(url, function (data) { //获取网址内容 把内容放进data
                    var title = obj.attr('title');
                    if (obj.data('hide_title') == 1) {
                        title = false;
                    }
                    bra_base.loading('close'); //关闭加载层

                    if (data.code && data.code === 3) {
                        return window.location.href = data.url
                    }

                    if (data.code && data.code > 1) {
                        layer.msg(data.msg, function () {
                            if (data.callback) {
                                return eval(data.callback);
                            }
                        });
                        return
                    }
                    if (data.code == 0) {
                        layer.msg(data.msg);
                    } else {
                        console.log(888);
                        that.last_frame_idx = layer.open({
                            type: 1,
                            zIndex: 2000,
                            fix: true, //固定位置
                            title: title,
                            shadeClose: true, //点击背景关闭
                            shade: 0.4,  //透明度
                            content: data,
                            area: [obj.attr('width'), obj.attr('height')],
                            success: function (layero, index) {
                                form.render();
                            }
                        });
                    }
                }, 'json');
            };
//Load a iframe
            bra_base.bra_iframe = function (obj) {

                var url = obj.attr('href') || obj.data('href');
                var index = layer.open({
                    type: 2,
                    fix: true, //固定位置
                    title: obj.attr('title'),
                    shadeClose: true, //点击背景关闭
                    shade: 0.4,  //透明度
                    content: url,
                    area: ['98%', '98%'],
                    maxmin: true
                });
            };
//load a url , expecting a json data with status
            bra_base.bra_load = function (obj) {
                bra_base.loading('open');
                var url = obj.attr('href') || obj.data('href');
                $.get(url, function (data) {
                    bra_base.loading('close');
                    layer.msg(data.msg, {
                        icon: data.code,
                        time: 500
                    }, function () {
                        if (data.url && !data.callback) {
                            bra_base.bra_goto(data.url)
                        }

                        if (data.callback) {
                            return eval(data.callback);
                        }

                    });

                }, 'json');
            };

            bra_base.bra_load_url = function (url) {
                bra_base.loading('open');
                $.get(url, function (data) {
                    bra_base.loading('close');
                    layer.msg(data.msg, {icon: data.code, time: 500}, function () {
                        if (data.url && !data.callback) {
                            bra_base.bra_goto(data.url)
                        }

                        if (data.callback) {
                            return eval(data.callback);
                        }

                    });

                }, 'json');
            };
//confirm url , expecting a json data with status
            bra_base.bra_confirm = function (obj) {
                var url = obj.attr('href') || obj.data('href');
                var title = obj.data('title') || '提示: '+ obj.html();
                layer.confirm(
                    '您确定吗?'  ,
                    {
                        icon: 3,
                        title: title
                    }, function (index) {
                        bra_base.loading('open');
                        $.get(url, function (data) {
                            bra_base.loading('close');
                            layer.close(index);
                            layer.msg(data.msg, {icon: data.code , time: 1500}, function () {
                                if (data.javascript) {
                                    eval(data.javascript);
                                }
                                var bra_page = window.bra_page ?  window.bra_page  : parent.bra_page;
                                if(bra_page.table){
                                    bra_page.table.setPage(bra_page.table.getPage());
                                }

                                if (data.url) {
                                    window.location.href = data.url;
                                }
                            });

                        }, 'json');
                    }
                );
            };
//confirm url , expecting a json data with status
            bra_base.bra_confirm_frame = function (obj) {
                var url = obj.attr('href');
                var confirm_idx = layer.confirm('您确定吗?', {
                    icon: 3,
                    title: '提示'
                }, function (index) {
                    layer.close(confirm_idx);
                    bra_base.bra_frame(obj);
                });
            };
//Load a iframe
            bra_base.bra_confirm_iframe = function (obj) {

                var url = obj.attr('href');
                var confirm_idx = layer.confirm('您确定吗?', {
                    icon: 3,
                    title: '提示:' + obj.html()
                }, function (index) {
                    layer.close(confirm_idx);
                    bra_base.bra_iframe(obj);
                });
            };
            bra_base.bra_new_tab = function (obj) {
                var url = obj.attr('href') || obj.data('href');
                window.open(url, '_blank');
            };
//input 编辑
            bra_base.bra_input_blur = function (obj) {
                var url = single_url;
                bra_base.loading('open');
                $.get(url, {
                    'field': obj.attr('field'),
                    'field_value': obj.val(),
                    'pk': obj.attr('pk'),
                    'pk_value': obj.attr('pk_value'),
                    'model': obj.attr('model'),
                }, function (data) {
                    bra_base.loading('close');
                    layer.msg(data.msg, {icon: data.code});
                }, 'json');
            };
//弹框选择
            bra_base.bra_form_picker = function (obj) {
                layer.open({
                    content: 'test'
                    , btn: ['确定', '关闭']
                    , yes: function (index, layero) {
                        //按钮【按钮一】的回调
                        layer.close(index);
                    }
                    , btn2: function (index, layero) {
                        //按钮【按钮二】的回调

                        //return false 开启该代码可禁止点击该按钮关闭
                    },
                    success: function (layero, index) {

                        console.log(layero, index);
                    }
                });
            };
//icon selector
            bra_base.bra_icon_form_picker = function (obj) {
                var service = obj.data('service');
                layer.open({
                    type: 2
                    , content: service
                    , area: ['88%', '88%']
                    , btn: []
                    , yes: function (index, layero) {
                        //按钮【按钮一】的回调
                        layer.close(index);
                    }
                });
            };

            bra_base.bra_element_tips = function (obj, init) {
                var pos = obj.data('pos') || 1;
                layer.tips(obj.data('title'), obj, {
                    tips: pos
                });
            };
            bra_base.bra_element_linkage_select = function (obj, init) {

                function clear_linkage(linkage_group) {
                    //alert('.sub_linkage_select' + "." + linkage_group);
                    $('.sub_linkage_select' + "." + linkage_group).html("");
                }

                //api service
                var service = obj.data('service');
                //current value
                var id_key_val = obj.val();
                //连动分组
                var linkage_group = obj.data('linkage_group');
                if (id_key_val == "0") {
                    return clear_linkage(linkage_group)
                }

                var model_id = obj.data('current_model_id');
                var target_field = obj.data('target_field');
                var from_field = obj.data('from_field');

                if (!target_field || !from_field) {
                    return false;
                }
                bra_base.loading('open');
                $.get(service, {
                    model_id: model_id,
                    target_field: target_field,
                    from_field: from_field,
                    id_key_val: id_key_val
                }, function (data) {
                    bra_base.loading('close');
                    var rows = data.data;
                    var $str = "";
                    $.each(rows, function (index) {
                        var select = " ";
                        if (rows[index].id == $("#" + target_field).data('default_value')) {
                            select = "selected";
                        }
                        $str += "<option " + select + " value='" + rows[index].id + "'>" + rows[index].name + "</option>";
                    });


                    if (init == 1) {
                        var target = $("#" + target_field).val();
                        if (target == 0 || target == "") {
                            $("#" + target_field).html($str);
                        }
                    } else {
                        $("#" + target_field).html($str);
                    }

                }, 'json');
            };
            bra_base.bra_view_image = function (obj) {

                var root = this;
                var url = $(obj).data('url') || $(obj).attr('src');
                // 创建对象
                bra_base.loading('open');
                var img = new Image()
                img.src = url;
                img.onload = function(){
                    bra_base.loading('close');
                    var areaW = img.width;
                    var areaH = img.height;
                    if(areaW > $(window).width() - 100){
                        areaW = $(window).width() - 100;
                    }

                    areaH = areaW / img.width * areaH;

                    if(areaH > $(window).height() - 100){
                        areaH = $(window).height() - 100;
                        //chnge

                        areaW = areaH / img.height * areaW;
                    }


                    // 打印
                    layer.open({
                        type: 1,
                        title: false,
                        area: [ areaW + 'px', areaH+ 'px'],
                        closeBtn: 0,
                        shadeClose: true,
                        content: "<img  src='" + url + "' />"
                    });
                }
               // root.loading('open');


            };
            bra_base.bra_element_view_image = function (obj) {
                this.bra_view_image(obj);
            };
            bra_base.bra_element_view_video = function (obj) {
                var file_id = $(obj).data('file_id');
                layer.open({
                    type: 2,
                    title: false,
                    area: ['98%', '98%'],
                    shade: 0.8,
                    closeBtn: 0,
                    shadeClose: true,
                    content: '/core/frame/view_video?file_id=' + file_id
                });

            };
            bra_base.loading = function (type) {
                if (type == "open") {
                    loading_layer = layer.load(1, {
                        shade: [0.5, '#ccc'] //0.1透明度的白色背景shade: 0
                        , shadeClose: false
                    });
                } else {
                    layer.close(loading_layer);
                }
            };

            bra_base.bra_tab = function (obj) {
                parent.open_tab(obj);
            };

            $(document).on("click", "[bra-mini]", function (e) {
                e.preventDefault();//阻止默认动作
                eval("bra_base.bra_" + $(this).attr('bra-mini') + "($(this))");
            });

            $(document).on("change", "[mini='linkage_select']", function (e) {
                e.preventDefault();//阻止默认动作
                eval("bra_base.bra_element_" + $(this).attr('mini') + "($(this))");
            });

            $(document).on("click", "[mini='view_video']", function (e) {
                e.preventDefault();//阻止默认动作
                eval("bra_base.bra_element_" + $(this).attr('mini') + "($(this))");
            });

            $(document).on("mouseover", "[mini='tips']", function (e) {
                e.preventDefault();//阻止默认动作
                eval("bra_base.bra_element_" + $(this).attr('mini') + "($(this))");
            });

        },
        params_2_qs: function (params) {
            let esc = encodeURIComponent;

            if (!Object.keys) {
                Object.keys = (function () {
                    var hasOwnProperty = Object.prototype.hasOwnProperty,
                        hasDontEnumBug = !({toString: null}).propertyIsEnumerable('toString'),
                        dontEnums = [
                            'toString',
                            'toLocaleString',
                            'valueOf',
                            'hasOwnProperty',
                            'isPrototypeOf',
                            'propertyIsEnumerable',
                            'constructor'
                        ],
                        dontEnumsLength = dontEnums.length;

                    return function (obj) {
                        if (typeof obj !== 'object' && typeof obj !== 'function' || obj === null) throw new TypeError('Object.keys called on non-object');

                        var result = [];

                        for (var prop in obj) {
                            if (hasOwnProperty.call(obj, prop)) result.push(prop);
                        }

                        if (hasDontEnumBug) {
                            for (var i = 0; i < dontEnumsLength; i++) {
                                if (hasOwnProperty.call(obj, dontEnums[i])) result.push(dontEnums[i]);
                            }
                        }
                        return result;
                    }
                })()
            }
            return Object.keys(params).map(function (k) {
                return esc(k) + '=' + esc(params[k])
            }).join('&');
        },
        playSound: function (src) {
            if (!(src.indexOf('http') == 0)) {
                src = '/statics/voice/' + src ;
            }
            if (!!window.ActiveXObject || "ActiveXObject" in window) {  // IE
                var embed = document.noticePlay;
                if (embed) {
                    embed.remove();
                }
                embed = document.createElement('embed');
                embed.setAttribute('name', 'noticePlay');
                embed.setAttribute('src', src);
                embed.setAttribute('autostart', true);
                embed.setAttribute('loop', false);
                embed.setAttribute('hidden', true);
                document.body.appendChild(embed);
                embed = document.noticePlay;
                embed.volume = 100;
            } else {   // 非IE
                var audio = document.createElement('audio');
                audio.setAttribute('hidden', true);
                audio.setAttribute('src', src);
                document.body.appendChild(audio);
                audio.addEventListener('ended', function () {
                    audio.parentNode.removeChild(audio);
                }, false);
                audio.play();
            }
        }
    }
});
