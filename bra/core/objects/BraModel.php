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

use Bra\core\facades\BraCache;
use Bra\core\models\Models;
use Bra\core\utils\BraException;
use Bra\core\utils\BraTree;
use Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

/**
 * Class BraModel
 * @mixin DB | Builder
 * @package App
 */
class BraModel {
	public $_TM;
	public $_m;
	public $scope;
	public $fields;

	public function __construct ($model_id, $scope = null, $update = false) {
		$this->model_id = $model_id;
		$llave = "bra_model_" . $model_id;
		$this->_TM = BraCache::get_cache($llave);
		if (!$this->_TM || $update) {
			if (is_numeric($model_id)) {
				$this->_TM = Models::find($model_id);
			} else {
				$this->_m = DB::table($model_id);
				$this->_TM = Models::where('table_name', $model_id)->first();
			}
			BraCache::set_cache($llave, $this->_TM);
		}
		if (!is_object($this->_TM) || get_class($this->_TM) == "__PHP_Incomplete_Class") {
			//rm cache
			BraCache::del_cache($llave);
			throw  new BraException('error' . 'object' . ' ' . $model_id);
			abort(403, 'error' . 'object' . ' ' . $model_id);
		}
		if (empty($this->_TM['table_name'])) {
			if (is_numeric($model_id)) {
				throw new BraException('bra_10006 布拉模型不存在:' . $this->model_id);
			} else {
				$this->_m = Db::table($model_id);
			}
		} else {
			$this->_m = Db::table($this->_TM['table_name']);
			$this->model_id = $this->_TM['table_name'];
			$this->get_fields($update);
		}
		$this->scope = $scope;
	}

	/**
	 * by Arthur , see core
	 * @param bool $update
	 * @return array|mixed
	 */
	public function get_fields ($update = false) {
		$fields = $this->get_all_fields($update);
		foreach ($fields as $k => &$field) {
			if (!empty($field['disabled'])) {
				unset($fields[$k]);
			}
		}
		$this->fields = $fields;

		//$bra_fields
		return $fields;
	}

	public function get_all_fields ($update = false) {
		if (!$this->_TM) {
			if (!empty($this->model_id)) {
				throw new BraException('BraModel model_id' . $this->model_id);
			} else {
				throw new BraException('BraModel model_id');
			}
		}
		$llave = "model_fields_" . $this->_TM['id'];
		$fields = Cache::get($llave);
		if (!$fields || $update) {
			$fields = $this->_TM->setting['fields'] ?? [];
			$set = config('bra_set');
			$models = $set['models'] ?? [];
			if (isset($models[$this->_TM->table_name])) {
				$m_m = $models[$this->_TM->table_name];
				$bra_fields = $m_m['Bra_Fields'];
				foreach ($fields as $k => &$field) {
					//ignore
					if (isset($m_m['Ocultar']) && in_array($k, $m_m['Ocultar'])) {
						unset($fields[$k]);
					}
					//show
					if (isset($m_m['Mostrar']) && !in_array($k, $m_m['Mostrar'])) {
						unset($fields[$k]);
					}
					// :) diy fields , u'll like it when u met with a mean customer.
					if (isset($bra_fields[$k]) && is_array($bra_fields[$k])) {
						foreach ($bra_fields[$k] as $kk => $v) {
							$field[$kk] = $v;
						}
					}
				}
				if (is_array($bra_fields)) {
					foreach ($bra_fields as $k => &$field) {
						if (empty($fields[$k])) {
							$fields[$k] = $field;
						}
					}
				}
			}
			BraArray::sort_by_val_with_key($fields);
			BraCache::set_cache($llave, $fields);
		}

		return $fields;
	}

	public function set_db ($db) {
		$this->_m = $db->from($this->_TM['table_name']);

		return $this;
	}

	public function set_db_m ($db) {
		$this->_m = $db;
		return $this;
	}

	public function __call ($name, $arguments) {
		$reset_arr = [
			'delete', 'truncate',
			'update', 'increment', 'decrement', 'insert', 'save', 'insertGetId', 'updateOrInsert', 'upsert', 'insertOrIgnore',
			'paginate', 'find', 'all', 'first', 'get', 'doesntExist', 'exists', 'value',
			'column', 'sum', 'count', 'avg', 'max', 'min', 'chunkById', 'chunk', 'pluck'
		];
		$rs = call_user_func_array(array($this->_m, $name), $arguments);
		if (in_array($name, $reset_arr)) {
			$this->reset();

			return $this->rs = $rs;
		} else {
			//from where orWhere whereExists whereColumn
			//reorder orderBy inRandomOrder latest groupBy
			//having whereNotNull whereNull  whereNotIn whereIn whereNotBetween whereJsonLength whereJsonContains
			//whereTime whereYear whereDay whereMonth whereDate
			//selectRaw groupByRaw havingRaw whereRaw selectRaw
			//rightJoin leftJoin join addSelect distinct skip  take offset latest inRandomOrder reorder havingBetween
			$this->rs = $rs;

			return $this;
		}
	}

