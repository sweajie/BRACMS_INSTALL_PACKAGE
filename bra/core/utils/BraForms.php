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
namespace Bra\core\utils;

use Bra\core\objects\BraAnnex;
use Bra\core\objects\BraField;
use Bra\core\objects\BraString;
use Exception;

/**
 * All forms should came out from here first , then apply different in sub elements;
 * Class Forms
 * @package app\common\util\forms
 */
class BraForms {

    public static function radio_item ($value, $checked, $form_name , $mvvm_model = '', $classname = "bra-radio", $property = "") {
        $checked = $checked ? "checked" : '';
        if($mvvm_model){
            $property .= " v-model='$mvvm_model'";
        }

        $value = BraString::new_html_special_chars($value);
        $string = "<label class='$classname' >";
        $string .= "<input name='$form_name' type='radio' $property $checked  value='$value' title='$value'>";
        $string .= "<span>$value</span>";
        $string .= '</label>';

        return $string;
    }

	public static function linkage_select ($form_data, $default_value, $form_name, $form_id, $id_key = "id", $name_key = "title", $parent_id = 0) {
		global $_W;
		$name_key = $name_key ? $name_key : "title";
		$id_key = $id_key ? $id_key : "id";
		$path = '';

		if ($default_value) {
			$tree = new BraTreeData($form_data);
			$path = $tree->get_node_path($default_value);
			$path = join(',', array_reverse($path) );
		}

//		dump($form_data);
//		$t = new BraTea($form_data);
//		$tea_data = $t->build_cached($parent_id);
		$data = json_encode($form_data);

		// $data = json_encode(BraTree::get_cate_tree([], 0, 0, $form_data, '', $id_key, $name_key));
		$form_str = <<<EOF

<div id="linkage_$form_id" class="is-flex"></div>
<input type="hidden" id="$form_id" name="$form_name"  value="$default_value">
EOF;
		$wscript = '';
		$wscript .= <<<EOF
<script type="text/javascript">
EOF;
		$wscript .= <<<EOF
var data_$form_id =$data;
require(['layui'] , function(layui) {
    layui.config({ base: '/statics/packs/layui/libs/' });
    layui.use(['bra_picker_desktop'] , function() {
        layui.bra_picker_desktop({
          elem: '#linkage_$form_id'
          ,search:[false,true]
          ,form_id : "$form_id"
          ,data: data_$form_id
          , field: {idName: '$id_key', titleName: '$name_key', statusName: 'status', childName: 'children'}
          , selected : [$path]
          , default_value : "$default_value"

        })
    });
});
EOF;
		$wscript .= <<<EOF
</script>
EOF;
		$_W->bra_scripts[] = $wscript;

		return $form_str;
	}

	public static function get_tree_path ($form_datas, $id, &$path = [],   $id_key = 'id') {
		foreach ($form_datas as $form_data) {
			if ($id == $form_data[$id_key]) {
				array_unshift($path, $id);
				if ($form_data['parent_id'] > 0) {
					self::get_tree_path($form_datas, $form_data['parent_id'], $path);
				}
			}
		}

		return $path;
	}


	public static function get_path ($leaf_id , $tree_datas , $path = [],   $id_key = 'id') {

		foreach ($tree_datas as $form_data) {
			$path = [];
			$path[] = $form_data['id'];
//			array_unshift($path, $form_data['id']);
			if($leaf_id == $form_data['id']){
				return $path;
			}
			$path = self::get_children_path($leaf_id , $form_data['id'] , $form_data['children'],   $id_key, $path);
			if($path){
				break;
			}
		}

		return $path;
	}

	public static function get_children_path ($leaf_id , $from_pid , $children , $id_key = 'id' , $path = []) {
		foreach ($children as $child) {
			array_unshift($path, $child['id']);
			if ($leaf_id != $child[$id_key]) {
				if(!empty($child['children'])){
					self::get_children_path($leaf_id , $from_pid , $child['children']  , $id_key ,$path);
				}else{
					return [];
				}
			}else{
				return $path;
			}
		}

		return $path;
	}

