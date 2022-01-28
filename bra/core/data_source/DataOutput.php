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
namespace Bra\core\data_source;

use Bra\core\objects\BraArray;
use Bra\core\objects\BraField;
use Bra\core\objects\BraString;
use Bra\core\utils\BraException;
use Exception;

class DataOutput {
	public $source = '';
	public $out_put = '';
	private $field;
	private $mapping;
	private $input;

	public function __construct (BraField $field, $input, $action) {
		global $_W, $_GPC;
		$this->input = $this->out_put = $input;
		$this->field = $field;
		$action = 'out_' . $action . '_' . $field->mode;
		$this->$action();
		$this->mapping = array_merge($_W['mapping'] ?? [], $_GPC);
		if (!empty($this->field->replace)) {
			$this->replace_char();
		}
	}

	public function replace_char () {
		$replaces = array_filter(explode("\r\n", $this->field->replace));
		foreach ($replaces as $replace) {
			$data = explode(',', $replace);
			$this->out_put = str_replace($data[0], $data[1], $this->out_put);
		}
	}

	public function out_bra_node () {
	}

	public function out_bra_node_bra_node () {
	}

	public function out_model_single_select () {
		$this->out_model();
	}

	public function out_model () {
		$field = $this->field;
		$input = $this->input;
		//get target model info
		switch ($field->mode) {
			case "checkbox": //多选
			case "layui_checkbox":
				$ids = array_filter(explode(',', $input));
				break;
			case "radio"://单选
			case "layui_radio":
				$ids[] = $input;
				break;
			default:
				$ids[] = $input;
		}
		$field->data_source_config = BraString::parse_param_str($field->data_source_config, $this->mapping);
		if (strpos($field->data_source_config, '|') !== false) {
			dd($field);
		}
		$bra_model = D($field->data_source_config);
		$TP_M = $bra_model->_TM;
		if ($TP_M) {
			$field->pk_key = $field->pk_key ? $field->pk_key : $TP_M->id_key;
			$name_key = $field->name_key = $field->name_key ? $field->name_key : $TP_M->name_key;
			if (!$field->pk_key) {
				die("ID KEY 2:" . $field->field_name . ' - ' . $field->data_source_config);
			}
			if (!$field->name_key) {
				die("name_key KEY 2:" . $field->field_name . ' - ' . $field->data_source_config);
			}
			try {
				$out_items = $bra_model->whereIn($field->pk_key, $ids)->get();
			} catch (Exception $e) {
			}
		}
		$type = "normal";
		$name_keys = [$name_key];
		if (strpos($name_key, '|') !== false) {
			$type = 'OR';
			$name_keys = explode('|', $name_key);
		}
		if (strpos($name_key, ',') !== false) {
			$type = 'BOTH';
			$name_keys = explode(',', $name_key);
		}
		if (defined("BRA_ADMIN") || !$field->is_multiple) {
			$out = "";
			foreach ($out_items as $k => $item) {
				$item = (array)$item;
				foreach ($name_keys as $name_key) {
					if ($out) {
						$out .= " , ";
					}
					$out .= $item[$name_key];
				}
			}
		} else {
			$out = [];
			foreach ($out_items as $k => $item) {
				$item = (array)$item;
				$out[] = $item[$field->name_key];
				foreach ($name_keys as $name_key) {
					$out[] = $item[$name_key];
				}
			}
		}
		$this->out_put = $out;
	}

	public function out_model_mhcms_m_select () {
		$this->out_model();
	}

	public function out_model_layui_radio () {
		$this->out_model();
	}

	public function out_model_layui_checkbox () {
		$this->out_model();
	}

	public function out_diy_arr_layui_radio () {
		$field = $this->field;
		$options = $this->get_diy_arr();
		$out_put = explode(",", $this->input);
		$out = [];
		if ($out_put) {
			foreach ($options as $item) {
				foreach ($out_put as $o) {
					if ($o == $item[$field->pk_key]) {
						$out = $item[$field->name_key];
					}
				}
			}
		}
		$this->out_put = $out;
	}