	public function reset () {
		/**
		 * 'select' => [],
		 * 'from' => [],
		 * 'join' => [],
		 * 'where' => [],
		 * 'groupBy' => [],
		 * 'having' => [],
		 * 'order' => [],
		 * 'union' => [],
		 * 'unionOrder' => [],
		 */
		$this->_m->wheres = [];
		$this->_m->orders = [];
		$this->_m->offset = null;
		$this->_m->unionOffset = null;
		$this->_m->columns = null;
		$this->_m->wheres = null;
		$this->_m->with = [];
		$this->_m->groups = null;
		$this->_m->distinct = false;
		$this->_m->joins = null;
		$this->_m->havings = null;
		$this->_m->lock = null;
		$this->_m->aggregate = null;
		$this->_m->columns = null;
		$this->_m->limit = null;
		$this->resetBinding('select', []);
		$this->resetBinding('join', []);
		$this->resetBinding('where', []);
		$this->resetBinding('groupBy', []);
		$this->resetBinding('having', []);
		$this->resetBinding('order', []);
		$this->resetBinding('union', []);
		$this->resetBinding('unionOrder', []);

		return $this;
	}

	public function resetBinding ($opt_name, $val = []) {
		$this->_m->bindings[$opt_name] = [];
		$this->cleanBindings([$opt_name]);
	}

	public function load_tree ($pid = 0, $where = [], $name_key = 'title', $id_key = 'id', $module = '', $field = "*") {
		$order = $this->field_exits('listorder') ? 'listorder' : 'id';
		$lists = $this->bra_where($where)->select($field)->orderBy($order, 'desc')->get();
		$lists = json_decode(json_encode($lists), 1);
		$tree_data = BraTree::get_cate_tree($pid, $lists, $module, $id_key, $name_key);

		return $tree_data;
	}

	/**
	 * by Arthur 2016-10-16 , see log 1
	 * @param $field_name
	 * @return bool
	 */
	public function field_exits ($field_name) {
		static $table_fields;
		$model_id = $this->_TM['id'];
		$table_fields[$model_id] = $table_fields[$model_id] ?? [];
		if (!isset($table_fields[$model_id][$field_name])) {
			$py_exist = Schema::hasColumns($this->_TM['table_name'], [$field_name]);
			if (!$py_exist) {
				return $table_fields[$model_id][$field_name] = false;
			} else {
				$model_fields = $this->_TM->setting['fields'];
				if (isset($model_fields[$field_name]) && (isset($model_fields[$field_name]['disabled']) && $model_fields[$field_name]['disabled'] == 1)) {
					return $table_fields[$model_id][$field_name] = false;
				} else {
					return $table_fields[$model_id][$field_name] = true;
				}
			}
		} else {
			return $table_fields[$model_id][$field_name];
		}
	}

	public function bra_where ($where) {
		static $num;
		if (empty($where)) {
			return $this;
		}
		if (is_array($where)) {
			foreach ($where as $k => $val) {
				if (is_array($val)) {
					$val[0] = strtoupper($val[0]);
					$val[0] = str_replace(' ', '', $val[0]);
					switch ($val[0]) {
						case "TREE" :
							$tree_info = explode('@', $val[1]);
							$from = D($tree_info[0])->get_item($tree_info['1']);
							$if = explode(',', $from['arrchild']);
							$this->_m->whereIn($k, $if);
							break;
						case "COLUMN" :
							$this->_m->whereColumn($k, $val[1]);
							break;
						case "YEAR" :
							$this->_m->whereYear($k, $val[1]);
							break;
						case "MONTH" :
							$this->_m->whereMonth($k, $val[1]);
							break;
						case "DAY" :
							if (is_array($val[1])) {
								$this->_m->whereDate($k, $val[1][0], $val[1][1]);
							} else {
								$this->_m->whereDate($k, $val[1]);
							}
							break;
						case "PERIOD" :
							$this->_m->whereBetween($k, $val[1]);
							break;
						case "TIME" :
							if (is_array($val[1])) {
								$this->_m->whereTime($k, $val[1][0], $val[1][1]);
							} else {
								$this->_m->whereTime($k, $val[1]);
							}
							break;
						case "TIMEBET" :
							$this->_m->whereBetween($k, [$val[1][0], $val[1][1]]);
							break;
						case "!TIMEBET" :
							$this->_m->whereNotBetween($k, $val[1][0], $val[1][1]);
							break;
						case "<" :
							$this->_m->where($k, '<', $val[1]);
							break;
						case "<=" :
							$this->_m->where($k, '<=', $val[1]);
							break;
						case ">" :
							$this->_m->where($k, '>', $val[1]);
							break;
						case ">=" :
							$this->_m->where($k, '>=', $val[1]);
							break;
						case "LIKE" :
							if (is_array($val[1])) {
								foreach ($val[1] as $v) {
									$this->where($k, "like", $v);
								}
							} else {
								$this->where($k, "like", $val[1]);
							}
							break;
						case "!LIKE" :
							$this->_m->where($k, "not like", $val[1]);
							break;
						case "EXIST" :
							$if = $val[1];
							$this->_m->whereExists($if);
							break;
						case "!EXIST" :
							$if = $val[1];
							$this->_m->whereNotExists($if);
							break;
						case "IN" :
							$if = $val[1];
							if (!is_callable($if)) {
								if (!is_array($if)) {
									$if = explode(',', $if);
								}
								$this->_m->whereIn($k, $if);
							} else {
								//closure
								$this->_m->whereExists($if);
							}
							break;
						case "!IN" :
							$val = $val[1];
							if (!is_callable($val)) {
								if (!is_array($val)) {
									$val = explode(',', $val);
								}
								$this->_m->whereNotIn($k, $val);
							} else {
								//closure
								$this->_m->whereNotIn($k, $val);
							}
							break;
						case "BET" :
							$this->_m->whereBetween($k, $val[1]);
							break;
						case "!BET" :
							$this->_m->whereNotBetween($k, $val[1]);
							break;
						case "=" :
							$this->_m->where($k, '=', $val[1]);
							break;
						case "<>" :
						case "!=" :
							$this->_m->where($k, '<>', $val[1]);
							break;
						case "DIS<=" :
							$dis_data = $val[1];
							$this->_m->select("6371 * acos( cos(radians({$dis_data['lat']})) * cos( radians( lat )) * cos(radians( lng ) - radians({$dis_data['lng']})) + sin( radians({$dis_data['lat']}) ) * sin( radians( lat ) ) )  as `distance`")
								->having("`distance`<={$dis_data['distance']}");
							break;
						default:
							throw new BraException('未知的操作符' . $val[0]);
					}
				} else {
					// throw new BraException('Bra Parse Error :)');
					if (is_null($val)) {
						$this->whereNull($k);
					} else {
						$this->where($k, '=', $val);
					}
				}
			}
		} else {
			if (strpos($where, '$') !== false) {
				$where = explode('$', $where);
				$_where[$where[0]] = [$where[1], $where[2]];
				$num++;

				return $this->bra_where($_where);
			}
			if (is_numeric($where)) {
				return $this->bra_where(['id' => $where]);
			} else {
				throw new BraException('Bra Parse Error :)');
			}
		}

		return $this;
	}

