@extends("themes." . $_W['theme'] . '.public.base_layout')
@section('table_form')
@endsection


@section('main')

    <div style="min-width:980px">
        <div class="tableBox" id="mhcms_admin_main_app">
            <div id="bra-table-ctrl" class="is-padding p-a is-margin m-b has-bg-white">
                @foreach( $sub_menus as $_menu)

                    @if($_menu['display'] =="2")

                        <?php
                        $_menu = (array)$_menu;
                        $res = build_back_link($_menu, '', $mapping);
                        ?>
                        <?php $sub_menu_count++; ?>
                        <a data-id="{{$_menu['mini']}}_{{$_menu['id']}}" id="{!! $_menu['mini'] !!}_{!!$_menu['id']!!}"

                           class="{!! $_menu['class'] !!}" data-action_type="{!! $_menu['mini'] !!}"
                           data-mini="bottom_action" data-href="{!! $res !!}">{{ trans($_menu['menu_name'])}}
                        </a>
                    @endif

                    @if($_menu['display'] =="1")
                        {!!  build_back_a((array)$_menu ,[], $mapping)  !!}
                    @endif

                @endforeach
            </div>

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
        window.bra_page = {};
        var tpl = `{!!  $templet  !!}`;
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
                        //console.log(tpl ,cell_row._row.data)
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


                bra_page.table = new Tabulator("#bra-table-idx", {
                    maxHeight: $("#bra_app").height() - $(".sub-menu-top").height() - $(".bra-body-header").height() - $("#mhcms_admin_main_app").height() - 40,
                    minWidth: 800,
                    ajaxParams:{bra_action:"post"},

                    dataTree:true,
                    dataTreeFilter:false,
                    dataTreeChildField:"children", //look for the child row data array in the childRows field


                    width: 2000,
                    ajaxURL: "{!! $_W['current_url'] !!}", //ajax URL
                    layout: "fitDataFill", //fit columns to width of table (optional)
                    columns: cols,
                    ajaxLoaderLoading: "<span class='bra-btn is-loading is-text'></span>",
                    pagination: "remote", //enable remote pagination
                    paginationSize: "{{ $_W['site']['config']['page_size'] ?? 10}}",
                    paginationSizeSelector: [5, 10, 20, 25, 30, 50],
                    ajaxResponse: function (url, params, response) {

                        bra_page.table.setHeight($("#bra_app").height() - $(".sub-menu-top").height() - $(".bra-body-header").height() - $("#mhcms_admin_main_app").height() - 40); //set table height to 500px

                        return response;
                    },
                    ajaxConfig: {
                        headers: {
                            'Accept': "application/json, text/javascript, */*; q=0.01",
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        }
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
                            bra_page.table.setPage(bra_page.table.getPage());
                        }, 'json');
                    },
                });


                /*头工具栏事件*/
                $("#bra-table-ctrl a[data-mini='bottom_action']").on('click', function (obj) {
                    console.log(obj, action = $(this).data('id'));
                    var el = "#" + $(this).data('id');
                    var datas = bra_page.table.getData('selected');
                    if (datas.length == 0) {
                        return layer.msg('最少选择一个');
                    }
                    console.log(bra_page.table.getData('selected'));
                    var ids = [], action = $(this).data('mini');
                    $.each(datas, function (idx, item) {
                        ids.push(item.id);
                    });

                    if (action === 'bottom_action') {
                        braui.bra_bat_action(el, {

                            ids: ids ,
                            _token : "{{ csrf_token() }}"
                        }, function (data) {
                            layer.closeAll();
                            layer.msg(data.msg);
                            //bra_page.table.setData("{{$_W['current_url']}}", {bra_action:"post"});

                            bra_page.table.setPage(bra_page.table.getPage());
                        });
                    }

                });

                var option_keys = [];

                bra_form.listen({
                    id: "*",
                    url: "{!! $_W['current_url'] !!}",
                    before_submit: function (fields, cb) {
                        switch (this.filter) {
                            case 'search':
                                for (var key in fields) {
                                    if (option_keys.indexOf(key) === -1) {
                                        option_keys.push(key);
                                    }
                                }
                                return bra_page.table.setData("{!! $_W['current_url'] !!}", fields);
                            case 'reset':

                                return bra_page.table.setData("{!! $_W['current_url'] !!}", {bra_action:"post"});
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
									$current_url = $_W['current_url'];
                                    if (strpos($_W['current_url'], '?') === false) {
                                        $current_url = $_W['current_url'] . "?";
                                    }
                                    ?>
                                var api_url = "{!!  $current_url !!}";

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
