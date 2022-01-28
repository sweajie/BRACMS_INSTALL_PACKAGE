@extends("themes." . $_W['theme'] . '.public.base_layout')

@section('main')
    <style>
        .bra-btn.is-info{width:150px}
    </style>
    <div class="container">
    <form id="cate_action" class="bra-form has-bg-white is-padding p-a">
        @csrf
        <div class="field has-addons">
            <div class="control">
                <div class="bra-btn is-info">
                    {{lang('操作单位类型')}}
                </div>
            </div>
            <div class="control">
                <div class="bra-input">
                @foreach ($coin_types as $unit_type=>$coin_type)
                    <label class="bra-radio">
                        <input type="radio" name="data[unit_type]" value="{{$unit_type}}" title="{{$coin_type['title']}}">
                        <span> {{$coin_type['title']}}</span>
                    </label>
                @endforeach
                </div>
            </div>
        </div>

        <div class="field has-addons">
            <div class="control">
                <div class="bra-btn is-info">
                    {{lang('用户名')}}
                </div>
            </div>
            <div class="control">
                    <input @if($target['id']) readonly @endif type="text" class="bra-input" name="data[user_name]"
                              value="{{$target['user_name']}}"/>
            </div>
        </div>

        <div class="field has-addons">
            <div class="control">
                <div class="bra-btn is-info">
                    {{lang('操作方向')}}
                </div>
            </div>
            <div class="control">
                <div class="bra-input">
                <label class="bra-radio"><input lay-ignore required name="data[operate]" value="2" type="radio"> <span> 增加</span></label>
                <label class="bra-radio"><input lay-ignore required name="data[operate]" value="1" type="radio"> <span> 减少</span></label>

                </div>
            </div>
        </div>

        <div class="field has-addons">
            <div class="control">
                <div class="bra-btn is-info">
                    {{lang('变化额度')}}
                </div>
            </div>
            <div class="control">
                <input type="text" name="data[amount]" size="10" value="" id="amount" class="bra-input">
            </div>
            <div class="control">
                <div class="bra-btn is-static">输入修改数量（资金或者点数）</div>
            </div>
        </div>
        <div class="field has-addons">
            <div class="control">
                <div class="bra-btn is-info">
                    {{lang('交易备注')}}
                </div>
            </div>
            <div class="control is-expanded">
                <textarea class="bra-textarea" name="data[note]" style="width: 80%" rows="5"></textarea>
            </div>
        </div>


        <div class="field has-addons">
            <div class="">
                <button data-action="close" type="button" bra-submit bra-filter="*" class="bra-btn is-primary" value="submit">立刻提交</button>
            </div>
        </div>
    </form>
    </div>
@endsection


@section('footer_js')
    <script>
        require(['layer', 'jquery', 'bra_form'], function (layer, $, bra_form) {

            bra_form.listen({
                url: "{!! $_W['current_url']  !!} ",
                before_submit: function (fields, cb) {
                    $('.bra-form').toggleClass('is-loading')
                    fields['bra_action'] = 'post';
                    cb(fields);
                },
                success: function (data, form) {

                    layer.msg(data.msg , {} , function () {

                        $('.bra-form').toggleClass('is-loading')
                        window.location.reload();
                    });
                },
                error: function (data) {
                    $('.bra-form').toggleClass('is-loading')
                    layer.msg(data.msg);
                },
                finish: function (data, form) {
                }
            });
        });
    </script>
@endsection


