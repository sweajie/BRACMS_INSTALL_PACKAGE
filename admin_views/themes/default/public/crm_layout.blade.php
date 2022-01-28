<!DOCTYPE html>
<html lang="en" style="overflow:hidden">
<head>
    <meta charset="UTF-8">
    <title>@yield('title')</title>
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="format-detection" content="telephone=no">
    <link rel="Shortcut Icon" href="{{ asset('favicon.ico') }} "/>
    @section('css_icon')
        <link rel="stylesheet" href="{{asset('statics/packs/semantic/components/icon.min.css')}}">
        <link rel="stylesheet" href="{{asset('statics/css/bra_icon.css')}}">
    @show

    @section('bra_css')
        <link rel="stylesheet" type="text/css" href="{{asset('statics/packs/braui/bra.min.css')}}" media="all"/>
        <link rel="stylesheet" type="text/css" href="{{asset('/statics/packs/braui/bra-admin.css')}}" media="all"/>
    @show

    @section('animate_css')
        <link rel="stylesheet" type="text/css" href="{{asset('statics/packs/animate/animate.css')}}" media="all"/>
    @show

    @section('layui_css')
        <link rel="stylesheet" type="text/css" href="{{asset('statics/packs/layui/css/layui.css')}}" media="all"/>
    @show

    @section('admin_theme_css')
        <link rel="stylesheet" type="text/css" href="{{asset('/statics/css/admin/bra-admin-default.css')}}" media="all"/>
    @show

    @section('layout_theme_css')
        <style>

        </style>
        @show

    <!--[if lt IE 9]>
        <script src="https://cdn.staticfile.org/html5shiv/r29/html5.min.js"></script>
        <script src="https://cdn.staticfile.org/respond.js/1.4.2/respond.min.js"></script>
        <![endif]-->
        <style>

            li.active{background:#7a4d0b;}
            li.active a{ color:#fff;}
            .menu-list li ul{margin-right:0}
        </style>
        @yield('header_extra')
</head>
<body class="bra-body" style="height: 100%;">
<div id="bra_app" :class="current_layout" v-cloak style="height: 100%;">

    <div class="bra-admin-side columns is-mobile is-marginless is-gapless" style="height: 100%;">

        <!-- level one menu-->
        <div class="menu-list column is-narrow menu-list-narrow">

            <div class="bar_admin_logo" target="_blank" @click="reload_active">
                <span class="logo-text">
                    @yield('logo_area')
                </span>
            </div>

            @verbatim
                <div :data-id="menu.id" class="menu-item" :class="{'active' : index==current_group}" v-for="( menu , index ) in menus" @click="switch_group(menu.id)" :data-title="menu.menu_name">
                    <i :class="menu.icon"></i> <span>{{menu .menu_name}}</span>
                </div>
            @endverbatim
        </div>

        @verbatim
            <div class="menu-list-sub column is-narrow menu-list-sub-show" v-if="show_mask">

                <div v-if="menus[current_group] && current_group !='mine'">
                    <div v-for="menu in menus[current_group].children">

                        <a @click="open_tab(menu)" :class="{'is-block'  : menu.children.length === 0 }" v-show="menu.children.length === 0" :data-id="menu.id" :len="menu.children.length"
                           :data-url="menu.url" class="menu-item-three">
                            <i :class="menu.icon"></i>{{menu.menu_name}}
                        </a>
                        <a b v-show="menu.children && menu.children.length > 0" class="menu-item-sub">
                            <i :class="menu.icon"></i>{{menu.menu_name}}
                        </a>

                        <div c v-show="menu.children && menu.children.length > 0" :data-id="menu.id">
                            <div @click="open_tab(_menu)" class="menu-item-three" :class="{'active' : active_tab_id == _menu.id}" :data-url="_menu.url"
                                 v-for="_menu in menu
                    .children">
                                <!--i :class="_menu.icon"></i-->{{_menu.menu_name}}
                            </div>
                        </div>
                    </div>
                </div>
                <!-- fav menus -->
                <div v-if="menus[current_group] && current_group =='mine'">
                    <div v-for="menu in menus[current_group].children" class="menu-item-three" :class="{'active' : active_tab_id == menu.id + '_mine'}" @click="open_fav_tab(menu)">
                        <a><i :class="menu.icon"></i>{{menu.title}}</a>
                    </div>
                </div>

            </div>

        @endverbatim
    </div>
    <!-- top bar  -->
    <div class="bra-admin-top">
        <div class="columns is-mobile is-marginless align-items-center is-gapless bra-admin-top-right">

            @verbatim
                <div class="column">
                    <!-- top tabs-->
                    <div class="columns is-mobile is-marginless has-text-centered align-items-center is-gapless">
                        <a style="width:65px" class="column is-narrow" @click="modalSign1 = !modalSign1">
                            <i class="layui-icon" :class="{'layui-icon-spread-left' : !modalSign1}"></i>
                            <i class="layui-icon" :class="{'layui-icon-shrink-right' : modalSign1}"></i>

                        </a>

                        <a style="width:65px" class="column  is-narrow" @click="reload_active">
                            <i class="layui-icon-refresh layui-icon"></i>
                        </a>

                        <div class="column is-gapless open_scroll is-clipped">
                            <div class="columns is-mobile is-marginless align-items-center is-gapless" style="width:100%;">
                                <div class="column is-narrow open_item" v-for="open in opened" :class="{active : active_tab_id == open.id}">

                                    <a class="is-inline-block" @click="active_tab(open)"><i :class="open.icon"></i> {{open.menu_name}}</a>
                                    <i v-if="open.id != 0" @click="close_tab(open)" class="layui-icon-close layui-icon"></i>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
        @endverbatim



        <!-- right icons -->
            <div class="column is-narrow is-pulled-right has-text-right bra-admin-top-right">


                <a title="{{$_W['user']['nickname']}} - {{$_W['user']['user_name']}}">
                    <img style="height: 32px" src="{{$_W['user']['avatar'] ?: '/statics/images/logo.png'}}" />
                </a>

                @section('user_act_section')

                @show
                @section('layout_theme_css')
                @show
                <a @click="clear_cache()">
                    <i class="layui-icon layui-icon-fonts-clear has-text-success"></i>
                </a>

                @section('act_logout')
                    <a bra-mini="confirm" href="{{ url('bra_admin/passport/logout')}}">注销</a>
                @show
            </div>
        </div>

    </div>
    <!-- content body-->

    @verbatim
        <div class="bra-admin-body" id="bra-admin-body">
            <div class="bra-mask zindex_1100" v-if="show_mask" @click="show_mask=false"></div>
            <div class="bra-admin-tab-body-item  bra-scroll-bar">

                <iframe :sandbox="open.sandbox" :src="active_tab_id == open.id ||cached_tabs[open.id] ? open.url:''" frameborder="0" :data-id="open.id" v-for="open in opened"
                        v-show="active_tab_id == open.id"
                        class="bra-admin-iframe  animated fadeIn    bra-scroll-bar"></iframe>
            </div>

        </div>
    @endverbatim
</div>

@yield('footer_css')

@yield('footer_php')

@section('requirejs')
    <x-requirejs/>
@show

@section('footer_js_php')

@show


@section('footer_js')

    <script>
        var vm = {};
        var menu_api = "{{$load_menu_url}}";
        var switch_object_api = "{{$switch_object_url ?? ''}}";
        require(['Vue', 'jquery', 'braui'], function (Vue, $, braui) {
            vm = new Vue({
                el: "#bra_app",
                data: {
                    current_group: 'mine',
                    active_tab_id: 0,
                    current_tab: {},
                    menus: [],
                    opened: [
                        {
                            group_id: 'mine',
                            id: 0, 'menu_name': '',
                            'url': '{{ $default_tab_url }}',
                            'icon': 'layui-icon layui-icon-star'
                        }
                    ],
                    cached_tabs: [],
                    opened_ids: [0],
                    screenWidth: '',
                    screenHeight: '',
                    mine_menus: {
                        id: 'mine',
                        menu_name: '收藏',
                        icon: 'layui-icon layui-icon-star-fill',
                        children: <?php echo json_encode($mine_menus) ?>
                    },
                    current_layout: 'default_open',
                    object: <?php echo json_encode($current_object ?? []) ?> ,
                    object_id:<?php echo $current_object['id'] ?? 'undefined' ?> ,
                    modalSign1: true,
                    show_mask: false
                },
                watch: {
                    modalSign1: function (new_val) {
                        console.log(new_val);

                        if (!new_val) {
                            this.current_layout = 'menu_narrow';
                        } else {
                            this.current_layout = 'default_open';
                        }
                    },
                    opened: function (new_opened) {
                        localStorage.setItem('{{$cp_prefix}}_opened_menus', JSON.stringify(new_opened));
                    },
                    active_tab_id: function (active_tab_id) {
                        localStorage.setItem('{{$cp_prefix}}_active_tab_id', active_tab_id);
                    }
                },
                methods: {
                    switch_group: function (index) {
                        console.log(index, this.menus);
                        if (this.current_group == index) {
                            if (this.show_mask) {
                                this.show_mask = false;
                            } else {
                                this.show_mask = true;
                            }
                        } else {
                            this.show_mask = true;
                        }
                        this.current_group = index;
                    },
                    toggle_target: function (e) {
                        const target = $(e.srcElement).data('target');
                        console.log(target);
                        $("#" + target).toggle();
                    },
                    reload_active: function () {
                        var $active_iframe = $('.bra-admin-iframe:visible');
                        $active_iframe.attr('src', $active_iframe.attr('src'));
                    },
                    switch_object: function () {
                        console.log(this.object)
                        var root = this;
                        $.get(switch_object_api, {object_id: this.object_id}, function (data) {
                            window.location.reload();
                        }, 'json');
                    },
                    clear_cache: function () {
                        localStorage.removeItem('{{$cp_prefix}}_opened_menus');
                        localStorage.removeItem('{{$cp_prefix}}_active_tab_id');
                        window.location.reload();
                    },
                    init: function () {
                        var root = this;
                        $.get(menu_api, function (data) {
                            console.log(data)
                            if (data.page_data) {
                                data.page_data.data.mine = root.mine_menus;
                                root.menus = data.page_data.data;
                            } else {
                                data.data.mine = root.mine_menus;
                                root.menus = data.data;
                            }

                            console.log(root.menus)
                        }, 'json');
                    },

                    open_from_sub(id) {
                        var node = this.find_node(this.menus, id);
                        if (!node) {
                            return alert(id);
                        }

                        this.open_tab(node);
                    },
                    find_node: function (object, id) {
                        var root = this, node, ii;

                        for (let key in object) {
                            node = object[key];
                            console.log(node.title, node.id);
                            if (node.id == id) {
                                return node;
                            } else if (node.children && node.children.length > 0) {
                                node = root.find_node(node.children, id);
                                if (node) {
                                    return node;
                                }
                            }
                        }


                        return null;
                    },
                    open_tab: function (e) {
                        if (this.opened_ids.indexOf(e.id) === -1) {
                            e.group_id = this.current_group;
                            this.opened_ids.push(e.id);
                            console.log(e);
                            this.opened.push(e);
                            this.current_tab = e;
                            this.active_tab_id = e.id;
                        } else {

                            this.active_tab_id = e.id;
                        }

                        //save this.opened

                        this.cached_tabs[e.id] = true;

                        this.show_mask = false;
                    },
                    open_fav_tab: function (e) {
                        this.open_diy_tab(e.id + '_mine', e.url, e.title, {});
                    },
                    active_tab: function (e) {
                        console.log(e);
                        this.current_group = e.group_id;
                        this.active_tab_id = e.id;
                        this.current_tab = e;
                        this.cached_tabs[e.id] = true;
                    },
                    open_diy_tab: function (id, url, title, opts) {
                        var e = opts || {};
                        e.id = id;
                        e.url = url;
                        e.menu_name = title;
                        this.open_tab(e);

                    },
                    close_tab: function (e) {
                        console.log(e);
                        var index = this.opened_ids.indexOf(e.id);
                        this.opened_ids.splice(index, 1);
                        this.opened.splice(index, 1);
                        if (this.opened_ids[index - 1] >= 0) {
                            this.active_tab_id = this.opened_ids[index - 1];
                        }
                        this.active_tab(this.opened[index - 1])
                    },
                    add_fav: function (obj) {
                        var root = this;
                        obj = obj || root.current_tab;

                        var params = {
                            id: obj.id,
                            url: obj.url,
                            type: obj.type,
                            icon: obj.icon,
                            title: obj.title
                        };

                        var action = "{:url('bra_admin/admin_api/add_menu_fav')}";
                        console.log(this.current_tab);
                        if (!this.current_tab.id) {
                            layer.msg('这个暂时不能添加到收藏!', {offset: 't'});
                        } else {
                            layer.prompt({
                                title: '收藏菜单,请另起一个收藏名字',
                                value: params.title
                            }, function (value, index, elem) {
                                params.title = value;
                                layer.close(index);
                                $.post(action, params, function (data) {
                                    layer.msg(data.msg);
                                }, 'json');

                            });
                        }
                    }
                },
                created: function () {
                    this.init();

                    var opened = JSON.parse(localStorage.getItem('{{$cp_prefix}}_opened_menus'));
                    var active_tab_id = localStorage.getItem('{{$cp_prefix}}_active_tab_id');
                    console.log(opened);
                    if (opened) {
                        for (var o of opened) {
                            if (o.id != 0) {
                                this.opened_ids.push(o.id);
                            }
                        }
                        this.opened = opened;
                        this.active_tab_id = active_tab_id;
                    }
                    this.cached_tabs[active_tab_id] = true;
                }
            });

            braui.bra_init();
        });

        function devOpened(e) {
            $.get('/bra/index/notice', {});
        }

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
                devOpened(e);
            }
        });
    </script>
@show
</body>
</html>
