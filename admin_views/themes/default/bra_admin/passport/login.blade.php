<!DOCTYPE html>
<html>
<head>
    <?php
    use Bra\core\objects\BraString;
    $season = BraString::get_season();
	$season =  $season =  "winter";
    ?>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport"  content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, minimal-ui">
    <title>{{ $page_title ?? 'BraCMS' }}  {{$admin_menu->user_menu_name ?? ''}} </title>
    <link rel="stylesheet" type="text/css" href="{{ asset('statics/css/bra_icon.css') }}" >
    <link rel="stylesheet" type="text/css" href="{{ asset('statics/packs/braui/bra.min.css') }}" >
    <link rel="stylesheet" type="text/css" href="{{ asset("statics/packs/braui/$season.css") }}" >
    <style>
        .login-container{width:100%;height:100%;overflow:hidden;background-size:cover;}
        .tab a{padding:0 5px}
        .left_box{width:100%;position:fixed;top:0;height:100%;transform:translateX(-100%);}
        .left_box.show{transform:translateX(0);transition:all 0.5s;}
        .logo{width:50%;height:100%}
        .right_box{width:30%;position:fixed;top:0;height:100%;opacity:0;transform:translateX(0);min-width:350px;max-width:500px;transition:all 1.2s;padding:0 1%}
        .right_box.show{opacity:1;right:12%; }
        .bra-form{margin:45% auto;}
        .login_item{margin:0 30px !important;}
        .login_item i.iconfont{padding-right:6px;font-size:18px}
        .login_button{padding:15px 5px;margin:0 30px !important;}

        .bra-btn, .bra-input, .bra-select select, .bra-textarea, .file-cta, .file-name {
            height: 3.5em!important;
        }
        .bra-input, .bra-select select, .bra-textarea {
            background-color: transparent!important;
        }


    </style>
</head>
<body style="position:fixed;height:100vh;left:0;top:0;width:100vw;">

<div id="container"></div>
<div class="login-container" id="bracms_app">

    <div class="left_box">
        <div class="logo"  style="background: url('/statics/images/logo.png')no-repeat center;background-size: 200px auto"></div>
    </div>

    <div class="right_box">

        <form id="login_form" class="bra-form" :class="{'is-loading' : is_loading}" bra-id="login_form">

            @csrf
            <div class="column is-12  is-relative">
                <div class="has-text-centered">
                    <h2 style="font-weight: 600;color:#7B6456; font-size: 32px;line-height: 80px;text-transform: uppercase;font-family: Impact">Login system！</h2>
                </div>

                <div class="login_item columns is-mobile is-marginless is-vcentered ">
                    <div class="column has-text-weight-bold is-narrow">
                    </div>
                    <div class="column">
                        <input placeholder="输入登录用户名" class="bra-input rm-border is-shadowless f14" name="data[user_name]" type="text" autofocus="" value="" bra-verify="required">
                    </div>
                </div>

                <div class="login_item columns is-mobile is-marginless is-vcentered ">

                    <div class="column has-text-weight-bold is-narrow">
                    </div>
                    <div class="column">
                        <input placeholder="输入登录密码" class="bra-input rm-border is-shadowless f14" name="data[password]" bra-verify="required" type="password" value="">
                    </div>
                </div>


                <div class="login_item columns is-mobile is-marginless is-vcentered ">

                    <div class="column has-text-weight-bold is-narrow">
                    </div>

                    <div class="column is-6-desktop is-6-touch">

                        <input name="captcha" bra-verify="required" class="bra-input rm-border is-shadowless f14" type="text" placeholder="输入验证码">
                    </div>

                    <div class="column is-5-desktop is-5-touch" style="text-align: right;">
                        <img src="{{ captcha_src('flat') }}" alt="captcha"
                             onclick="this.src='/captcha/flat?'+Math.random();" style="height: 2.5em"/>
                    </div>
                </div>

                <div class="bk10"></div>
                <div class="bk10"></div>
                <div class="bk10"></div>

                <div class="login_button columns  is-mobile is-marginless align-items-center is-gapless">
                    <div class="column is-12-desktop is-12-touch">
                        <div bra-submit bra-filter="login_form" type="button" class="login-btn bra-btn is-block">立即登录</div>
                    </div>
                </div>
                <div class="column has-text-centered f12"> Please login to proceed.</div>
            </div>
        </form>
    </div>
</div>


<script type="text/javascript" src="{{ asset('statics/js/require.js') }}"></script>
<script type="text/javascript" src="{{ asset('statics/js/config.js') }}"></script>
<script type="text/javascript" src="{{ asset("statics/packs/braui/$season.js") }}"></script>
<script>
    if (window.top !== window.self) {
        window.top.location = window.location;
    }
    require(['layer', 'Vue', 'bra_form', 'bra_verify'  ], function (layer, Vue, bra_form, bra_verify) {
        new Vue({
            el: "#bracms_app",
            data: function () {
                return {
                    is_loading: false
                }
            },
            methods: {},
            mounted: function () {
                var root = this;
                bra_form.listen({
                    url: "{{$_W['current_url']}}",
                    verify: bra_verify,
                    filter: "login_form",
                    before_submit: function (fields, cb) {
                        root.is_loading = true;
                        cb();
                    },
                    error: function (data) {
                        console.log(data)
                    },
                    finish: function (data) {
                        if (data.code == 1) {
                            window.location.href = data.url;
                        } else {
                            root.is_loading = false;
                            layer.msg(data.msg);
                        }
                    }
                });
                setTimeout(function () {
                    $('.right_box').addClass('show');
                    $('.left_box').addClass('show');
                }, 500);
            }
        });
    });
</script>
</body>
</html>