	public function get_item ($id, $format = true, $config = [
		'update' => false,
		'with_old' => false
	]) {
		global $_W;
		$with_old =  $config['with_old'] ??  false;
		$cache_llave = "get_item_" . (int)$format . (int) $with_old . $id . '_' . $this->_TM['id'];
		$ret = BraCache::get_cache($cache_llave);
		if (!$ret || !empty($config['update'])) {
			$old_data = (array)$this->with_site()->select("*")->find($id);
			if (empty($old_data)) {
				return [];
			}
			if (module_exist("sites") && $this->field_exits("site_id")) {
				if (!isset($old_data["site_id"])) {
					throw  new Exception('丢失的站点权限');
				}
				if (module_exist('sites') && $old_data["site_id"] != $_W["site"]["id"] && $old_data["site_id"] != 0) {
					throw  new Exception('错误的站点权限');
				}
			}
			if ($format) {
				$ret = $this->datos_de_renderizado($old_data, $config['with_old'] ?? false);
			} else {
				$ret = $old_data;
			}
			if (!$ret) {
				throw  new Exception('渲染错误');
			}
			BraCache::set_cache($cache_llave, $ret);
		}

		return $ret;
	}

	public function with_site ($site_id = false) {
		global $_W;
		if (module_exist('sites') && $this->field_exits('site_id')) {
			$site_id = is_numeric($site_id) ? $site_id : $_W['site']['id'];

			return $this->bra_where(['site_id' => $site_id]);
		} else {
			return $this;
		}
	}

	public function datos_de_renderizado ($old_data, $with_old_data = false) {
		$item = $old_data;
		foreach ($this->fields as $field_config) {
			if (($field_config['field_name'] ?? false) && isset($old_data[$field_config['field_name']])) { // val was found
				$field_config['module_id'] = $this->_TM->module_id;
				$field_config['model_id'] = $this->_TM['id'];
				$field_name = $field_config['field_name'];
				$field = new BraField($field_config, $item);
				if ($field->bra_form) {
					$item[$field_name] = $field->bra_form->process_model_output($item[$field_name]);
				}
				if (is_string($item[$field_name]) && strlen($item[$field_name]) >= 15) {
					$item[$field_name] .= '';
				}
				if ($item[$field_name] == $old_data[$field_name] && strlen($old_data[$field_name]) >= 64) {
					unset($old_data[$field_name]);
				}
			}
		}
		if ($with_old_data) {
			$item['old_data'] = $old_data;
		}

		return $item;
	}

	public function load_tree_options ($pid = 0, $where = [], $name_key = 'title', $id_key = 'id', $parent_key = 'parent_id') {
		$t = new BraModelTree($this->model_id, $pid, $id_key, $name_key, $parent_key);

		return $t->load_tree($pid, $where);
	}

	public function load_r_tree_options ($pid = 0, $where = [], $name_key = 'title', $id_key = 'id', $parent_key = 'parent_id') {
		$t = new BraModelTree($this->model_id, $pid, $id_key, $name_key, $parent_key);

		return $t->load_tree_render($pid, $where);
	}

	public function bra_get () {
		$res = $this->get()->toJson();

		return json_decode($res, 1);
	}

	public function bra_one ($donde = [], $render = false, $config = []) {
		if (is_null($donde)) {
			abort(403, 'NULL');
		}
		if ($donde) {
			$item = (array)$this->bra_where($donde)->first();
		} else {
			$item = (array)$this->first();
		}
		if ($render && $item) {
			$item = $this->get_item($item['id'], $render, $config);
		}

		return $item;
	}

	public function order ($orders) {
		$orders = explode(',', $orders);
		foreach ($orders as $order) {
			$datas = explode(' ', $order);
			$this->_m->orderBy($datas[0], $datas[1] ?? 'asc');
		}

		return $this;
	}

	public function get_user_data ($user_id = "C_USER", $render = false) {
		$item = (array)$this->with_user($user_id)->first();
		if ($item && $render) {
			$item = $this->get_item($item['id']);
		}
		$this->reset();

		return $item;
	}

