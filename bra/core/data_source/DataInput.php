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

use Bra\core\objects\BraField;
use Bra\core\objects\BraString;
use Illuminate\Support\Facades\Hash;

class DataInput {
    public $source = '';
    public $input;
    private $field;

    public function __construct (BraField $field, $action) {
        $this->input = $field->default_value;
        $this->field = $field;
        $action = 'in_' . $action;

		if(method_exists($this, $action)){
			$this->$action();
		}
    }

	public function in_color () {
		return $this->input;
	}

    public function in_ () {
        return $this->input;
    }

    public function in_bra_node() {
        return $this->input;
    }

    public function in_text () {
        return $this->input;
    }

    public function in_password () {
        if (strlen($this->input) > 40) {
            return $this->input;
        } else {
            return $this->input = Hash::make($this->input);
        }
    }

    public function in_link_select () {
        return $this->input;
    }

    public function in_semantic_ajax_select () {
        return $this->input = $this->input ? $this->input : 0;
    }

    public function in_layui_radio () {
        return $this->input = $this->input == '' ? 0 : $this->input;
    }

    public function in_single_select () {
        return $this->input;
    }

    public function in_number () {
        return $this->input = intval($this->input);
    }

    public function in_layui_checkbox () {

        if(!is_array($this->input)){
			$this->input = array_filter(explode("," ,  $this->input));
			return $this->input = ',' .  $this->input. ',';
        }

		return $this->input = ',' . join(',', $this->input) . ',';
    }

    public function in_layui_single_upload () {
		if(!is_array($this->input)){
			return $this->input = str_replace(["[" ,"]"] , "" , $this->input);
		}else{
			return $this->input =  join("" , $this->input);
		}
    }

    private function multi_vals () {
        if ($this->field->is_multiple > 1) {
            if (!is_array($this->input)) {
				$this->input = array_filter(explode("," ,  $this->input));
			}

            return $this->input = $this->input ? ',' . join(',', $this->input) . ',' : '';
        }

        return $this->input;
    }

    public function in_layui_mutil_upload () {
        return $this->multi_vals();
    }

    public function in_mhcms_icon_selector () {
        return $this->input = intval($this->input);
    }

    public function in_textarea () {
        return $this->input;
    }
    public function in_ueditor () {
        $input = htmlspecialchars_decode($this->input);
        $input = BraString::remove_xss($input);
        return $this->input = $input;
    }

    public function in_input_date () {
        if(empty($this->input)){
            $_input = null;
        }else{
            $_input = $this->input;
        }
        return $this->input = $_input;
    }

    public function in_date_picker () {
        if(empty($this->input)){
            $_input = null;
        }else{
            $_input = $this->input;
        }
        return $this->input = $_input;
    }

    public function in_storge_mixed_upload() {
		if(is_array($this->input)){
			return $this->input = join(',' , $this->input);
		}else{
			return $this->input;
		}


    }

    public function in_bra_fields() {
    	if(is_array($this->input)){
			return $this->input = join(',' , $this->input);
		}
        else{
			return $this->input;
		}
    }
	public function in_mhcms_m_select() {

	}
}
