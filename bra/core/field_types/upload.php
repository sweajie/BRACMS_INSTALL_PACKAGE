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

use Bra\core\objects\BraAnnex;
use Bra\core\objects\BraString;
use Bra\core\utils\BraException;
use Bra\core\utils\BraForms;

class upload extends FieldType {

	public function storge_mixed_upload () {
		$field = $this->field;
		$bra_annex = BraAnnex::get_default();
		$form_str = $bra_annex->form($field);

		return $form_str;
	}

	public function layui_mutil_upload () {
		$field = $this->field;
		$data_source_config = $field->data_source_config;
		$accept = $field->accept ?? '';

		return BraForms::layui_mutil_image_upload($field->default_value, $field->form_name, $field->field_name, $field->max_count, $data_source_config, $accept);
	}

	public function layui_single_upload () {
		$field = $this->field;
		$params = [];
		if (isset($field->keep_clear)) {
			$params['keep_clear'] = 1;
		}
		$params = json_encode($params);

		return BraForms::layui_mutil_image_upload((int)$field->default_value, $field->form_name, $field->field_name, $field->is_multiple,
			$field->data_source_config, $field->accept, $params);
	}

	public function process_model_output ($input) {
		$out_put = [];
		$input = array_filter(explode(",", $input));
		foreach ($input as $f) {
			if (BraString::is_url($f)) {
				$file = [];
				$file['url'] = $f;
				$out_put[] = $file;
			} else {
				$file = new BraAnnex((int)$f);
				if ($file->annex) {
					$file->annex['path'] = $file->get_url(true);
					$file->annex['url'] = $file->to_media($this->field->user_img_style, false);
					if ($f && $file->annex) {
						$out_put[] = $file->annex;
					}
				}
			}
		}

		return $out_put;
	}

	public function process_model_input_old () {
		$input = $this->field->default_value;
		// app is_app
		if ($this->field->is_multiple > 1) {
			if (!is_array($input)) {
				$input = explode(",", $input);
			}

			return "," . join(",", array_filter($input)) . ",";
		} else {
			if (!is_array($input)) {
				return $input;
			} else {
				return join("", array_filter($input));
			}
		}
	}
}
