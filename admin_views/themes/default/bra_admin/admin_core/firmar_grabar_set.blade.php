@extends("themes." . $_W['theme'] . '.public.base_layout')

@section('table_form')

@endsection
@section('mhcms-header')
    签到设置
@endsection


@section('main')
<div class="tableBox" >
    <div class="layui-container">
        <div class="layui-row">
            <form class="bra-form form-inline" >
                @csrf


                <div class="layui-form-item">
                    <div class="layui-input-">
                        <label class="layui-form-label">签到开关</label>
                        <div class="layui-input-block">
                            <input type="checkbox" name="data[allow_firmar]" value="1" @if(isset($config['allow_firmar']) &&  $config['allow_firmar']==1) checked @endif lay-skin="switch">
                        </div>
                    </div>
                </div>

                <div class="layui-form-item">
                    <div class="layui-input-block">
                        <button class="layui-btn" bra-submit bra-filter="*" >立即提交</button>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>


<script>
    require(['braui'], function (braui) {
        braui.bra_form("{$_W.current_url}")
    });
</script>
@endsection



@section('footer_js')
    <x-bra_admin_post_js/>
    @foreach( $_W['bra_scripts'] as $bra_script)
        {!! $bra_script !!}
    @endforeach

@endsection
