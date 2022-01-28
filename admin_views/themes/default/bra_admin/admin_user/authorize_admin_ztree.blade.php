@extends("themes." . $_W['theme'] . '.public.base_layout')

@section('main')
    <link rel="stylesheet" href="/statics/packs/ztree/css/zTreeStyle/zTreeStyle.css" type="text/css">

    <div class="content_wrap">
        <div class="zTreeDemoBackground left">
            <ul id="treeDemo" class="ztree"></ul>
        </div>

    </div>
@endsection


@section('footer_js')

    <SCRIPT type="text/javascript">

        var setting = {
            check: {
                enable: true
            },
            key: {
                name: 'menu_name',
                title: "menu_name"
            },
            data: {
                simpleData: {
                    idKey: "id",
                    pIdKey: "parent_id",
                    enable: false
                }
            },
            callback: {
                onCheck: onCheck
            }
        };

        function onCheck(e, treeId, treeNode) {
            console.log(treeNode);
            var treeObj = $.fn.zTree.getZTreeObj("treeDemo"),
                nodes = treeObj.getCheckedNodes(true),
                v = "";
            for (var i = 0; i < nodes.length; i++) {
                v += nodes[i].id + ",";
            }
            var url = "{!!   $_W['current_url'] !!}";
            $.post(url, {
                menu_ids: v ,
                _token : "{{csrf_token()}}" ,
                bra_action : 'post'
            }, function (data) {
                layer.msg(data.msg);
            }, 'json');
        }

        var zNodes = <?php echo json_encode($menus);?>;


        require(['jquery.ztree'], function (ztree) {
            $(document).ready(function () {
                $.fn.zTree.init($("#treeDemo"), setting, zNodes);
            });
        });

    </SCRIPT>
@endsection
