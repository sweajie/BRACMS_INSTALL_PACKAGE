@extends("themes." . $_W['theme'] . '.public.base_layout')

@section('main')
    <?php
    $bg_url = $detail['background'][0]['url'];
    $elements = json_decode($detail['poster_data'], 1);

    ?>
    <script src="/statics/packs/jquery/jquery.min.js"></script>
    <script src="/statics/packs/jquery/poster_designer.js"></script>
    <script src="/statics/packs/jquery/jquery.contextMenu.js"></script>
    <script src="/statics/packs/layui/layui.js"></script>
    <p class="blockquote is-info">
        文本支持变量模式 {nickname} 支持纯文本 背景图片尺寸: 640 * 1008
    </p>
    <div class="main">

        <form action="" method="post" class="form-horizontal layui-form" enctype="multipart/form-data">

            <div class='panel panel-default'>

                <div class='panel-body'>

                    <div class="form-group">
                        <label class="col-xs-12 col-sm-3 col-md-2 control-label"><span style='color:red'>*</span> 海报设计</label>
                        <div class="col-sm-9 col-xs-12">
                            <table style='width:100%;'>
                                <tr>
                                    <td style='width:320px;padding:10px;' valign='top'>
                                        <div id='poster'>
                                            <img src="{{$bg_url}}" class="bg"/>

                                            @if(!empty($elements))
                                                @foreach( $elements as $key => $d)
                                                    <div class="drag" type="{{$d['type']}}" index="<?php echo $key + 1 ?>"
                                                         style="zindex:<?php echo $key + 1 ?>;left:{{$d['left']}};top:{{$d['top']}};  width:{{$d['width']}};height:{{$d['height']}}"
                                                         size="{{$d['size']}}" color="{{$d['color']}}">
                                                        @if($d['type']=='img' || $d['type']=='head')
                                                            <img src="/statics/images/logo.png"/>
                                                        @elseif($d['type']=='qr')
                                                            <img src="/statics/images/qrcode.jpg"/>
                                                        @elseif($d['type']=='realname')
                                                            <div class='text' style="font-size:{{$d['size']}};color:{{$d['color']}}">{{$d['text']}}</div>
                                                        @endif
                                                        <div class="rRightDown"></div>
                                                        <div class="rLeftDown"></div>
                                                        <div class="rRightUp"></div>
                                                        <div class="rLeftUp"></div>
                                                        <div class="rRight"></div>
                                                        <div class="rLeft"></div>
                                                        <div class="rUp"></div>
                                                        <div class="rDown"></div>
                                                    </div>
                                                @endforeach
                                            @endif
                                        </div>
                                    </td>
                                    <td valign='top' style='padding:10px;'>
                                        <div class='panel panel-default'>
                                            <div class='panel-body'>

                                                <div class="form-group">
                                                    <label class="col-xs-12 col-sm-3 col-md-2 control-label">海报元素</label>
                                                    <div class="col-sm-9 col-xs-12">
                                                        <button class='ui button btn-com' type='button' data-type='head'>头像</button>
                                                        <button class='ui button btn-com' type='button' data-type='realname'>文本</button>
                                                        <button class='ui button btn-com' type='button' data-type='qr'>二维码</button>
                                                        <button class='ui button btn-com' type='button' data-type='img'>图片</button>
                                                    </div>
                                                </div>
                                                <div id='qrset' style='display:none'>
                                                    <div class="form-group">
                                                        <label class="col-xs-12 col-sm-3 col-md-2 control-label">二维码尺寸</label>
                                                        <div class="col-sm-9 col-xs-12">
                                                            <select id='qrsize' class='form-control'>
                                                                <option value='1'>1</option>
                                                                <option value='2'>2</option>
                                                                <option value='3'>3</option>
                                                                <option value='4'>4</option>
                                                                <option value='5'>5</option>
                                                                <option value='6'>6</option>
                                                            </select>
                                                        </div>

                                                    </div>
                                                </div>

                                                <div id='nameset' style='display:none'>
                                                    <div class="ui right labeled input"></div>
                                                    <div class="form-group">
                                                        <div class="ui right labeled input">
                                                            <input style="height: 38px" id="color" type="color" name="color" placeholder="请选择颜色" value="">
                                                            <div class="ui basic label">
                                                                文本颜色
                                                            </div>
                                                        </div>

                                                        <div class="ui right labeled input">
                                                            <input type="text" id="namesize" placeholder="字体大小">
                                                            <div class="ui basic label">
                                                                px
                                                            </div>
                                                        </div>

                                                        <div class="ui right labeled input">
                                                            <input type="text" id="text">
                                                            <div class="ui basic label">
                                                                支持变量
                                                            </div>
                                                        </div>

                                                    </div>
                                                </div>
                                                <div class="form-group" id="imgset" style="display:none">
                                                    <label class="col-xs-12 col-sm-3 col-md-2 control-label">图片设置</label>
                                                    <div class="col-sm-9 col-xs-12">

                                                        <div class="input-group ">
                                                            <input type="text" name="img" value="" class="form-control" autocomplete="off">

                                                        </div>

                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
            <div class="field is-clearfix form-group col-sm-12">
                @csrf

                <input type="hidden" name="data" value="" />
                <button type="submit" lay-submit lay-filter="*" class="bra-btn is-primary col-lg-1">提交</button>

            </div>
        </form>
    </div>


