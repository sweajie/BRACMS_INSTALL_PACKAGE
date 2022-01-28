@extends("themes." . $_W['theme'] . '.public.base_layout')

@section('table_form')

@endsection

@section('mhcms-header')
    自定义字段选项
@endsection

@section('main')
    <div class=" is-multiline ">
        <?php
        $has_models = [];

        ?>
        @foreach($lists as $list)
            <?php
            $set = config('bra_set');

            $modules = $set['modules'] ?? [];
            $where = [];
            $where['module_id'] = $list['id'];
            if (isset($modules[$list['module_sign']])) {
                $where['table_name'] = ['NOT IN', $modules[$list['module_sign']]['hide_models']];
            }

            $models = D('models')->bra_where($where)->list_item();

                //$models = \Bra\core\objects\BraArray::to_array($models);
            $fount = 0;
            foreach ($models as $k => $model) {
                if(!$model['id']){
                    dd($models);
                }
                $model['fields'] = D($model['id'])->get_fields();
                $models[$k] = $model;
                foreach ($model['fields'] as $f => $field) {
                    if (!isset($field['data_source_type'])) {
                        continue;
                    }
                    if ($field['data_source_type'] == 'bra_options') {
                        $fount = 1;
                        $has_models[] = $model['table_name'];
                    }
                }
            }

            if (!$fount) {
                continue;
            }
            ?>

            <div class="card " style="margin: 15px;">

                <div class="card-content">
                    <div class="content">

                        <div class="columns is-multiline ">

                            @foreach( $models as $model)
                                <?php

                                if (!in_array($model['table_name'], $has_models)) {
                                    continue;
                                }
                                ?>
                                <div class="column is-2">
                                    <div class="bra-dialog is-box is-primary">
                                        <div class="bra-dialog-header">
                                            {{$model['title']}}
                                        </div>
                                        <div class="blockquote is-paddingless">

                                            <div class="bra-cells bra-cell_access is-marginless">
                                                @foreach( $model['fields'] as $field)
                                                    @if(isset($field['data_source_type']) && $field['data_source_type'] =='bra_options')
<?php
    $map = [];
    $map['field_name'] = $field['field_name'];
    $map['table_name'] = $model['table_name'];
    $vars = "field_name={field_name}&model_id={table_name}";
?>
<a class="bra-cell" bra-mini="iframe" href="{{ build_back_link(729 , $vars , $map ) }}">
    <span class="panel-icon">
    <i class="iconfont icon-youhuixinxi" aria-hidden="true"></i>
    </span>
    <div class="bra-cell__bd">{{ $field['slug'] }} ({{ $model['title']}})</div>

    <div class="bra-cell__ft"></div>
</a>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>

                                </div>

                            @endforeach

                        </div>
                    </div>
                </div>

            </div>
        @endforeach
    </div>

@endsection