	public function with_user ($user_id = "C_USER", $field = 'user_id') {
		global $_W;
		if ($user_id === "C_USER") {
			$user_id = $_W['user']['id'];
		}
		if (is_numeric($user_id) && $user_id > 0) {
			return $this->bra_where([$field => $user_id]);
		} else {
			return false;
		}
	}

	public function list_user_data ($user_id = "C_USER", $render = false) {
		return (array)$this->with_user($user_id)->get();
	}

	/**
	 * @param $inputs
	 * @param $mapping
	 * @param array $config
	 * @return array
	 */
	public function gen_ajax_filter_forms ($inputs, $config = []) {
		$_where = $fields = [];
		$filter_fields = $this->get_filter_fields($config['hide'] ?? [], $config['show'] ?? []);
		foreach ($filter_fields as $k => $field) {
			$field['scope'] = $this->scope;
			$field['with_form'] = true;
			$field['default_value'] = $inputs[$k] ?? '';
			$field['model_id'] = $this->_TM['id'];
			$bra_field = new BraField($field, $inputs);
			$bra_field->process_filter();
			if ($bra_field->filter_where) {
				$_where = array_merge($_where, $bra_field->filter_where);
			}
			$field['form_str'] = $bra_field->form_str;
			$fields[] = $field;
		}

		return [$_where, $fields];
	}

	/**
	 * @param array $hide
	 * @param array $show
	 * @return mixed
	 */
	public function get_filter_fields ($hide = [], $show = []) {
		if (defined('BRA_ADMIN')) {
			$this->filter_fields = $this->get_admin_filter_fields($hide, $show);
		} else {
			$this->filter_fields = $this->get_admin_filter_fields($hide, $show);
		}

		return $this->filter_fields;
	}

	/*
		 * by yasuo  2018-05-21, see log 202
		 */
	public function get_admin_filter_fields ($hide_fields = [], $show_fields = [], $add_extra = []) {
		$new_field_list = $this->get_fields();
		if ($add_extra) {
			foreach ($add_extra as $field_name) {
				$new_field_list[$field_name]['is_filter'] = 1;
			}
		}
		foreach ($new_field_list as $k => $field) {
			$continue = false;
			if (isset($field['disabled']) && $field['disabled'] == 1) {
				$continue = 1;
			}
			if (in_array($k, $hide_fields) || empty($field['is_filter'])) {
				$continue = 1;
			}
			if ($show_fields && !in_array($k, $show_fields)) {
				$continue = 1;
			}
			if ($continue) {
				unset($new_field_list[$k]);
				continue;
			}
			$new_field_list[$k] = $field;
		}

		return $new_field_list;
	}

	public function get_admin_column_fields ($hide_fields = [], $show_fields = [], $add_extra = []) {
		$new_field_list = $this->get_fields();
		if ($add_extra) {
			foreach ($add_extra as $field_name) {
				$new_field_list[$field_name]['show_admin_colum'] = 1;
			}
		}
		$set = config('bra_set');
		$models = $set['models'] ?? [];
		if (isset($models[$this->_TM->table_name]['hide_admin_columns'])) {
			$m_m = $models[$this->_TM->table_name];
			$hide_fields = array_merge($hide_fields, $m_m['hide_admin_columns']);
		}
		foreach ($new_field_list as $k => $field) {
			$continue = false;
			if (isset($field['disabled']) && $field['disabled'] == 1) {
				$continue = 1;
			}
			if (in_array($k, $hide_fields) || empty($field['show_admin_colum'])) {
				$continue = 1;
			}
			if (in_array($k, $hide_fields) || empty($field['show_admin_colum'])) {
				$continue = 1;
			}
			if ($show_fields && !in_array($k, $show_fields)) {
				$continue = 1;
			}
			if ($continue) {
				unset($new_field_list[$k]);
				continue;
			}
			//$new_field_list[$k] = $field;
		}

		return $new_field_list;
	}

	public function list_item ($si_render = false, $config = [], $limit = 0) {
		if ($limit) {
			$this->limit($limit);
		}
		if (isset($config['collects'])) {
			$rs = $this->with_site()->get();
		} else {
			$rs = BraArray::to_array($this->with_site()->get());
			if ($si_render) {
				foreach ($rs as &$r) {
					$r = $this->get_item($r['id'], $si_render, $config);
					if (($config['with_user'] ?? false) && $config['with_user'] === true) {
						$r['user'] = D('users')->get_item($r['user_id']);
					}
				}
			}
		}

		return $rs;
	}

	public function list_obj($si_render = false, $config = [], $limit = 0) {
		if ($limit) {
			$this->limit($limit);
		}
		$rs = $this->with_site()->get();
		if (!$si_render) {
			return $rs;
		} else {
			$rs->transform(function ($item) use ($config, $si_render) {
				$item = (array)$item;
				$item = $this->get_item($item['id'], $si_render, $config);
				return $item;
			});
		}

		return $rs;
	}