@endsection
@section('requirejs')
@show
@section('bra_init_js')
@endsection
@section('footer_js')

    <script language="javascript">

        layui.use(['form'], function () {
            var form = layui.form;
            form.on('submit(*)', function (data) {

                var data = [];
                $('.drag').each(function () {
                    var obj = $(this);
                    var type = obj.attr('type');
                    var left = obj.css('left'), top = obj.css('top');
                    var d = {
                        left: left,
                        top: top,
                        type: obj.attr('type'),
                        width: obj.css('width'),
                        height: obj.css('height')
                    };
                    if (type == 'realname' || type == 'title' || type == 'marketprice' || type == 'productprice') {
                        d.size = obj.attr('size');
                        d.color = obj.attr('color');
                        d.text = obj.find('.text').html();
                    } else if (type == 'qr') {
                        d.size = obj.attr('size');
                    } else if (type == 'img') {
                        d.src = obj.attr('src');
                    }
                    data.push(d);
                });

                $(':input[name=data]').val(JSON.stringify(data));

            });
        });

        function bindEvents(obj) {
            var index = obj.attr('index');
            console.log(obj);
            var rs = new Resize(obj, {Max: true, mxContainer: "#poster"});
            rs.Set($(".rRightDown", obj), "right-down");
            rs.Set($(".rLeftDown", obj), "left-down");
            rs.Set($(".rRightUp", obj), "right-up");
            rs.Set($(".rLeftUp", obj), "left-up");
            rs.Set($(".rRight", obj), "right");
            rs.Set($(".rLeft", obj), "left");
            rs.Set($(".rUp", obj), "up");
            rs.Set($(".rDown", obj), "down");
            rs.Scale = true;
            var type = obj.attr('type');
            if (type == 'realname' || type == 'img' || type == 'title' || type == 'marketprice' || type == 'productprice') {
                rs.Scale = false;
            }
            new Drag(obj, {Limit: true, mxContainer: "#poster"});
            $('.drag .remove').unbind('click').click(function () {
                $(this).parent().remove();
            })

            $.contextMenu({
                selector: '.drag[index=' + index + ']',
                callback: function (key, options) {
                    var index = parseInt($(this).attr('zindex'));

                    if (key == 'next') {
                        var nextdiv = $(this).next('.drag');
                        if (nextdiv.length > 0) {
                            nextdiv.insertBefore($(this));
                        }
                    } else if (key == 'prev') {
                        var prevdiv = $(this).prev('.drag');
                        if (prevdiv.length > 0) {
                            $(this).insertBefore(prevdiv);
                        }
                    } else if (key == 'last') {
                        var len = $('.drag').length;
                        if (index >= len - 1) {
                            return;
                        }
                        var last = $('#poster .drag:last');
                        if (last.length > 0) {
                            $(this).insertAfter(last);
                        }
                    } else if (key == 'first') {
                        var index = $(this).index();
                        if (index <= 1) {
                            return;
                        }
                        var first = $('#poster .drag:first');
                        if (first.length > 0) {
                            $(this).insertBefore(first);
                        }
                    } else if (key == 'delete') {
                        $(this).remove();
                    }
                    var n = 1;
                    $('.drag').each(function () {
                        $(this).css("z-index", n);
                        n++;
                    })
                },
                items: {
                    "next": {name: "调整到上层"},
                    "prev": {name: "调整到下层"},
                    "last": {name: "调整到最顶层"},
                    "first": {name: "调整到最低层"},
                    "delete": {name: "删除元素"}
                }
            });
            obj.unbind('click').click(function () {
                bind($(this));
            })
        }

        var imgsettimer = 0;
        var nametimer = 0;
        var bgtimer = 0;

        function bindType(type) {
            $("#goodsparams").hide();
            $(".type4").hide();
            if (type == '4') {
                $(".type4").show();
            } else if (type == '3') {
                $("#goodsparams").show();
            }
        }

        function clearTimers() {
            clearInterval(imgsettimer);
            clearInterval(nametimer);
            clearInterval(bgtimer);

        }

        function getImgUrl(val) {

            if (val.indexOf('http://') == -1) {
                val = "/" + val;
            }
            return val;
        }

        function bind(obj) {
            var imgset = $('#imgset'), nameset = $("#nameset"), qrset = $('#qrset');
            imgset.hide(), nameset.hide(), qrset.hide();
            clearTimers();
            var type = obj.attr('type');
            console.log(type);
            if (type == 'img') {
                imgset.show();
                var src = obj.attr('src');
                var input = imgset.find('input');
                var img = imgset.find('img');
                if (typeof (src) != 'undefined' && src != '') {
                    input.val(src);
                    img.attr('src', getImgUrl(src));
                }

                imgsettimer = setInterval(function () {
                    if (input.val() != src && input.val() != '') {
                        var url = getImgUrl(input.val());

                        obj.attr('src', input.val()).find('img').attr('src', url);
                    }
                }, 10);

            } else if (type == 'realname' || type == 'title' || type == 'marketprice' || type == 'productprice') {

                nameset.show();
                var color = obj.attr('color') || "#000";
                var size = obj.attr('size') || "16";
                var color_input = nameset.find('#color');
                color_input.val(color);
                var text = nameset.find('#text');
                text.val(obj.find(".text").html());
                console.log(obj.find(".text"));
                var namesize = nameset.find('#namesize');
                var picker = nameset.find('.sp-preview-inner');
                namesize.val(size.replace("px", ""));
                picker.css({'background-color': color, 'font-size': size});

                nametimer = setInterval(function () {
                    var color = color_input.val();
                    console.log(color);
                    obj.attr('color', color).css('color', color);
                    obj.attr('size', namesize.val() + "px").css('font-size', namesize.val() + "px");
                    obj.find(".text").html(text.val());
                }, 100);

            } else if (type == 'qr') {
                qrset.show();
                var size = obj.attr('size') || "3";
                var sel = qrset.find('#qrsize');
                sel.val(size);
                sel.unbind('change').change(function () {
                    obj.attr('size', sel.val())
                });
            }
        }

        $(function () {

            $('#poster .drag').each(function () {
                bindEvents($(this));
            })
            $(':radio[name=type]').click(function () {
                var type = $(this).val();
                bindType(type);
            });
            $('.btn-com').click(function () {

                var imgset = $('#imgset'), nameset = $("#nameset"), qrset = $('#qrset');
                imgset.hide(), nameset.hide(), qrset.hide();
                clearTimers();

                if ($('#poster img').length <= 0) {
//alert('请选择背景图片!');
//return;
                }
                var type = $(this).data('type');
                var img = "";
                if (type == 'qr') {
                    img = '<img src="/statics/images/qrcode.jpg" />';
                } else if (type == 'head') {
                    img = '<img src="/statics/images/logo.png" />';
                } else if (type == 'img' || type == 'thumb') {
                    alert("对不起 暂时不支持图片");
                    return;
                    img = '<img src="../addons/hc_house_boy/style/poster/images/img.jpg" />';
                } else if (type == 'realname') {
                    img = '<div class=text>{nickname}</div>';
                } else if (type == 'title') {
                    img = '<div class=text>商品名称</div>';
                } else if (type == 'marketprice') {
                    img = '<div class=text>商品现价</div>';
                } else if (type == 'productprice') {
                    img = '<div class=text>商品原价</div>';
                }
                var index = $('#poster .drag').length + 1;
                var obj = $('<div class="drag" type="' + type + '" index="' + index + '" style="z-index:' + index + '">' + img + '<div class="rRightDown"> </div><div class="rLeftDown"> </div><div class="rRightUp"> </div><div class="rLeftUp"> </div><div class="rRight"> </div><div class="rLeft"> </div><div class="rUp"> </div><div class="rDown"></div></div>');
                $('#poster').append(obj);
                bindEvents(obj);

            });

            $('.drag').click(function () {
                bind($(this));
            })

        })
    </script>
    <style type="text/css">

        .labeled.input{
            margin-bottom:10px;
        }
        /*!
        * jQuery contextMenu - Plugin for simple contextMenu handling
        *
        * Version: 1.6.6
        *
        * Authors: Rodney Rehm, Addy Osmani (patches for FF)
        * Web: http://medialize.github.com/jQuery-contextMenu/
        *
        * Licensed under
        *   MIT License http://www.opensource.org/licenses/mit-license
        *   GPL v3 http://opensource.org/licenses/GPL-3.0
        *
        */
        .context-menu-list{
            margin:0;
            padding:0;
            min-width:120px;
            max-width:250px;
            display:inline-block;
            position:absolute;
            list-style-type:none;
            border:1px solid #DDD;
            background:#EEE;
            -webkit-box-shadow:0 2px 5px rgba(0, 0, 0, 0.5);
            -moz-box-shadow:0 2px 5px rgba(0, 0, 0, 0.5);
            -ms-box-shadow:0 2px 5px rgba(0, 0, 0, 0.5);
            -o-box-shadow:0 2px 5px rgba(0, 0, 0, 0.5);
            box-shadow:0 2px 5px rgba(0, 0, 0, 0.5);
            font-family:Verdana, Arial, Helvetica, sans-serif;
            font-size:11px;
        }
        .context-menu-item{
            padding:2px 2px 2px 24px;
            background-color:#EEE;
            position:relative;
            -webkit-user-select:none;
            -moz-user-select:-moz-none;
            -ms-user-select:none;
            user-select:none;
        }
        .context-menu-separator{
            padding-bottom:0;
            border-bottom:1px solid #DDD;
        }
        .context-menu-item > label > input,
        .context-menu-item > label > textarea{
            -webkit-user-select:text;
            -moz-user-select:text;
            -ms-user-select:text;
            user-select:text;
        }
        .context-menu-item.hover{
            cursor:pointer;
            background-color:#39F;
        }
        .context-menu-item.disabled{
            color:#666;
        }
        .context-menu-input.hover,
        .context-menu-item.disabled.hover{
            cursor:default;
            background-color:#EEE;
        }
        .context-menu-submenu:after{
            content:">";
            color:#666;
            position:absolute;
            top:0;
            right:3px;
            z-index:1;
        }
        /* icons
        #protip:
        In case you want to use sprites for icons (which I would suggest you do) have a look at
        http://css-tricks.com/13224107-pseudo-spriting/ to get an idea of how to implement
        .context-menu-item.icon:before {}
        */
        .context-menu-item.icon{ min-height:18px; background-repeat:no-repeat; background-position:4px 2px; }
        .context-menu-item.icon-edit{ background-image:url(images/page_white_edit.png); }
        .context-menu-item.icon-cut{ background-image:url(images/cut.png); }
        .context-menu-item.icon-copy{ background-image:url(images/page_white_copy.png); }
        .context-menu-item.icon-paste{ background-image:url(images/page_white_paste.png); }
        .context-menu-item.icon-delete{ background-image:url(images/page_white_delete.png); }
        .context-menu-item.icon-add{ background-image:url(images/page_white_add.png); }
        .context-menu-item.icon-quit{ background-image:url(images/door.png); }
        /* vertically align inside labels */
        .context-menu-input > label > *{ vertical-align:top; }
        /* position checkboxes and radios as icons */
        .context-menu-input > label > input[type="checkbox"],
        .context-menu-input > label > input[type="radio"]{
            margin-left:-17px;
        }
        .context-menu-input > label > span{
            margin-left:5px;
        }
        .context-menu-input > label,
        .context-menu-input > label > input[type="text"],
        .context-menu-input > label > textarea,
        .context-menu-input > label > select{
            display:block;
            width:100%;
            -webkit-box-sizing:border-box;
            -moz-box-sizing:border-box;
            -ms-box-sizing:border-box;
            -o-box-sizing:border-box;
            box-sizing:border-box;
        }
        .context-menu-input > label > textarea{
            height:100px;
        }
        .context-menu-item > .context-menu-list{
            display:none;
            /* re-positioned by js */
            right:-5px;
            top:5px;
        }
        .context-menu-item.hover > .context-menu-list{
            display:block;
        }
        .context-menu-accesskey{
            text-decoration:underline;
        }
        #poster{
            width:320px;border:1px solid #ccc;position:relative
        }
        #poster .bg{width:100%;z-index:0;display:block}
        #poster .drag[type=img] img, #poster .drag[type=thumb] img{ width:100%;height:100%; }
        #poster .drag{ position:absolute; width:80px;height:80px; border:1px solid #000; }
        #poster .drag[type=realname]{ width:80px;height:40px; font-size:16px; font-family:黑体;}
        #poster .drag img{position:absolute;z-index:0;width:100%; }
        #poster .rRightDown, .rLeftDown, .rLeftUp, .rRightUp, .rRight, .rLeft, .rUp, .rDown{
            position:absolute;
            background:#C00;
            width:7px;
            height:7px;
            z-index:1;
            font-size:0;
        }
        .rLeftDown, .rRightUp{cursor:ne-resize;}
        .rRightDown, .rLeftUp{cursor:nw-resize;}
        .rRight, .rLeft{cursor:e-resize;}
        .rUp, .rDown{cursor:n-resize;}
        .rLeftDown{left:-4px;bottom:-4px;}
        .rRightUp{right:-4px;top:-4px;}
        .rRightDown{right:-4px;bottom:-4px;background-color:#00F;}
        .rLeftUp{left:-4px;top:-4px;}
        .rRight{right:-4px;top:50%;margin-top:-4px;}
        .rLeft{left:-4px;top:50%;margin-top:-4px;}
        .rUp{top:-4px;left:50%;margin-left:-4px;}
        .rDown{bottom:-4px;left:50%;margin-left:-4px;}
        .context-menu-layer{ z-index:9999;}
        .context-menu-list{ z-index:9999;}
    </style>
@endsection