	/**
	 * data filter for default val
	 * @param $default_value
	 * @return array
	 */
	public static function filter_default ($default_value) {
		if (!is_array($default_value)) {
			if (strpos($default_value, ',') !== false) {
				$default_value = explode(',', $default_value);
			} else {
				$default_value = [$default_value];
			}
		}

		return $default_value;
	}

	public static function make_bra_input ($i, $value, $title, $type, $form_id, $form_name, $form_classname, $property, $checked) {
		return <<<EOF
<input bra-filter="$form_id" name="{$form_name}[]" class="$form_classname" type="$type" $property id="{$form_id}_$i" $checked value="$value" title="$title">
EOF;
	}



    public static function radio ($datas, $default_value, $form_name, $form_id, $id_key = "id", $name_key = "name", $property = " lay-ignore ", $is_layui = false, $field_name = '') {
        $string = '<div class="bra-radio">';
        $default = [
            $id_key => '',
            'name' => '不选择',
            $name_key => '不选择'
        ];
        array_unshift($datas, $default);
        if (strpos($property, 'lay-ignore') !== false) {
            $is_layui = false;
        }
        $v_model = $field_name ? "v-model='$field_name'" : '';
        foreach ($datas as $key => $value) {
            if (empty($value)) {
                continue;
            }
            $checked = trim($default_value) == trim($value[$id_key]) ? 'checked' : '';
            $string .= '<label class="helper_label radio" >';
            $string .= "<input type='radio' $v_model name='$form_name' $property  data-id='{$form_id}_" . BraString::new_html_special_chars($key) . "' $checked  value='$value[$id_key]'  title='$value[$name_key]'> ";
            if (!$is_layui) {
                $string .= "<span>$value[$name_key]</span>";
            }
            $string .= '</label>';
        }
        $string .= '</div>';

        return $string;
    }


    public static function bra_checkbox ($data_s, $default_value, $form_name, $form_id, $id_key = "id", $name_key = "name", $show_no = true, $property = "", $form_classname = '') {
        $form = [];
        $default_value = self::filter_default($default_value);
        $i = 1;
        if ($show_no) {
            $default = [
                $id_key => '',
                $name_key => '不选择',
            ];
            array_unshift($data_s, $default);
        }
        foreach ($data_s as $key => $value) {
            if (!empty($value)) {
                $string = [];
                $checked = (isset($default_value) && in_array($value[$id_key], $default_value)) ? 'checked' : '';
                $string['form_str'] = self::make_bra_input($i, BraString::new_html_special_chars($value[$id_key]), BraString::new_html_special_chars($value[$name_key]), 'checkbox', $form_id, $form_name, $form_classname, $property, $checked);
                $string['text'] = BraString::new_html_special_chars($value[$name_key]);
                $i++;
                $form[] = $string;
            }
        }

        return $form;
    }

	public static function checkbox ($data_s, $default_value, $form_name, $form_id, $id_key = "id", $name_key = "name", $property = "", $form_classname = '', $is_layui = true) {
	    $string = '<div class="bra-radio">';
        $default = [
            $id_key => '',
            'name' => '不选择',
            $name_key => '不选择'
        ];
        array_unshift($data_s, $default);
        $default_value = array_filter( explode(',' , $default_value) , function ($item){
            return $item!== '';
        });

        foreach ($data_s as $key => $value) {
            if (empty($value)) {
                continue;
            }
            $checked = in_array($value[$id_key] , $default_value) ? 'checked' : '';
            $string .= '<label class="helper_label radio" >';
            $string .= "<input type='checkbox'   name='$form_name' $property  data-id='{$form_id}_" . BraString::new_html_special_chars($key) . "' $checked  value='$value[$id_key]'  title='$value[$name_key]'> ";
            $string .= "<span>$value[$name_key]</span>";
            $string .= '</label>';
        }
        $string .= '</div>';

        return $string;
	}

	public static function input_text ($form_id, $form_name, $type, $value, $tips = "", $form_property = '', $class_name = '', $width = '', $height = '') {
		$str = '';
		$str .= "<input type='$type' placeholder=\"$tips\" name='$form_name' id='$form_id' style='width:$width;height:$height' value='{$value}' class='$class_name'  $form_property >";

		return $str;
	}