	public function get_admin_publish_fields ($detail = [], $hide_fields = [], $show_fields = [], $form_group = "data") {
		$fields = $this->fields;
		foreach ($fields as $k => &$field_config) {
			if (empty($field_config['mode']) || in_array($k, $hide_fields) || empty($field_config['asform']) || !empty($field_config['disabled'])) {
				unset($fields[$k]);
				continue;
			}
			if (!empty($show_fields) && !in_array($k, $show_fields)) {
				unset($fields[$k]);
				continue;
			}
			if (isset($detail[$field_config['field_name']])) {
				$field_config['default_value'] = $detail[$field_config['field_name']];
			}
			$field_config['form_group'] = $form_group;
			$field_config['model_id'] = $this->_TM['id'];
			$_bra_field = new BraField($field_config, $detail);
			$field_config['form_str'] = $_bra_field->build_form();
		}
		$delete_keys = [];
		//todo reformat the field_group
		foreach ($fields as $k => &$field_config) {
			if (isset($field_config['bra_form_group']) && $field_config['bra_form_group']) {
				if ($field_config['is_group_header']) {
					$field_config['sub_fields'] = $this->get_group_fields($field_config['bra_form_group'], $fields);
				} else {
					$delete_keys[] = $k;
				}
			}
		}
		//todo reformat the form_group
		foreach ($delete_keys as $dk) {
			unset($fields[$dk]);
		}

		return $fields;
	}

	public function item_del ($item_id, $extra = [], $use_trans = false) {
		$res = $this->bra_where($extra)->find($item_id);
		if (!$res) {
			return bra_res(500, '删除失败 , 数据可能已经删除过了或者您无权删除改数据!', '', $extra);
		}
		$del_amount = $this->bra_where($item_id)->delete();
		if ($del_amount == 0) {
			return bra_res(501, '删除失败 , 数据可能已经删除过了!');
		}
		if ($use_trans && $del_amount != 1) {
			return bra_res(500, '删除失败 , 超过预期数量!');
		}

		return bra_res(1, '删除成功');
	}

	public function get_field_config ($donde, $field_name) {
		$detail = (array)$this->with_site()->bra_where($donde)->bra_one();

		if (!$detail) {
			$config = [];
		} else {
			$config = json_decode($detail[$field_name], 1);
		}

		return [$config, $detail];
	}

	public function set_field_config ($donde, $field_name, $data = []) {
		$detail = (array)$this->with_site()->bra_where($donde)->bra_one();
		$ist[$field_name] = json_encode($data);
		if ($detail) {
			return $this->item_edit($ist, $donde);
		} else {
			$ist = array_merge($ist, $donde);
			if ($id = $this->insertGetId($ist)) {
				$this->clear_item($id);

				return bra_res(1, '操作成功');
			} else {
				return bra_res(500, '更新数据失败');
			}
		}
	}

	/**
	 * @param array $base
	 * @param $where
	 * @param bool $merge_old 是否带着旧数据验证
	 * @param bool $strict
	 * @return mixed
	 * @throws Exception
	 */
	public function item_edit (array $base, $where, $merge_old = false, $strict = true , $update_time = true) {
		$fields = $this->fields;
		if (is_numeric($where)) {
			$old_data = (array)$this->bra_where($where)->first();
		} else {
			$old_data = (array)$this->bra_where($where)->first();
		}
		if (empty($old_data)) {
			throw new BraException('EDIT FAILED ,DATA NOT FOUND!');
			return bra_res(3, 'EDIT FAILED ,DATA NOT FOUND!' . $this->model_id , '' , $where);
		}
		// less field more speed
		if ($merge_old) {
			$base = array_merge($old_data, $base);
		}
		$base = array_map(function ($k) {
			return is_null($k) ? '' : $k;
		}, $base);
		$update_fields = [];
		foreach ($base as $key => $val) {
			if ($merge_old || isset($fields[$key])) {
				if (!($fields[$key]['disabled'] ?? false)) {
					$base[$key] = $this->process_model_input($fields[$key], $val, $base); //
					$update_fields[$key] = $fields[$key];
				}
			} else {
				if ($strict) {
					unset($base[$key]);
				}
			}
		}
		$fields = $update_fields;
		if (!$old_data || is_null($base) || !$base) {
			return bra_res(100005, '无法处理输入的数据,因为输入的数据为空!');
		}
		list($rules, $slugs) = $this->make_rule($fields, $base, false);
		/* 遍历每一个当前节点类型的字段进行验证 */
		$validator = Validator::make($base, $rules, $messages = [
			'required' => 'The :attribute  必须填写.',
			'regex' => 'The :attribute field is 不合法.',
		], $slugs);
		if ($validator->fails()) {
			return bra_res(500, $validator->errors()->first());
		}
		if ($this->field_exits('user_id')) {
			if ($this->_TM->as_user) {
				$this->_TM->amount_per_user = 1;
			}
			if ($this->_TM->amount_per_user != 0) {
				$test_where['user_id'] = $old_data['user_id'];
				$count = D($this->_TM->table_name)->bra_where($test_where)->count('*');
				if ($count > $this->_TM->amount_per_user) {
					return bra_res(0, "you can not post more than $count post in this channel! ");
				}
			}
		}
		if ($this->field_exits("create_at") && strpos($base['create_at'] ?? '', '0000-') !== false) {
			$base['create_at'] = date("Y-m-d H:i:s", time());
		}
		if ($update_time && $this->field_exits("update_at") && !$base['update_at']) {
			$base['update_at'] = date("Y-m-d H:i:s", time());
		}
		$res_id = $this->bra_where($where)->limit(1)->update($base);
		//rm cache
		$this->clear_item($where);
		if (!empty($this->_TM->is_index)) {
			//todo rm BraIndex
		}
		if ($res_id > 0) {
			return bra_res(1, "操作完成! ", '', array_merge($old_data, $base));
		} else {
			return bra_res(2, "O! 您没有改变任何数据 ", $where, $base);
		}
	}

