@extends("themes." . $_W['theme'] . '.public.base_layout')

@section('main')
<div id="BraAPP" v-cloak>
    <form class="bra-form" :class="{'is-loading' : is_loading}" bra-id="*">

        <div class="is-box" style="padding:30px">

            <div class="bra-tabs-body" style="background:#fff;padding:25px">
                <div class="bra-tab-item ">
                    <div class="field has-addons">
                        <div class="control">
                            <div class="bra-btn is-static">
                                模板渲染
                            </div>
                        </div>

                        <div class="control">
                            <div class="bra-input">

                                <label class='bra-radio' >
                                    <input type="radio" v-model="config.fixed_device" name="data[fixed_device]" value="mobile" title="手机模板" >
                                    <span>手机模板</span>
                                </label>
                                <label class='bra-radio' >
                                    <input type="radio" v-model="config.fixed_device" name="data[fixed_device]" value="desktop" title="电脑模板" >
                                    <span>电脑模板</span>
                                </label>
                                <label class='bra-radio' >
                                    <input type="radio" v-model="config.fixed_device" name="data[fixed_device]" value="auto" title="自动模板" >
                                    <span>自动模板</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="field has-addons">
                        <div class="control">
                            <div class="bra-btn is-static">
                                默认主题
                            </div>
                        </div>
                        <div class="control">
                            <div class="bra-input">
                                <?php
                                use Bra\core\objects\BraField;
                                $field_config = [
                                    'data_source_type' => 'theme',
                                    'data_source_config' => "",
                                    'form_name' => 'theme',
                                    'field_name' => 'theme',
                                    'mode' => 'theme_selector',
                                    'field_type_name' => 'bra_theme',
                                    'default_value' => isset($config['theme']) && $config['theme'] ? $config['theme'] : 'default',
                                    'form_id' => 'field_type_name',
                                    'class_name' => ' form-control',
                                    'pk_key' => 'theme_dir',
                                    'name_key' => 'theme_name',
                                    'parentid_key' => '',
                                    'form_property' => '  required ',
                                    'primary_option' => 'Please,Select',
                                    'form_group' => 'data',
                                    'module' => 'bra'
                                ];
                                $def = [];
                                echo (new BraField($field_config))->bra_form->out_put_form();
                                ?>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="field has-addons">
                </div>
                <div class="field has-addons">
                    <div class="control">
                        @csrf
                        <a class="bra-btn is-primary" bra-submit bra-filter="*">立即提交</a>
                    </div>
                </div>
            </div>
        </div>
    </form>

</div>

@endsection

@section('footer_js')

    <script>
        require(['Vue', 'bra_form'], function (Vue, bra_form) {


            new Vue({
                el: "#BraAPP",
                data: {
                    show_dialog: false,
                    tab: 'basic',
                    config_name: '{{$config_name}}',
                    is_loading: false ,
                    bra_msg : {} ,
                    config : <?php echo json_encode($config ?? []) ?>
                },
                computed : {
                },
                methods: {},
                mounted: function () {
                    var root = this;
                    bra_form.listen({
                        url: "{{$_W['current_url']}}",
                        before_submit: function (fields, cb) {
                            root.is_loading = true;
                            cb();
                        },
                        finish: function (data) {
                            root.is_loading = false;
                            root.show_dialog = true;
                            root.bra_msg = data;
                            console.log(data)
                        }
                    });
                }
            });
        });
    </script>

    @foreach( $_W['bra_scripts'] as $bra_script)
        {!! $bra_script !!}
    @endforeach

@endsection

