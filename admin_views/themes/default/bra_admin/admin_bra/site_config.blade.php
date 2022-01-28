@extends("themes." . $_W['theme'] . '.public.base_layout')

@section('main')
    <?php global $_W; ?>
    <div class="container">
        <form class="bra-form">
            @csrf

            <div class="layui-tab layui-tab-card">
                <ul class="layui-tab-title">
                    <li class="layui-this" data-tab="网站设置">网站设置</li>
                    <li class="item " data-tab="security">安全设置</li>
                    <li class="item " data-tab="map">地图配置</li>
                    <li class="item " data-tab="func">功能设置</li>
                    <li class="item " data-tab="siteinfo">网站信息</li>
                </ul>
                <div class="layui-tab-content has-bg-white">
                    <!--web-->
                    <div class="layui-tab-item layui-show" id="网站设置">
                        <div class="field has-addons is-grouped field-body">

                            <div class="field has-addons">
                                <div class="control">
                                    <div class="bra-btn">是否关闭</div>
                                </div>
                                <div class="control">
                                    <label class="bra-input">
                                        <input type="checkbox" name="close_site" value="1" @if(isset($config['close_site']) &&
                                   $config['close_site']==1) checked @endif>
                                    </label>
                                </div>
                            </div>

                            <div class="field has-addons">
                                <div class="control">
                                    <div class="bra-btn">关闭原因</div>
                                </div>
                                <div class="control">
                                    <input type="text" name="close_site_tips" placeholder="关闭网站用户提示语" class="bra-input"
                                           value="{{$config['close_site_tips'] ?? '网站维护中'}}"/>
                                </div>
                            </div>

                            <div class="field has-addons">
                                <div class="control">
                                    <div class="bra-btn">站点名字</div>
                                </div>
                                <div class="bra-input-block">
                                    <input type="text" name="site_name" required value="{{$config['site_name']??''}}"
                                           placeholder="SEO地名" autocomplete="off" class="bra-input">
                                </div>
                            </div>

                            <div class="field has-addons">
                                <div class="control">
                                    <div class="bra-btn">浏览基数</div>
                                </div>
                                <div class="control">
                                    <input type="text" name="hit[hit_base]" value="{{$config['hit']['hit_base']??'50'}}"
                                           placeholder="点击基数" autocomplete="off" class="bra-input">
                                </div>
                                <div class="control">
                                    <div class="bra-btn is-static">信息被浏览会增加一个数据范围为1 - 此数值点击量</div>
                                </div>
                            </div>
                        </div>

                        <div class="field has-addons">
                            <div class="control">
                                <div class="bra-btn">统计代码</div>
                            </div>
                            <div class="control is-expanded">
                            <textarea name="tongji" placeholder="统计代码"
                                      class="bra-textarea">{{$config['tongji'] ??""}}</textarea>
                            </div>
                        </div>

                        <div class="field has-addons">
                            <div class="control">
                                <div class="bra-btn">{{lang('Logo上传')}}</div>
                            </div>
                            <div class="control is-expanded">
                                <?php
                                use Bra\core\objects\BraField;$field_config = [
                                    'data_source_type' => '',
                                    'data_source_config' => "jpg,gif,png,jpeg",
                                    'form_name' => 'logo',
                                    'slug' => 'Logo上传',
                                    'field_name' => 'logo',
                                    'length' => 1,
                                    'mode' => 'layui_single_upload',
                                    'field_type_name' => 'upload',
                                    'default_value' => isset($config['logo']) ? $config['logo'] : "",
                                    'form_id' => 'logo',
                                    'class_name' => 'layui-select form-control',
                                    'pk_key' => 'field_type_name',
                                    'name_key' => 'field_type_name_desc',
                                    'parentid_key' => '',
                                    'form_group' => '',
                                    'primary_option' => ''
                                ];
                                echo (new BraField($field_config))->bra_form->out_put_form();
                                ?>
                            </div>
                        </div>

                        <div class="field has-addons">
                            <div class="control">
                                <div class="bra-btn">{{lang('分销海报')}}</div>
                            </div>
                            <div class="control is-expanded">
                                <?php
                                $field_config = [
                                    'data_source_type' => '',
                                    'data_source_config' => "jpg,gif,png,jpeg",
                                    'form_name' => 'poster',
                                    'slug' => '海报上传',
                                    'field_name' => 'poster',
                                    'length' => 1,
                                    'mode' => 'layui_single_upload',
                                    'field_type_name' => 'upload',
                                    'default_value' => isset($config['poster']) ? $config['poster'] : "",
                                    'form_id' => 'poster',
                                    'class_name' => 'layui-select form-control',
                                    'pk_key' => 'field_type_name',
                                    'name_key' => 'field_type_name_desc',
                                    'parentid_key' => '',
                                    'form_group' => '',
                                    'primary_option' => ''
                                ];
                                $base = [];
                                echo (new BraField($field_config))->bra_form->out_put_form();
                                ?>
                            </div>
                        </div>

                        <div class="field has-addons">
                            <div class="control">
                                <div class="bra-btn">{{lang('默认主题')}}</div>
                            </div>

                            <div class="control is-expanded">
                                <?php
                                $field_config = [
                                    'data_source_type' => 'theme',
                                    'data_source_config' => "",
                                    'form_name' => 'theme',
                                    'field_name' => 'theme',
                                    'mode' => 'theme_selector',
                                    'field_type_name' => 'bra_theme',
                                    'default_value' => isset($config['theme']) && $config['theme'] ? $config['theme'] : 'default',
                                    'form_id' => 'field_type_name',
                                    'class_name' => ' form-control',
                                    'pk_key' => 'theme_dir',
                                    'name_key' => 'theme_name',
                                    'parentid_key' => '',
                                    'form_property' => '  required ',
                                    'primary_option' => 'Please,Select',
                                    'form_group' => '',
                                    'module' => 'bra'
                                ];
                                echo (new BraField($field_config))->bra_form->out_put_form();                        ?>
                            </div>

                        </div>

                    </div>

                    <!--security-->
                    <div class="layui-tab-item" data-tab="security">

                        <div class="field is-grouped field-body">
                            <div class="field has-addons">
                                <div class="control">
                                    <div class="bra-btn">{{lang('限制后台访问域名')}}</div>
                                </div>
                                <div class="control">
                                    <label class="bra-input">
                                        <input type="checkbox" name="limit_admin_domain" value="1" @if(isset($config['limit_admin_domain']) && $config['limit_admin_domain'] ==1) checked @endif>
                                    </label>
                                </div>
                            </div>

                            <div class="field has-addons">
                                <div class="control">
                                    <div class="bra-btn">管理员编号</div>
                                </div>
                                <div class="control">
                                    <input type="text" name="admin_id" value="{{$config['admin_id'] ??''}}"
                                           placeholder="管理员admin_id" autocomplete="off" class="bra-input">
                                </div>
                            </div>

                            <div class="field has-addons">
                                <div class="control">
                                    <div class="bra-btn">缓存后缀</div>
                                </div>
                                <div class="control">
                                    <input type="text" name="bra_suffix" value="{{$config['bra_suffix'] ??'0'}}"
                                           placeholder="bra_suffix" autocomplete="off" class="bra-input">
                                </div>
                            </div>
                        </div>

                        <div class="field has-addons">
                            <div class="control">
                                <div class="bra-btn">{{lang('敏感词过滤 逗号（,）隔开')}}</div>
                            </div>
                            <div class="control is-expanded">
                                <textarea name="bad_words" placeholder="敏感词过滤" class="bra-textarea">{{$config['bad_words']??""}}</textarea>
                            </div>
                        </div>

                        <div class="field has-addons">
                            <div class="control">
                                <div class="bra-btn">{{lang('后台访问IP 白名单逗号（,）隔开')}}</div>
                            </div>
                            <div class="control is-expanded">
                                <textarea name="white_ip" placeholder="后台访问IP ," class="bra-textarea">{{$config['white_ip'] ??""}}</textarea>
                            </div>
                        </div>

                        <div class="field has-addons">
                            <div class="control">
                                <div class="bra-btn">{{lang('IP 黑名单逗号（,）隔开')}}</div>
                            </div>
                            <div class="control is-expanded">
                                <textarea name="bad_ip" placeholder="IP 黑名单中前后台都不能访问" class="bra-textarea">{{$config['bad_ip']??''}}</textarea>
                            </div>
                        </div>

                        <div class="field has-addons">
                            <div class="control">
                                <div class="bra-btn">{{lang('敏感词过滤替代字符')}}</div>
                            </div>
                            <div class="control is-expanded">
                                <input type="text" name="bad_word_replace" value="{{$config['bad_word_replace']??'*'}}"
                                       placeholder="敏感词过滤替代字符" autocomplete="off" class="bra-input">
                            </div>
                        </div>

                        <div class="field has-addons">
                            <div class="control">
                                <div class="bra-btn">{{lang('后台CSS方案')}}</div>
                            </div>

                            <div class="control is-expanded">
                                <div class="bra-input">
                                    <?php
                                    $field_config = [
                                        'data_source_type' => 'file',
                                        'data_source_config' => "bra-admin-",
                                        'form_name' => 'admin_theme',
                                        'field_name' => 'admin_theme',
                                        'mode' => 'file_selector',
                                        'field_type_name' => 'bra_theme',
                                        'default_value' => isset($config['admin_theme']) && $config['admin_theme'] ? $config['admin_theme'] : 'bra-admin-default.css',
                                        'form_id' => 'icon_set',
                                        'class_name' => 'form-control',
                                        'pk_key' => 'file_name',
                                        'name_key' => 'file_name',
                                        'parentid_key' => '',
                                        'form_property' => '  required ',
                                        'primary_option' => 'Please,Select',
                                        'form_group' => '',
                                        'module' => 'bra',
                                        'model_id' => PUBLIC_ROOT . 'statics' . DS . 'css' . DS . 'admin' . DS
                                    ];
                                    $def = [];
                                    echo (new BraField($field_config))->bra_form->out_put_form();                        ?>
                                </div>
                            </div>

                        </div>
                    </div>
                    <!--Map Config-->

                    <div class="layui-tab-item" data-tab="map">

                        <div class="field has-addons">
                            <div class="control">
                                <div class="bra-btn">{{lang('腾讯地图密钥')}}</div>
                            </div>
                            <div class="control is-expanded">
                                <input type="text" name="map[tx_key]" value="{{$config['map']['tx_key']??''}}"
                                       placeholder="腾讯地图密钥"
                                       autocomplete="off" class="bra-input">
                            </div>
                        </div>
                        <div class="field has-addons">
                            <div class="control">
                                <div class="bra-btn">{{lang('百度地图密钥')}}</div>
                            </div>
                            <div class="control is-expanded">
                                <input type="text" name="map[bd_key]" value="{{$config['map']['bd_key']??''}}"
                                       placeholder="度地图密钥"
                                       autocomplete="off" class="bra-input">
                            </div>
                        </div>

                    </div>

                    <!--func-->

                    <div class="layui-tab-item" id="func">

                        <div class="field is-grouped field-body">
                            <div class="field has-addons">
                                <div class="control">
                                    <div class="bra-btn">{{lang('图片压缩')}}</div>
                                </div>
                                <div class="control">
                                    <label class="bra-input">
                                        <input type="checkbox" name="attach[compress_img]" value="1"
                                               @if(isset($config['attach']['compress_img']) && $config['attach']['compress_img']==1) checked @endif
                                               lay-skin="switch">
                                    </label>

                                </div>
                            </div>
                            <div class="field has-addons">
                                <div class="control">
                                    <div class="bra-btn">{{lang('压缩宽度')}}</div>
                                </div>
                                <div class="control">
                                    <input type="text" name="attach[width]" value="{{$config['attach']['width']??''}}"
                                           placeholder="请填写数字!" autocomplete="off" class="bra-input">
                                </div>
                            </div>

                            <div class="field has-addons">
                                <div class="control">
                                    <div class="bra-btn">{{lang('压缩高度')}}</div>
                                </div>
                                <div class="control">
                                    <input type="text" name="attach[height]" value="{{$config['attach']['height']??''}}"
                                           placeholder="压缩高度 请填写数字!" autocomplete="off" class="bra-input">
                                </div>
                            </div>
                        </div>

                        <!--后台设置-->

                        <div class="field is-grouped field-body">
                            <div class="control">
                                <div class="bra-btn is-info">{{lang('后台设置')}}</div>
                            </div>

                            <div class="field has-addons">
                                <div class="control">
                                    <div class="bra-btn">{{lang('默认首页模块')}}</div>
                                </div>
                                <div class="control">
                                    <input type="text" name="admin[index_module]" value="{{$config['admin']['index_module']??'bra_admin'}}"
                                           placeholder="默认首页模块 , 填写模块目录名" autocomplete="off" class="bra-input">
                                </div>
                            </div>
                            <div class="field has-addons">
                                <div class="control">
                                    <div class="bra-btn">{{lang('分页大小')}}</div>
                                </div>
                                <div class="control">
                                    <input type="text" name="page_size" value="{{$config['page_size']??'10'}}" placeholder="分页大小" autocomplete="off" class="bra-input">
                                </div>
                            </div>

                        </div>

                        <div class="field has-addons">

                            <div class="control">
                                <label class="bra-btn">第三方客服</label>
                            </div>
                            <div class="control">
                                <div class="bra-input-block">
                                    <input type="text" name="kefu_link" value="{{$config['kefu_link'] ??''}}"
                                           placeholder="第三方客服 直接连接" autocomplete="off" class="bra-input">
                                </div>
                            </div>

                            <div class="control">
                                <label class="bra-btn is-static">直接连接</label>
                            </div>
                        </div>

                    </div>

                    <!--网站信息-->
                    <div class="layui-tab-item" id="siteinfo">

                        <div class="field is-grouped field-body">
                            <div class="field has-addons">

                                <div class="control">
                                    <label class="bra-btn">备案号</label>
                                </div>
                                <div class="control">
                                    <input type="text" name="beian" value="{{$config['beian'] ??''}}" placeholder="备案号"
                                           autocomplete="off" class="bra-input">
                                </div>
                            </div>
                            <div class="field has-addons">

                                <div class="control">
                                    <label class="bra-btn">版权信息</label>
                                </div>
                                <div class="control">
                                    <input type="text" name="copyright" value="{{$config['copyright'] ??''}}" placeholder="版权设置"
                                           autocomplete="off" class="bra-input">
                                </div>
                            </div>

                        </div>

                        <div class="field is-grouped field-body">
                            <div class="field has-addons">

                                <div class="control">
                                    <label class="bra-btn">网站联系人</label>
                                </div>
                                <div class="control">
                                    <input type="text" name="contact" value="{{$config['contact'] ??''}}" placeholder="网站联系人"
                                           autocomplete="off" class="bra-input">
                                </div>
                            </div>
                            <div class="field has-addons">

                                <div class="control">
                                    <label class="bra-btn">联系微信号</label>
                                </div>
                                <div class="control">
                                    <input type="text" name="wxacount" value="{{$config['wxacount'] ??''}}" placeholder="网站联系人微信号"
                                           autocomplete="off" class="bra-input">
                                </div>
                            </div>

                            <div class="field has-addons">

                                <div class="control">
                                    <label class="bra-btn">联系电话</label>
                                </div>
                                <div class="control">
                                    <input type="text" name="mobile" value="{{$config['mobile'] ??''}}" placeholder="网站联系电话"
                                           autocomplete="off" class="bra-input">
                                </div>
                            </div>
                        </div>


                        <div class="field has-addons">
                            <div class="control">
                                <label class="bra-btn">联系地址</label>
                            </div>
                            <div class="control is-expanded">
                                <input type="text" name="address" value="{{$config['address'] ??''}}" placeholder="网站联系地址"
                                       autocomplete="off" class="bra-input">
                            </div>
                        </div>

                        <div class="field has-addons">
                            <div class="control">
                                <label class="bra-btn">广告语1</label>
                            </div>
                            <div class="control is-expanded">
                                <input type="text" name="add_txt1" value="{{$config['add_txt1'] ??''}}" placeholder="广告语1"
                                       autocomplete="off" class="bra-input">
                            </div>
                        </div>
                        <div class="field has-addons">
                            <div class="control">
                                <label class="bra-btn">广告语2</label>
                            </div>
                            <div class="control is-expanded">
                                <input type="text" name="add_txt2" value="{{$config['add_txt2'] ??''}}" placeholder="广告语2"
                                       autocomplete="off" class="bra-input">
                            </div>
                        </div>

                        <div class="field has-addons">
                            <div class="control">
                                <label class="bra-btn">广告语3</label>
                            </div>
                            <div class="control is-expanded">
                                <input type="text" name="add_txt3" value="{{$config['add_txt3'] ??''}}" placeholder="广告语3"
                                       autocomplete="off" class="bra-input">
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="field has-addons">
                <div class="">
                    <button type="button" bra-submit bra-filter="*" class="bra-btn is-primary" value="submit">保存配置</button>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('footer_js')
    <x-bra_admin_post_js/>
    @foreach( $_W['bra_scripts'] as $bra_script)
        {!! $bra_script !!}
    @endforeach

@endsection
