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

class bra_jingtai extends FieldType {
    public function bra_fields() {
        $field = $this->field;
        $fields = collect(D($field->data_source_config)->fields)->filter(function (& $item){
            if(!$item['slug']){
                return  false;
            }
            $opt_item['title'] = $item['slug'];
            $opt_item['id'] = $item['field_name'];
            $opt_item['asform'] = $item['asform'];

            $item = $opt_item;
            return $item['asform'];
        });

        if(strpos($field->form_name , '[]' ) === false){
            $field->form_name .= "[]";
        }

        return BraForms::checkbox($fields->toArray(), $field->default_value, $field->form_name, $field->field_name,  "id", "title",  $field->form_property, $field->class_name);
    }
    /**
     * 模板选择器
     */
    public function mhcms_tpl_selector ($field) {
        $string = '';
        foreach ($field->form_data as $key => $value) {
            $checked = trim($field->default_value) == trim($value[$field->pk_key]) ? 'checked' : '';
            $string .= '<label class="helper_label" >';
            $string .= '<input type="radio" name="' . $field->form_name . '" ' . $field->form_property . ' id="' . $field->form_name . '_' . new_html_special_chars($key) . '" ' . $checked . ' value="' . $value[$field->pk_key] . '" title="' . $value[$field->name_key] . '"> ';
            $string .= '</label>';
        }

        return $string;
    }

    public function bra_popup_selector_filter () {
        $field = $this->field;
        return BraForms::semantic_ajax_select($field->data_source_config, $field->field_name, $field->form_name, $field->default_value);
    }

    public function bra_popup_selector () {
        global $_W;
        $field = $this->field;
        $mapping = array_merge($_W['mapping'], $field->inputs);
        $bra_m = D($field->data_source_config);
        $module_id = $bra_m->_TM['module_id'];
        $module = module_exist($module_id);
        $string = '';
        if ($module) {
            $where = [];
            if ($field->where) {
                $wheres = explode("\n", $field->where);
                foreach ($wheres as $__where) {
                    $__where = trim($__where);
                    $__where = BraString::parse_param_str($__where, $mapping);
                    $_where_data = explode('$', $__where);
                    if (BraString::bra_isset($_where_data[2])) {
                        if ($_where_data[1] == '=') {
                            $where[$_where_data[0]] = $_where_data[2];
                        } else {
                            $where[$_where_data[0]] = [$_where_data[1], $_where_data[2]];
                        }
                    }
                }
            }
            $params = array_merge($where, [
                'field_name' => $field->field_name,
                'max_select' => max((int)$field->is_multiple, 1)
            ]);
            $module_sign = $module['module_sign'];
            $service = make_url($module_sign . '/admin_api/list_' . $field->data_source_config, $params);
            $html = '';
            if ($field->default_value) {
                try {
                    $item = $bra_m->get_item($field->default_value);
                    $html = $item[$field->name_key];
                } catch (Exception $e) {
                }
            }
            $string .= "<div id='{$field->field_name}_wrapper' class='ui labeled button' >";
            $string .= "<div class=\"ui button\" data-id='{$field->field_name}_html'>$html</div>";
            $string .= "<input  type='hidden' name='{$field->form_name}' " . $field->form_property . ' data-id="' . $field->field_name . '_val" ' . ' value="' . $field->default_value . '" title="' . '"> ';
            $string .= "<a class=\"ui basic label\" data-service='$service' data-model='zhibo_cate' data-field_name='$field->field_name'  bra-mini=\"icon_form_picker\" mode=\"text\">选择{$field->slug}
                </a></div>";
        }

        return $string;
    }
    /**
     * 图标选择器
     */
    public function mhcms_icon_selector () {
        $field = $this->field;
        $service = make_url('bra_admin/admin_api/list_icon',
            [
                'field_name' => $field->field_name,
                'max_select' => max((int)$field->is_multiple, 1),
                'pick_type' => 'icon'
            ]
        );
        $html = '';
        if ($field->default_value) {
            try {
                $item = D('icon')->get_item($field->default_value);
                if ($item['font_type'] == "图片图标") {
                    $html = "<img src='{$item['annex'][0]['url']}' style='max-height:25px' />";
                } else {
                    $html = $item['icon'];
                }
            } catch (Exception $e) {

            }
        }
        $string = <<<EOF
<div id='{$field->field_name}_wrapper' class='control labeled button' >
<div class="bra-btn has-addons" data-id='{$field->field_name}_html'>$html</div>
<input  type='hidden' name="{$field->form_name}" $field->form_property  data-id="{$field->field_name}_val" value="$field->default_value">
<a class="bra-btn" data-service='$service' data-model='zhibo_cate' data-field_name='$field->field_name'  bra-mini="icon_form_picker" mode="text">
    选择{$field->slug}
</a>
</div>
EOF;

        return $string;
    }

