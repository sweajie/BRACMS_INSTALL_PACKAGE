/* bra_form for jquery */
;!function (window, bra_form , $ ,undefined) {
    "use strict";
    if (typeof define === 'function' && define.amd) {
        define("bra_form", ["jquery"], function ($) {
            "use strict";
            $.extend({
                bra_form: bra_form
            });
            return bra_form;
        });
    } else {
        $.extend({
            bra_form: bra_form
        });
    }
}(window , function () {
    if(typeof jQuery === 'undefined'){
        throw new Error("BraForm Need Jquery");
    }
    return {
        config: {
            el: '.bra-form',
            id: false,
            url: false,
            verify: false,
            before_submit: false,
            success: false,
            error: false,
            error_class: 'is-danger',
            finish: false,
            filter: '',
            form_elem: false //当前所在表单域
            , fields: {} ,
            fail : function (resp) {
                console.log(resp)
                layer.msg('服务器错误!');
            }
        },
        errors: [],

        init_config: function (opt) {
            var form_filter;
            this.config = $.extend({}, this.config, opt);

            if(!this.form_elem){
                if (this.config.id) {
                    form_filter = this.config.el + "[bra-id='" + this.config.id + "']";
                } else {
                    form_filter = this.config.el;
                }
                this.form_elem = $(form_filter);
            }
            return this;
        },

        /**
         * 获取表单值
         * @returns {string}
         */
        getValue: function () {
            var nameIndex = {} //数组 name 索引
                , field = {}
                , fieldElems = this.form_elem.find('input,select,textarea'); //获取所有表单域

            $.each(fieldElems, function (_, item) {
                item.name = (item.name || '').replace(/^\s*|\s*&/, '');

                if (!item.name) return;

                //用于支持数组 name
                if (/^.*\[\]$/.test(item.name)) {
                    var key = item.name.match(/^(.*)\[\]$/g)[0];
                    nameIndex[key] = nameIndex[key] | 0;
                    item.name = item.name.replace(/^(.*)\[\]$/, '$1[' + (nameIndex[key]++) + ']');
                }

                if (/^checkbox|radio$/.test(item.type) && !item.checked) return;
                field[item.name] = item.value;
            });

            return field;
        },

        /***
         * @param e event src
         * @param obj
         * @returns {[]}
         */
        submit: function (e , obj) {

            var that = this, errors = [], form_fields;
            form_fields = this.form_elem.find('*[bra-verify]'); //获取需要校验的元素

            this.config.filter = obj.attr('bra-filter');
            if(this.config.filter != 'reset'){
                e.preventDefault();
            }
            //获取当前表单值
            that.fields = this.getValue();

            if (this.config.verify) {
                var verify = this.config.verify.configs;
                //开始校验
                $.each(form_fields, function (_, item) {

                    // $(item).removeClass('is-danger');

                    var othis = $(this), vers = othis.attr('bra-verify').split('|'), value = othis.val();
                    othis.removeClass(that.config.error_class); //移除警示样式
                    //遍历元素绑定的验证规则
                    $.each(vers, function (_, thisVer) {
                        var is_error //是否匹配错误规则
                            , errorText = '' //错误提示文本
                            , isFn = typeof verify[thisVer] === 'function';

                        //匹配验证规则
                        if (verify[thisVer]) {
                            is_error = isFn ? errorText = verify[thisVer](value, item) : !verify[thisVer][0].test(value);

                            if (is_error) {
                                errorText = errorText || verify[thisVer][1];
                                //trigger error event

                                if (typeof that.config.error === 'function') {
                                    othis.addClass(that.config.error_class);
                                    that.config.error({
                                        form_name: $(item).attr('name'),
                                        msg: errorText,
                                        val: value
                                    });
                                }

                            }

                        }

                        if (is_error) {
                            errors.push({
                                form_name: $(item).attr('name'),
                                msg: errorText,
                                val: value
                            });
                        }
                    });

                });
            }

            if (errors.length > 0) {
                return that.errors = errors;
            } else {
                that.errors = [];
            }

            if (typeof this.config.before_submit === 'function') {
                this.config.before_submit(this.fields, function (before_data) {
                    before_data = before_data ? before_data : that.fields;
                    that.bra_submit(before_data);
                });
            } else {
                this.bra_submit(this.fields)
            }
        },

        bra_submit: function (params) {
            var $this = this;
            $.post(this.config.url, params, function (ret_data) {
                if (ret_data.code === 1) {//服务器处理成功
                    if (typeof $this.config.success === 'function') {
                        $this.config.success(ret_data, $this);
                    }
                } else {
                    if (typeof $this.config.error === 'function') {
                        $this.config.error(ret_data);
                    }
                }
            }, 'json').fail(function (resp) {
                if (typeof $this.config.fail === 'function') {
                    $this.config.fail(resp , $this);
                }
            }).always(function (ret_data) {
                if (typeof $this.config.finish === 'function') {
                    $this.config.finish(ret_data , $this);
                }
            });
        },

        /**
         * 传统JS使用
         * @param config
         */
        listen: function (config) {
            var that = this, filter  = "[bra-submit]", form_filter;
            this.init_config(config);

            if (this.config.id) {
                form_filter = this.config.el + "[bra-id='" + this.config.id + "']";
            } else {
                form_filter = this.config.el;
            }
            $(document).on('click', filter, function (e) {
                that.form_elem = $(form_filter);
                that.trigger = $(this);
                that.submit(e , that.trigger)
            });
        }
    };
}() , jQuery);
