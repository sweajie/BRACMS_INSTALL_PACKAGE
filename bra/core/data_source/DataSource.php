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

use Bra\core\facades\BraCache;
use Bra\core\objects\BraArray;
use Bra\core\objects\BraField;
use Bra\core\objects\BraString;
use Bra\core\objects\BraTheme;

class DataSource {
    public $source_name = '';
    public $source = '';
    public $form_data = '';
    private $field;
    private $mapping;
    private $render;
    private $use_blank;

    public function __construct (BraField $field, $render, $use_blank) {
        global $_W, $_GPC;
        //
        $this->field = $field;
        $this->llave = $field->model_id . '_' . (int)$render . (int)$use_blank . $field->field_name . "_form_data_" . $_W['site']['id'];
        $this->source_name = $source_name = 'ds_' . $field->data_source_type;
        if (is_callable(DataSource::class, $source_name)) {
            $this->mapping = array_merge($_W['mapping'] ?? [], $_GPC);
            $this->$source_name();
            $this->form_data = $this->field->form_data;
            $this->render = $render;
            $this->use_blank = $use_blank;
        } else {
            abort(403, $this->field->field_type_name . "@" . $source_name);
        }
    }

    public function ds_node () {
        $field = $this->field;
        $field->form_data = [];
    }

    public function ds_model () {
        global $_W , $_GPC;
		$field = $this->field;

		if($field->mode == "semantic_ajax_select"){
			return $field->form_data = [];
		}

		$mapping = $this->mapping;
		$where = [];
		if ($field->where) {
			$wheres = explode("\n", $field->where);
			foreach ($wheres as $__where) {
				$__where = trim($__where);
				$__where = BraString::parse_param_str($__where, $mapping);
				$_where_data = explode('$', $__where);
				if (BraString::bra_isset($_where_data[2])) {
					$where[$_where_data[0]] = [$_where_data[1], $_where_data[2]];
				}
			}
			$this->llave .= BraArray::md5_array(($where));
		}
		$result = BraCache::get_cache($this->llave);
		$field->data_source_config = BraString::parse_param_str($field->data_source_config, $mapping);
		if (empty($field->data_source_config)) {
			$result = [];
		}

		$bra_model = D($field->data_source_config ,'' , true);
        if($field->mode == "link_select"){
			$field->form_data = $bra_model->load_r_tree_options();
		}else{


			$field->pk_key = $field->pk_key ? $field->pk_key : $bra_model->_TM['id_key'];
			$field->name_key = $field->name_key ? $field->name_key : $bra_model->_TM['name_key'];
			$field->parentid_key = $field->parentid_key ? $field->parentid_key : $bra_model->_TM['parent_id_key'];
			if (!$field->pk_key || !$field->name_key) {
				abort('500', "model " . $field->data_source_config . "'s id_key or name_key is empty");
			}
			if (!$result || $field->update) {
				if ($bra_model->with_site()->bra_where($where)->count() < 5000) {
					$order = $bra_model->field_exits('listorder') ? "listorder" : "id";
					$result = $bra_model->with_site()->bra_where($where)->orderBy($order, 'desc')->get();
					if (strpos($field->name_key, '|') !== false || strpos($field->name_key, '&') !== false) {
						foreach ($result as &$item) {
							$item = $bra_model->get_item($item[$field->pk_key]);
						}
					}
				} else {
					$result = [];
				}

				BraCache::set_cache($this->llave, $result);
			}
			if ($bra_model->field_exits('parent_id')) {
				$field->form_data = json_decode($result->toJson(), 1);
			} else {
				$field->form_data = $this->__clean_data($result);
			}
		}


    }

    public function __clean_data ($data) {
        $field = $this->field;
        $result_data = [];
        if ($data) {
            foreach ($data as $v) {
                if (is_object($v)) {
                    $v = (array)$v;
                }
                $_V = $v;
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
                $_V[$field->pk_key] = $v[$field->pk_key];
                switch ($type) {
                    case 'or':
                        foreach ($keys as $key) {
                            if ($v[$key]) {
                                $_V[$field->name_key] = $v['title'] = $v[$key];
                                break;
                            }
                        }
                        break;
                    case 'both':
                        $_V[$keys[0]] = "";
                        foreach ($keys as $key) {
                            if (strpos($key, ',') !== false) {
                                dump($key);
                            }
                            if ($v[$key]) {
                                $_V[$keys[0]] .= $v[$key] . " ";
                            }
                        }
                        break;
                    default:
                        $_V[$field->name_key] = $_V['title'] = $v[$field->name_key];
                }
                if (!empty($field->parentid_key)) {
                    $_V[$field->parentid_key] = $v['parent_id'] = (int)$v[$field->parentid_key];
                } else {
                    $_V[$field->parentid_key] = $v['parent_id'] = (int)"";
                }
                $result_data[$v[$field->pk_key]] = $_V;
            }

            return $result_data;
        } else {
            return $result_data = [];
        }
    }

