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

use Bra\core\objects\BraFS;
use Bra\core\utils\BraForms;

class bra_theme extends FieldType {

	/**
	 * take files from a dir as option to select
	 * @param $field
	 * @return string
	 */
	public function file_selector () {
        $field = $this->field;
		$files = BraFS::get_dir_files($field->model_id);
		$string = '';
		foreach ($files as $key => $value) {
			if (strpos($value, $field->data_source_config) !== false) {
				$checked = trim($field->default_value) == trim($value) ? 'checked' : '';
				$string .= '<label class="helper_label" >';
				$string .= '<input type="radio" name="' . $field->form_name . '" ' . $field->form_property . ' id="' . $field->form_name . '_' . $key . '" ' . $checked . ' value="' . $value . '" title="' . $value . '"> ';
				$string .= '</label>';
			}
		}

		return $string;
	}

	/**
	 * select theme for a module
	 * @param $field
	 * @return string
	 */
	public function theme_selector () {
        $field = $this->field;
		$string = '';
		foreach ($field->form_data as $key => $value) {
			$checked = trim($field->default_value) == trim($value[$field->pk_key]);
			$string .= BraForms::radio_item($value[$field->pk_key], $checked , $field->form_name, $field->mvvm_model);
		}

		return $string;
	}


}
