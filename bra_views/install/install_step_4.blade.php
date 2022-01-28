@extends('install.base_layout')

@section('top_header')
    @include('install.style')
@endsection

@section('main')
<div class="install-box">
    <fieldset class="layui-elem-field layui-field-title">
        <legend>正在安装程序</legend>
    </fieldset>

    <table class="layui-table">
        <tr><td><span id="">如果下载过程中,有文件无法下载或者卡住:请关闭WAF防火墙</span></td></tr>
        <tr>
            <td style="white-space: nowrap;text-overflow: ellipsis">
                <span id="tips">正在下载文件 ， 请稍后</span>
                <span id="current_file" style="display:none;"></span>
                <div>

                    <progress style="width:100%" class="progress is-size-1 is-danger" id="file_process" value="0" max="{{count($files_to_update)}}">1</progress>
                </div>

            </td>
        </tr>
    </table>
    <div class="step-btns">
        <a href="javascript:history.go(-1);" class="layui-btn layui-btn-primary layui-btn-big fl">返回上一步</a>
        <button type="submit" onclick="check_status()" class="layui-btn layui-btn-big layui-btn-normal fr" lay-submit="" lay-filter="formSubmit">下一步</button>

        <button id="retry" onclick="down_load()" class="layui-btn layui-btn-big layui-btn-normal fr" lay-submit="" lay-filter="formSubmit">重试</button>
    </div>



</div>
@endsection

@section('footer_js')

    <script>
        var next_step_url = "/?step=5";
        var files = <?php echo json_encode($files_to_update) ?>;
        var total = files.length;
        var down_load_api = "/?step=4&a=download_file";
        // var files_length = files.length;
        var download_num = 0;
        function down_load(){
            console.log('down_load');

            layui.use(['layer'] , function () {
                var $ = layui.$ , layer = layui.layer;

                $("#current_file").html(  " 进度: "+ download_num + "/" + files.length + " : " +   files[0] );

                $("#file_process").val(total - files.length);

                $.get(down_load_api , {
                    file_path: files[0] , module : 'system'
                } , function (data) {
                    console.log(data);
                    if(data.code!=1){
                        down_load();
                        console.log(data);
                        $("#tips").html(data.msg);
                        $('#retry').show();
                        return;
                    }
                    $('#retry').hide();
                    files.splice(0 ,1);
                    download_num++;
                    if(files.length> 0){
                        setTimeout(function () {
                            down_load();
                        } , 100)

                    }else{
                        layer.msg("您好所有文件已经下载完成！请点击下一步继续！");
                        $("#current_file").html("");
                        $("#tips").html("文件下载完成");
                        check_status();
                    }
                },'json').fail(function() {
                    layer.msg('文件下载出错,尝试从新下载!', {
                        icon: 2,
                        time: 1000 //2秒关闭（如果不配置，默认是3秒）
                    }, function(){
                        //do something
                        if(files.length> 0){
                            setTimeout(function () {
                                down_load();
                            } , 100)
                        }
                    });


                });
            });

        }

        function down_load_back(){
            console.log('down_load_back');

            layui.use(['layer'] , function () {
                var $ = layui.$ , layer = layui.layer;

                $("#current_file").html(  " 进度: "+ download_num + "/" + files.length + " : " +   files[0] );
                $("#file_process").val(total - files.length);

                $.get(down_load_api , {
                    file_path: files[files.length-1] , module : 'system'
                } , function (data) {
                    console.log(data);
                    if(data.code!=1){
                        down_load_back();
                        console.log(data);
                        $("#tips").html(data.msg);
                        $('#retry').show();
                        return;
                    }
                    $('#retry').hide();
                    files.splice(-1 ,1);
                    download_num++;
                    if(files.length> 0){
                        down_load_back();
                    }else{
                        layer.msg("您好所有文件已经下载完成！请点击下一步继续！");
                        $("#current_file").html("");
                        $("#tips").html("文件下载完成");
                        check_status();
                    }
                },'json').fail(function() {
                    layer.msg('文件下载出错,尝试从新下载!', {
                        icon: 2,
                        time: 1000 //2秒关闭（如果不配置，默认是3秒）
                    }, function(){
                        //do something
                        if(files.length> 0){
                            down_load_back();
                        }
                    });


                });
            });

        }


        require(['layui', 'jquery'], function () {

            layui.use(['layer'] , function () {

                var $ = layui.$ , layer = layui.layer;

                $(document).ready(function () {
                    if(files.length> 0){
                        down_load();
                        down_load_back();
                    }else{
                        $("#tips").html("文件更新完成");
                        layer.msg("文件更新完成");
                    }
                });
            })
        });

        function check_status() {
            if(files.length == 0){
                window.location.href=next_step_url;
            }else{
                layer.msg("请等待文件更新完成，再进入下一步");
            }
        }

    </script>

@endsection