    public function ds_diy_arr () {
        $field = $this->field;
        //自定义数组可以是 组织好的数组也可以是 一行一个的字符串
        $field->pk_key = $field->pk_key ? $field->pk_key : 'id';
        $field->name_key = $field->name_key ? $field->name_key : 'title';
        if (!is_array($field->data_source_config)) {
            $datas = explode("\n", $field->data_source_config);
        } else {
            $datas = $field->data_source_config;
        }
        if (!is_array($datas)) {
            $datas = [];
        }
        foreach ($datas as $data) {
            $_new_data = [];
            if (is_array($data)) {
                $_new_data = $data;
            } else {
                if (strpos($data, "|") !== false) {
                    $data = explode("|", $data);
                    $_new_data[$field->pk_key] = $data[0];
                    $_new_data[$field->name_key] = $data[1];
                } elseif (trim($data)) {
                    $_new_data = [];
                    $_new_data[$field->pk_key] = $data;
                    $_new_data[$field->name_key] = $data;
                }
            }
            $field->form_data[] = $_new_data;
        }
    }

    public function ds_bra_shared_options () {
        $field = $this->field;
        $org_field = clone $field;
        $conf = explode("@", $field->data_source_config);
        $conf__m = D($conf[0]);
        $conf_fields = $conf__m->fields;
        $conf_f = $conf_fields[$conf[1]];
        $field->set_config($conf_f);
        $field->set('mode', $org_field->mode);
        $field->set('default_value', $org_field->default_value);
        $field->model_id = $conf__m->_TM['id'];
        $this->ds_bra_options();
    }

    public function ds_bra_options () {
        $field = $this->field;
        $mapping = $this->mapping;
        $results = BraCache::get_cache($this->llave);
        if (!$results) {
            $bra_model = D($field->data_source_config);
            $where = [];
//manual where data
            if ($field->where) {
                $wheres = explode("\n", $field->where);
                foreach ($wheres as $__where) {
                    $__where = trim($__where);
                    $__where = BraString::parse_param_str($__where, $mapping);
                    $_where_data = explode('$', $__where);
                    $where[$_where_data[0]] = [$_where_data[1], $_where_data[2]];
                }
            }
            $where['field_name'] = $field->field_name;
            $where['model_id'] = D($field->model_id)->_TM['id'];
            if (isset($field->bind_cate) && $field->bind_cate && $field->inputs['cate_id']) {
                $where['cate_ids'] = ['like', "%,{$field->inputs['cate_id']},%"];
            }
            $field->pk_key = $bra_model->_TM['id_key'];
            $field->name_key = $bra_model->_TM['name_key'];
            $results = $bra_model->with_site()->bra_where($where)->list_item($this->render);
            BraCache::set_cache($this->llave, $results);
        } else {
            $bra_model = D($field->data_source_config);
            $field->pk_key = $bra_model->_TM['id_key'];
            $field->name_key = $bra_model->_TM['name_key'];
        }
        $field->form_data = $this->__clean_data($results);
    }

    public function ds_bra_client () {
        $field = $this->field;
        $field->pk_key = $field->pk_key ? $field->pk_key : 'id';
        $field->name_key = $field->name_key ? $field->name_key : 'title';
        $field->form_data = BraArray::bra_client();
    }

    public function ds_text () {
        $field = $this->field;
        $field->form_data = [];
    }

    public function ds_bra_sys_set () {
        $field = $this->field;
        $field->pk_key = $field->pk_key ? $field->pk_key : 'id';
        $field->name_key = $field->name_key ? $field->name_key : 'title';
        $conf = explode("@", $field->data_source_config);
        $datas = config($conf[0]);
        if ($conf[1]) {
            $form_data = $datas[$conf[1]];
        } else {
            $form_data = $datas;
        }
        $field->form_data = $this->__clean_data($form_data);
    }

    public function bra_admin_admin_user_withdraw () {
    }

    public function ds_theme () {
        $field = $this->field;
        $themes = BraTheme::get_module_themes_list($field->module);
        $field->form_data = $this->__clean_data($themes);
    }

    public function ds_file () {
    }

	public function ds_bra_config () {

		$field = $this->field;
		$field->form_data = config($field->data_source_config);
	}
}
