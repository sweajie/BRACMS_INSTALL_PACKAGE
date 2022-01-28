@extends('update.base_layout')


@section('top_header')
    @include('update.style')
@endsection

@section('main')

    <div class="install-box">
    <fieldset class="layui-elem-field layui-field-title">
        <legend>您好，恭喜您程序更新完成！</legend>
    </fieldset>


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
