@extends('install.base_layout')

@section('top_header')
    @include('install.style')
@endsection

@section('main')

    <div class="install-box">
    <fieldset class="layui-elem-field layui-field-title">
        <legend>您好，恭喜您程序安装完成！</legend>
    </fieldset>

    <div class="step-btns">
        <a href="/" class="layui-btn layui-btn-primary layui-btn-big fl">查看前台</a>
        <a  class="layui-btn layui-btn-big layui-btn-normal fr" href="/bra_admin/passport/login">后台管理</a>
    </div>

</div>

@endsection

@section('footer_js')
    <script>
        require(['layui' , 'jquery'] , function (){
            layui.use(['layer' , 'jquery'], function(){
            });
        });
    </script>
@endsection
