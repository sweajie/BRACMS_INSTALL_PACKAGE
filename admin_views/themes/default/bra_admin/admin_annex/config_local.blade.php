@extends("themes." . $_W['theme'] . '.public.base_layout')

@section('main')
<div class="section container">
    <form class="bra-form">
        @csrf
        <div class="remote-qiniu layui-row" >

            <div class="field has-addons">

                <div class="control">
                    <div class="bra-btn is-info">自定义访问域名</div>
                </div>

                <div class="control is-expanded">
                    <input type="text" name="{{$provider['sign']}}[url]" class="bra-input is-primary" value="{{$config['url'] ?? ''}}" placeholder="" />
                </div>
                <div class="control">
                    <div class="bra-btn is-grey">
                        例如:  http://www.bracms.com/
                    </div>
                </div>
            </div>
            <div class="layui-form-item">
                <div class="">
                    <button name="submit" bra-submit bra-filter="*"  class="bra-btn layui-btn-primary" value="submit">保存配置</button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection


@section('footer_js')
    <x-bra_admin_post_js />
@endsection
