@yield('bra_top')<!DOCTYPE html>
<html style="height: auto">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<title>@yield('page_title')</title>
@section('css_icon')
    <link rel="stylesheet" href="{{asset('statics/css/bra_icon.css')}}">
@show
@section('csrf_token')
<meta name="csrf-token" content="{{ csrf_token() }}">
@show
@section('bra_css')
    <link rel="stylesheet" type="text/css" href="{{asset('statics/packs/braui/bra.min.css')}}" media="all"/>
    <link rel="stylesheet" type="text/css" href="{{asset('/statics/packs/braui/bra-admin.css')}}" media="all"/>
@show

@section('animate_css')
    <link rel="stylesheet" type="text/css" href="{{asset('statics/packs/animate/animate.css')}}" media="all"/>
@show

@section('admin_theme_css')
    <link rel="stylesheet" type="text/css" href="{{asset('/statics/css/admin/bra-admin-default.css')}}" media="all"/>
@show

<script type="text/javascript">
    var urlArgs = "v={{$_W['site']['config']['bra_suffix'] ?? ''}}";
    var single_url = "{{url('bra_admin/admin_api/update_field')}}";
    var upload_url = "{{url('bra/annex/upload')}}";
    var save_attach = "{{url('bra/annex/save')}}";
    var csrf_token = "{{ csrf_token() }}";
  //  window.is_prod = "{if !$_W.develop}.min{/if}";
</script>

    @section('semantic_css')
        <link rel="stylesheet" href="/statics/packs/semantic/semantic.min.css">
    @endsection
</head>
<body class="bra-scroll-bar">
<?php
$is_fav_menu = false;
if (($_W['menu'] ?? false) && $_W['menu']['id']) {
    $is_fav_menu = (array) D('user_menu_fav')->bra_where(['menu_id' => $_W['menu']['id'], 'user_id' => $_W['admin']['id']])->first();
}
?>

<div class="bra-body bra-wrapper" id="bra_app">

    @section('top_header')
    @unless (isset($show_header))
        <div class="bra-body-header">
            <div class="columns is-gapless is-mobile">
                <div class="tit column">
                    <a class="tool-icon" onclick="$('.sub-menu-top').toggle()">
                        <i class="brafont bra-shouqizhankai is-size-7"></i>
                    </a>
                    @if(empty($is_fav_menu) && isset($_W['menu']) && $_W['menu'])
                    <a class="tool-icon" onclick="if(top.vm){top.vm.add_fav({
                id : '{{$_W['menu']['id']}}' ,
                url : '{{$_W['current_url']}}',
                type : 1,
                icon : '{{$_W['menu']['icon']}}' ,
                title : '{{$_W['menu']['menu_name']}}'
        })}else{alert('不支持子窗口在外部操作!')}" title="收藏菜单">
                        <i class="bra-icon bra-op-fav-line  "></i>
                    </a>
                    @elseif($is_fav_menu)
                    <a class="tool-icon" mini="confirm" data-href="{{url('bra_admin/admin_api/cancel_fav_menu' ,
['id' => $is_fav_menu['id']])}}" title="取消收藏菜单">
                        <i class="brafont bra-star-full"></i>
                    </a>
                    @endif

                    <a title="新窗口打开" target="_blank" class="tool-icon" href="{{$_W['current_url']}}"><i class="brafont bra-paper-plane"></i></a>
                    <a title="刷新" onclick="window.location.reload()" class="tool-icon" href="{{$_W['current_url']}}"><i class="brafont bra-reload"></i></a>

                </div>
                <div class="tit column">

                    @section('bar_text')
                    @if(isset($bar_text))
                        {{ $bar_text }}
                    @endif
                    @show

                    @section('bread')
                            @if(isset($menu))
                                <nav class="breadcrumb has-arrow-separator is-pulled-right is-marginless" aria-label="breadcrumbs" style="font-size: 12px">
                                    <ul>{!! \Bra\core\objects\BraMenu::get_admin_menu_path($menu['id']) !!}</ul>
                                </nav>
                            @endif

                    @show
                    <div class="is-clearfix"></div>
                </div>
            </div>

        </div>
    @endunless
    @show
    <div class="bra-body-content clearfix">


            <div class="sub-menu-top">
                @section('table_form')
                @show
            </div>



        @section('tips')
        @show


        @section('main')
        @show
    </div>
</div>
@section('requirejs')
    <x-requirejs />
@show
@section('footer_js')
@show
@section('bra_init_js')
    <script>
        require(['braui'], function (braui) {
            braui.bra_init();
        });
    </script>
@show

</body>
</html>
@section('bra_end')
@endsection
