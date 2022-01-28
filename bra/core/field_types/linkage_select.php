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
namespace app\bra\forms;

use app\common\util\Tree2;
use think\Cache;

class linkage_select extends Form
{

    /**
     * single_select form
     * @param Field $field
     * @return string the generated form
     */
    public function select(Field $field, $base)
    {
        $properties = [];
        $properties['mini'] = 'linkage_select';
        $properties['lay-ignore'] = 'linkage_select';
        $field->class_name .= ' top_linkage_select ';
        $properties['data-service'] = $service = U('core/admin_service/linkage_list_item');
        $properties['data-target_field'] = $field->target_field;
        $properties['data-target_element'] = $field->target_field;
        $properties['data-linkage_group'] = $field->linkage_group;
        $properties['data-from_field'] = $field->field_name;
        $properties['data-current_model_id'] = $field->model_id;
        $properties['data-default_value'] = $field->default_value;

        $field->form_property .= self::gen_mhcms_property($properties);

        $main_select = Forms::select($field);

        $main_select .= <<<EOF
        
        <script>
        require(['mhcms'] , function(mhcms) {
            mhcms.mhcms_element_linkage_select($('#{$field->field_name}'));
        });
        </script>

EOF;

        return $main_select;
    }

    public function sub_select($field)
    {
        $properties = [];
        $properties['mini'] = 'linkage_select';
        $properties['lay-ignore'] = 'linkage_select';
        $field->class_name .= ' sub_linkage_select ' . $field->linkage_group;
        $properties['data-service'] = $service = U('core/admin_service/linkage_list_item');
        $properties['data-target_field'] = $field->target_field;
        $properties['data-target_element'] = $field->target_field;
        $properties['data-linkage_group'] = $field->linkage_group;
        $properties['data-from_field'] = $field->field_name;
        $properties['data-current_model_id'] = $field->model_id;
        $properties['data-default_value'] = $field->default_value;
        $field->form_property .= self::gen_mhcms_property($properties);
        $main_select = Forms::select($field);
        return $main_select;
    }

    public function process_model_output($input, &$base)
    {
        $out_put = $input;
        $field = $this->field;

        switch ($field->data_source_type) {
            case "linkage":
                $result = \think\Cache::get("linkage/$field->data_source_config");
                //$field = NodeFields::get($field->id);
                $out_put = $result['data'][$input];
                $out_put = $out_put['name'];
                break;
            case 'model' :
                $model = conjunto_modelo($field->data_source_config);
                $model_info = $model->model_info;
                if (empty($model_info['id_key'])) {
                    die(" id_key" . $model_info['model_name']);
                }
                if (!$field->pk_key) {
                    $field->pk_key = $model_info['id_key'];
                }

                if (!$field->name_key) {
                    $field->name_key = $model_info['name_key'];
                }
                $out_put = $model->where([$model_info['id_key'] => $input])->find();
                $out_put = $out_put[$field->name_key];
                break;
            case 'sub_model' :
                $out_put = '';
                $model = conjunto_modelo($field->data_source_config);
                $model_info = $model->model_info;
                if (empty($model_info['id_key'])) {
                    die(" id_key" . $model_info['model_name']);
                }
                if (!$field->pk_key) {
                    $field->pk_key = $model_info['id_key'];
                }

                if (!$field->name_key) {
                    $field->name_key = $model_info['name_key'];
                }
                $out_put_rs = $model->where([$model_info['id_key'] => $input])->find();
                //$out_put = $out_put[$field->name_key];

                $type = '';
                if (strpos($field->name_key, '|') !== false) {
                    $keys = explode('|', $field->name_key);
                    $type = 'or';
                }

                if (strpos($field->name_key, ',') !== false) {
                    $keys = explode(',', $field->name_key);
                    $type = 'both';
                }


                if (strpos($field->name_key, '&') !== false) {
                    $keys = explode('&', $field->name_key);
                    $type = 'both';
                }

                switch ($type) {
                    case 'or':
                        foreach ($keys as $key) {
                            if ($out_put_rs[$key]) {
                                $out_put  = $out_put_rs[$key];
                                break;
                            }
                        }
                        break;
                    case 'both':
                        foreach ($keys as $key) {
                            if ($out_put_rs[$key]) {
                                $out_put .= $out_put_rs[$key] . " ";
                            }
                        }
                        break;


                    default:
                        $out_put =  $out_put_rs[$field->name_key];
                }
                break;
            case 'area' :
                $out_put = $input;
                break;
            case 'diy_arr' :
                foreach ($field->data_source_config as $k => $v) {
                    if (!empty($field->parentid_key)) {
                        $v['parent_id'] = $v[$field->parentid_key];
                    } else {
                        $v['parent_id'] = "";
                    }
                    $field->data_source_config[$k] = $v;
                }
                $out_put = $field->data_source_config;
                break;
            case 'sub_cate_id' :
                $out_put = D($field->data_source_config)->find([$field->pk_key => $input]);
                return $out_put[$field->name_key];
                break;
        }
        return $out_put;
    }

    public static function gen_mhcms_property($properties)
    {
        $ret_property = "";
        foreach ($properties as $k => $property) {
            if ($property) {
                $ret_property .= " " . $k . "='" . $property . "' ";
            } else {
                $ret_property .= " " . $k . " ";
            }
        }
        return $ret_property;
    }
}
