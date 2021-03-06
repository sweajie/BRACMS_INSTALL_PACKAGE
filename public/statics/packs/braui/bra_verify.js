var bra_verify = {
    configs : {
        required: [
            /[\S]+/
            , '必填项不能为空'
        ]
        , phone: [
            /^1\d{10}$/
            , '请输入正确的手机号'
        ]
        , email: [
            /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/
            , '邮箱格式不正确'
        ]
        , url: [
            /(^#)|(^http(s*):\/\/[^\s]+\.[^\s]+)/
            , '链接格式不正确'
        ]
        , number: function (value) {
            console.log(value)
            if (!value || isNaN(value)) return '只能填写数字'
        }
        , date: [
            /^(\d{4})[-\/](\d{1}|0\d{1}|1[0-2])([-\/](\d{1}|0\d{1}|[1-2][0-9]|3[0-1]))*$/
            , '日期格式不正确'
        ]
        , identity: [
            /(^\d{15}$)|(^\d{17}(x|X|\d)$)/
            , '请输入正确的身份证号'
        ]
    } ,
    set_verify : function (settings) {
        $.extend({}, this.configs, settings);
    }
};

if(typeof define === 'function'){
    define("bra_verify", ["jquery"], function ($) {
        return bra_verify;
    });
}