	public static function layui_color ($form_id, $form_name, $value) {
		global $_W , $_GPC;
		$str = <<<EOF
 <div class="">
      <div class="layui-input-inline" style="width: 120px;">
        <input type="text" value="$value" placeholder="请选择颜色" name="$form_name" class="layui-input" id="$form_id">
      </div>
      <div class="layui-inline" style="left: -11px;">
        <div id="color_$form_id"></div>
      </div>
    </div>
<div id="color_$form_id"></div>

EOF;

		$script = <<<EOF

<script>
require(['layui'] , function(layui) {
    layui.use('colorpicker', function(){
      var colorpicker = layui.colorpicker;
      //渲染
       colorpicker.render({
        elem: '#color_$form_id'
        ,color: '$value'
        ,done: function(color){
          $('#$form_id').val(color);
        }
      });
    });
});

</script>
EOF;


		$_W->bra_scripts[] = $script;
		return $str;
	}

	public static function single_select ($datas, $default_value, $form_name, $form_id, $node_field_class_name, $id_key = "id", $name_key = "name", $parentid_key = "", $property = "", $primary_option = "请选择") {
		$name_key = self::get_name_key($name_key);
		if (!$primary_option) {
			$primary_option = "请选择";
		}


		//$string = "<select class='$node_field_class_name' name='$form_name' id='$form_id' $property>\n<option value=''>" . $primary_option . "</option>\n";
		$tree_str = [];
		foreach ($datas as $key => $value) {
			$tree_str[$key] = array(
				"id" => $value[$id_key],
				"name" => $value[$name_key]
			);
			if ($parentid_key) {
				$tree_str[$key]["parent_id"] = $parentid_key && isset($value[$parentid_key]) ? $value[$parentid_key] : false;
			}
			if ($value[$id_key] == $default_value) {
				$tree_str[$key]["selected"] = "selected";
				$has_selection = true;
			} else {
				$tree_str[$key]["selected"] = "";
			}
		}
		if(is_numeric($default_value)){
            $noselect_value = 0;
        }else{
            $noselect_value = '';
        }

        $string = <<<EOF
<select class='$node_field_class_name' name='$form_name' id='$form_id' $property>
<option value='$noselect_value'>$primary_option</option>
EOF;

		$str = "<option value='{id}' {selected}>{spacer} {name}</option>";
		$tree = new BraTree($tree_str, null, null, $form_id);
		$string .= $tree->get_bra_tree(0, $str, $default_value);
		$string .= '</select>';

		//  add sub level
		return $string;
	}

	public static function get_name_key ($key) {
		if (strpos($key, ',') !== false) {
			$keys = explode(',', $key);
			$key = $keys[0];
		}
		if (strpos($key, '|') !== false) {
			$keys = explode('|', $key);
			$key = $keys[0];
		}

		return $key;
	}

	public static function date_picker ($default_val, $field_name, $form_name, $class_name = 'bra-input', $form_property = '') {
		global $_W;
		$form_str = "";
		$form_str .= "<input  type='text' id='$field_name' name='$form_name'  value='" . $default_val . "' class='" . $class_name . " ' " . $form_property . ' ' . '>';
		$_W->bra_scripts[] = "<script>
require(['layui'] , function(layui) {
    layui.use(['laydate'] , function() {
        var laydate = layui.laydate;
        laydate.render({
          elem: '#$field_name'
          ,type: 'date'
        });
    });
});
</script>
";

		return $form_str;
	}

	public static function input ($name, $value, $tips = "") {
		$str = '';
		$str .= "<input class='$name' type='text' placeholder='$tips' name='$name' value='$value' />";

		return $str;
	}

	public static function text (BraField $field, $type = "text") {
		$str = $vue = "";
		if($field['vue_cell']){
			$vue = " v-model='$field->field_name' ";
		}
		$str .= "<input $vue autocomplete='off' type='$type' placeholder=\"$field->tips\" name='$field->form_name'  style='width:$field->width;height:$field->height' value='{$field->default_value}' class='$field->class_name' $field->form_property >";

		return $str;
	}

	public static function textarea ($default_value, $field_name, $form_name, $form_property = '', $placeholder = '', $class_name = '', $width = '', $height = '') {
		$str = <<<RRR
<textarea placeholder='$placeholder'  data-toggle='tooltip' title='$placeholder' name='$form_name' id='$field_name'  $form_property class='$class_name' style='width:$width;height:$height'  >$default_value</textarea>
RRR;

		return $str;
	}

