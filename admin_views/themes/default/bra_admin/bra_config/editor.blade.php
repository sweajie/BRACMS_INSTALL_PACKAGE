@extends("themes." . $_W['theme'] . '.public.base_layout')

@section('main')
    <div class="tableBox">
        <div class="container">
            <div class="layui-row">
                <form class="bra-form layui-form-pane has-bg-white padded">
@csrf
                    <div class="layui-form-item">
                        <label class="layui-form-label bra-btn is-link">{{$config_name}}</label>
                        <div class="layui-input-block">
                            <?php

                            global $_W;
                            use Bra\core\utils\BraForms;

                            echo BraForms::bra_editor('term', $config['term'] ?? '', 'data[term]', '100%', '400px')
                            ?>
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <div class="layui-input-block">
                            <button class="bra-btn" bra-submit bra-filter="*">立即提交</button>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>

@endsection


@section('footer_js')
    <x-bra_admin_post_js/>
    @foreach( $_W['bra_scripts'] as $bra_script)
        {!! $bra_script !!}
    @endforeach

@endsection