	public function out_diy_arr_layui_checkbox () {
		$field = $this->field;
		$options = $this->get_diy_arr();
		$out_put = explode(",", $this->input);
		$out = [];
		if ($out_put) {
			foreach ($options as $item) {
				foreach ($out_put as $o) {
					if ($o == $item[$field->pk_key]) {
						$out[] = $item[$field->name_key];
					}
				}
			}
		}
		$this->out_put = $out;
	}

	/**
	 * reformate diy_arr options
	 * @return array
	 */
	private function get_diy_arr () {
		$field = $this->field;
		if (empty($field->data_source_config)) {
			return [];
		}
		if (is_array($field->data_source_config)) {
			return $field->data_source_config;
		}
		$options = explode("\n", $field->data_source_config);
		if (!$field->pk_key) {
			$field->pk_key = "id";
		}
		if (!$field->name_key) {
			$field->name_key = "name";
		}
		foreach ($options as $option) {
			$_v = [];
			if (strpos($option, "|") !== false) {
				$data = explode("|", $option);
				$_v[$field->name_key] = $data[1];
				$_v[$field->pk_key] = $data[0];
			} else {
				$_v[$field->name_key] = $option;
				$_v[$field->pk_key] = $option;
			}
			$_new_options[] = $_v;
		}

		return $_new_options;
	}

	public function out_mhcms_icon_selector_mhcms_icon_selector () {
		$this->out_mhcms_icon_selector();
	}

	public function out_mhcms_icon_selector () {
		$html = '';
		try {
			$item = D('icon')->get_item($this->input);
			if ($item['font_type'] == "图片图标") {
				$html = "<img class='img' src='{$item['annex'][0]['url']}'  />";
			} else {
				$html = $item['icon'];
			}
		} catch (Exception $e) {
		}

		return $this->out_put = $html;
	}

	public function out_bra_options_layui_radio () {
		$field = clone $this->field;
		$ids[] = $this->input;
		$bra_model = D($field->data_source_config);
		$TP_M = $bra_model->_TM;
		$field->pk_key = $field->pk_key ? $field->pk_key : $TP_M['id_key'];
		$field->name_key = $field->name_key ? $field->name_key : $TP_M['name_key'];
		if (!$field->pk_key) {
			throw new BraException('UNKNOWN ID KEY IN MODEL' . $field['field_name']);
		}
		if (!$field->name_key) {
			throw new BraException('UNKNOWN name_key KEY IN MODEL' . $field['field_name']);
		}
		$out_put = (array)$bra_model->bra_where([$field->pk_key => ["IN", $ids]])->first();
		$out = $out_put[$field->name_key] ?? '';
		$this->out_put = $out;
	}

	public function out_bra_options_layui_checkbox () {
		$field = $this->field;
		$ids = array_filter(explode(',', $this->input));
		$bra_model = D($field->data_source_config);
		$TP_M = $bra_model->_TM;
		$field->pk_key = $field->pk_key ? $field->pk_key : $TP_M['id_key'];
		$field->name_key = $field->name_key ? $field->name_key : $TP_M['name_key'];
		if (!$field->pk_key) {
			throw new BraException('UNKNOWN ID KEY IN MODEL' . $field['field_name']);
		}
		if (!$field->name_key) {
			throw new BraException('UNKNOWN name_key KEY IN MODEL' . $field['field_name']);
		}
		$out_put = $bra_model->bra_where([$field->pk_key => ["IN", $ids]])->bra_get();
		$out = [];
		foreach ($out_put as $k => $item) {
			$_item['id'] = $item['id'];
			$_item['title'] = $item['option_name'];
			$out[] = $_item;
		}
		$this->out_put = $out;
	}

	public function out_model_semantic_ajax_select () {
		$this->out_model();
	}

	public function out_input_date () {
	}

	public function out_input_date_input_date () {
	}

	public function out_date_picker () {
	}

