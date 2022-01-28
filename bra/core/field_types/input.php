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

use Bra\core\utils\BraForms;

class input extends FieldType {

    public function textarea_filter () {
        return $this->text();
    }

    public function text () {
        return BraForms::text($this->field);
    }

    public function color () {
		$field = $this->field;
        return BraForms::layui_color($field->field_name, $field->form_name, $field->default_value);
    }

    public function number () {
        $field = $this->field;
        $field->class_name = $field->class_name ? str_replace('layui', 'bra', $field->class_name) : 'bra-input';

        return BraForms::text($field, 'number');
    }

    public function password () {
        $field = $this->field;
        $field->class_name = $field->class_name ? str_replace('layui', 'bra', $field->class_name) : 'bra-input';
        $field->form_property .= ' autocomplete="off" ';

        return BraForms::text($field, 'password');
    }

    /** this is text mode output for field
     * @return string
     * @internal param string $value
     */
    public function hidden () {
        return BraForms::text($this->field);
    }

    /**
     * @return string
     */
    public function textarea () {
        $field = $this->field;

        return BraForms::textarea($field->default_value, $field->field_name, $field->form_name, $field->form_property, $field->tips, $field->class_name,
            $field->width, $field->height);
    }

}
