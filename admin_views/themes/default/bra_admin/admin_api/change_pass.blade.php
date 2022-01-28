@extends($_W['theme'] ? $_W['theme']. '.public.base_layout' : 'default' . '.public.base_layout')

@section('top_header')
@endsection
@section('main')
    <?php global $_W; ?>
    <div class="container">
        <form class="bra-form">
            @csrf

            <div class="layui-tab layui-tab-card ">

                <div class="layui-tab-content has-bg-white is-padding p-a">

                    <div class="field has-addons">
                        <div class="control">
                            <label class="bra-btn">原密码</label>
                        </div>
                        <div class="control is-expanded">
                            <input type="text" name="data[pass_old]" placeholder="原密码"
                                   autocomplete="off" class="bra-input">
                        </div>
                    </div>

                    <div class="field has-addons">
                        <div class="control">
                            <label class="bra-btn">输入新密码</label>
                        </div>
                        <div class="control is-expanded">
                            <input type="text" name="data[password]"  placeholder="新密码"
                                   autocomplete="off" class="bra-input">
                        </div>
                    </div>

                    <div class="field has-addons">
                        <div class="control">
                            <label class="bra-btn">确认新密码</label>
                        </div>
                        <div class="control is-expanded">
                            <input type="text" name="data[password2]"  placeholder="确认新密码" autocomplete="off" class="bra-input">
                        </div>
                    </div>

                    <div class="field has-addons">
                        <div class="">
                            <button type="button" bra-submit bra-filter="*" class="bra-btn is-primary" value="submit">保存密码</button>
                        </div>
                    </div>
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