    public function process_model_output ($input) {
        $source = new DataOutput($this->field, $input, $this->field->mode);

        return $source->out_put;
    }

    public function icon_input_filter () {
        return BraForms::text($this->field);
    }

    public function process_model_output_old ($input, &$base) {
        global $_W;
        $field = $this->field;
        $out_put = $input;
        switch ($this->field->mode) {
            case "mhcms_icon_selector":
                $html = '';
                try {
                    $item = D('icon')->get_item($input);
                    if ($item['old_data']['font_type'] == 2) {
                        $html = "<img class='img' src='{$item['annex'][0]['url']}'  />";
                    } else {
                        $html = $item['icon'];
                    }
                } catch (Exception $e) {
                }
                $out_put = $html;
                break;
            case "icon_input":
                $out_put = "<i class='$input'></i>";
                break;
            case "bra_node":
                $bra_m = D($base['model_id']);
                $out_put = $bra_m->get_item($base['item_id']);
                $out_put = $out_put[$bra_m->_TM['name_key']];
                break;
            case "bra_json":
                $out_put = $out_put ? $out_put : "{}";
                $out_put = json_decode($out_put, 1);
                break;
            case 'bra_popup_selector':
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
                $mapping = $_W['mapping'] ?? [];
                //$mapping['cate'] = $field->module . "_cate";
                $field->data_source_config = BraString::parse_param_str($field->data_source_config, $mapping);
                $bra_model = D($field->data_source_config);
                $TP_M = $bra_model->_TM;
                if ($TP_M) {
                    $field->pk_key = $field->pk_key ? $field->pk_key : $TP_M->id_key;
                    $field->name_key = $field->name_key ? $field->name_key : $TP_M->name_key;
                    if (!$field->pk_key) {
                        die("ID KEY 2:" . $field->field_name . ' - ' . $field->data_source_config);
                    }
                    if (!$field->name_key) {
                        die("name_key KEY 2:" . $field->field_name . ' - ' . $field->data_source_config);
                    }
                    if (strpos($field->name_key, '|') !== false) {
                        $name_keys = explode('|', $field->name_key);
                        foreach ($name_keys as $name_key) {
                            $field->name_key = $name_key;
                            if ($out_put[$field->name_key]) {
                                break;//$out_put = $out_put[$name_key];break;
                            }
                        }
                    }
                    $out_put = $bra_model->_m->whereIn($field->pk_key, $ids)->select();
                }
                if (defined("BRA_ADMIN") || !$field->is_multiple) {
                    $out = "";
                    foreach ($out_put as $k => $item) {
                        if ($out) {
                            $out .= " , ";
                        }
                        $out .= $item[$field->name_key];
                    }

                    return $out;
                } else {
                    $out = [];
                    foreach ($out_put as $k => $item) {
                        $out[] = $item[$field->name_key];
                    }

                    return $out;
                }
                break;
            default:
                if (defined("BRA_ADMIN")) {
                    $out_put = $input;
                } else {
                    $out_put = $input;
                }
        }

        return $out_put;
    }

    public function bra_node ($input, $base) {
    }

    public function bra_node_filter () {
        $field = $this->field;
        return BraForms::text($field);
    }

}
