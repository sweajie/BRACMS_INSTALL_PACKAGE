// 布拉组件 个个超神
define("bra_upload", ["braui"], function (braui) {
    return {

        init_bra_upload: function (token, putExtra, config) {
            var $this = this;
            var BLOCK_SIZE = 4 * 1024 * 1024;
//widget
            (function (global) {
                function widget() {
                    this.widget = {};
                }

                widget.prototype.register = function (name, component) {
                    this.widget[name] = component;
                };
                widget.prototype.add = function (name, obj) {
                    if (this.widget[name]) {
                        this.widget[name].node = obj.node;
                        return this.widget[name].init(obj);
                    }
                    return false;
                };
                global.widget = new widget();
            })(window);

//item
            (function () {
                var init = function (obj) {
                    var data = obj.data;
                    var name = data.name;
                    var size = data.size;
                    var idx = data.file_idx;
                    var parent = "";

                    var file_type = 'attach';
                    if (data.type !== "") {
                        file_type = data.type;
                    }
                    console.log(data);
                    if (data.annex_id) {
                        parent += "<div class='wraper'><a class='linkWrapper'></a>" +
                            "<input type='hidden' name='" + config.real_form_name + "' value='" + data.annex_id + "'/>" +

                            "<p class=' delete' onclick='remove_parent(this , \"li\")'>删除</p>" +
                            "  </div>";
                    } else {

                        parent += "<div class='wraper'><a class='linkWrapper'>" +
                            "" +
                            "</a>" +
                            "<p class='weui-uploader__file-content has-text-centered speed'>等待上传</p> </div>";
                    }

                    var item = document.createElement("li");
                    $(item).html(parent);

                    //edit
                    console.log(data , '-------58');
                    if (!data.file_content.annex_id) {
                        if (file_type === 'image') {
                            console.log(data.file_content.getSource());
                            var image = $(new Image()).appendTo(item);
                            var preloader = new $this.moxie.image.Image();
                            preloader.onload = function () {
                                preloader.downsize(300, 300);
                                image.prop("src", preloader.getAsDataURL());
                            };
                            preloader.load(data.file_content.getSource());
                        } else if (file_type === 'video') {
                            $("<span mini='view_video' class='type_item layui-icon layui-icon-video'></span>").appendTo(item);
                        } else if (file_type === 'audio') {
                            $("<span class='type_item layui-icon layui-icon-voice'></span>").appendTo(item);
                        } else {
                            $("<span class='type_item layui-icon layui-icon-file'></span>").appendTo(item);
                        }
                        $(item).attr('id', 'item_' + idx);
                        $(item).attr('class', 'weui-uploader__file weui-uploader__file_status');

                    } else {
                        //console.log(data);
                        $(new Image()).appendTo(item).attr('src', data.file_content.annex_url);
                        $(item).attr('class', 'weui-uploader__file');
                    }
                    obj.node.append(item);
                    return item;
                };
                widget.register("weui_upload_item", {
                    init: init
                });
            })();

//add ui
            function addUploadBoard(file, config, key, type) {
                console.log(file);
                var file_type;
                if(file.annex_id){
                    file_type = file.annex.filemime;
                }else{
                    file_type = file.type;
                }
                var count = Math.ceil(file.size / BLOCK_SIZE);
                var board = widget.add("weui_upload_item", {
                    data: {
                        type: file_type.split('/')[0],
                        num: count,
                        name: key,
                        size: file.size,
                        file_idx: file.id,
                        annex_id: file.annex_id,
                        file_content: file
                    },
                    node: $("#" + config.container)
                });
                if (file.size > 100 * 1024 * 1024 * 10) {
                    $(board).html("本实例最大上传文件1GB!");
                    return "";
                }
                return board;
            }

//upload
            require(['qiniu', 'plupload'], function (qiniu, plupload) {
                $this.moxie = plupload.moxie;
                qiniu.getUploadUrl(config, token).then(function (res) {
                    //console.log(config);
                    var uploadUrl = res;
                    var board = {};
                    var indexCount = 0;
                    var resume = false;
                    var chunk_size;
                    var blockSize;
                    var uploader = new plupload.Uploader({
                        runtimes: "html5,flash,silverlight,html4",
                        url: uploadUrl,
                        browse_button: config.browse_button, // 触发文件选择对话框的按钮，为那个元素id
                        flash_swf_url: "./js/Moxie.swf", // swf文件，当需要使用swf方式进行上传时需要配置该参数
                        silverlight_xap_url: "./js/Moxie.xap",
                        chunk_size: BLOCK_SIZE,
                        max_retries: 3,
                        multipart_params: {
                            token: token
                        },
                        init: {
                            PostInit: function () {
                                $iobj_v = $("#" + config.container + "_btn_v").parent().find('input');
                                $iobj_v.attr('accept', 'video/*');
                                $iobj_i = $("#" + config.container + "_btn").parent().find('input');
                                $iobj_i.attr('accept', 'image/*');
                                if(config.force_cam == 1){
                                    $iobj_v.attr('capture', 'camcorder');
                                    $iobj_i.attr('capture', 'camcorder');
                                }

                                console.log("upload init");
                                $("#" + config.container + "_btn_ss").on("click", function () {
                                    if (uploader.state !== 1) {
                                        uploader.stop();
                                        $(this).text("继续");
                                        console.log("取消上传 init");
                                    } else {
                                        uploader.start();
                                        $(this).text("暂停");
                                        console.log("开始上传 init");
                                    }
                                });
                                /**
                                 *
                                 */
                                for (var key in config.default_value) {
                                    var file_info = config.default_value[key];
                                    file_info.size = file_info.filesize;
                                    file_info.key = file_info.filename;
                                    addUploadBoard(file_info, config, file_info.filename, "2");
                                }
                            },
                            FilesAdded: function (up, files) {

                                resume = false;
                                chunk_size = uploader.getOption("chunk_size");
                                var id = files[0].id;
                                // 添加上传dom面板
                                board[id] = addUploadBoard(files[0], config, files[0].name, "2");
                                board[id].start = true;

                                setTimeout(function () {
                                    uploader.start();
                                }, 500);


                                // $("#" + config.container + "_btn_ss").show().click();
                            },
                            FileUploaded: function (up, file, info) {
                            }
                            ,
                            UploadComplete: function (up, files) {
                                // Called when all files are either uploaded or failed
                                console.log("[完成]");
                                $("#" + config.container + "_btn_ss").hide().text('开始上传');
                            },
                            Error: function (up, err) {
                                console.log(err.response);
                            }
                        }
                    });
                    uploader.init();
                    uploader.bind('Error', function () {
                        console.log(1234)
                    });
                    uploader.bind("BeforeUpload", function (uploader, file) {
                        var myDate = new Date();
                        var Y = myDate.getFullYear(); //获取完整的年份(4位,1970)
                        var M = myDate.getMonth(); //获取当前月份(0-11,0代表1月)
                        var D = myDate.getDate(); //获取当前日(1-31)
                        key = "upload_file/" + parseInt(config.user_id) + "/" + Y + M + D + "/" + new Date().getTime() + "_" + file.name;
                        putExtra.params["x:name"] = key.split(".")[0];
                        var id = file.id;
                        chunk_size = uploader.getOption("chunk_size");
                        var directUpload = function () {
                            var multipart_params_obj = {};
                            multipart_params_obj.token = token;
                            // filterParams 返回符合自定义变量格式的数组，每个值为也为一个数组，包含变量名及变量值
                            var customVarList = qiniu.filterParams(putExtra.params);
                            for (var i = 0; i < customVarList.length; i++) {
                                var k = customVarList[i];
                                multipart_params_obj[k[0]] = k[1];
                            }
                            multipart_params_obj.key = key;
                            uploader.setOption({
                                url: uploadUrl,
                                multipart: true,
                                multipart_params: multipart_params_obj
                            });
                        };

                        var resumeUpload = function () {
                            blockSize = chunk_size;
                            initFileInfo(file);
                            if (blockSize === 0) {
                                mkFileRequest(file);
                                uploader.stop();
                                return
                            }
                            resume = true;
                            var multipart_params_obj = {};
                            // 计算已上传的chunk数量
                            var index = Math.floor(file.loaded / chunk_size);
                            var dom_total = $(board[id])
                                .find("#totalBar")
                                .children("#totalBarColor");
                            if (board[id].start != "reusme") {
                                $(board[id])
                                    .find(".fragment-group")
                                    .addClass("is-hidden");
                            }
                            dom_total.css(
                                "width", file.percent + "%"
                            );
                            // 初始化已上传的chunk进度
                            for (var i = 0; i < index; i++) {
                                var dom_finished = $(board[id])
                                    .find(".fragment-group li")
                                    .eq(i)
                                    .find("#childBarColor");
                                dom_finished.css("width", "100%");
                            }
                            var headers = qiniu.getHeadersForChunkUpload(token);
                            uploader.setOption({
                                url: uploadUrl + "/mkblk/" + blockSize,
                                multipart: false,
                                required_features: "chunks",
                                headers: {
                                    Authorization: "UpToken " + token
                                },
                                multipart_params: multipart_params_obj
                            });
                        };
                        // 判断是否采取分片上传
                        if (
                            (uploader.runtime === "html5" || uploader.runtime === "flash") &&
                            chunk_size
                        ) {
                            if (file.size < chunk_size) {
                                directUpload();
                            } else {
                                resumeUpload();
                            }
                        } else {
                            console.log(
                                "directUpload because file.size < chunk_size || is_android_weixin_or_qq()"
                            );
                            directUpload();
                        }
                    });

                    uploader.bind("ChunkUploaded", function (up, file, info) {
                        var res = JSON.parse(info.response);
                        var leftSize = info.total - info.offset;
                        var chunk_size = uploader.getOption && uploader.getOption("chunk_size");
                        if (leftSize < chunk_size) {
                            up.setOption({
                                url: uploadUrl + "/mkblk/" + leftSize
                            });
                        }
                        up.setOption({
                            headers: {
                                Authorization: "UpToken " + token
                            }
                        });
                        // 更新本地存储状态
                        var localFileInfo = JSON.parse(localStorage.getItem(file.name)) || [];
                        localFileInfo[indexCount] = {
                            ctx: res.ctx,
                            time: new Date().getTime(),
                            offset: info.offset,
                            percent: file.percent
                        };
                        indexCount++;
                        localStorage.setItem(file.name, JSON.stringify(localFileInfo));
                    });

                    // 每个事件监听函数都会传入一些很有用的参数，
                    // 我们可以利用这些参数提供的信息来做比如更新UI，提示上传进度等操作
                    uploader.bind("UploadProgress", function (uploader, file) {
                        var id = file.id;
                        // 更新进度条进度信息;
                        var fileUploaded = file.loaded || 0;
                        var dom_total = $(board[id]).find("#totalBar").children("#totalBarColor");
                        var percent = file.percent + "%";
                        dom_total.css("width", file.percent + "%");
                        $(board[id]).find(".speed").text(percent);
                        var count = Math.ceil(file.size / uploader.getOption("chunk_size"));
                        if (file.size > chunk_size) {
                            updateChunkProgress(file, board[id], chunk_size, count);
                        }
                    });

                    uploader.bind("FileUploaded", function (uploader, file, info) {
                        var id = file.id;
                        if (resume) {
                            mkFileRequest(file)
                        } else {
                            uploadFinish(JSON.parse(info.response), file, board[id]);
                        }
                    });

                    function updateChunkProgress(file, board, chunk_size, count) {

                    }

                    function uploadFinish(res, file, board) {
                        localStorage.removeItem(name);
                        // $(board).find("#totalBar").addClass("is-hidden");
                        // $(board).find(".control-container").html("<p><strong>Hash：</strong>" +res.hash +"</p>" );

                        $(board).removeClass("weui-uploader__file_status");

                        var name = file.name;

                        var response = res;
                        console.log(response);
                        var params = {
                            url: response.key,
                            provider_id: 4,
                            filename: file.name,
                            filemime: file.type,
                            file_type: file.type.split("/")[0],
                            md5: response.hash,
                            filesize: file.origSize
                        };

                        console.log(params);
                        $.post(save_attach, params, function ($res) {
                            console.log($res);
                            //todo add hidden form control
                            var $hidden_form = '<span class="success"><input type="hidden" value="' + $res.data.annex_id + '" name="' + config.real_form_name + '"></span>';
                            $("#item_" + file.id + " .wraper").append($hidden_form);
                            $("#item_" + file.id + " .type_item").data('annex_id', $res.data.annex_id);
                        }, 'json');

                        if (res.key && res.key.match(/\.(jpg|jpeg|png|gif)$/)) {
                            console.log('this is an image!');
                        }

                        if (res.key && res.key.match(/\.(mp4|flv|avi)$/)) {
                            console.log('this is an video!');
                        }

                    }

                    function initFileInfo(file) {
                        var localFileInfo = JSON.parse(localStorage.getItem(file.name)) || [];
                        indexCount = 0;
                        var length = localFileInfo.length;
                        if (length) {
                            var clearStatus = false;
                            for (var i = 0; i < localFileInfo.length; i++) {
                                indexCount++;
                                if (isExpired(localFileInfo[i].time)) {
                                    clearStatus = true;
                                    localStorage.removeItem(file.name);
                                    break;
                                }
                            }
                            if (clearStatus) {
                                indexCount = 0;
                                return
                            }
                            file.loaded = localFileInfo[length - 1].offset;
                            var leftSize = file.size - file.loaded;
                            if (leftSize < chunk_size) {
                                blockSize = leftSize
                            }
                            file.percent = localFileInfo[length - 1].percent;
                            return
                        } else {
                            indexCount = 0
                        }
                    }

                    function mkFileRequest(file) {
                        // 调用sdk的url构建函数
                        var requestUrl = qiniu.createMkFileUrl(
                            uploadUrl,
                            file.size,
                            key,
                            putExtra
                        );
                        var ctx = [];
                        var id = file.id;
                        var local = JSON.parse(localStorage.getItem(file.name));
                        for (var i = 0; i < local.length; i++) {
                            ctx.push(local[i].ctx)
                        }
                        // 设置上传的header信息
                        var headers = qiniu.getHeadersForMkFile(token);
                        $.ajax({
                            url: requestUrl,
                            type: "POST",
                            headers: headers,
                            data: ctx.join(","),
                            success: function (res) {
                                uploadFinish(res, file, board[id]);
                            }
                        })
                    }

                    function isExpired(time) {
                        let expireAt = time + 3600 * 24 * 1000;
                        return new Date().getTime() > expireAt;
                    }

                });
            });
        },
    }
});
