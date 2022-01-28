layui.define(['mobile', 'upload', 'layer', 'jquery', 'layim'], function (exports) {


    var $ = layui.$,
        device = layui.device(),
        layer = layui.layer,
        upload = layui.upload,
        element = layui.element
        , mobile = layui.mobile
        , layim = mobile.layim;

    console.log(layim);
    var mhcms_im_mobile = {

        connect: function ($im_config) {
            var $this = this;
            this.im_config = $im_config;
            this.init_mhcms_im();

            this.socket.onopen = function () {
                $this.send_data.msg_type = 'mhcms_reg';
                $this.send_data.auth_str = auth_str;
                $this.register($this.send_data);
            };

            this.socket.onmessage = function (res) {

                var res_msg = JSON.parse(res.data);
                console.log(res_msg);
                if (res_msg.msg_type === 'msg_single') {
                    layim.getMessage(res_msg);
                }

                if (res_msg.msg_type === 'mhcms_reg') {
                    this.heart_timer = setInterval(function () {
                        $this.check_heart_beat();
                    } , 5000);
                }
                if (res_msg.msg_type === 'mhcms_toast') {
                    layer.msg(res_msg.message)
                }
            };

            this.socket.onerror = function (e) {
                console.log("error", e);
            };
            this.socket.onclose = function (e) {
                console.log("onclose", e);
                clearInterval(this.heart_timer);
                console.log('您已经离线');
            }
        },
        check_heart_beat : function(){
            this.talk([] , 'mhcms_heart_beat')
        },
        talk : function($body , $msg_type){
            var send_data = this.send_data;
            send_data.msg_type = $msg_type;
            send_data.query = $body;
            this.socket.send(JSON.stringify(send_data));
        },
        register: function (send_data) {
            this.socket.send(JSON.stringify(send_data));
        },
        send: function ($body) {
            this.talk($body , 'msg_single');
        },

        init_mhcms_im: function () {
            var $this = this;
            //基础配置
            layim.config($this.im_config);
            layim.on('sendMessage', function (res) {
                $this.send({
                    from: res.mine,
                    to: res.to
                });
            });

            layim.on('newFriend', function () {
                //弹出面板
                require(['mhcms'] , function (mhcms) {
                    var index = layer.load();
                    $.get($this.im_config.add_contact_api, function (data) { //获取网址内容 把内容放进data
                        layer.close(index); //关闭加载层
                        if (data.code == 0) {
                            layer.msg(data.msg);
                        } else {
                            layer.open({
                                closeBtn : 0,
                                type: 1,
                                fix: true, //固定位置
                                title: false,
                                shadeClose: true, //点击背景关闭
                                shade: 0.4,  //透明度
                                content: data,
                                zIndex : 999 ,
                                area: [ "100%", "100%"],
                                success: function (layero, index) {

                                }
                            });
                        }
                    }, 'json');
                });


            });
        },


        open_private_chat: function ($config) {
            $config.type = 'friend';
            this.open_chat($config)
        },
        open_group_chat: function ($config) {
            $config.type = 'group';
            $config.avatar = 'http://tp1.sinaimg.cn/5619439268/180/40030060651/1'; //头像

            //todo: register user to the group
            this.send_data.msg_type = 3;
            this.send_data.group_id = $config.id;
            //delete  this.send_data.auth_str;
            //delete  this.send_data.query;
            this.socket.send(JSON.stringify(this.send_data));

            this.open_chat($config);
        },
        open_chat($config) {
            layim.chat($config);
        }

    };



    exports('mhcms_im_mobile', mhcms_im_mobile);
});