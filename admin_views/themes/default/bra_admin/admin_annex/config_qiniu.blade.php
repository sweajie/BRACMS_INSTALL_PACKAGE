@extends("themes." . $_W['theme'] . '.public.base_layout')

@section('main')
<style>
    .bra-btn.is-info{
        min-width:120px;
    }
</style>
    <div class="section container">
        <form class="bra-form">
            @csrf
            <div class="remote-qiniu  ">

                <div class="field has-addons">
                    <div class="control">
                        <label class="bra-btn is-info">Accesskey</label>
                    </div>
                    <div class="control is-expanded">
                        <input type="text" name="{{$provider['sign']}}[accesskey]" class="bra-input" value="{{$config['accesskey']??''}}" placeholder=""/>
                    </div>
                    <div class="control">
                        <span class="bra-btn">用于签名的公钥</span>
                    </div>
                </div>
                <div class="field has-addons">
                    <div class="control">
                        <label class="bra-btn is-info">Secretkey</label>
                    </div>
                    <div class="control is-expanded">
                        <input type="text" name="{{$provider['sign']}}[secretkey]" class="bra-input encrypt" value="{{$config['secretkey'] ?? ''}}" placeholder=""/>
                    </div>
                    <div class="control">
                        <span class="bra-btn">用于签名的私钥</span>
                    </div>
                </div>
                <div class="field has-addons" id="qiniubucket">
                    <div class="control">
                        <label class="bra-btn is-info">Bucket</label>
                    </div>

                    <div class="control">
                        <input type="text" name="{$provider.sign}[bucket]" class="bra-input" value="{{$config['bucket'] ?? ''}}" placeholder=""/>
                    </div>
                    <div class="control">
                        <span class="bra-btn">请保证bucket为可公共读取的</span>
                    </div>
                </div>

                <div class="field has-addons" id="region">
                    <div class="control">
                        <label class="bra-btn is-info">Bucket区域</label>
                    </div>
                    <div class="control">
                        <div class="bra-select">
                        <select name="{{$provider['sign']}}[region]">
                            <option value="z0" @if (isset($config['region']) && $config['region']=='z0') selected @endif>华东</option>
                            <option value="z1" @if (isset($config['region']) && $config['region']=='z1') selected @endif>华北</option>
                            <option value="z2" @if (isset($config[ 'region']) && $config['region']=='z2') selected @endif>华南</option>
                            <option value="na0" @if (isset($config['region']) && $config['region']=='na0') selected @endif>北美</option>
                            <option value="as0" @if (isset($config['region']) && $config['region']=='as0') selected @endif>东南亚</option>
                        </select>
                        </div>
                    </div>

                    <div class="control">
                        <span class="bra-btn">请保证bucket为可公共读取的</span>
                    </div>
                </div>

                <div class="field has-addons">
                    <div class="control">
                        <label class="bra-btn is-info">Url</label>
                    </div>
                    <div class="control is-expanded">
                        <input type="text" name="{{$provider['sign']}}[url]" class="bra-input" value="{{$config['url'] ?? ''}}" placeholder=""/>
                    </div>
                    <div class="control">
                    <span class="bra-btn">
                        七牛自定义域名。例：http(s)://www.bracms.com/
                    </span>
                    </div>
                </div>

                <div class="field has-addons" id="qiniubucket">
                    <div class="control">
                        <label class="bra-btn is-info">图片样式</label>
                    </div>
                    <div class="control">
                        <input type="text" name="{{ $provider['sign']}}[img_style]" class="bra-input" value="{{$config['img_style'] ?? ''}}" placeholder="图片样式"/>
                    </div>
                    <div class="control">
                    <span class="bra-btn">
                       七牛云图片样式
                    </span>
                    </div>
                </div>

                <div class="field has-addons">
                    <div class="">
                        <button type="button" bra-submit bra-filter="*" class="bra-btn is-primary" value="submit">保存配置</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

@endsection


@section('footer_js')

    <x-bra_admin_post_js />
@endsection