    public static function bra_editor ($field_name, $default_value, $form_name, $width = '', $height = '', $tool_bar = 'full') {
        global $_W;
        $form_str = "<script  style='width:$width;height:$height' id='$field_name' name='$form_name' type='text/plain'>";
        $form_str .= $default_value;
        $form_str .= "</script>";
        $script_str = <<<EOF
<script>
require(['ueditor'],function (UE) {
    var editor = UE.getEditor('$field_name', {
        lang:'zh-cn',
        serverUrl: '/bra/annex/ueditor',
    });
});
</script>
EOF;

        $_W->bra_scripts[] = $script_str;
        return $form_str;
    }

	public static function layui_mutil_image_upload ($default_value, $form_name, $field_name , $number = 1, $exts = '', $accept = null, $form_data = '{}') {
		global $_W, $_GPC;
		$version = $_W['site']['config']['bra_suffix'];
		$number = $number ? $number : 1;
		$accept = $accept ? $accept : 'image/*';

		$exts = str_replace(',', '|', $exts);
		if (is_array($default_value)) {
			$file_ids = $default_value;
		} else {
			$file_ids = array_filter(explode(",", $default_value));
		}
		$form_str = "
<div class='weui-uploader__input-box'>
<div  class=\"weui-uploader__input needsclick layui_mutil_upload layui_mutil_upload_$field_name\" data-name='$form_name' >

</div></div>
        <ul class=\"bra-upload-list weui-uploader__files\" id=\"$field_name\">";
		foreach ($file_ids as $file_id) {
			$annex = new BraAnnex($file_id);
			if ($annex->annex) {
				$file = $annex->annex['old_data'];
				$src = $annex->get_url();

				if (strpos($file['filemime'], 'image') !== false) {
					$form_str .= "<li class='layui-upload-img weui-uploader__file'>
<img src='{$src}' bra-mini='view_image' alt='{$file['filename']}' class='layui-upload-img'>
<input type='hidden' value='{$file_id}' name='$form_name'>
<i class='icon close' onclick='remove_parent(this , \".layui-upload-img\")'></i>
</li>";
				} else {
					$form_str .= "<li class='layui-upload-img weui-uploader__file'>
附件
<input type='hidden' value='{$file_id}' name='$form_name'>
<i class='icon close' onclick='remove_parent(this , \".layui-upload-img\")'></i>
</li>";
				}
			}
		}
		$form_str .= "</ul>";
		if (is_weixin()) {
			$form_str .= <<<EOF
<script>
require(['layui'] , function(layui) {
    layui.config({ base: '/statics/packs/layui/libs/' , version : '$version'});
    layui.use(['bra_upload' , 'layer'], function(){
           layui.bra_upload.init_mutil_wx_upload('$field_name' , '$number');
    });
});
</script>
EOF;
		} else {
			$script_str = <<<EOF
    <script>
EOF;
            $script_str .= <<<EOF
    function remove_parent(obj , selector) {
        $(obj).parents(selector).remove();
    }
    require(['layui'] , function(layui) {
        layui.config({
         base: '/statics/packs/layui/libs/'
        });
        layui.use(['bra_upload' , 'layer'], function(){
             layui.bra_upload.init_mutil_upload(
                 '$field_name'  , '$number' , '$accept' , "$exts"  , {$form_data})
        });
    });

    </script>
EOF;
		}

		$_W->bra_scripts[] = $script_str;

       // dd($script_str);
		return $form_str;
	}

