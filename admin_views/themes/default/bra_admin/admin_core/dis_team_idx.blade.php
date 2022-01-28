@extends("themes." . $_W['theme'] . '.public.base_layout')

@section('table_form')

@endsection

@section('mhcms-header')
    自定义字段选项
@endsection

@section('main')

    @verbatim
    <div id="bra_cms_app">
        <span class="bra-btn" :class="{'is-active' :level == 1 }" @click="load_lists(1)">一级</span>
        <span class="bra-btn" :class="{'is-active' :level == 2 }" @click="load_lists(2)">二级</span>
        <div>
            <table class="table is-bordered">
                <thead>
                <th>ID</th>
                <th>姓名</th>
                <th>手机</th>
                <th>等级</th>
                </thead>

                <tr v-for="item in items">
                    <td>{{item.id}}</td>
                    <td>{{item.nickname}}</td>
                    <td>{{item.mobile}}</td>
                    <td>
                        <span>{{item.dis_level_id}}</span>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    @endverbatim

@endsection
@section('footer_js')

    <script>
        var $action = "{!! $_W['current_url'] !!}";
        require(['Vue', 'jquery'], function (Vue, $) {
            new Vue({
                el: "#bra_cms_app",
                data: {
                    items: [],
                    level: 1
                },
                created: function () {
                    this.load_lists(1);
                },
                methods: {
                    load_lists: function (level, init) {

                        this.level = level;
                        var root = this;
                        $.post($action, {
                            level: level ,
                            _token : "{{csrf_token()}}"
                        }, function (data) {

                            root.items = data;

                        }, 'json');

                    }
                }
            });
        });
    </script>
@endsection
