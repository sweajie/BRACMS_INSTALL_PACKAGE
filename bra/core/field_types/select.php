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
use Bra\core\objects\BraString;
use Bra\core\utils\BraForms;
use Exception;

class select extends FieldType {

	public function link_select () {
        $field = $this->field;
        $base = $this->field->inputs;
		$parent_id = $base['__parent_' . $field->field_name] ?? 0 ;

        return BraForms::linkage_select($field->form_data, $field->default_value, $field->form_name, $field->field_name, $field->pk_key, $field->name_key , $parent_id );
	}

	public function single_select () {
        $field = $this->field;
		$field->primary_option = $field->slug ? $field->slug : $field->primary_option;
		$field->primary_option = isset($field->primary_option) ? $field->primary_option : null;
		if (empty($field->form_data)) {
			return [];
		}
        $field->class_name = $field->class_name ? str_replace('layui' , 'bra' , $field->class_name) :  'bra-select';


		return BraForms::single_select($field->form_data, $field->default_value, $field->form_name, $field->field_name, $field->class_name, $field->pk_key, $field->name_key, $field->parentid_key, $field->form_property, $field->primary_option);
	}


	public function process_model_output ($input) {
        $source = new DataOutput($this->field , $input , $this->field->data_source_type);


        return  $source->out_put;
	}

	public function mhcms_m_select () {
		global $_W;
		$field = $this->field;
		$base = $this->field->inputs;
		$display_text = "请输入关键字";
		if (isset($base[$field->field_name]) && BraString::bra_isset($base[$field->field_name])) {
			$target_model = D($field->data_source_config);
			$donde[$field->pk_key] = ['IN', $base[$field->field_name]];
			$targets = $target_model->bra_where($donde)->list_item(true);
		}
		$params = [];
		$params['model_id'] = $field->data_source_config;
		if (defined("BRA_ADMIN")) {
			$service_api_url = make_url('bra_admin/admin_api/list_item', $params);
		} else {
			$md = $_W['bra_api_module'] ? $_W['bra_api_module'] : ROUTE_M;
			$service_api_url = make_url($md . '/web_api/list_item', $params);
		}
		$form_str = "";
		$form_str .= "<div id='$field->field_name'  style='min-width:15em'   class=\"SEMANTIC_AJAX_INPUT ui fluid multiple search selection dropdown\">";
		if (empty($targets)) {
		} else {
			foreach ($targets as $target) {
				$form_str .= <<<EOF
<a class="ui label transition visible" data-value="{$target['id']}" style="display: inline-block !important;">{$target[$field->name_key]}<i class="delete icon"></i></a>
EOF;
			}
		}
		$form_str .= "<div  class='default text'>$display_text</div>";
		$form_str .= "<input type=\"hidden\" value='$field->default_value' name=\"$field->form_group[$field->field_name]$field->multiple\">
  <i class=\"dropdown icon\"></i>";
		$form_str .= "</div>";
		$_W->bra_scripts[] = "
<script type='text/javascript'>
require([ 'semantic'] , function( semantic ) {
          $('#$field->field_name').dropdown({
                apiSettings: {
                  /*  this url parses query server side and returns filtered results*/
                  url: '$service_api_url' + '&q={query}' ,
                  data : {
                      f : 'sematic_drop_down' ,
                      _rnd : Math.random()
                  },
                  cache : false
                },
                forceSelection : false ,
                transition: 'drop' ,
                clearable: true ,
          });
});

</script>
            ";

		return $form_str;
	}

	public function semantic_ajax_select () {
		global $_W;
        $field = $this->field;
        $base = $this->field->inputs;
		$bra_m = D($field->data_source_config);
		$display_text = "";
		if (isset($base[$field->field_name]) && BraString::bra_isset($base[$field->field_name])) {
			try {
				$target = (array) $bra_m->bra_where([$field->pk_key => $base[$field->field_name]])->bra_one();
			} catch (Exception $e) {
				$target = '';
			}
			$model_info = $bra_m->_TM;
			if ($target && strpos($model_info['name_key'], "|") !== false) {
				$name_keys = explode("|", $model_info['name_key']);
				foreach ($name_keys as $name_key) {
					if ($target[$name_key]) {
						$display_text = $target[$name_key];
						break;
					}
				}
			}
			if ($target && strpos($model_info['name_key'], ",") !== false) {
				$name_keys = explode(",", $model_info['name_key']);
				foreach ($name_keys as $name_key) {
					$display_text .= $target[$name_key];
				}
			}
			if (!$display_text) {
				$display_text = $target[$model_info['name_key']] ?? '';
			}
			$icon = "dropdown clear ";
		} else {
			$display_text = $field->default_value ? $field->default_value : "请输入关键字";
			$icon = "dropdown ";
		}
		$params = [];
		$params['model_id'] = $field->data_source_config;
		if (defined("BRA_ADMIN")) {
			$module = !empty($field->bra_ajax_module) ? $field->bra_ajax_module : "bra_admin";
			$action = !empty($field->bra_ajax_action) ? $field->bra_ajax_action : "list_item";
			$service_api_url = make_url($module . '/admin_api/' . $action, $params);
		} else {
			$md = $_W['bra_api_module'] ? $_W['bra_api_module'] : ROUTE_M;
			$service_api_url = make_url($md . '/web_api/list_item', $params);
		}
		$form_str = "";

		$form_str .= <<<EOF
 <div style='min-width:15em' id='$field->field_name' class=' ui fluid  search normal selection dropdown'>
  <input type='hidden' value='$field->default_value' name='$field->form_name'>
  <i class='icon $icon'></i>
  <div class='default text'>$display_text</div></div>

EOF;
		$_W->bra_scripts[] = "<script type='text/javascript'>
require([ 'semantic'] , function( semantic ) {
          $('#$field->field_name').dropdown({
                apiSettings: {
                  url: '$service_api_url' + '&q={query}' ,
                  data : {
                      f : 'sematic_drop_down'
                  },
                  cache : false ,
                  saveRemoteData : false ,
                  filterRemoteData : true
                },
                placeholder : '请选择',
                forceSelection : false ,
                transition: 'drop' ,
                clearable: true
          });
});

</script>";
		return $form_str;
	}

}