	public static function semantic_ajax_select ($model_id, $field_name, $form_name, $default_value, $base = [], $module_sign = 'bra_admin', $pk_key = 'id') {
		global $_W;
		$bra_m = D($model_id);
		$display_text = "";
		if (isset($base[$field_name]) && BraString::bra_isset($base[$field_name])) {
			try {
				$target = $bra_m->bra_one([$pk_key => $base[$field_name]]);
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
			$display_text = $default_value ? $default_value : "请输入关键字";
			$icon = "dropdown ";
		}
		$params = [];
		$params['model_id'] = $model_id;
		if (defined("BRA_ADMIN")) {
			$module = $module_sign;
			$action = !empty($bra_ajax_action) ? $bra_ajax_action : "list_item";
			$service_api_url = make_url($module . '/admin_api/' . $action, $params);
		} else {
			$md = $_W['bra_api_module'] ? $_W['bra_api_module'] : ROUTE_M;
			$service_api_url = make_url($md . '/web_api/list_item', $params);
		}
		$form_str = "";
		$form_str .= <<<EOF
 <div style='min-width:15em' id='$field_name' class=' ui fluid  search normal selection dropdown'>
  <input type='hidden' value='$default_value' name='$form_name'>
  <i class='icon $icon'></i>
  <div class='default text'>$display_text</div></div>
<script type='text/javascript'>
require([ 'semantic'] , function( semantic ) {
          $('#$field_name').dropdown({
                apiSettings: {
                  url: '$service_api_url' + '?q={query}' ,
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

</script>
EOF;

		return $form_str;
	}

	/**
	 * @param $model_id
	 * @param $field_name
	 * @param $form_name
	 * @param $default_value
	 * @param array $base
	 * @param string $name_key
	 * @param string $module_sign
	 * @param string $pk_key
	 * @return string
	 */
	public static function bra_multi_select ($model_id, $field_name, $form_name, $default_value, $name_key = '', $module_sign = '', $pk_key = 'id') {
		global $_W;
		$bra_m = D($model_id);
		$display_text = "请输入关键字";
		$default_ids = array_filter(explode(',', $default_value));
		if (!empty($default_ids)) {
			$donde[$pk_key] = ['IN', $default_ids];
			$targets = $bra_m->bra_where($donde)->list_item(true);
		}
		$params = [];
		$params['model_id'] = $model_id;
		if (!$module_sign) {
			$module_sign = $_W['bra_api_module'] ? $_W['bra_api_module'] : ROUTE_M;
		}
		$service_api_url = self::get_dropdown_api_url($module_sign, $params);
		$form_str = "";
		$form_str .= "<div id='$field_name'   class=\"SEMANTIC_AJAX_INPUT ui fluid multiple search selection dropdown\">";
		if (!empty($targets)) {
			$model_info = $bra_m->_TM;
			foreach ($targets as $target) {
				$display_text = '';
				if ($name_key) {
					$display_text = $target[$name_key] ?? '';
				} else {
					if ($targets && strpos($model_info['name_key'], "|") !== false) {
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
				}
				$form_str .= <<<EOF
<a class="ui label transition visible" data-value="{$target['id']}" style="display: inline-block !important;">{$display_text}<i class="delete icon"></i></a>
EOF;
			}
		}
		$form_str .= "<div class='default text'>$display_text</div>";
		$form_str .= "<input type=\"hidden\" value='$default_value' name=\"$form_name\">
  <i class=\"dropdown icon\"></i>";
		$form_str .= "</div>";
		$_W->bra_scripts[] = "
<script type='text/javascript'>
require([ 'semantic'] , function( semantic) {
          $('#$field_name').dropdown({
                apiSettings: {
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
</script>";

		return $form_str;
	}

	public static function get_dropdown_api_url ($module_sign, $params) {
		if (defined("BRA_ADMIN")) {
			$service_api_url = make_url('bra_admin/admin_api/list_item', $params);
		} else {
			$service_api_url = make_url($module_sign . '/web_api/list_item', $params);
		}

		return $service_api_url;
	}

	public static function bra_flow_upload ($field_name, $form_name, $default_value = '', $number = 1, $exts = '', $accept = '', $filesize = '', $keep_clear = false) {
		global $_W, $_GPC;
		$keep_clear = intval($keep_clear);
		$form_str = $template = $slot = '';
		$number = $number ? $number : 1;
		$exts = str_replace(',', '|', $exts);
		if (is_array($default_value)) {
			$file_ids = $default_value;
		} else {
			$file_ids = array_filter(explode(",", $default_value));
		}

		foreach ($file_ids as $file_id) {
			$annex = new BraAnnex($file_id);

			if ($annex->annex) {
				$file = $annex->annex['old_data'];
				$src = $annex->get_url();
				if ($file['file_type'] == 1) {
					$slot .= "<li class='bra-upload-img bra-uploader__file is-box is-relative'>
<img src='{$src}' bra-mini='view_image' alt='{$file['filename']}' class='bra-upload-img'>
<input type='hidden' value='{$file_id}' name='$form_name'>
<i class='bra-del flow-file-cancel is-absolute is-top is-right' onclick='remove_parent(this , \".bra-upload-img\")'></i>
</li>";
				} else {
					$slot .= "<li class='bra-upload-img bra-uploader__file is-box is-relative'>
附件
<input type='hidden' value='{$file_id}' name='$form_name'>
<i class='bra-del flow-file-cancel is-absolute is-top is-right' onclick='remove_parent(this , \".bra-upload-img\")'></i>
</li>";
				}
			}
		}
		if (!defined('bra_file_upload_item')) {
			$template = <<<BRA
<script type="text/template" id="bra_file_upload_item">

<li class="bra-uploader__file is-relative is-box" id="flow-file-{{uniqueIdentifier}}">
<img src="{{src}}" class="bra-upload-img img" bra-mini="view_image"/>
<span class="flow-file-name bra-file-name is-clipped"></span>
<span class="flow-file-size bra-file-size"></span>
<span class="flow-file-progress"></span>
<a href="" class="flow-file-download" target="_blank"></a>
<span class="flow-file-pause" style="display:none"> 暂停</span>
<span class="flow-file-resume" style="display:none">继续</span>
<span class="bra-del flow-file-cancel is-absolute is-top is-right"></span>

<input type="hidden" value="{{data.id}}" name="{{form_name}}">
</li>
</script>
BRA;
			define('bra_file_upload_item', true);
		}
		$form_str .= <<<BRA
<div class="bra-cell is-paddingless">
	<div class="bra-cell__bd">
		<div class="br-uploader">
			<div class="bra-uploader__bd">
				<ul class="bra-uploader__files"   id="files_$field_name">
					{$slot}
				</ul>
				<div class="bra-uploader__input-box" >
					<div id="btn_$field_name" class="uploader" style="width: 100%;height: 100%;"></div>
				</div>
			</div>
		</div>
	</div>
</div>

<div class='bra-progress' id="bra-progress_$field_name">
    <div class="flow-progress">
        <table>
            <tr>
                <td width="100%"><div class="progress-container"><div class="progress-bar"></div></div></td>
                <td class="progress-text" nowrap="nowrap"></td>
            </tr>
        </table>
    </div>
</div>

BRA;
		$script = <<<BRA
<script type="text/javascript">
		function remove_parent(obj , selector) {
			$(obj).parents(selector).remove();
		}
        require(['jquery' , 'flow' , 'handlebars/handlebars.min', 'layer' ] , function ($ ,Flow , Handlebars , layer) {

            (function () {
                var r = new Flow({
                    target: '/bra/annex/chunk_upload',
                    chunkSize: 1024*1024,
                    testChunks: true ,
                    autoStart : true ,
                    maxUploads : parseInt("$number") ,
                    query:{
                        keep_clear: "{$keep_clear}"
                    }
                });

                if (!r.support) {
                    return console.log('not supported flow'); // Flow.js isn't supported, fall back on a different method
                }

                r.assignBrowse($('#btn_$field_name'), false);
                // r.assignBrowse($('.flow-browse')[0]);
                // r.assignBrowse($('.flow-browse-image')[0], false, false, {accept: 'image/*'});

                // Handle file add event
                r.on('fileAdded', function(file){
                    if($("#files_$field_name li").length >= this.opts.maxUploads){
                        layer.msg('超出最大可上传数量!');
                        file.cancel();
                        return false;
                    }
                	var arr = Object.keys(this.files);
                    if(arr.length >= this.opts.maxUploads){
                       layer.msg('超出最大可上传数量!');
                       file.cancel();
                       return false;
                    }

                    $('#flow-files_$field_name').show(); // Show progress bar

                    var tpl = $("#bra_file_upload_item").html();
                    var template = Handlebars.compile(tpl);

                    var add_event = function(file) {
                      	file.form_name= "$form_name";
						var html = template(file);

						$('#files_$field_name').append( html);
						var id_self = $('#flow-file-'+file.uniqueIdentifier);
						id_self.find('.flow-file-name').text(file.name);
						id_self.find('.flow-file-size').text(readablizeBytes(file.size));
						id_self.find('.flow-file-download').hide();
						id_self.find('.flow-file-pause').on('click', function () {
							file.pause();
							id_self.find('.flow-file-pause').hide();
							id_self.find('.flow-file-resume').show();
						});
						id_self.find('.flow-file-resume').on('click', function () {
							file.resume();
							id_self.find('.flow-file-pause').show();
							id_self.find('.flow-file-resume').hide();
						});
						id_self.find('.flow-file-cancel').on('click', function () {
							file.cancel();
							id_self.remove();
						});

						if(!file.src){
							id_self.find('img').remove();
						}
                    };   // Add the file to the list
                    const reader = new FileReader();
                    reader.addEventListener('load' , function() {
						file.src= this.result;
						add_event(file);
                    });

                    if(file.file.type.indexOf('image') === -1){
						add_event(file);
                    }else{
                    	reader.readAsDataURL(file.file);
                    }

                });
                r.on('filesSubmitted', function(file) {

                   if(this.opts.autoStart){
                       r.upload();
                   }

                });
                r.on('complete', function(){ // Hide pause/resume when the upload has completed
                    $('.flow-progress .progress-resume-link, .flow-progress .progress-pause-link').hide();
                });
                r.on('fileSuccess', function(file,message){
                		var msg = JSON.parse(message);
                		var id_self = $('#flow-file-'+file.uniqueIdentifier + " input").val(msg.id);

                    console.log(file,msg)
                    // Reflect that the file upload has completed
                    //  id_self.find('.flow-file-progress').text('(completed)');
                    // id_self.find('.flow-file-pause, .flow-file-resume').remove();
                    // id_self.find('.flow-file-download').attr('href', '/download/' + file.uniqueIdentifier).show();
                });
                r.on('fileError', function(file, message){
                    // Reflect that the file upload has resulted in error
                    $('.flow-file-'+file.uniqueIdentifier+' .flow-file-progress').html('(file could not be uploaded: '+message+')');
                });
                r.on('fileProgress', function(file){
                    // Handle progress for both the file and the overall upload
                    $('.flow-file-'+file.uniqueIdentifier+' .flow-file-progress')
                        .html(Math.floor(file.progress()*100) + '% '
                            + readablizeBytes(file.averageSpeed) + '/s '
                            + secondsToStr(file.timeRemaining()) + ' remaining') ;
                    $('.progress-bar').css({width:Math.floor(r.progress()*100) + '%'});
                });
                r.on('uploadStart', function(){
                    // Show pause, hide resume
                    $('.flow-progress .progress-resume-link').hide();
                    $('.flow-progress .progress-pause-link').show();
                });
                r.on('catchAll', function() {
                 //   console.log.apply(console, arguments);
                });
            })();

            function readablizeBytes(bytes) {
                var s = ['bytes', 'kB', 'MB', 'GB', 'TB', 'PB'];
                var e = Math.floor(Math.log(bytes) / Math.log(1024));
                return (bytes / Math.pow(1024, e)).toFixed(2) + " " + s[e];
            }
            function secondsToStr (temp) {
                function numberEnding (number) {
                    return (number > 1) ? 's' : '';
                }
                var years = Math.floor(temp / 31536000);
                if (years) {
                    return years + ' year' + numberEnding(years);
                }
                var days = Math.floor((temp %= 31536000) / 86400);
                if (days) {
                    return days + ' day' + numberEnding(days);
                }
                var hours = Math.floor((temp %= 86400) / 3600);
                if (hours) {
                    return hours + ' hour' + numberEnding(hours);
                }
                var minutes = Math.floor((temp %= 3600) / 60);
                if (minutes) {
                    return minutes + ' minute' + numberEnding(minutes);
                }
                var seconds = temp % 60;
                return seconds + ' second' + numberEnding(seconds);
            }
        });
</script>
BRA;
		$_W->bra_scripts [] = $script;
		$_W->bra_templates[] = $template;

		return $form_str;
	}
}
