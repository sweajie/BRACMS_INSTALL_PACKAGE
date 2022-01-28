@extends($_W['theme'] ? $_W['theme']. '.public.base_layout' : 'default' . '.public.base_layout')

@section('table_form')
    <?php
    use Bra\core\objects\BraMenu;$editor_menu_id = $idx_m->_TM['editor_menu_id'];
    $editor_url = BraMenu::get_menu_tpl_url($editor_menu_id);
    $sort_keys = explode(',', $idx_m->_TM['sort_keys']);
    $sort_keys = array_filter($sort_keys);
    $fields = $idx_m->fields;
    $sub_menu_count = 0;
    ?>
    <div class=" layui-row has-bg-white" id="table_app" style="padding:15px;">
        <form class="bra-form layui-form layui-form-pane" bra-id="*">
            <div class="columns is-multiline is-mobile">
                @foreach( $filter_fields as $k=>$field)
                    <?php
                    if (in_array($field['field_name'], $sort_keys)) {
                        $sort_fields[] = $field;
                    }
                    ?>
                    <div class="bra-form-item column  is-6-tablet  is-3-desktop is-3-widescreen" id="row_{!! $k !!}">
                        <div class="field has-addons">
                            <div class="control">
                                <label style="width:85px" class="is-static bra-btn">{!! $field['slug'] !!}</label>
                            </div>
                            <div class="control is-expanded">
                                {!! $field['form_str'] !!}
                            </div>
                        </div>
                    </div>

                @endforeach

                @if($sort_keys)
                    <div class="bra-form-item column  is-6-tablet  is-3-desktop is-3-widescreen" id="row_{$k}">
                        <div class="field has-addons ">
                            <label class="control">
                                <span class="is-static bra-btn" style="width:85px">排序</span>
                            </label>
                            <div class="control is-expanded">
                                <div class="bra-select">
                                    <select name="order" class="bra-select" style="width:100%">
                                        <option value="">
                                            自动排序
                                        </option>
                                        @foreach($sort_keys as $field_name)
                                            <option value="{$field_name} desc">
                                                {!! $fields[$field_name]['slug'] !!} 倒叙
                                            </option>

                                            <option value="{$field_name} asc">
                                                {!! $fields[$field_name]['slug'] !!} 顺序
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
            <div class="columns is-multiline is-mobile">
                <div class="column is-narrow">
                    @if($filter_fields)
                        <input type="hidden" name="bra_action" value="post">
                        <button type="button" class="bra-btn  is-primary"
                                bra-submit bra-filter="search">查询
                        </button>
                        <button type="reset" class="bra-btn  is-info"
                                bra-submit bra-filter="reset"
                        >重置
                        </button>
                    @endif
                </div>
                <div class="column">
                </div>
                @if($_W['admin_role']['id'] == 1 || $_W['admin_role']['allow_download'])
                    <div class="column is-narrow">
                        <a class="bra-btn  is-danger" bra-submit bra-filter="download" >下载数据</a>
                    </div>
                @endif
            </div>
        </form>
    </div>
@endsection

