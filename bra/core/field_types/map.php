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

use Bra\core\utils\map\AMap;

class map extends FieldType {

    public function input_location () {

        $field = $this->field;
        $base = $this->field->inputs;
        $lat_lng['lng'] = $base[$field->lng_field] ? $base[$field->lng_field] : '116.404';
        $lat_lng['lat'] = $base[$field->lat_field] ? $base[$field->lat_field] : '39.915';

        return AMap::render_input_form($field->form_name, $field->field_name, $field->default_value, $lat_lng, $field->form_group . "[$field->lng_field]", $field->form_group . "[$field->lat_field]");
    }

    public function process_model_output ($input) {
        $out_put = $input;

        return $out_put;
    }

    public function process_model_input () {
        $input = htmlspecialchars_decode($this->field->default_value);
        $lng_lat = explode(",", $input);
        if (count($lng_lat) == 2) {
            $base[$this->field->lng_field] = $lng_lat[0] ? $lng_lat[0] : '';
            $base[$this->field->lat_field] = $lng_lat[1] ? $lng_lat[1] : '';
        }

        return $input;
    }

    public function baidu_map ($field, $base) {
        if (!empty($base[$field->lng_field])) {
            $field->default_value = $base[$field->lng_field] . "," . $base[$field->lat_field];
        }

        return BaiduMap::render_form($field->field_name, $field->default_value, $field->form_group);
    }

    public function amap () {
        $field = $this->field;
        $base = $this->field->inputs;
        if (!empty($base[$field->lng_field])) {
            $field->default_value = $base[$field->lng_field] . "," . $base[$field->lat_field];
        }

        return AMap::render_form($field->form_name, $field->field_name, $field->default_value);
    }

}
