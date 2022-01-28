@extends("themes." . $_W['theme'] . '.public.base_layout')

@section('main')
    <?php

        global $_W;
    ?>
    <style>
        .bra-btn.is-info{
            min-width:120px;
        }
    </style>

    <div class="section container">
        <form class="bra-form">
            @csrf
            <div class="field has-addons">
                <div class="control">
                    <label class="bra-btn is-info">服务商APPID</label>
                </div>

                <div class="control is-expanded">
                    <input type="text" class="bra-input" name="data[app_id]" value="{{$pay_config['app_id']?? ''}}">
                </div>
            </div>

            <div class="field has-addons">
                <div class="control">
                    <label class="bra-btn is-info">服务商商户号</label>
                </div>
                <div class="control is-expanded">
                    <input type="text" class="bra-input" name="data[mchid]" value="{{$pay_config['mchid']??''}}">
                </div>
            </div>

            <div class="field has-addons">
                <div class="control">
                    <label class="bra-btn is-info">子商户号</label>
                </div>
                <div class="control is-expanded">
                    <input type="text" class="bra-input" name="data[sub_mch_id]" value="{{$pay_config['sub_mch_id'] ??''}}">
                </div>
            </div>


            <div class="field has-addons">
                <div class="control">
                    <label class="bra-btn is-info">支付apikey</label>
                </div>
                <div class="control is-expanded">
                    <input type="text" class="bra-input" name="data[apikey]" value="{{$pay_config['apikey'] ?? ''}}">
                </div>
            </div>

            <div class="field has-addons">
                <div class="control">
                    <label class="bra-btn is-info">apiclient_cert.pem 证书</label>
                </div>
                <div class="control is-expanded">
                    <?php
                    use Bra\core\utils\BraForms;
                    echo BraForms::layui_mutil_image_upload($pay_config['apiclient_cert'] ?? '', 'data[apiclient_cert]', 'apiclient_cert', 1 , 'pem' , 'file');
                    ?>
                </div>
            </div>

            <div class="field has-addons">
                <div class="control">
                    <label class="bra-btn is-info">apiclient_key.pem 证书</label>
                </div>
                <div class="control is-expanded">
                    <?php
                    echo BraForms::layui_mutil_image_upload($pay_config['apiclient_key'] ?? '', 'data[apiclient_key]', 'apiclient_key', 1, 'pem'  ,'file');

                    ?>
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
    <x-bra_admin_post_js/>


    @foreach( $_W['bra_scripts'] as $bra_script)
        {!! $bra_script !!}
    @endforeach

@endsection