@section('main')
    <div class="" style="min-width:980px">

        <div class="tableBox" id="mhcms_admin_main_app">

            @verbatim
                <script type="text/html" id="_upload_bra_tpl">
                    {{#  if(d[d.field][0]){ }}  <img bra-mini="view_image" src="{{d[d.field][0].url}}" style="max-height:100%"/> {{#  } else { }}   {{#  } }}
                </script>
            @endverbatim
        </div>

        <div class="bra-table-idx" id="bra-table-idx">

        </div>

    </div>

@endsection

@section('footer_js')
    <script>
        var tpl = `{!!  $templet  !!}`;
        var sub_menu_count = {{ $sub_menu_count }};
        require(['tabulator', 'tabulator_editor', 'braui', 'handlebars/handlebars.min', 'layer', 'bra_form', 'Vue'],

            function (Tabulator, TabulatorEditor, braui, Handlebars, layer, bra_form, Vue) {
                $("#table_app").show();

                var extra = [];

                extra.unshift({
                    title: '操作',
                    headerSort: false, frozen: true,
                    formatter: function (cell, formatterParams, onRendered) {
                        var cell_row = cell.getRow();
                        var template = Handlebars.compile(tpl);
                        console.log(tpl ,cell_row._row.data)
                        return template({d: cell_row._row.data});
                    }
                });
                extra.unshift({
                    formatter: "rowSelection", titleFormatter: "rowSelection", hozAlign: "center", headerSort: false, cellClick: function (e, cell) {
                        cell.getRow().toggleSelect();
                    }, frozen: true
                });
                var cols = [<?php echo html($cols)?>];
                if (tpl != "<div></div>") {
                    cols = extra.concat(cols[0]);
                } else {
                    cols = cols[0];
                }


                console.log(cols);
                var table = new Tabulator("#bra-table-idx", {
                    maxHeight: $("#bra_app").height() - $(".sub-menu-top").height() - $(".bra-body-header").height() - $("#mhcms_admin_main_app").height() - 40,
                    minWidth: 800,
                    ajaxParams:{bra_action:"post"},
                    width: 2000,
                    ajaxURL: "{!! $_W['current_url'] !!}", //ajax URL
                    layout: "fitDataFill", //fit columns to width of table (optional)
                    columns: cols,
                    ajaxLoaderLoading: "<span class='bra-btn is-loading is-text'></span>",
                    pagination: "remote", //enable remote pagination
                    paginationSize: "{{ $_W['site']['config']['page_size'] ?? 10}}",
                    paginationSizeSelector: [5, 10, 20, 25, 30, 50],
                    ajaxResponse: function (url, params, response) {

                        table.setHeight($("#bra_app").height() - $(".sub-menu-top").height() - $(".bra-body-header").height() - $("#mhcms_admin_main_app").height() - 40); //set table height to 500px

                        return response;
                    },
                    ajaxConfig: {
                        headers: {
                            'Accept': "application/json, text/javascript, */*; q=0.01",
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    },
                    rowClick:function(e, row){
                        //e - the click event object
                        //row - row component
                        var annex = row._row.data.annex[0];

                        parent.$("[data-id='{{$_GPC['field_name']}}_val']").val(row._row.data.id);
                        var html = "";
                        if (row._row.data.font_type == '图片图标') {
                            html = "<img src='" +annex.url + "' style='max-height:25px' />";
                        } else {
                            html = params.data.icon;
                        }
                        parent.$("[data-id='{{$_GPC['field_name']}}_html']").html(html);
                        parent.layer.closeAll();

                    },
                    paginationDataSent: {
                        "size": "__page_size",
                    },
                    paginationDataReceived: {},
                    dataChanged: function (data) {
                        console.log(data)
                        //data - the updated table data
                    },
                    cellEdited: function (cell) {
                        var cell_row = cell.getRow();
                        var tpl = `{:html($editor_url)}`;
                        var template = Handlebars.compile(tpl);
                        var $editor_url = template({d: cell_row._row.data});
                        console.log($editor_url);
                        var params = {};
                        params[cell._cell.column.field] = cell._cell.value;
                        $.post($editor_url, {data: params}, function (data) {
                            if (data.code != 1) {
                                layer.msg(data.msg);
                            }
                            table.setPage(table.getPage());
                        }, 'json');
                    },
                });


                /*头工具栏事件*/
                $("#bra-table-ctrl a").on('click', function (obj) {
                    console.log(obj, action = $(this).data('id'));
                    var el = "#" + $(this).data('id');
                    var datas = table.getData('selected');
                    if (datas.length == 0) {
                        return layer.msg('最少选择一个');
                    }
                    console.log(table.getData('selected'));
                    var ids = [], action = $(this).data('mini');
                    $.each(datas, function (idx, item) {
                        ids.push(item.id);
                    });

                    if (action === 'bottom_action') {
                        braui.bra_bat_action(el, {ids: ids}, function (data) {
                            console.log(data);
                            layer.closeAll();
                            layer.msg(data.msg);
                            table.setData();
                        });
                    }

                });

                var option_keys = [];

                bra_form.listen({
                    id: "*",
                    url: "{{$_W['current_url']}}",
                    before_submit: function (fields, cb) {
                        console.log(this.filter)
                        switch (this.filter) {
                            case 'search':
                                console.log(fields)
                                for (var key in fields) {
                                    if (option_keys.indexOf(key) === -1) {
                                        option_keys.push(key);
                                    }
                                }
                                return table.setData("{{$_W['current_url']}}", fields);
                            case 'reset':

                                return table.setData("{{$_W['current_url']}}", {});
                            case 'download':

                                for (var key in fields) {
                                    if (option_keys.indexOf(key) === -1) {
                                        option_keys.push(key);
                                    }
                                }
                                /*检查 已经检索过的索引是否 被取消了*/
                                for (var key in option_keys) {
                                    var val = option_keys[key];
                                    if (!fields[val]) {
                                        fields[val] = '';
                                    }
                                }
                                fields._bra_download = 1;
                                    <?php
                                    if (strpos($_W['current_url'], '?') === false) {
                                        $current_url = $_W['current_url'] . "?";
                                    }
                                    ?>
                                var api_url = "{{ $current_url}}";

                                var url = api_url + braui.params_2_qs(fields);

                                window.location = url;
                                break;
                            default:
                                console.log(this.filter)
                        }
                    }
                });


            });
    </script>

    @foreach( $_W['bra_scripts'] as $bra_script)
        {!! $bra_script !!}
    @endforeach
@endsection
