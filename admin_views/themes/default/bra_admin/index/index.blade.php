@extends("themes." . $_W['theme'] . '.public.crm_layout')

@section('title', $_W['site']['config']['site_name'] . ' - 后台中心')


@section('logo_area')
    <span class="logo-text">{{$_W['site']['config']['site_name']}}<span></span></span>
@endsection


@section('footer_js_php')
    <?php

    $cp_prefix = 'cp_bra_admin';

    $mo = $_W['site']['config']['admin']['index_module'] ?? 'bra_admin';
    $default_tab_url = url($mo . '/admin_api/main');
    if (!empty($_W['site']['default_tab_url'])) {
        $default_tab_url = $_W['site']['default_tab_url'];
    }
    $mine_menus = D('user_menu_fav')->bra_where(['user_id' => $_W['user']['id']])->get();
    $load_menu_url = url('bra_admin/admin_api/load_menu');
    ?>
@show

@section('user_act_section')

    <a target="_blank" href="/"><i class="layui-icon layui-icon-home"></i></a>

    <a bra-mini="iframe" data-href="/bra_admin/admin_api/change_pass"><i class="layui-icon layui-icon-password"></i></a>

    <a bra-mini="load" data-href="{{ url('bra_admin/admin_api/clear_cache') }}"><i class="layui-icon layui-icon-fonts-clear has-text-danger"></i></a>


@endsection
