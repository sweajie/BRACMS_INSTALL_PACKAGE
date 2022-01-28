@extends("themes." . $_W['theme'] . '.public.base_layout')

@section('main')

    <?php
    $content = $detail['content'] ?? [];
    ?>
    <div class="columns is-mobile is-marginless">
        <div class="column is-8 tableBox">
            <form class="bra-form">
                @csrf
                <div class="panel panel-default">
                    <div class="panel-body">

                        <div class="field has-addons" id="mhcms_tpl">
                            <label class="control">
                                <span class="bra-btn"> 模板消息ID</span>
                            </label>
                            <div class="control is-expanded">
                                <input type="text" v-model="template_id" class="bra-input" name="content[template_id]" value="{$content['template_id']|default=''}">
                            </div>
                            <div class="control">
                                <div class="bra-select">
                                    <select v-model="template_id" lay-ignore="">
                                        <option value="">请选择消息</option>
                                        @foreach ($tpls as $tpl)
                                            <option value="{{$tpl['template_id']}}">{{$tpl['title']}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="field has-addons">
                            <label class="control">
                                <span class="bra-btn"> 头部标题</span>
                            </label>
                            <div class="control is-expanded">
                                <textarea name="content[first][value]" class="bra-textarea" data-rule-required='true' placeholder="first.DATA 的值">{{$content['first']['value']?: ""}}</textarea>
                            </div>
                            <div class="control">
                                <input type="color" class="bra-input" name="content[first][color]" value="{{$content['first']['color'] ?: ''}}" style="width:32px;height:32px;"/>
                            </div>
                        </div>

                        @if(isset($content['data']))
                            @foreach ($content['data'] as $k => $v)

                                <?php
                                if (in_array($k, ['first', 'remark', 'miniprogram', 'url', 'template_id'])) {
                                    continue;
                                }

                                ?>
                                <div class="field has-addons">
                                    <label class="control">
                                        <div class="bra-btn">{{$k}}</div>
                                    </label>
                                    <label class="control">
                                        <input type="text" name="keyword[]" class="bra-input" value="{{$k}}">
                                    </label>
                                    <label class="control is-expanded">
                                        <input type="text" name="value[]" class="bra-input" value="{{$v['value'] ?:''}}" placeholder="值"/>
                                    </label>

                                    <label class="control">
                                        <input type="color" name="color[]" class="bra-input" value="{{$v['color']?:''}}" style="width:32px"/>
                                    </label>
                                </div>
                            @endforeach
                        @endif

                        <div class="field has-addons">
                            <div class="col-xs-4 title">
                                <input type="button" class="bra-btn is-primary" value="增加" onclick="add_extra()">
                            </div>
                        </div>
                        <div id="extra" class="field">

                        </div>

                        <div class="field has-addons">
                            <label class="control">
                                <div class="bra-btn">尾部描述</div>
                            </label>

                            <label class="control is-expanded">
                                <textarea name="content[remark][value]" class="bra-textarea -control" placeholder="remark.DATA 的值">{{$content['remark']['value']?:''}}</textarea>

                            </label>

                            <label class="control">
                                <input type="color" class="bra-input" name="content[remark][color]" value="{{$content['remark']['color'] ?: ''}}" style="width:32px;"/>
                            </label>

                        </div>
                        <div class="field has-addons">
                            <label class="control">
                                <div class="bra-btn">消息链接</div>
                            </label>
                            <label class="control is-expanded">
                                <input type="text" class="bra-input" placeholder="" name="content[url]" value="{{$content['url'] ?: ''}}">
                            </label>
                        </div>
                        <div class="field has-addons">
                            <label class="control">
                                <div class="bra-btn">小程序APPID</div>
                            </label>
                            <label class="control is-expanded">
                                <input type="text" class="bra-input" placeholder="" name="content[miniprogram][appid]" value="{{$content['miniprogram']['appid']?: ''}}">
                            </label>
                        </div>

                        <div class="field has-addons">
                            <label class="control">
                                <div class="bra-btn">小程序页面</div>
                            </label>
                            <label class="control is-expanded">
                                <input type="text" class="bra-input" placeholder="" name="content[miniprogram][pagepath]" value="{{$content['miniprogram']['pagepath']?:''}}">
                            </label>
                        </div>

                        <div class="field has-addons">
                            <label class="control">
                                <div class="bra-btn">是否开启</div>
                            </label>
                            <div class="control is-expanded">

                                <div class="bra-radio bra-input">
                                    <label class="helper_label radio">
                                        <input type="radio" lay-ignore="" name="status" value="0" title="关闭"
                                               @if (!isset($detail['status']) || $detail['status']==0) checked @endif />
                                        <span>关闭</span>
                                    </label>

                                    <label class="helper_label radio">
                                        <input type="radio" lay-ignore name="status" value="1" title="开启"
                                               @if (!isset($detail['status']) || $detail['status']==1) checked @endif />
                                        <span>开启</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="field form-group">
                            <label class="col-sm-2 control-label"></label>
                            <div class="col-sm-9 col-xs-12">
                                <a bra-submit bra-filter="*" value="" class="bra-btn is-primary">提交</a>
                            </div>
                        </div>
                    </div>

                </div>
            </form>
        </div>
        <div class="column is-4">
        <?php
        $push_model_ids = array_filter(explode(',', $push['models']));

        ?>

        <div class="layui-collapse">

            @foreach ($push_model_ids as $push_model_id)
                <?php $bra_m = D($push_model_id); ?>
                <div class="layui-colla-item">
                    <h2 class="layui-colla-title"> <?php  echo $bra_m->_TM['title']; ?></h2>
                    <div class="layui-colla-content layui-show">

                        <table class="layui-table">

                            <thead>
                            <tr>
                                <th>别名</th>
                                <th>变量参数</th>
                            </tr>

                            </thead>
                            @foreach ($bra_m->fields as $k=>$field)
                                @if (!empty($field['slug']))
                                    <tr>
                                        <td>{{$field['slug'] ?:''}}</td>
                                        <td>{${{$bra_m->_TM['table_name']}}.{{$k}}}</td>
                                    </tr>
                                @else}

                                @endif
                            @endforeach
                        </table>
                    </div>
                </div>
            @endforeach

        </div>
    </div>
    </div>

    <div id="tpl" style="display: none">
        <div class="field has-addons">
            <label class="control">
                <div class="bra-btn">新项目</div>
            </label>
            <label class="control">
                <input type="text" name="keyword[]" class="bra-input" value="" placeholder="键名">
            </label>
            <label class="control is-expanded">
                <input type="text" name="value[]" class="bra-input" value="" placeholder="值"/>
            </label>
            <label class="control">
                <input type="color" name="color[]" value="" class="bra-input" style="width:32px;"/>
            </label>
        </div>
    </div>
@endsection

@section('footer_js')
    <script>

        function add_extra() {
            require(['jquery'], function ($) {
                $($("#tpl").html()).appendTo("#extra");
            });
        }

        require(['Vue'], function (Vue) {
            new Vue({
                el: "#mhcms_tpl",
                data: {
                    template_id: "{{ $content['template_id'] ?: '' }}"
                }, methods: {}
            });
        });
    </script>
    <x-bra_admin_post_js/>
@endsection
