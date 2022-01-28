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

use Bra\core\data_source\DataInput;
use Bra\core\data_source\DataSource;
use Bra\core\objects\BraField;

class FieldType {

    /**
     * @var BraField
     */
    public BraField $field;
    public string $scope;

    public function __construct (BraField $field, $scope = '') {
        $this->field = $field;
        $this->scope = $scope;
    }

    /**
     * 表单输出
     * @param $base
     * @return mixed
     */
    public function out_put_form () {
        //load form data options
        $res = $this->form_data();

        $field_type = $this->field->bra_form;
		if($this->field->field_name == 'thumb'){

		}
        if (method_exists($field_type, $this->field->mode)) {
            return call_user_func_array(array($field_type, $this->field->mode), array());
        }
        abort(403, 'Form Not Allowed!->' . $this->field->field_type_name. '@' . $this->field->mode);
    }

    /**
     * 表单数据
     * @param array $base
     * @param bool $render
     * @param bool $use_blank
     * @return array|mixed|null
     */
    public function form_data ( $render = false, $use_blank = false) {
        if($this->field->data_source_type){
            $source = new DataSource($this->field, $render, $use_blank);
            return $source->form_data;
        }else{
            return [];
        }
    }

    /**
     * @param $input
     * @param bool $render
     * @return string
     */
    public function process_model_output ($input) {
        $out_put = $input;
        return $out_put = htmlspecialchars($out_put);
    }

    /**
     * @param $input
     * @param $base
     * @return string
     */
    public function process_model_input () {

        $source = new DataInput($this->field  , $this->field->mode);

        return  $source->input;
    }

    /**
     * 筛选表单
     * @param $base
     * @return mixed
     */
    public function out_put_filter_form ($base) {

        if($this->field->mode){
            $this->form_data();
            $field_type = $this->field->bra_form;
            if (method_exists($field_type, $this->field->mode . "_filter")) {
                return call_user_func_array(array($field_type, $this->field->mode . "_filter"), array());
            }
            if (method_exists($field_type, $this->field->mode)) {
                return call_user_func_array(array($field_type, $this->field->mode), array());
            }
            abort(403, 'Filter Form Not Allowed!->' . $this->field->field_type_name. '@' . $this->field->mode);
        }else{
            return  '';
        }

    }

}
