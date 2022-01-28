@extends("themes." . $_W['theme'] . '.public.base_layout')

@section('main')
    <style>
        .bra-btn.is-info{
            min-width:120px;
        }
    </style>
<div class="section">
    <form class="bra-form">
        @csrf
        <div class="field has-addons">
            <div class="control">
                <label class="bra-btn is-info">app_key</label>
            </div>
            <div class="control is-expanded">
                <input type="text" name="data[config][app_key]" value="{{$detail['config']['app_key']}}" required placeholder="请输入app_key" autocomplete="off" class="bra-input">
            </div>
        </div>
        <div class="field has-addons">
            <div class="control">
                <label class="bra-btn is-info">app_secret</label>
            </div>
            <div class="control is-expanded">
                <input type="text" name="data[config][app_secret]" value="{{$detail['config']['app_secret']}}" required  placeholder="请输入app_secret" autocomplete="off" class="bra-input">
            </div>
        </div>

        <div class="field has-addons">
            <div class="control">
                <label class="bra-btn is-info">signature</label>
            </div>
            <div class="control is-expanded">
                <input type="text" name="data[config][signature]" value="{{$detail['config']['signature']}}" required  placeholder="请输入短信签名" autocomplete="off" class="bra-input">
            </div>
        </div>



        <div class="field has-addons">
            <div class="">
                <button type="button" bra-submit bra-filter="*" class="bra-btn is-primary" value="submit">保存配置</button>
            </div>
        </div>

    </form>
</div>
@endsection

@section('footer_js')
    <x-bra_admin_post_js />
@endsection
