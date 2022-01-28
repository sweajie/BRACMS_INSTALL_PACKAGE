@extends("themes." . $_W['theme'] . '.public.base_layout')

@section('main')

    <div id="BraAPP" v-cloak>
        <form class="bra-form" :class="{'is-loading' : is_loading}" bra-id="*">

            @csrf
            <div class="is-box" style="padding:30px">
                <div class="bra-tabs is-toggle is-boxed" style="margin-bottom:15px">
                    <ul>
                        <li class="bra-tab"><a>
                                {{$detail['title']}}
                            </a>
                        </li>

                        <li @click="current_tab = 'store_set'" :class="{'is-active' : current_tab == 'store_set'}" class="bra-tab"><a>
                                升级设置
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="bra-tabs-body" style="background:#fff;padding:25px">

                    <!-- store_set -->
                    <div v-show="current_tab == 'store_set'" class="bra-tab-item">

                        <div class="field is-grouped field-body">
                            <div class="control" @click="add_deposit_set"><a class="bra-btn is-purple">添加套餐</a></div>

                        </div>

                        <div class="field is-grouped field-body" class="" v-for="(item,key) in deposit_set">

                            <div class="field has-addons">
                                <div class="control"><a class="bra-btn is-static">消费</a></div>

                                <div class="control">
                                    <select :name="'data[deposit_set]['+key+'][coin_type]'" required bra-verify="required " v-model="item.coin_type" lay-ignore class="bra-select">
                                        <option value="">
                                            选择币种
                                        </option>
                                        <option :value="bra_coin.field" v-for="bra_coin in bra_coins">
                                            @{{bra_coin.title}}
                                        </option>
                                    </select>
                                </div>
                                <div class="control is-expanded">
                                    <input type="text" :name="'data[deposit_set]['+key+'][amount]'" v-model="item.amount" class="bra-input">
                                </div>

                                <div class="control">
                                    <a class="bra-btn is-static">个</a>
                                </div>
                            </div>

                            <div class="field has-addons">
                                <div class="control"><a class="bra-btn is-static">赠送</a></div>
                                <div class="control is-expanded">
                                    <input type="text" :name="'data[deposit_set]['+key+'][reward_days]'" v-model="item.reward_days" class="bra-input">
                                </div>

                                <div class="control">
                                    <a class="bra-btn is-static">天会员</a>
                                </div>
                            </div>

                            <div class="field has-addons">
                                <div class="control"><a class="bra-btn is-static">套餐描述</a></div>
                                <div class="control is-expanded">
                                    <input type="text" :name="'data[deposit_set]['+key+'][text]'" v-model="item.text" class="bra-input">
                                </div>
                            </div>

                            <div class="control" @click="delete_deposit_set(key)"><a class="bra-btn is-purple">删除</a></div>

                        </div>

                        <div class="field has-addons">
                        </div>

                    </div>

                </div>
                <div class="field has-addons">
                </div>
                <div class="field has-addons">
                    <div class="control">
                        <a class="bra-btn is-primary" bra-submit bra-filter="*">立即提交</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection


@section('footer_js')
    <script>
        <?php
        if (!$config['deposit_set']) {
            $config['deposit_set'] = "{}";
        } else {
            $config['deposit_set'] = json_encode($config['deposit_set']);
        }
        ?>
        require(['Vue', 'bra_form'], function (Vue, bra_form) {
            new Vue({
                el: "#BraAPP",
                data: {
                    is_loading: false,
                    bra_msg: {},
                    config: <?php echo json_encode($config ?? []) ?> ,
                    current_tab: 'store_set',
                    deposit_set: <?php echo $config['deposit_set'] ?> ,
                    bra_coins: <?php echo json_encode(config('bra_coin')) ?>
                },
                computed: {},
                methods: {
                    add_deposit_set() {
                        this.$set(this.deposit_set, Math.random(), {
                            amount: '', reward_days: '', coin_type: '', text: ''
                        })
                    },

                    delete_deposit_set(index) {
                        this.$delete(this.deposit_set, index);
                    }
                },
                mounted: function () {
                    var root = this;
                    bra_form.listen({
                        url: "{!! $_W['current_url'] !!}",
                        before_submit: function (fields, cb) {
                            fields['bra_action'] = 'post';
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
