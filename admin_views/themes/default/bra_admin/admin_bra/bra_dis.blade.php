@extends("themes." . $_W['theme'] . '.public.base_layout')

@section('main')
    <div class="container" id="BraAPP" v-cloak>
        <form class="bra-form has-bg-white is-padding p-a" :class="{'is-loading' : is_loading}" bra-id="*">

            @csrf
            <div class="field has-addons">
                <div class="control"><a class="bra-btn is-info  is-static">是否开启分销</a></div>
                <div class="control">
                    <label class="bra-input">
                        <div class="bra-switch is-primary">
                            <label>
                                <input v-model="config.status" value="1" name="data[status]" type="checkbox">
                                <span class="lever"></span>
                            </label>
                        </div>
                    </label>
                </div>
            </div>

            <div class="field has-addons">
                <div class="control"><a class="bra-btn is-info  is-static">开启顶级保护</a></div>
                <div class="control">
                    <label class="bra-input">
                        <div class="bra-switch is-primary">
                            <label>
                                <input v-model="config.protect_parent" value="1" name="data[protect_parent]" type="checkbox">
                                <span class="lever"></span>
                            </label>
                        </div>
                    </label>
                </div>
                <div class="control">
                    <a class="bra-btn is-info  is-static">开启以后 , 只有顶级分销商才会有下线 a - b - c- d ,c和d都会是a的下线</a>
                </div>
            </div>


            <div class="field has-addons">
                <div class="control"><a class="bra-btn is-info  is-static">无上级可变化</a></div>
                <div class="control">
                    <label class="bra-input">
                        <div class="bra-switch is-primary">
                            <label>
                                <input v-model="config.re_refer" value="1" name="data[re_refer]" type="checkbox">
                                <span class="lever"></span>
                            </label>
                        </div>
                    </label>
                </div>
                <div class="control">
                    <a class="bra-btn is-info  is-static"> 开启以后 , 已注册没有上级的普通会员 扫描其他人的分销二维码可以成为他人的下级</a>
                </div>
            </div>

            <div class="field is-grouped field-body is-hidden">

                <div class="field has-addons">
                    <div class="control"><a class="bra-btn is-static">拉新奖励数量</a></div>
                    <div class="control">
                        <input type="text" name="data[point_new]" class="bra-input" placeholder="拉新奖励积分"
                               v-model="config.point_new">
                    </div>
                    <p class="control">
                        <select type="text" v-model="unit_type" class="bra-select bra-input" name="data[unit_type]">
                            <option>请选择模式拉下奖励单位</option>
                            <option value="1">积分</option>
                            <option value="2">金币</option>
                        </select>
                    </p>
                </div>

                <div class="field has-addons">
                    <div class="control"><a class="bra-btn is-static">拉新每月封顶 </a></div>
                    <div class="control">
                        <input type="text" name="data[point_month]" v-model="config.point_month" class="bra-input">
                    </div>
                </div>

            </div>

            <div class="field has-addons">
                <div class="control">
                    <div class="bra-btn is-static">
                        分销加入模式
                    </div>
                </div>

                <div class="control">
                    <div class="bra-input">

                        <label class='bra-radio '>
                            <input type="radio" v-model="config.mode" name="data[mode]" value="0" title="自动成为">
                            <span>自动成为</span>
                        </label>
                        <label class='bra-radio'>
                            <input type="radio" v-model="config.mode" name="data[mode]" value="1" title="需要申请,无需审核">
                            <span>需要申请,无需审核</span>
                        </label>
                        <label class='bra-radio'>
                            <input type="radio" v-model="config.mode" name="data[mode]" value="2" title="需要申请和审核">
                            <span>需要申请和审核</span>
                        </label>
                        <label class='bra-radi  is-hidden'>
                            <input type="radio" v-model="config.mode" name="data[mode]" value="3" title="总消费金额满">
                            <span>总消费金额满</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="field has-addons">
                <div class="control">
                    <div class="bra-btn is-static">
                        全局分成层级
                    </div>
                </div>

                <div class="control">
                    <div class="bra-input">

                        <label class='bra-radio'>
                            <input type="radio" v-model="config.level" name="data[level]" value="0" title="不分成">
                            <span>不分成</span>
                        </label>
                        <label class='bra-radio'>
                            <input type="radio" v-model="config.level" name="data[level]" value="1" title="一级分成">
                            <span>一级分成</span>
                        </label>
                        <label class='bra-radio'>
                            <input type="radio" v-model="config.level" name="data[level]" value="2" title="二级分成">
                            <span>二级分成</span>
                        </label>
                        <label class='bra-radi  is-hidden'>
                            <input type="radio" v-model="config.mode" name="data[mode]" value="3" title="三级分成">
                            <span>三级分成</span>
                        </label>
                    </div>
                </div>

            </div>

            <div class="field has-addons">
                <div class="control">
                    <div class="bra-btn is-static">
                        分销协议说明
                    </div>
                </div>

                <div class="control">
                    <?php
                    global $_W;
                    use Bra\core\utils\BraForms;

                    list($f , $s) = BraForms::bra_editor('term', $config['term'] ?? '', 'data[term]', '100%', '400px');
                    echo $f;
                    ?>
                </div>
            </div>

            <div class="layui-form-item">
                <div class="layui-input-block">
                    <input type="hidden" name="bra_action" value="post">
                    <a :class="{'is-loading' : is_loading}" class="bra-btn is-primary" bra-submit bra-filter="*">立即提交</a>
                </div>
            </div>

    </div>

@endsection

@section('footer_js')
    <script>
        require(['Vue', 'bra_form', 'layer'], function (Vue, bra_form, layer) {

            new Vue({
                el: "#BraAPP",
                data: {
                    show_dialog: false,
                    tab: 'basic',
                    is_loading: false,
                    unit_type: "{{$config['unit_type']}}",
                    config: <?php echo json_encode($config ?? []) ?>
                },
                computed: {
                    unit_type_text: function () {
                        if (this.unit_type == 2) {
                            return '金币';
                        }
                        if (this.unit_type == 1) {
                            return '积分';
                        }
                        return '单位';
                    }
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
                            layer.msg(data.msg);
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
