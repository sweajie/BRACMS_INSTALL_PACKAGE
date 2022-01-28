// 布拉组件 个个超神
define("bra_slider", ["braui"], function (braui) {
    return {
        config: {
            el: '',
            min: 0,
            max: 100,
            value: 0,
            step: 1,
            input: true,
            range: false,
            callback: {}
        },
        init_config: function (config) {
            this.config = $.extend(this.config, config);
            this.bra_slider = $(this.config.el);
            this.total_length = parseInt(this.bra_slider.find('.bra-slider').width());
            this.left_ctrl = this.bra_slider.find('.bra-slider-left-ctrl');
            this.slider_text = this.bra_slider.find('.bra-slider-text');
            this.bra_slider_line = this.bra_slider.find('.bra-slider-line');
            if (this.config.range) {
                this.right_ctrl = this.bra_slider.find('.bra-slider-right-ctrl');
                this.right_ctrl.show();
            }
            this.set_val(this.config.range);

            return this.config;
        },
        init_slider: function (config) {
            this.init_config(config);
            this.init_left_ctrl();
            if (this.config.range) {
                this.init_right_ctrl();
            }
        },

        set_val: function (val) {

            if (this.config.range) {
                if (isNaN(this.config.value)) {
                    var point = this.config.value.split('-');
                    this.left_ctrl_dis = parseFloat(point[0]) / this.config.max * 100;
                    this.right_ctrl_dis = parseFloat(point[1]) / this.config.max * 100;
                }else{
                    this.left_ctrl_dis = this.right_ctrl_dis = 0;
                }

                this.right_ctrl = this.bra_slider.find('.bra-slider-right-ctrl');
                this.right_ctrl.show();
            } else {
                this.left_ctrl_dis = this.config.value / this.config.max * 100;
            }

            this.set_slider_res();
        },

        set_slider_text : function(text){
            this.slider_text.html(text);
        },
        init_right_ctrl: function () {

            var root = this;
            var startLeft = 0, startX = 0;
            this.right_ctrl.on('touchstart', function (e) {
                //记录起点
                startLeft = parseInt(root.right_ctrl.css('left'));
                startX = e.originalEvent.changedTouches[0].clientX;

            }).on('touchmove', function (e) {
                //计算左边距离
                var left_dist = startLeft + e.originalEvent.changedTouches[0].clientX - startX;

                left_dist = left_dist < 0 ? 0 : left_dist > root.total_length ? root.total_length : left_dist; // 左边不能小于0 不能大于右边总长

                root.right_ctrl_dis = parseInt(left_dist / root.total_length * 100);

                if (root.config.range && root.right_ctrl_dis < root.left_ctrl_dis) {
                    root.right_ctrl_dis = root.left_ctrl_dis;
                }

                root.set_slider_res();
                e.preventDefault();
            });


        },

        init_left_ctrl: function () {

            var root = this;
            var startLeft = 0, startX = 0;
            this.left_ctrl.on('touchstart', function (e) {
                //记录起点
                startLeft = parseInt(root.left_ctrl.css('left'));
                startX = e.originalEvent.changedTouches[0].clientX;

            }).on('touchmove', function (e) {
                //计算左边距离
                var left_dist = startLeft + e.originalEvent.changedTouches[0].clientX - startX;

                left_dist = left_dist < 0 ? 0 : left_dist > root.total_length ? root.total_length : left_dist; // 左边不能小于0 不能大于右边总长

                root.left_ctrl_dis = parseInt(left_dist / root.total_length * 100);

                if (root.config.range && root.left_ctrl_dis > root.right_ctrl_dis) {
                    root.left_ctrl_dis = root.right_ctrl_dis;
                }

                root.set_slider_res();
                e.preventDefault();
            });


        },


        set_slider_res: function () {
            //开启区间
            this.left_ctrl.css('left', this.left_ctrl_dis + '%');
            if (this.config.range) {

                this.right_ctrl.css('left', this.right_ctrl_dis + '%');
                this.bra_slider_line.css('width', (this.right_ctrl_dis - this.left_ctrl_dis) + '%');
                this.bra_slider_line.css('left', this.left_ctrl_dis + '%');
            } else {
                this.bra_slider_line.css('width', this.left_ctrl_dis + '%');
                this.bra_slider_line.css('left', 0);
            }

            if (typeof this.config.callback == 'function') {
                this.config.callback(this , this.left_ctrl_dis, (this.left_ctrl_dis * this.config.max / 100).toFixed(0), this.right_ctrl_dis, (this.right_ctrl_dis * this.config.max / 100).toFixed(0));
            }
        }


    };
});