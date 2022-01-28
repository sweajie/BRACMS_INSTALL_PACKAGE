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
namespace Bra\core\objects;

use ArrayAccess;
use Bra\core\field_types\FieldType;
use Bra\core\utils\BraException;

class BraField implements ArrayAccess {
    public $field_type_name;
    public $default_value;
    public $is_multiple;
    public $accept;
    public $mode;
    public $model;
    public $slug;
    public $data_source_config;
    public $where;
    public $field_name;
    public $form_name;
    public $module;
    public $parentid_key;
    public $data_source_type;
    public $options;
    public $class_name;
    public $form_property;
    public $form_str;
    public $multiple;
    public $filter_where;
    public $with_form = true;
    public $update = false;
    public $tips = '';
    public $width = '';
    public $height = '';
    public $max_count = '';
    public $pk_key = '';
    public $name_key = '';
    public $replace = '';
    /**
     * @var $bra_form FieldType
     */
    public $bra_form;
    /**
     * @var array
     */
    public $inputs;

    /**
     * Field constructor.
     * @param array $config_arr
     * @param array $inputs
     */
    public function __construct ($config_arr , $inputs = []) {
        if (is_array($config_arr)) {
            $this->set_config($config_arr);
        }
        $this->form_name = $config_arr['field_name'];

        if (!empty($config_arr['form_group'])) {
            $this->form_name = $config_arr['form_group'] . "[" . $this->form_name . "]";
        }
        if (isset($config_arr['is_multiple'])) {
            $this->form_name .= (int)$config_arr['is_multiple'] <= 1 ? "" : "[]";
        }

        if($this->with_form){
            $field_type_name = $this->field_type_name;
            $class_name = "\\Bra\\core\\field_types\\$field_type_name";
            if(class_exists($class_name)){
                $this->bra_form = new $class_name($this);
            }else{
				if($field_type_name){
					throw new BraException("对不起 , 处理器不存在!".$class_name);
				}

            }
        }

        $this->class_name = $this->class_name ? str_replace('layui' , 'bra' , $this->class_name) :  'bra-input';
        $this->inputs = $inputs;
    }

    public function build_form () {
        return $this->form_str = $this->bra_form->out_put_form();
    }

    /**
     * set with config
     * @param array $config_arr
     */
    public function set_config ($config_arr = []) {
        if (is_array($config_arr)) {
            foreach ($config_arr as $k => $v) {
                $this->set($k, $v);
            }
        }
    }

    /**
     * set property
     * @param $k
     * @param $v
     */
    public function set ($k, $v) {
        $this->$k = $v;
    }

    public function process_filter () {
        $this->build_filter_form($this->inputs);
        $this->build_where($this->default_value);
    }

    public function build_filter_form ($inputs) {
        $this->form_str = $this->bra_form->out_put_filter_form($inputs);
    }

    public function build_where ($default_value) {
        $_where = [];
        if (!BraString::bra_isset($default_value)) {
            return $this->filter_where = $_where; // no data  no filter
        }
        switch ($this->mode) {
            case "layui_checkbox": // checkbox filter
                $is_multi = 1;
                break;
            case "text": // input filter
                $is_multi = 2;
                break;
            case "input_date": // the date filter
                $is_multi = 3;
                break;
            default :  //
                $is_multi = 0;
        }
        //根据多选的模式
        switch ($is_multi) {

            case 0:
                $_where[$this->field_name] = $default_value;
                if ($this->data_source_type == "model" && D($this->data_source_config)->field_exits('parent_id')&& D($this->data_source_config)->field_exits('arrchild')) {

                    $cur = D($this->data_source_config)->get_item($default_value);
                    if (BraString::bra_isset($cur['arrchild'])) {
                        $_where[$this->field_name] = ['IN', $cur['arrchild']];
                    }
                }
                break;
            case 1:
                if (is_array($default_value)) {
                    $nwe_arr = [];
                    foreach ($default_value as $v) {
                        if (BraString::bra_isset($v)) {
                            $nwe_arr[] = '%,' . $v . ',%';
                        }
                    }
                    $_where[$this->field_name] = ['LIKE', $nwe_arr];
                } else {
                    $_where[$this->field_name] = ['LIKE', '%,' . $default_value . ',%'];
                }
                break;
            case 2:
                $_where[$this->field_name] = ['LIKE', '%' . $default_value . '%'];
                break;
            case 3:
                $val = explode(' - ', $default_value);
                $_where[$this->field_name] = ['BET', $val];
                break;
        }

        return $this->filter_where = $_where;
    }

    /**
     * Whether a offset exists
     * @link https://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return bool true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists ($offset) {
        return $this->$offset;
    }

    /**
     * Offset to retrieve
     * @link https://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet ($offset) {
        return $this->$offset ?? null;
    }

    /**
     * Offset to set
     * @link https://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet ($offset, $value) {
    }

    /**
     * Offset to unset
     * @link https://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset ($offset) {
    }
}
