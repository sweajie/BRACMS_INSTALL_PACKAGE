@extends("themes." . $_W['theme'] . '.public.base_layout')

@section('main')

    <?php
    $levels = D('dis_level')->get()->toArray();
    ?>
    <div id="BraAPP" v-cloak>
        <form class="bra-form" :class="{'is-loading' : is_loading}" bra-id="*">

            @csrf
            <div class="is-box" style="padding:30px">
                <div class="bra-tabs is-toggle is-boxed" style="margin-bottom:15px">
                    <ul>
                        <li class="bra-tab" :class="{'is-active' : tab == 'basic'}" @click="tab = 'basic'"><a>{{ $config_name}}</a></li>
                    </ul>
                </div>

                <div class="bra-tabs-body" style="background:#fff;padding:25px">

                    <!--fixed-->
                    <div class="bra-tab-item " v-show="tab == 'basic'">

                        <div class="field is-grouped field-body is-hidden">
                            <div class="field has-addons">
                                <div class="control"><a class="bra-btn is-info  is-static">提现需实名</a></div>
                                <div class="control">
                                    <label class="bra-input">
                                        <div class="bra-switch is-primary">
                                            <label>
                                                <input v-model="config.draw_need_verify" name="data[draw_need_verify]" type="checkbox">
                                                <span class="lever"></span>
                                            </label>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="field has-addons">
                                <div class="control"><a class="bra-btn is-info  is-static">绑卡需实名</a></div>
                                <div class="control">
                                    <label class="bra-input">
                                        <div class="bra-switch is-primary">
                                            <label>
                                                <input v-model="config.bank_need_verify" name="data[bank_need_verify]" type="checkbox">
                                                <span class="lever"></span>
                                            </label>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="field has-addons">
                                <div class="control"><a class="bra-btn is-info  is-static">转账需实名</a></div>
                                <div class="control">
                                    <label class="bra-input">
                                        <div class="bra-switch is-primary">
                                            <label>
                                                <input v-model="config.trans_out_need_verify" name="data[trans_out_need_verify]" type="checkbox">
                                                <span class="lever"></span>
                                            </label>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="field has-addons">
                                <div class="control"><a class="bra-btn is-info  is-static">收款需实名</a></div>
                                <div class="control">
                                    <label class="bra-input">
                                        <div class="bra-switch is-primary">
                                            <label>
                                                <input v-model="config.trans_in_need_verify" name="data[trans_in_need_verify]" type="checkbox">
                                                <span class="lever"></span>
                                            </label>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>



                        <div class="field has-addons is-grouped field-body">

                            <div class="control"><a class="bra-btn is-purple">提现币种</a></div>

                            <div class="field has-addons">

                                <div class="control"><a class="bra-btn is-static">请选择</a></div>
                                <?php

                                $unit_types = D('pay_logs')->load_options('unit_type');

                                echo \Bra\core\utils\BraForms::radio($unit_types, $config['draw_coin'], 'data[draw_coin]', 'draw_coin', 'id', 'title', false , '' , '');
                                ?>

                            </div>

                        </div>
                        <!-- draw dee -->
                        <div class="field is-grouped field-body">


                            <div class="field has-addons">


                                <p class="control">
                                    <a class="bra-btn is-static">
                                        提现费率模式
                                    </a>
                                </p>
                                <p class="control">
                                    <select type="text" v-model="drawfee_way" class="bra-select bra-input" name="data[drawfee_way]" >
                                        <option value="">无手续费</option>
                                        <option value="1">按照百分比</option>
                                        <option value="2">按照固定金额</option>
                                    </select>
                                </p>
                                <p class="control">
                                    <a class="bra-btn is-static">
                                        请选择
                                    </a>
                                </p>
                            </div>

                            <div class="field has-addons" v-show="drawfee_way != ''">
                                <p class="control">
                                    <a class="bra-btn is-static">
                                        取款手续费
                                    </a>
                                </p>
                                <p class="control">
                                    <input class="bra-input"  name="data[drawfee]" value="{{$config['drawfee'] ?: ''}}" type="text" placeholder="取款手续费">
                                </p>
                                <p class="control">
                                    <a class="bra-btn is-static">
                                        @{{fee_unit}}
                                    </a>
                                </p>
                            </div>
                        </div>


                        <!-- trans dee -->
                        <div class="field is-grouped field-body">
                            <div class="field has-addons">
                                <p class="control">
                                    <a class="bra-btn is-static">
                                        转账手续费率模式
                                    </a>
                                </p>
                                <p class="control">
                                    <select type="text" v-model="transfee_way" class="bra-select bra-input" name="data[transfee_way]" >
                                        <option value="">无手续费</option>
                                        <option value="1">按照百分比</option>
                                        <option value="2">按照固定金额</option>
                                    </select>
                                </p>
                                <p class="control">
                                    <a class="bra-btn is-static">
                                        请选择
                                    </a>
                                </p>
                            </div>

                            <div class="field has-addons" v-show="transfee_way != ''">
                                <p class="control">
                                    <a class="bra-btn is-static">
                                        转账手续费
                                    </a>
                                </p>
                                <p class="control">
                                    <input class="bra-input"  name="data[transfee]" value="{{$config['transfee'] ?: ''}}" type="text" placeholder="转账手续费">
                                </p>
                                <p class="control">
                                    <a class="bra-btn is-static">
                                        @{{transfee_unit}}
                                    </a>
                                </p>
                            </div>
                        </div>



                        <div class="field has-addons">
                            <div class="control"><a class="bra-btn is-static">最低充值 </a></div>
                            <div class="control">
                                <input type="text" name="data[minimum_charge]" class="bra-input" placeholder="最低充值" value="{{ $config['minimum_charge'] ?: ''}}">
                            </div>
                            <p class="control">
                                <a class="bra-btn is-static">
                                    元
                                </a>
                            </p>
                        </div>



                        <div class="field has-addons">
                            <div class="control"><a class="bra-btn is-static">余额提现最低金额 </a></div>
                            <div class="control">
                                <input type="text" name="data[min_withdraw]" class="bra-input" placeholder="余额提现最低金额" value="{{$config['min_withdraw'] ?: ''}}">
                            </div>
                            <p class="control">
                                <a class="bra-btn is-static">
                                    元
                                </a>
                            </p>
                        </div>

                        <div class="field is-grouped field-body is-hidden">

                            <div class="field has-addons">
                                <div class="control"><a class="bra-btn is-static">周期 </a></div>
                                <div class="control">
                                    <input type="text" name="data[max_draw_days]" class="bra-input" placeholder="多少天之内" value="{{$config['max_draw_days'] ?: ''}}">
                                </div>
                                <p class="control">
                                    <a class="bra-btn is-static">
                                        天内
                                    </a>
                                </p>
                            </div>

                            <div class="field has-addons">
                                <div class="control"><a class="bra-btn is-static">最大提现次数 </a></div>
                                <div class="control">
                                    <input type="text" name="data[max_draw_count]" class="bra-input" placeholder="会员最大提现次数" value="{{$config['max_draw_count']?:''}}">
                                </div>
                                <p class="control">
                                    <a class="bra-btn is-static">
                                        次
                                    </a>
                                </p>
                            </div>
                        </div>




                        <div class="field has-addons">

                            <div class="control"><a class="bra-btn is-static">提现说明</a></div>
                            <div class="control is-expanded">
                                <textarea name="data[draw_txt]"  class="bra-textarea" id="" cols="30" rows="10">{{$config['draw_txt'] ?: ''}}</textarea>
                            </div>
                        </div>


                        <div class="field has-addons">
                            <div class="control"><a class="bra-btn is-static">充值说明</a></div>
                            <div class="control is-expanded">
                                <textarea name="data[deposit_txt]"  class="bra-textarea" id="" cols="30" rows="10">{{$config['deposit_txt'] ?: ''}}</textarea>
                            </div>
                        </div>


                    </div>

                    <div class="field has-addons">
                    </div>
                    <div class="field has-addons">
                        <div class="control">
                            <input type="hidden" value="post" name="bra_action">
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
                    drawfee_way : '{{$config['drawfee_way']}}' ,
                    transfee_way : '{{$config['transfee_way']}}' ,
                    config : <?php echo json_encode($config ?? []) ?>
                },
                computed : {
                    fee_unit : function () {
                        if(this.drawfee_way == 2){
                            return '元';
                        }
                        if(this.drawfee_way == 1){
                            return '%';
                        }
                        return '请选择模式';
                    } ,
                    transfee_unit : function () {
                        if(this.transfee_way == 2){
                            return '元';
                        }
                        if(this.transfee_way == 1){
                            return '%';
                        }
                        return '请选择模式';
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
                            root.bra_msg = data;
                            console.log(data)
                        }
                    });
                }
            });
        });
    </script>

@endsection