	public function process_model_input ($config, $input, &$base) {
		$tag_name = $config['field_type_name'];
		if ($tag_name) {
			foreach ($config as $k => &$v) {
				if (is_string($v)) {
					if (strpos($v, "|") !== false) {
						$field_mode = explode("|", $v);
						$v = $field_mode[0];
					}
				}
				$config[$k] = $v;
			}
			$config['model_id'] = $this->_TM['id'];
			$config['default_value'] = $input;
			$field = new BraField($config, $base);

			return $field->bra_form->process_model_input();
		} else {
			return $input;
		}
	}

	public function make_rule ($fields, $copy_base, $check_unique = true) {
		$bad_words = explode(',', $_W['site']['config']['bad_words'] ?? '');
		$rules = $slugs = [];
		$i = 1;
		foreach ($fields as $field_name => $field) {
			$sub_rules = [];
			$slugs[$field_name] = $field['slug'] ?? '';
			$sub_rules[] = 'bail';
			//检测安全过滤
			if ($bad_words) {
				foreach ($bad_words as $bad_word) {
					if (!empty($bad_word)) {
						if ($copy_base[$field_name] && strpos($copy_base[$field_name], $bad_word) !== false) {
							return bra_res(2, "对不起 安全检验失败");
						}
					}
				}
			}
			$tmp_data = $copy_base;
			//enabled strlen < 1
			if (!empty($field['must_fill'])) {
				$sub_rules[] = "required";
			}
			//&& verify data with php regex
			if (isset($field['expression']) && $field['expression'] && $tmp_data[$field_name]) {
				$sub_rules[] = "regex:{$field['expression']}";
			}
			if ($check_unique && !empty($field['is_unique']) && $field['is_unique']) {
				$sub_rules[] = "unique:" . $this->_TM['table_name'];
			}
			if (isset($rules[$field_name]) && $rules[$field_name] == 'bail|') {
				unset($rules[$field_name]);
			}
			$rules[$field_name] = join('|', $sub_rules);
		}

		return [$rules, $slugs];
	}

	public function clear_item ($where) {
		$this->get_item($where, false, ['update' => true]);
		$this->get_item($where, false, ['update' => true, 'with_old' => true]);
		$this->get_item($where, true, ['update' => true]);
		$this->get_item($where, true, ['update' => true, 'with_old' => true]);
	}

	public function bra_query ($query) {
		return $this->bra_where($this->build_bra_query($query));
	}

	public function build_bra_query ($query) {
		global $_W;
		$donde = isset($query['where']) ? $query['where'] : [];
		$bra_int_fields = isset($query['bra_int_fields']) ? $query['bra_int_fields'] : [];// is_numeric fields
		$bra_like_fields = isset($query['bra_like_fields']) ? $query['bra_like_fields'] : []; // is_checkbox fields
		$bra_input_fields = isset($query['bra_input_fields']) ? $query['bra_input_fields'] : []; // text fields
		$compare_fields = isset($query['bra_compare_fields']) ? $query['bra_compare_fields'] : []; // compare  fields
		$tree_fields = isset($query['bra_tree_fields']) ? $query['bra_tree_fields'] : [];// tree  fields
		$bet_fields = isset($query['bra_bet_fields']) ? $query['bra_bet_fields'] : [];// tree  fields
		$closure_fields = isset($query['bra_closure_fields']) ? $query['bra_closure_fields'] : [];// tree  fields
		$bra_distance_fields = isset($query['bra_distance_fields']) ? $query['bra_distance_fields'] : [];
		if ("numeric fields") {
			/* default number field*/
			foreach ($this->fields as $k => $field) {
				if ($this->is_int_fields($field)) {
					if (!in_array($k, $bra_int_fields)) {
						$bra_int_fields[] = $k;
					}
				}
			}
			foreach ($bra_int_fields as $bra_int_field) {
				$Valor = $query[$bra_int_field] ?? '';
				if (BraString::bra_isset($Valor)) {
					if (is_array($Valor)) {
						$donde[$bra_int_field] = ['IN', $Valor];
					} else {
						$donde[$bra_int_field] = (int)$Valor;
					}
				}
			}
		}

		if ("bet fields") {
			/* default number field*/
			foreach ($this->fields as $k => $field) {
				if ($this->is_bet_fields($field)) {
					if (!in_array($k, $bet_fields)) {
						$bet_fields[] = $k;
					}
				}
			}
			#bet_fields search
			foreach ($bet_fields as $GustaCampo) {
				if (isset($query[$GustaCampo])) {
					$delimiter = strpos($query[$GustaCampo]," - ")===false ? "-" : " - ";
					$Valor = explode(" - ", $query[$GustaCampo]);
					if (isset($Valor) && count($Valor) == 2) {
						$donde[$GustaCampo] = ['BET', [$Valor[0], $Valor[1]]];
					}
				}
			}
		}

		foreach ($compare_fields as $compare_field) {
			$Valor = $query[$compare_field] ?? '';
			if (BraString::bra_isset($Valor)) {
				$donde[$compare_field] = $Valor;
			}
		}
		#field search
		if ($query["_bra_q"] ?? false) {
			$q_like_fields = [];
			foreach ($bra_input_fields as $k => $GustaCampo) {
				$Valor = $query[$GustaCampo];
				if (!$Valor) {
					$q_like_fields[] = $GustaCampo;
				}
			}
			if ($q_like_fields) {
				$_bra_q = $query["_bra_q"];
				$donde[join('|', $q_like_fields)] = ['LIKE', "%$_bra_q%"];
			}
		} else {
			foreach ($bra_input_fields as $k => $GustaCampo) {
				$Valor = $query[$GustaCampo];
				if ($Valor) {
					switch ($k) {
						case "BRA%" :
							$donde[$GustaCampo] = ['LIKE', "$Valor%"];
							break;
						case "%BRA" :
							$donde[$GustaCampo] = ['LIKE', "%$Valor"];
							break;
						default:
							$donde[$GustaCampo] = ['LIKE', "%$Valor%"];
					}
				}
			}
		}
		#checkbox search
		foreach ($bra_like_fields as $GustaCampo) {
			if (isset($query[$GustaCampo])) {
				$Valor = $query[$GustaCampo];
				if (isset($Valor) && $Valor) {
					$donde[$GustaCampo] = ['LIKE', "%,$Valor,%"];
				}
			}
		}
		#layered model data
		foreach ($tree_fields as $tree_field => $tree_field_model) {
			if (is_numeric($tree_field)) {
				throw new BraException('Tree fields index can not be number');
			}
			if ($query[$tree_field]) {
				$tree_item = (array)D($tree_field_model)->find($query[$tree_field]);
				$donde[$tree_field] = ['IN', $tree_item['arrchild']];
			}
		}
		#$closure_fields
		foreach ($closure_fields as $closure_field) {
			$donde[$closure_field] = $query[$closure_field];
		}
		foreach ($bra_distance_fields as $bra_distance_field => $op) {
			if ($query[$bra_distance_field]) {
				$donde[$bra_distance_field] = [$op, $query[$bra_distance_field]];
			}
		}

		return $donde;
	}