	public function out_bra_shared_options () {
		$field = clone $this->field;
		$conf = explode("@", $field->data_source_config);
		$conf__m = D($conf[0]);
		$conf_fields = $conf__m->fields;
		$conf_field = $conf_fields[$conf[1]];
		$field->set_config($conf_field);
		$field->set('mode', $field->mode);
		$field->set('default_value', $field->default_value);
		$field->model_id = $conf__m->_TM['id'];
		$this->field = $field;
		$this->out_bra_options();
	}

	public function out_bra_shared_options_layui_radio () {
		$field = clone $this->field;
		$conf = explode("@", $field->data_source_config);
		$conf__m = D($conf[0]);
		$conf_fields = $conf__m->fields;
		$conf_field = $conf_fields[$conf[1]];
		$field->set_config($conf_field);
		$field->set('mode', $field->mode);
		$field->set('default_value', $field->default_value);
		$field->model_id = $conf__m->_TM['id'];
		$this->field = $field;
		$this->out_bra_options();
	}

	public function out_bra_options () {
		$field = $this->field;
		switch ($field->mode) {
			//多选
			case "checkbox":
			case "layui_checkbox":
				$ids = array_filter(explode(',', $this->input));
				break;
			//单选
			case "radio":
			case "layui_radio":
			case "single_select":
				$ids[] = $this->input;
				break;
		}
		$bra_model = D($field->data_source_config);
		$TP_M = $bra_model->_TM;
		$field->pk_key = $field->pk_key ? $field->pk_key : $TP_M['id_key'];
		$field->name_key = $field->name_key ? $field->name_key : $TP_M['name_key'];
		if (!$field->pk_key) {
			throw new BraException('UNKNOWN ID KEY IN MODEL' . $field['field_name']);
		}
		if (!$field->name_key) {
			throw new BraException('UNKNOWN name_key KEY IN MODEL' . $field['field_name']);
		}
		if (strpos($this->input, ',') === false) {
			$is_multiple = false;
		} else {
			$is_multiple = true;
		}
		if ($is_multiple) {
			$out_put = $bra_model->bra_where([$field->pk_key => ["IN", $ids]])->get();
			$out = [];
			foreach ($out_put as $k => $item) {
				$item = (array)$item;
				$_item['id'] = $item['id'];
				$_item['title'] = $item['option_name'];
				$out[] = $_item;
			}
		} else {
			$out_put = (array)$bra_model->bra_where([$field->pk_key => ["IN", $ids]])->first();
			$out = $out_put[$field->name_key] ?? '';
		}
		$this->out_put = $out;
	}

	public function out_bra_client_layui_radio () {
		$this->out_bra_client();
	}

	public function out_bra_client () {
		$field = $this->field;
		$field->pk_key = $field->pk_key ? $field->pk_key : 'id';
		$field->name_key = $field->name_key ? $field->name_key : 'title';
		$form_data = BraArray::bra_client();
		$out_put = [];
		foreach ($form_data as $item) {
			if ($item['id'] == $this->input) {
				$out_put = $item;
				break;
			}
		}
		$this->out_put = $out_put[$field->name_key] ?? '';
	}

	public function out_model_link_select () {
		$this->out_model();
	}

	public function out_bra_options_ () {
	}

	public function out_bra_client_ () {
	}

	public function out_bra_popup_selector () {
		return $this->out_put = $this->input;
	}

	public function out_bra_popup_selector_bra_popup_selector () {
		return $this->out_put = $this->input;
	}

	public function out_bra_fields () {
	}

	public function out_bra_sys_set_layui_radio () {
		$this->out_bra_sys_set();
	}

	public function out_bra_sys_set () {
		$field = $this->field;
		$field->pk_key = $field->pk_key ? $field->pk_key : "id";
		$field->name_key = $field->name_key ? $field->name_key : "title";
		$source_config = explode('@', $field->data_source_config);
		$out_puts = config($source_config[0]);

		return $this->out_put = $out_puts[$this->input][$field->name_key];
	}

	public function out_date_picker_date_picker () {

	}
}
