@extends("themes." . $_W['theme'] . '.public.base_layout')

@section('main')

    <div class="container" id='bra_form_app'>
        <div class="has-bg-white" style="padding:15px">
            <form class="bra-form">
            @csrf
            @if($model->_TM['si_label']==1)
                <!-- labels -->
					<?php
					$donde = [];
					/**
					 * @var  $model Bra\core\objects\BraModel
					 */
					$donde['model_id'] = $model->_TM['id'];
					$labels = D('models_label')->bra_where($donde)->list_item();
					?>
                    <div class="layui-collapse">
                        @foreach( $labels as $label)
                            <div class="layui-colla-item">
                                <h2 class="layui-colla-title">{{ $label['title'] }}</h2>
                                <div class="layui-colla-content layui-show">
                                    <fieldset>

                                        @foreach($field_list as $k=>$field)
											<?php
											if (empty($field['form_str'])) {
												continue;
											}
											?>
                                            @if($field['field_group'] == $label['id'])

                                                <div class="field" id="row_{{$k}}">
                                                    @if(!empty($field['sub_fields']))
                                                        @foreach($field['sub_fields'] as $sub_field)
															<?php
															if (empty(trim($sub_field['form_str']))) {
																continue;
															}
															?>
                                                            <div class="layui-inline {$sub_field.field_group_class}">
                                                                <label class="layui-form-label">{{ trans($sub_field['slug'])}}
                                                                    @if($sub_field['must_fill']) <span class="ui red">*</span> @endif</label>
                                                                <div class="layui-input-block  {$sub_field.field_group_class}"> {{ $sub_field['form_str'] }}</div>
                                                            </div>
                                                        @endforeach
                                                    @else
                                                        <div class="field has-addons">

                                                            <div class="control">
                                                                <div class="bra-btn" style="width:100px">
                                                                    {{ trans($field['slug'])}}

                                                                    @if($field['must_fill'] ?? false) <span
                                                                            class="has-text-danger">*</span> @endif

                                                                    @if($field['tips'] ?? false)
                                                                        <span mini="tips" class="has-text-danger" data-title="{{$field['tips']}}">?</span>
                                                                    @endif
                                                                </div>
                                                            </div>

                                                            <div class="control is-expanded">
                                                                {!! $field['form_str'] !!}
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif
                                        @endforeach

                                    </fieldset>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    @foreach ($field_list as $k=>$field)
						<?php if (empty($field['form_str'])) {
							continue;
						}
						?>
                        <div class="field" id="row_{{$k}}">
                            @if(isset($field['sub_fields']) && $field['sub_fields'])
                                @foreach( $field['sub_fields'] as $sub_field)
                                    <div class="layui-inline">
                                        <label class="layui-form-label">
                                            {{ trans($sub_field['slug'])}}
                                            @if($sub_field['must_fill'])
                                                <span class="has-text-danger">*</span>
                                            @endif

                                            @if($sub_field['tips'] ?? '')
                                                <span class="has-text-danger" data-title="{$sub_field['tips']}">?</span>
                                            @endif
                                        </label>

                                        <div class="layui-input-inline"> {{ $sub_field['form_str']}}</div>
                                    </div>
                                @endforeach
                            @else
                                <div class="field has-addons">
                                    <div class="control">
                                        <div class="bra-btn" style="width:100px">
                                            {{ trans($field['slug'])}}

                                            @if($field['must_fill'] ?? false) <span
                                                    class="has-text-danger">*</span> @endif

                                            @if($field['tips'] ?? false)
                                                <span mini="tips" class="has-text-danger" data-title="{{$field['tips']}}">?</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="control is-expanded">
                                        {!! $field['form_str'] !!}
                                    </div>
                                </div>

                            @endif
                        </div>
                    @endforeach
                @endif
                <div class="field">
                    <div class="">
                        <a data-action="close" class="bra-btn is-info" bra-submit bra-filter="*">{{ $btn_txt ?? '保存并关闭'}}</a>
                        <a data-action="keep" class="bra-btn is-primary" bra-submit bra-filter="*">{{ $btn_txt ?? '保存继续'}}</a>
                    </div>
                </div>
            </form>
        </div>

    </div>


@endsection


@section('footer_js')

    <script>
        require(['layer', 'jquery', 'bra_form'], function (layer, $, bra_form) {
            console.log(bra_form)
            bra_form.listen({
                url: "{!! $_W['current_url']  !!} ",
                before_submit: function (fields, cb) {
                    $('.bra-form').toggleClass('is-loading')
                    cb();
                },
                success: function (data, form) {

                    if (form.trigger.data('action') == 'close' && parent.layer) {
                        parent.layer.closeAll();
                    } else {
                        layer.msg(data.msg);
                    }
                    if (parent.bra_page && parent.bra_page.table) {
                        parent.bra_page.table.setData();
                    }

                },
                error: function (data) {
                    console.log(data);
                    layer.msg(data.msg);
                },
                finish: function (data, form) {
                    $('.bra-form').toggleClass('is-loading')
                }
            });
        });
    </script>

    @if(is_array($_W['bra_templates'] ?? ''))
        @foreach($_W['bra_templates'] as $tpl)
            {!! $tpl !!}
        @endforeach
    @endif

    @foreach( $_W['bra_scripts'] as $bra_script)
        {!! $bra_script !!}
    @endforeach

@endsection
