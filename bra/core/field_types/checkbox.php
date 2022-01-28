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
use Bra\core\utils\BraForms;

class checkbox extends FieldType {

    public function layui_checkbox () {
        $field = $this->field;

        if(strpos($field->form_name , '[]' ) === false){
            $field->form_name .= "[]";
        }
        return BraForms::checkbox($field->form_data, $field->default_value, $field->form_name, $field->field_name, $field->pk_key, $field->name_key, $field->form_property, $field->class_name);
    }

    public function layui_checkbox_filter () {
        return $this->single_select();
    }

    public function single_select () {
        $field = $this->field;
        $field->primary_option = $field->slug ? $field->slug : $field->primary_option;
        $field->primary_option = isset($field->primary_option) ? $field->primary_option : null;
        $field->class_name = $field->class_name ? str_replace('layui', 'bra', $field->class_name) : 'bra-select';

        return BraForms::single_select($field->form_data, $field->default_value, $field->form_name, $field->field_name, $field->class_name, $field->pk_key, $field->name_key, $field->parentid_key, $field->form_property, $field->primary_option);
    }

    public function layui_radio () {
        $field = $this->field;
        $bra_ui = $field->ui ?? false;
        $default = $field->default_value = isset($field->default_value) ? $field->default_value : $field->default_value;
        $field->form_property .= " lay-ignore ";

        return BraForms::radio($field->form_data, $default, $field->form_name, $field->form_group, $field->pk_key, $field->name_key, $field->form_property, $bra_ui, $field->field_name);
    }

    public function layui_radio_filter () {
        $field = $this->field;
        $field->form_property = 'lay-ignore';

        return $this->single_select();
    }
//
//    public function process_model_input () {
//        $input = $this->field->default_value;
//        if (is_array($input)) {
//            $input = array_filter($input);
//            $_value = ",";
//            $_value .= implode(",", $input);
//            $_value .= ",";
//        } else {
//            $_value = $input;
//        }
//
//        return $_value;
//    }

    public function process_model_output ($input) {
        $source = new DataOutput($this->field, $input, $this->field->data_source_type);

        return $source->out_put;
        switch ($field->data_source_type) {
            case "linkage":
                $result = Cache::get("linkage/$field->data_source_config");
                $out_put = $result['data'][$input];
                $out_put = $out_put['name'];

                return $out_put;
                break;
            case 'sub_cate_id' :
                $out_put = D($field->data_source_config)->find([$field->pk_key => $input]);

                return $out_put[$field->name_key];
                break;
            case 'bra_sys_set' :
                $field->pk_key = $field->pk_key ? $field->pk_key : "id";
                $field->name_key = $field->name_key ? $field->name_key : "title";
                $source_config = explode('@', $field->data_source_config);
                $out_puts = config($source_config[0]);

                return $out_puts[$input][$field->name_key];
        }
    }

}
