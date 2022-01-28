@extends("themes." . $_W['theme'] . '.public.base_layout')

@section('main')
    <?php global $_W; ?>
    <div class="container has-bg-white is-padding p-a" id="bra-app">
        <form class="bra-form">
            @csrf

            <div class="layui-tab layui-tab-card  is-padding p-a">
                <ul class="layui-tab-title">
                    <li class="layui-this" data-tab="网站设置">升级配置</li>
                </ul>
                <div class="layui-tab-content has-bg-white">
                    <!--web-->
                    <div class="layui-tab-item layui-show  is-padding p-a" id="网站设置">
                        <div class="field has-addons is-grouped field-body">

                            <div class="field has-addons">
                                <div class="control">
                                    <div class="bra-btn">允许</div>
                                </div>
                                <div class="control">
                                    <label class="bra-input">
                                        <input type="checkbox" name="allow_trans" value="1" @if(isset($config['allow_trans']) &&
                                   $config['allow_trans']==1) checked @endif>
                                    </label>
                                </div>
                            </div>

                            <div class="field has-addons">
                                <div class="control">
                                    <div class="bra-btn">服务器域名</div>
                                </div>
                                <div class="control is-expanded">
                                    <input type="text" name="server_url" v-model="server_url" placeholder="服务器域名" class="bra-input" />
                                </div>
                            </div>


                            <div class="field has-addons">
                                <div class="control">
                                    <div class="bra-btn">应用名称</div>
                                </div>
                                <div class="control">
                                    <input type="text" name="module_sign" v-model="module_sign"  placeholder="应用名称" autocomplete="off" class="bra-input">
                                </div>
                            </div>


                            <div class="field has-addons">
                                <div class="control">
                                    <div class="bra-btn">授权密码</div>
                                </div>
                                <div class="control">
                                    <input type="text" name="password" v-model="password"  placeholder="授权密码" autocomplete="off" class="bra-input">
                                </div>
                            </div>

                            <div class="field has-addons">
                                <div class="">
                                    <button type="button" bra-submit bra-filter="*" class="bra-btn is-primary" value="submit">立刻检测</button>
                                </div>
                            </div>
                            <div class="field has-addons" v-if="models.length > 0">
                                <div class="">
                                    <button type="button" @click="confirm_update" class="bra-btn is-primary" >确定升级</button>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
            </div>

        </form>

        <table class="table is-bordered">
            <tr v-for="(item , idx) in models" :class="{'has-bg-success' : item.status == 1}">
                <td>
@{{ item.table_name }}
                </td>

                <td>
<div class="bra-btn is-danger" @click="update(idx)">更新</div>
                </td>
            </tr>
        </table>
    </div>
@endsection

@section('footer_js')
    <script>
        require(['layer', 'Vue' , 'jquery', 'bra_form'], function (layer,Vue , $, bra_form) {

            new Vue({
                el :"#bra-app" ,
                data : {
                    server_url : '' ,
                    password : '' ,
                    module_sign : '' ,
                    models : []
                } ,
                methods : {
                    confirm_update : function(){
                        for(let key in this.models){
                            this.update(key);
                        }

                        this.update_menu();
                        this.update_roles();

                    } ,
                    update : function (item) {
                        var root = this;

                        $.post('{!! $_W['current_url'] !!}' , {
                            action : 'sync_model' ,
                            server_url : this.server_url,
                            password : this.password,
                            module_sign : this.module_sign,
                            bra_action : 'post' ,
                            table : this.models[item].table_name ,
                            _token : "{{csrf_token()}}"
                        } , function (data) {
                            root.models[item].table_name += " ";
                            root.models[item].status = 1;
                        } );
                    } ,
                    update_menu : function () {
                        var root = this;

                        $.post('{!! $_W['current_url'] !!}' , {
                            action : 'sync_menu' ,
                            server_url : this.server_url,
                            password : this.password,
                            module_sign : this.module_sign,
                            bra_action : 'post' ,
                            _token : "{{csrf_token()}}"
                        } , function (data) {
                        } );
                    },
                    update_roles : function () {
                        var root = this;

                        $.post('{!! $_W['current_url'] !!}' , {
                            action : 'sync_roles' ,
                            server_url : this.server_url,
                            password : this.password,
                            module_sign : this.module_sign,
                            bra_action : 'post' ,
                            _token : "{{csrf_token()}}"
                        } , function (data) {
                        } );
                    }
                } ,
                mounted : function () {
                    var root = this;
                    bra_form.listen({
                        url: "{!! $_W['current_url']  !!} ",
                        before_submit: function (fields, cb) {
                            $('.bra-form').toggleClass('is-loading')
                            fields['bra_action'] = 'post';
                            cb(fields);
                        },
                        success: function (data, form) {
                            console.log(data)
                            root.models = data.data;
                        },
                        error: function (data) {
                            console.log(data);
                            layer.msg(data.msg);
                        },
                        finish: function (data, form) {
                            $('.bra-form').toggleClass('is-loading')
                        }
                    });
                }
            });
        });
    </script>

    @foreach( $_W['bra_scripts'] as $bra_script)
        {!! $bra_script !!}
    @endforeach

@endsection
