@extends("themes." . $_W['theme'] . '.public.base_layout')


@section('footer_js')

    <style>
        .tabulator-row{
            border-top: 1px solid #aaa;;
        }
        .tabulator-row .tabulator-frozen , .tabulator .tabulator-header .tabulator-frozen.tabulator-frozen-left{
            border-right: 1px solid #aaa!important;
        }
        .tabulator-row .tabulator-frozen  {

            border-bottom: 1px solid #aaa;
        }

        .tabulator-row.bra-row-99{background:#f9fefa}
        .tabulator-row.bra-row-1{background:#ffffef}
        .tabulator-row.bra-row-2{background:#ffffef}
        .tabulator-row.bra-row-3{background:#efffff}
        .tabulator-row.bra-row-4{background:#f6efff}
    </style>
    <script>
        //define table

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
                    }, frozen: true  , width : 30
                });
                var cols = [<?php echo html($cols)?>];
                if (tpl != "<div></div>") {
                    cols = extra.concat(cols[0]);
                } else {
                    // cols = cols[0];
                    cols = extra.concat(cols[0]);
                }


                bra_page.table = new Tabulator("#bra-table-idx", {
                    maxHeight: $("#bra_app").height() - $(".sub-menu-top").height() - $(".bra-body-header").height() - $("#mhcms_admin_main_app").height() - 40,
                    minWidth: 800,
                    ajaxParams: {bra_action: "post"},
                    width: 2000,
                    ajaxURL: "{!! $_W['current_url'] !!}", //ajax URL
                    layout: "fitData", //fit columns to width of table (optional)
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
                    rowFormatter: function (row) {
                        if (row.getData().old_data && row.getData().old_data.status) {
                            row.getElement().classList.add("bra-row-" + row.getData().old_data.status); //mark rows with age greater than or equal to 18 as successful;
                        }


                        var sub_cols = [<?php echo json_encode($sub_cols)?>];

                        //create and style holder elements
                        var holderEl = document.createElement("div");
                        var tableEl = document.createElement("div");

                        holderEl.style.boxSizing = "border-box";
                        holderEl.style.padding = "10px 30px 10px 10px";
                        holderEl.style.marginTop = "-1px";
                        holderEl.style.borderBotom = "3px solid #333";
                        holderEl.style.borderTop = "1px solid #aaa";

                        tableEl.style.border = "1px solid #333";

                        holderEl.appendChild(tableEl);

                        row.getElement().appendChild(holderEl);

                        var subTable = new Tabulator(tableEl, {
                            layout:"fitColumns",
                            data:row.getData().sub_datas,
                            columns:sub_cols[0]
                        })

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
                        var tpl = `{!! $editor_url !!}`;
                        var template = Handlebars.compile(tpl);
                        var $editor_url = template({d: cell_row._row.data});
                        console.log($editor_url);
                        var params = {};
                        params[cell._cell.column.field] = cell._cell.value;
                        $.post($editor_url, {
                            data: params,
                            _token: "{{ csrf_token() }}"
                        }, function (data) {
                            if (data.code != 1) {
                                layer.msg(data.msg);
                            }
                            bra_page.table.setPage(bra_page.table.getPage());
                        }, 'json');
                    },
                });


                /*头工具栏事件*/
                $("#bra-table-ctrl  a[data-mini='bottom_action']").on('click', function (obj) {
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
                            ids: ids,
                            _token: "{{ csrf_token() }}"
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

                                return bra_page.table.setData("{!! $_W['current_url'] !!}", {bra_action: "post"});
                            case 'download':
                                var _btn_action = bra_form.trigger.data('action');
                                @yield('extra_acts')

                                    for (var key in fields) {
                                    if (option_keys.indexOf(key) === -1) {
                                        option_keys.push(key);
                                    }
                                }
                                /*检查 已经检索过的索引是否 被取消了*/
                                for (var key in option_keys) {
                                    var val = option_keys[key];
                                    if (!fields[val]) {
                                        delete fields[val];
                                    }
                                }
                                fields._bra_download = 1;
                                fields._btn_action = _btn_action;
                                    <?php
                                    $current_url = $_W['current_url'];
                                    if (strpos($_W['current_url'], '?') === false) {
                                        $current_url = $current_url . "?";
                                    } else {
                                        $current_url = $current_url . "&";
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


    @yield('extra_styles')
@endsection
