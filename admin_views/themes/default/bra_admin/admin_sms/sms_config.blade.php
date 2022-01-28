@extends("themes." . $_W['theme'] . '.public.base_layout')

@section('main')
    <?php
    $content = $detail['content'] ?? [];
    ?>

    <link href="//cdn.bootcss.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">
    <div class="clearfix container">
        <form class="bra-form form-horizontal form">
            @csrf
            <div class="panel panel-default">
                <div class="panel-body">

                    <div class="form-group">
                        <label class="col-sm-2 control-label must">模板消息ID</label>
                        <div class="col-sm-9 col-xs-12">
                            <input type="text" class="form-control" name="content[template_id]" value="{{$content['template_id'] ?? ''}}">
                        </div>
                    </div>

                    @if(isset($content['data']))
                        @foreach ($content['data'] as $k => $v)
                            <?php
                            if (in_array($k, ['first', 'remark'])) {
                                continue;
                            }
                            ?>
                            <div class="form-group key_item">
                                <label class="col-xs-12 col-sm-4 col-md-3 col-lg-2 control-label">{{$k}}</label>
                                <div class="col-xs-3" style="padding:0;padding-left:15px;">
                                    <input type="text" name="keyword[]" class="form-control" value="{{$k}}">
                                </div>
                                <div class="col-xs-5" style="padding:0;padding-left:15px;">
                                    <input type="text" name="value[]" class="form-control" value="{{$v['value']}}" placeholder="值"/>
                                </div>
                                <div class="col-xs-2">
                                    <input type="color" name="color[]" value="{{$v['color']}}" style="width:32px;height:32px;"/>
                                </div>
                            </div>
                        @endforeach
                    @endif

                    <div id="extra">

                    </div>

                    <div class="form-group">
                        <label class="col-xs-12 col-sm-4 col-md-3 col-lg-2 control-label"></label>
                        <div class="col-xs-4 title">
                            <input type="button" class="btn btn-primary" value="增加" onclick="add_extra()">
                        </div>

                    </div>

                    <div class="form-group">

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

                    </div>
                    <div class="form-group"></div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label"></label>
                        <div class="col-sm-9 col-xs-12">
                            <a bra-submit bra-filter="*" value="" class="btn btn-primary">提交</a>
                        </div>
                    </div>

                </div>
            </div>
        </form>
    </div>
    <div id="tpl" style="display: none">
        <div class="form-group key_item">
            <label class="col-xs-12 col-sm-4 col-md-3 col-lg-2 control-label"></label>
            <div class="col-xs-3" style="padding:0;padding-left:15px;">
                <input type="text" name="keyword[]" class="form-control" value="" placeholder="键名">
            </div>
            <div class="col-xs-5" style="padding:0;padding-left:15px;">
                <input type="text" name="value[]" class="form-control" value="" placeholder="值"/>
            </div>
            <div class="col-xs-2">
                <input type="color" name="color[]" value="" style="width:32px;height:32px;"/>
            </div>
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
    </script>

    <x-bra_admin_post_js/>
@endsection