	private function is_int_fields ($field) {
		if (!empty($field['field_type_name']) && $field['field_type_name'] == 'select') {
			$modes = ['semantic_ajax_select', 'single_select'];
			if (in_array($field['mode'], $modes)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param $query
	 * @param bool $pre_process
	 * @param array $config
	 * @param false $u_cache
	 * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection
	 */
	public function list_bra_resource ($query, $pre_process = true, $config = [], $u_cache = false) {
		global $_W;
		$where = $this->build_bra_query($query);
		$order = $this->build_bra_order($query);
		$limit = $query['__limit'] ?? 15;
		$limit = min(500, $limit);
		$__pk = $query['__pk'] ?? 'id';
		$fields = [];
		if ($query['bra_distance_fields']) {
			$fields[] = "*";
			foreach ($query['bra_distance_fields'] as $field => $op) {
				$dis_data = $query[$field];
				$fields[] = "6371 * acos( cos(radians({$dis_data['lat']})) * cos( radians( `lat` )) * cos(radians( `lng` ) - radians({$dis_data['lng']})) + sin( radians({$dis_data['lat']}) ) * sin( radians( `lat` ) ) )  as `distance`";
			}
			$sets =collect([]);
			$sets->data = $this->with_site()->select(join(',', $fields))->bra_where($where)->bra_order($order)->limit($limit)->forPage((int)$query['page'])->get();
			$this->reset();
		} else {
			$fields = $config['fields'] ?? [$__pk];
			$sets = $this->with_site();

			$sets->selectRaw(join(',', $fields));
			$sets = $sets->with_site()->bra_where($where)->bra_order($order)->paginate(
				$limit, "*", "page", (int)$query['page']
			);
		}
		$model = $this;
		if ($pre_process) {
			$sets->transform(function ($item) use ($model, $pre_process, $config) {
				return $model->get_item($item->id, $pre_process, $config);
			});
		}

//        $sets['query'] = $query;
		return $sets;
	}

	/**
	 * safe order
	 * @param $query
	 * @return bool|string
	 */
	public function build_bra_order ($query) {
		$order = isset($query['order']) ? $query['order'] : false;
		if ($order) {
			$orders = explode(",", $order);
			foreach ($orders as $order) {
				$order = str_replace('-', ' ', $order);
				list($field, $type) = explode(' ', $order);
				$_order[] = [$field, $type ?? 'desc'];
			}
		} else {
			$_order[] = ['id', 'desc'];
		}

		return $_order ?? null;
	}

	public function bra_order ($orders) {
		foreach ($orders as $order) {
			$this->_m->orderBy($order[0], $order[1] ?? '');
		}

		return $this;
	}

	public function bra_foc (array $where, array $ext = [], $update = false) {
		$target = (array)$this->bra_where($where)->first();
		if (!$target) {
			$insert = array_merge($ext, $where);
			$add_res = $this->item_add($insert, false);
			if (is_error($add_res)) {
				$add_res['data'] = $insert;

				return bra_res(500, $add_res['msg'], '', $insert);
			} else {
				return $add_res;
			}
		} else {
			if ($update && $ext) {
				$res = $this->item_edit($ext, $where);
				if (is_error($res)) {
					return bra_res(500, $res['msg'], '', $target);
				} else {
					return $res;
				}
			} else {
				return bra_res(1, '', '', $target);
			}
		}
	}

	/**
	 * @param $base
	 * @param bool $strict
	 * @return mixed
	 */
	public function item_add ($base, $strict = true) {
		global $_W;
		if (empty($base)) {
			return bra_res(500, "对不起， 数据获取错误~!!!!", '', $base);
		}
		$base = array_filter($base, function ($item) {
			return $item !== null;
		});
		$copy_base = $org_base = $base;
		$amount_per_user = (int)$this->_TM->amount_per_user;
		if ($this->field_exits('user_id')) {
			if (!isset($copy_base['user_id'])) {
				$copy_base['user_id'] = $_W['user']['id'] ?? 0;
			}
			if ($this->_TM->as_user) {
				$amount_per_user = 1;
			}
			if ($copy_base['user_id'] && $amount_per_user != 0) {
				$test_where['user_id'] = $copy_base['user_id'];
				$count = $this->bra_where($test_where)->count();
				if ($count >= $amount_per_user) {
					return bra_res(500, "对不起，该用户已经超过最大允许的信息数量 $count!");
				}
			}
			if ($this->_TM->as_user == 1) {
				if (!$copy_base['user_id']) {
					return bra_res(500, "对不起，该信息后台增加时必须绑定用户!");
				}
			}
		}
		if ($this->_TM->as_user && !$copy_base['user_id']) {
			$ret['code'] = 1;
			$ret['msg'] = "无法识别的身份,请重新登录!!! ";

			return $ret;
		}
		// handle input for base fields here
		foreach ($copy_base as $key => $value) {
			if (!empty($this->fields[$key])) {
				$copy_base[$key] = $this->process_model_input($this->fields[$key], $value, $copy_base);
			} else {
				if ($strict) {
					unset($copy_base[$key]);
				}
			}
		}
		list($rules, $slugs) = $this->make_rule($this->fields, $copy_base);
		/* 遍历每一个当前节点类型的字段进行验证 */
		$validator = Validator::make($copy_base, $rules, $messages = [
			'required' => 'The :attribute 必须填写.',
			'regex' => 'The :attribute field is 不合法.',
		], $slugs);
		if ($validator->fails()) {
			return bra_res(500, $validator->errors()->first() , '' , $copy_base);
		}
		if ($this->field_exits('site_id')) {
			if ((!isset($base['site_id']) || empty($base['site_id']))) {
				$copy_base['site_id'] = (int)$_W['site']['id'];
			} else {
				$copy_base['site_id'] = (int)$base['site_id'];
			}
		}
		if ($this->field_exits('module')) {
			if ((!isset($base['module']) || empty($base['module']))) {
				$copy_base['module'] = ROUTE_M;
			} else {
				$copy_base['module'] = $base['module'];
			}
		}
		//Verify and process end
		if ($this->field_exits('create_at')) {
			if ((!isset($base['create_at']) || empty($base['create_at']))) {
				$copy_base['create_at'] = date("Y-m-d H:i:s", time());
			} else {
				$copy_base['create_at'] = $base['create_at'];
			}
		}
		if ($this->field_exits('update_at')) {
			if ((!isset($base['update_at']) || empty($base['update_at']))) {
				$copy_base['update_at'] = date("Y-m-d H:i:s", time());
			} else {
				$copy_base['update_at'] = $base['update_at'];
			}
		}
		$last_id = $this->insertGetId($copy_base);
		if ($last_id) {
			$copy_base['id'] = $last_id;
			$ret['code'] = 1;
			$ret['msg'] = "操作完成! ";
			$ret['data'] = $copy_base;
			if ($this->_TM->is_index) {
				$index = new BraIndex($this);
				$index->create($ret['item']['id']);
			}
			//todo get all the annex fields
			//todo get all the annex ids
			//todo add annex idx to annex index
			return $ret;
		} else {
			$info['code'] = 0;
			$info['msg'] = "O! BASE BIG FAILES !" . $this->model_id;
			$info['copy_base'] = $copy_base;

			return $info;
		}
	}

	public function load_publish_opts ($hide = []) {
		$assigns_opts = [];
		foreach ($this->fields as $field) {
			if (in_array($field['field_name'], $hide)) {
				continue;
			}
			if (in_array($field['field_type_name'], ['select', 'checkbox'])) {
				$assigns_opts[$field['field_name'] . '_opts'] = array_values($this->load_options($field['field_name'], true));
			}
		}

		return $assigns_opts;
	}

	public function load_publish_fields ($hide = []) {
		$fields  = $this->fields;
		foreach ($fields as $key => &$field) {
			if (in_array($field['field_name'], $hide)) {
				unset($fields[$key]);
			}
			if (empty($field['asform'])) {
				continue;
			}
			if (in_array($field['field_type_name'], ['select', 'checkbox'])) {
				$field['opts'] = array_values($this->load_options($field['field_name'], true));
			}
		}

		return $fields;
	}
	public function load_options ($field_name, $render = false, $use_blank = false) {
		$this->fields[$field_name]['model_id'] = $this->_TM['id'];
		$field = new BraField($this->fields[$field_name]);
		if (!$field->bra_form) {
			abort(403, $field_name . 'NO FORM ALLOWED' . "\\Bra\\core\\field_types\\{$this->fields['field_type_name']}");
		}

		return $field->bra_form->form_data($render, $use_blank);
	}
	private function is_bet_fields ($field) {
		if (!empty($field['field_type_name']) && $field['field_type_name'] == 'date') {
			$modes = ['input_date'];
			if (in_array($field['mode'], $modes)) {
				return true;
			}
		}

		return false;
	}
}
