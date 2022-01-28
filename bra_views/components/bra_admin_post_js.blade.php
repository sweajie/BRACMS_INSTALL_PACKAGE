<script>
    require(['layer', 'jquery', 'bra_form'], function (layer, $, bra_form) {

        bra_form.listen({
            url: "{!! $_W['current_url']  !!} ",
            before_submit: function (fields, cb) {
                $('.bra-form').toggleClass('is-loading')
                fields['bra_action'] = 'post';
                cb(fields);
            },
            success: function (data, form) {

                layer.msg(data.msg);
            },
            error: function (data) {
                console.log(data);
                layer.msg(data.msg);
            },
            finish: function (data, form) {
                $('.bra-form').toggleClass('is-loading')
            }
        });
    });
</script>
