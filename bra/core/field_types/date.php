<?php
// +----------------------------------------------------------------------
// | 鸣鹤CMS [ New Better  ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2017 http://www.bracms.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( 您必须获取授权才能进行使用 )
// +----------------------------------------------------------------------
// | Author: new better <1620298436@qq.com>
// +----------------------------------------------------------------------
namespace Bra\core\field_types;

use Bra\core\data_source\DataOutput;

class date extends FieldType {
    public function input_date_filter () {
        return $this->datetime_period();
    }

    public function datetime_period ($divider = " - ") {
        global $_W;
        $field = $this->field;
        $append = '';
        $sec_date = $base[$field->data_source_config] ?? '';
        if ($sec_date) {
            $field->default_value = $field->default_value . $divider . $sec_date;
            $append .= "
            'startDate': '$field->default_value',
            'endDate': '$sec_date'
			";
        } else {
            $field->default_value = "";
        }
        $form_str = "";
        $form_str .= "<input  type='text' id='$field->field_name' name='$field->form_name' id='$field->field_name'   value='" . $field->default_value . "' class='" . $field->class_name . " ' " . $field->form_property . ' ' . '>';
        $func = ['trans'];
        $formate = "YYYY-MM-DD HH:mm:ss";
        $_W->bra_scripts[] = <<<EOF
<script>
	require(['jquery' , 'moment' ,'daterangepicker'] , function ($ , moment) {
        $('#$field->field_name').daterangepicker({
            "timePicker": true,  autoUpdateInput: false, timePicker24Hour : true ,"timePickerSeconds": true,
            ranges: {
                '{$func[0]('Today')}': [moment().startOf('day').format('YYYY-MM-DD HH:mm:ss'), moment().format('YYYY-MM-DD HH:mm:ss')],
                '{$func[0]('Yesterday')}': [
                    moment().subtract(1, 'days').format('YYYY-MM-DD 00:00:00'),
                    moment().subtract(1, 'days').format('YYYY-MM-DD 23:59:59')
                ],
                '{$func[0]('Last 7 Days')}': [
                    moment().subtract(6, 'days').format('YYYY-MM-DD 00:00:00'),
                    moment().format('YYYY-MM-DD HH:mm:ss')
                ],
                '{$func[0]('Last 30 Days')}': [
                    moment().subtract(29, 'days').format('YYYY-MM-DD 00:00:00'),
                    moment().format('YYYY-MM-DD HH:mm:ss')
                    ],
                '{$func[0]('This Month')}': [
                    moment().startOf('month').format('YYYY-MM-DD HH:mm:ss'),
                    moment().endOf('month').format('YYYY-MM-DD HH:mm:ss')
                ],
                '{$func[0]('Last Month')}': [
                    moment().subtract(1, 'month').startOf('month').format('YYYY-MM-DD HH:mm:ss'),
                    moment().subtract(1, 'month').endOf('month').format('YYYY-MM-DD HH:mm:ss')
                ]
            },
            "locale": {
                "format": "$formate",
                "separator": " - ",
                "applyLabel": "Apply",
                "cancelLabel": "Clear",
                "fromLabel": "From",
                "toLabel": "To",
                "customRangeLabel": "Custom",
                "weekLabel": "W",
                "daysOfWeek": [
                    "Su",
                    "Mo",
                    "Tu",
                    "We",
                    "Th",
                    "Fr",
                    "Sa"
                ],
                "monthNames": [
                    "January",
                    "February",
                    "March",
                    "April",
                    "May",
                    "June",
                    "July",
                    "August",
                    "September",
                    "October",
                    "November",
                    "December"
                ],
                "firstDay": 1
            },
            "alwaysShowCalendars": true,
             {$append}
        }, function(start, end, label) {
            $('#$field->field_name').val(this.startDate.format('$formate') + ' - ' + this.endDate.format('$formate'));
        }).on('cancel.daterangepicker', function(ev, picker) {
             $(this).val('');
        });
    });
</script>
EOF;

        return $form_str;
    }

    public function input_date () {
        global $_W;
        $field = $this->field;
        $form_str = "";
        $form_str .= "<input v-model='$field->field_name' type='text' id='$field->field_name' name='$field->form_name' id='$field->field_name'   value='" . $field->default_value . "' class='" . $field->class_name . " ' " . $field->form_property . ' ' . '>';
        $_W->bra_scripts[] = "<script>
require(['layui'] , function(layui) {
    layui.use(['laydate'] , function() {
        var laydate = layui.laydate;
        laydate.render({
          elem: '#$field->field_name'
          ,type: 'datetime'
        });
    });
});
</script>
";

        return $form_str;
    }

    public function date_picker () {
        global $_W;
        $field = $this->field;
        $form_str = "";
        $form_str .= "<input  type='text' id='$field->field_name' name='$field->form_name' id='$field->field_name'   value='" . $field->default_value . "' class='" . $field->class_name . " ' " . $field->form_property . ' ' . '>';
        $_W->bra_scripts[] = "<script>
require(['layui'] , function(layui) {
    layui.use(['laydate'] , function() {
        var laydate = layui.laydate;
        laydate.render({
          elem: '#$field->field_name'
          ,type: 'date'
        });
    });
});
</script>
";

        return $form_str;
    }

    public function time_picker () {
        global $_W;
        $field = $this->field;
        $form_str = "";
        $form_str .= "<input  type='text' id='$field->field_name' name='$field->form_group[$field->field_name]$field->multiple' id='$field->field_name'   value='" . $field->default_value . "' class='" . $field->class_name . " ' " . $field->form_property . ' ' . '>';
        $_W->bra_scripts[] = "<script>
require(['layui'] , function(layui) {
            layui.use(['laydate'] , function() {
                var laydate = layui.laydate;
                laydate.render({
                  elem: '#$field->field_name'
                  ,type: 'time'
                });
            });
 });
</script>
";

        return $form_str;
    }

    public function process_model_output ($input) {
        $source = new DataOutput($this->field, $input, $this->field->mode);

        return $source->out_put;
    }
}
