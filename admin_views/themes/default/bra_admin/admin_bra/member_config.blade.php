@extends("themes." . $_W['theme'] . '.public.base_layout')

@section('main')
	<?php
	$levels =$levels = D('dis_level')->bra_get();
	?>
<div id="BraAPP" v-cloak>
    <form class="bra-form" :class="{'is-loading' : is_loading}" bra-id="*">

        <div class="is-box" style="padding:30px">
            <div class="bra-tabs is-toggle is-boxed" style="margin-bottom:15px">
                <ul>
                    <li @click="current_tab = 'member'" :class="{'is-active' : current_tab == 'member'}" class="bra-tab"><a>{{$config_name}}</a></li>
                    <li @click="current_tab = 'third_login'" :class="{'is-active' : current_tab == 'third_login'}" class="bra-tab"><a>第三方登录</a></li>
                </ul>
            </div>

            <div class="bra-tabs-body" style="background:#fff;padding:25px">

                <!--member-->
                <div v-show="current_tab == 'member'" class="bra-tab-item">


                    <div class="field has-addons is-grouped field-body">
                        <div class="field has-addons">
                            <div class="control"><a class="bra-btn is-info  is-static">手机注册</a></div>
                            <div class="control">
                                <label class="bra-input">
                                    <div class="bra-switch is-primary">
                                        <label>
                                            <input v-model="config.verify_mobile" name="data[verify_mobile]" type="checkbox">
                                            <span class="lever"></span>
                                        </label>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="field has-addons">
                            <div class="control"><a class="bra-btn is-info  is-static">使用实名接口</a></div>
                            <div class="control">
                                <label class="bra-input">
                                    <div class="bra-switch is-primary">
                                        <label>
                                            <input v-model="config.use_verify_api" name="data[use_verify_api]" type="checkbox">
                                            <span class="lever"></span>
                                        </label>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="field has-addons">
                            <div class="control"><a class="bra-btn is-info  is-static">注册展示填写推荐人</a></div>
                            <div class="control">
                                <label class="bra-input">
                                    <div class="bra-switch is-primary">
                                        <label>
                                            <input v-model="config.show_rec" name="data[show_rec]" type="checkbox">
                                            <span class="lever"></span>
                                        </label>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="field has-addons">
                            <div class="control"><a class="bra-btn is-info  is-static">注册必须填写推荐人</a></div>
                            <div class="control">
                                <label class="bra-input">
                                    <div class="bra-switch is-primary">
                                        <label>
                                            <input v-model="config.verify_rec" name="data[verify_rec]" type="checkbox">
                                            <span class="lever"></span>
                                        </label>
                                    </div>
                                </label>
                            </div>
                        </div>


                        <div class="field has-addons">
                            <div class="control"><a class="bra-btn is-info  is-static">注册时展示昵称填写</a></div>
                            <div class="control">
                                <label class="bra-input">
                                    <div class="bra-switch is-primary">
                                        <label>
                                            <input v-model="config.show_reg_nickname" name="data[show_reg_nickname]" type="checkbox">
                                            <span class="lever"></span>
                                        </label>
                                    </div>
                                </label>
                            </div>
                        </div>

                    </div>

                    <div class="field has-addons">
                        <div class="control"><a class="bra-btn is-info  is-static">关闭图片验证码</a></div>
                        <div class="control">
                            <label class="bra-input ">
                                <div class="bra-switch is-primary">
                                    <label>
                                        <input v-model="config.hide_code" type="checkbox" name="data[hide_code]" value="1">
                                        <span class="lever"></span>
                                    </label>
                                </div>
                            </label>

                        </div>
                    </div>

<div class="field has-addons">
                        <div class="control"><a class="bra-btn is-info  is-static">API单设备限制</a></div>
                        <div class="control">
                            <label class="bra-input ">
                                <div class="bra-switch is-primary">
                                    <label>
                                        <input v-model="config.api_single_device" type="checkbox" name="data[api_single_device]" value="1">
                                        <span class="lever"></span>
                                    </label>
                                </div>
                            </label>

                        </div>
                    </div>

<div class="field has-addons">
                        <div class="control"><a class="bra-btn is-info  is-static">WEB单设备限制</a></div>
                        <div class="control">
                            <label class="bra-input ">
                                <div class="bra-switch is-primary">
                                    <label>
                                        <input v-model="config.web_single_device" type="checkbox" name="data[web_single_device]" value="1">
                                        <span class="lever"></span>
                                    </label>
                                </div>
                            </label>

                        </div>
                    </div>


                    <div class="field has-addons">
                    </div>

                </div>

                <!--third_login-->
                <div v-show="current_tab == 'third_login'" class="bra-tab-item">

                    <div class="field has-addons is-grouped field-body">

                        <div class="control"><a class="bra-btn is-purple">布拉统一身份</a></div>


                        <div class="field has-addons">

                            <div class="control"><a class="bra-btn is-static">布拉系统域名</a></div>
                            <div class="control is-expanded">
                                <input type="text" name="data[bra_open_host]" v-model="config.bra_open_host" autocomplete="off" class="bra-input" placeholder="例:www.braui.org 域名必须支持https访问">
                            </div>
                        </div>
                        <div class="field has-addons">

                            <div class="control"><a class="bra-btn is-static">布拉媒体编号</a></div>
                            <div class="control">
                                <input type="text" name="data[bra_open_app_id]" v-model="config.bra_open_app_id" autocomplete="off" class="bra-input" placeholder="布拉媒体编号">
                            </div>
                            <div class="control"><a class="bra-btn is-static">ID</a></div>
                        </div>
                        <div class="field has-addons">

                            <div class="control"><a class="bra-btn is-static">布拉媒体编号</a></div>
                            <div class="control">
                                <input type="text" name="data[bra_open_app_secret]" v-model="config.bra_open_app_secret" autocomplete="off" class="bra-input" placeholder="布拉媒体编号">
                            </div>
                            <div class="control"><a class="bra-btn is-static">ID</a></div>
                        </div>

                    </div>
                </div>
            </div>
            <div class="field has-addons">
            </div>
            <div class="field has-addons">
                <div class="control">
                    @csrf
                    <input type="hidden" name="bra_action" value="post">

                    <a class="bra-btn is-primary" bra-submit bra-filter="*">立即提交</a>
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
                is_loading: false,
                bra_msg: {},
                config: <?php echo json_encode($config ?? []) ?> ,
                current_tab : 'member'
            },
            computed: {},
            methods: {},
            mounted: function () {
                var root = this;
                bra_form.listen({
                    url: "{!! $_W['current_url'] !!}",
                    before_submit: function (fields, cb) {
                        root.is_loading = true;
                        cb();
                    },
                    finish: function (data) {
                        root.is_loading = false;
                        root.show_dialog = true;
                        root.bra_msg = data;
                    }
                });
            }
        });
    });
</script>
@endsection
