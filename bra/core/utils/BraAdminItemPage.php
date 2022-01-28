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
use Bra\core\objects\BraArray;
use Bra\core\objects\BraString;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

trait BraAdminItemPage {
	public function t__bra_table_tree_idx ($model_id, $where = [], $config = [], $name_key = 'title', $id_key = 'id') {
		global $_W, $_GPC;
		$tpl = $config['tpl'] ?? 'bra_admin.bra_admin_item.bra_tree_idx';
		$page_size = $_GPC['__page_size'] ?? 15;
		$bra_m = D($model_id);
		$hide = $show = [];
		$assign['field_list'] = $field_list = $bra_m->get_admin_column_fields($hide, $show);
		if (is_bra_access(0, 'post')) {
			$tree_data = $bra_m->load_tree($config['parent_id'] ?? 0, $where, $name_key, $id_key);
			$ret['code'] = 1;
			$ret['total'] = $ret['count'] = count($tree_data);
			$ret['data'] = $tree_data;
			$ret['where'] = $where;
			$ret['last_page'] = ceil($ret['count'] / $page_size);

			return $ret;
		} else {
			$assign['cols'] = json_encode($this->get_tabulator_cols($field_list, $bra_m));
			$assign['templet'] = $this->build_submenu_tpl($config['display'] ?? 0, $config['use_old'] ?? false);
			$assign['mapping'] = array_merge($config, $_W['mapping']);
			A($assign);

			return A_T($tpl);
		}
	}

	public function get_tabulator_cols ($fields, $bra_m) {
		$cols = [];
		foreach ($fields as $k => $field) {
			$_col = [];
			$_col['field'] = $field['field_name'];
			$_col['title'] = $field['slug'];
			if ($field['editor'] ?? false) {
				$_col['editor'] = $field['editor'];
				$options = $bra_m->load_options($field['field_name']);
				$_col['editorParams'] = [
					"values" => BraArray::get_array_from_array_vals($options, 'title', 2, 'id')
				];
			}
			if ($field['formatter'] ?? false) {
				$_col['formatter'] = $field['formatter'];
			}
			//editorParams:{values:{"male":"Male", "female":"Female", "unknown":"Unknown"}}
			if (!empty($field['col_width'])) {
				$_letter = strlen($field['slug']);
				$_col['width'] = max((int)$field['col_width'], $_letter * 5 + 30);
			}
			$cols[] = $_col;
		}

		return $cols;
	}

	public function build_submenu_tpl ($display = 0, $user_old = false) {
		$template = "<div>";
		foreach (view()->getShared()['sub_menus'] ?? [] as $menu) {
			$menu = (array)$menu;
			if ($menu['display'] == $display) {
				preg_match_all("/{(.*?)}/i", $menu['params'], $machs);
				$mapping = $menu;
				foreach ($machs['1'] as $mach) {
					$mapping[$mach] = $user_old ? "{{d.old_data.{$mach}}}" : "{{d.{$mach}}}";
				}
				$template .= build_back_a($menu, [], $mapping);
			}
		}
		$template .= "</div>";

		return $template;
	}

	public function t__bra_nested_tables_idx ($m_pid, $m_cid, $on, $config_p = []) {
		$bra_m = D($m_cid);
		$assign['sub_cols'] = $this->get_tabulator_cols($bra_m->get_admin_column_fields(), $bra_m);
		A($assign);
		$config_p['config']['tpl'] = $config_p['config']['tpl'] ?: 'bra_admin.bra_admin_item.bra_nested_idx';
		$page_data = $this->t__bra_table_idx($m_pid, $config_p['ext'] ?? [], $config_p['config']);
		if (is_bra_access(2, 'ajax')) {
			foreach ($page_data['data'] as &$item) {
				$item['sub_datas'] = $bra_m->bra_where([$on => $item['id']])->list_item(true);
			}
		}

		return $this->page_data = $page_data;
	}

	public function t__bra_table_idx ($model_id, $ext_where = [], $config = [], $query = []) {
		global $_W, $_GPC;
		$tpl = $config['tpl'] ?? 'bra_admin.bra_admin_item.bra_idx';
		$page_size = $_GPC['__page_size'] ?? 15;
		$bra_m = D($model_id, '', true);
		$show = !empty($config['show']) ? $config['show'] : [];
		$hide = !empty($config['hide']) ? $config['hide'] : [];
		$filter_info = $bra_m->gen_ajax_filter_forms($_GPC, $config);
		list($where, $filter_fields) = $filter_info;
		//  bra_end_resp(1 ,$timer);
		$assign['filter_fields'] = $filter_fields;

		foreach ($filter_fields as $filter_field){
			if($_GPC[$filter_field['field_name']]){
				$query[$filter_field['field_name']] = $_GPC[$filter_field['field_name']];

			}
		}
		//ext where
		$filter_where = $bra_m->build_bra_query($query);
		if (is_array($ext_where)) {
			$where = array_merge($where ?? [], $ext_where, $filter_where);
		}
		$orders[] = ['id', 'desc'];
		if ($_GPC['order'] ?? false) {
			//todo config _GPC order
			$order = $_GPC['order'];
		}
		if (isset($config['order'])) {
			//todo config order
			$order = $config['order'];
		}
		if ($bra_m->field_exits('listorder')) {
			$orders[] = ['listorder', 'desc'];
		}
		//
		$set = config('bra_set');
		$models = $set['models'];
		$table_name = $bra_m->_TM['table_name'];
		if (!empty($models[$table_name])) {
			if (isset($models[$table_name]['Ocultar_ids']) && $models[$table_name]['Ocultar_ids']) {
				$where['id'] = ['NOT IN', $models[$table_name]['Ocultar_ids']];
			}
		}

		$this->idx_where = $where;
		if (is_bra_access(0, 'post')) {
			if (isset($_GPC['_bra_download']) && $_GPC['_bra_download'] == 1) {
				if ($_GPC['_btn_action'] == "export_data") {
					if ($_W['super_power'] || $_W['admin_role']['allow_download'] == 1) {
						return $this->t__export_data($model_id, $where, true);
					}
				} else {
					$action = "t__" . $_GPC['_btn_action'];

					return $this->$action($model_id, $where, true);
				}
			} else {
				$lists = $bra_m->bra_where($where)->select('id')->bra_order($orders)->paginate($page_size);
				$lists->transform(function ($item) use ($bra_m) {
					$item = $bra_m->get_item($item->id, true, ['with_old' => true]);

					return $item;
				});
				$ret['code'] = 1;
				$ret['total'] = $ret['count'] = $lists->total();
				$ret['data'] = $lists->items();
				$ret['where'] = $where;
				$ret['last_page'] = ceil($ret['count'] / $page_size);

				return $ret;
			}
		} else {
			$assign['field_list'] = $field_list = $bra_m->get_admin_column_fields($hide, $show, $config['admin_columns'] ?? []);
			$assign['cols'] = json_encode($this->get_tabulator_cols($field_list, $bra_m));
			$assign['mapping'] = array_merge($ext_where, $_W['mapping']);
			$assign['idx_m'] = $bra_m;
			$assign['templet'] = $this->build_submenu_tpl();
			$assign['filter_info'] = $filter_info;
			A($assign);

			return A_T($tpl);
		}
	}

	public function t__export_data ($model_id, $ext = [], $render = false) {
		global $_W;
		$spreadsheet = new Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();
		$model = D($model_id);
		$model_info = $model->_TM;
		$name = $model_info['title'];
		$fields = $model->get_fields();
		$i = 0;
		foreach ($fields as $k => $field) {
			$field['slug'] = $field['slug'] ?? $k;
			$sheet->setCellValue($this->column($i), $field['slug']);
			$i++;
		}
		//下载数据
		//$items = $model->bra_where($ext)->find();
		$row = 2;
		foreach ($this->exp_all($model_id, $ext, $render) as $item) {
			$i = 0;
			foreach ($fields as $k => $field) {
				if (is_array($item[$k])) {
				} else {
					if (is_numeric($item[$k])) {
						$val = "" . $item[$k];
					} else {
						$val = $item[$k];
					}
				}
				$sheet->setCellValueExplicit($this->column($i, $row), $val, DataType::TYPE_STRING2);
				//$sheet->setCellValue($this->column($i, $row), $val);
				$i++;
			}
			$row++;
		}
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="' . $name . '.xlsx"');
		header('Cache-Control: max-age=0');
		$writer = new Xlsx($spreadsheet);
		$writer->save('php://output');
		//删除清空：
		$spreadsheet->disconnectWorksheets();
		unset($spreadsheet);
		exit;
	}

	protected function column ($key, $columnnum = 1) {
		return $this->column_str($key) . $columnnum;
	}

	protected function column_str ($key) {
		$array = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ', 'BA', 'BB', 'BC', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BK', 'BL', 'BM', 'BN', 'BO', 'BP', 'BQ', 'BR', 'BS', 'BT', 'BU', 'BV', 'BW', 'BX', 'BY', 'BZ', 'CA', 'CB', 'CC', 'CD', 'CE', 'CF', 'CG', 'CH', 'CI', 'CJ', 'CK', 'CL', 'CM', 'CN', 'CO', 'CP', 'CQ', 'CR', 'CS', 'CT', 'CU', 'CV', 'CW', 'CX', 'CY', 'CZ', 'DA', 'DB', 'DC', 'DD', 'DE', 'DF', 'DG', 'DH', 'DI', 'DJ', 'DK', 'DL', 'DM', 'DN', 'DO', 'DP', 'DQ', 'DR', 'DS', 'DT', 'DU', 'DV', 'DW', 'DX', 'DY', 'DZ', 'EA', 'EB', 'EC', 'ED', 'EE', 'EF', 'EG', 'EH', 'EI', 'EJ', 'EK', 'EL', 'EM', 'EN', 'EO', 'EP', 'EQ', 'ER', 'ES', 'ET', 'EU', 'EV', 'EW', 'EX', 'EY', 'EZ');

		return $array[$key];
	}

	function exp_all ($model_id, $ext, $render) {
		static $i = 0;
		$model = D($model_id);
		if ($render) {
			$items = $model->bra_where($ext)->select('id')->get();
		} else {
			$items = $model->bra_where($ext)->select('*')->get();
		}
		foreach ($items as $item) {
			$item = (array)$item;
			if ($render) {
				yield $model->get_item($item['id']);
			} else {
				yield $item;
			}
		}
	}

	public function t__add_iframe ($model_id, $extra = [], $config = []) {
		global $_W, $_GPC;
		$tpl = $config['tpl'] ?? 'bra_admin.bra_admin_item.bra_add';
		$assign['model'] = $model = D($model_id, $config['scope'] ?? '');
		if (is_bra_access(2, 'post', $config['check_token'] ?? true)) {
			$base_info = $_GPC['data'] ?? [];//get the base info
			$base_info = array_merge($base_info, $extra);

			return $model->item_add($base_info);
		} else {
			$assign['field_list'] = $model->get_admin_publish_fields($extra, $config['hide'] ?? [], $config['show'] ?? []);
			A($assign);

			return A_T($tpl);
		}
	}

	public function t__edit_iframe (int $id, $model_id, $ext_where = [], $config = [], $query = []) {
		global $_W, $_GPC;
		$check_token = $config['check_token'] ?? false;
		$tpl = $config['tpl'] ?? 'bra_admin.bra_admin_item.bra_edit';
		$model = D($model_id);
		if (!$model->field_exits('user_id')) {
			unset($ext_where['user_id']);
		}
		$assign['detail'] = $detail = (array)$model->bra_query($query)->bra_where($ext_where)->find($id);
		if (isset($config['query']) && is_array($config['query'])) {
			$detail = array_merge($detail, $config['query']);
		}
		if (!$detail) {
			abort(403, '权限不足!');
		}
		if (is_bra_access(2, 'post', $check_token)) {
			$base_info = $_GPC['data'] ?? [];
			$res = $model->item_edit($base_info, $id);

			return $res;
		} else {
			$assign['model'] = $model;
			$assign['field_list'] = $model->get_admin_publish_fields($detail, $config['hide'] ?? [], $config['show'] ?? []);
			A($assign);

			return A_T($tpl);
		}
	}

	public function t__del_batch ($ids, $model_id, $extra = [], $query = [], $pk = 'id') {
		$resall = [];
		$msg = '';
		$ok_count = $error_count = 0;
		foreach ($ids as $id) {
			$resall[] = $res = $this->t__del($id, $model_id, $extra, $query, $pk);
			if (is_error($res)) {
				$error_count++;
			} else {
				$ok_count++;
			}
		}

		return bra_res(1, "操作完成 , 删除成功: $ok_count , 删除失败 : $error_count" . $msg, '', $resall);
	}

	public function t__del ($id, $model_id, $extra = [], $query = [], $pk = 'id', $uid_field = 'user_id') {
		$model = D($model_id, '', true);
		$where = [];
		if ($id !== null) {
			$where[$pk] = $id;
		}
		if (is_array($extra)) {
			$where = array_merge($where, $extra);
		}
		if ($model->field_exits($uid_field)) {
			$where = $this->map_auth($where, $uid_field);
		}
		$del_res = $model->bra_query($query)->item_del($id, $where);
		if (!is_error($del_res)) {
			return bra_res(1, '操作完成', '', $del_res);
		} else {
			return $del_res;
		}
	}

	public function t__full_del ($id, $model_id, $extra = []) {
		$model = D($model_id);
		$where = [];
		$where = $this->map_auth($where);
		if (is_array($extra)) {
			$where = array_merge($where, $extra);
		}
		if (!$model->field_exits('user_id')) {
			unset($where['user_id']);
		}
		$detail = $model->with_site()->bra_where($where)->find($id);
		if ($detail) {
			//  get all upload fields
			foreach ($model->fields as $k => $field) {
				if ($field['field_type_name'] == 'upload') {
					$annex_ids = explode(',', $detail[$k]);
					foreach ($annex_ids as $annex_id) {
						if (is_numeric($annex_id) && $annex_id > 0) {
							$annex = new BraAnnex($annex_id);
							$annex->delete();
						}
					}
				}
			}
			//delete annex
			$del_res = $model->bra_where($where)->delete($where);
		}
		if ($del_res) {
			return bra_res(1, '操作完成', $where);
		} else {
			return bra_res(500, '操作失败', $where);
		}
	}

	public function t__sub_del ($item_id, $model_id, $subs, $extra = [], $query = []) {
		$detail = D($model_id)->bra_one($item_id);
		if (!$detail) {
			return bra_res(500, "数据不存在 无法删除! ", "");
		}
		foreach ($subs as $map_model => $sub) {
			$map_target_field = $sub[0];
			$map_from_field = $sub[1] ?? "id";
			$nodes = D($map_model)->bra_where([$map_target_field => $detail[$map_from_field]])->bra_get();
			foreach ($nodes as $node) {
				$this->t__del($node['id'], $map_target_field);
			}
		}

		return $this->t__del($item_id, $model_id, $extra, $query);
	}

	public function t__module_setting ($module_sign, $data) {
		$_module = module_exist($module_sign);
		$module_set_model = D("modules_setting");
		$where['module_id'] = $_module['id'];
		$detail = (array)$module_set_model->with_site()->bra_where($where)->first();
		if (is_bra_access()) {
			$data['module_id'] = $_module['id'];
			$data['setting'] = json_encode($data['data']);
			if ($detail) {
				$res = $module_set_model->item_edit($data, $where);
			} else {
				$res = $module_set_model->item_add($data);
			}

			return $res;
		} else {
			$set = config('bra_set');
			$modules = $set['modules'];
			if (isset($modules[$module_sign]) && $modules[$module_sign]['diy_set'] == 1) {
				$view_path = $module_sign . '.set_diy';
			} else {
				$view_path = $module_sign . '.set';
			}
			A('config', json_decode($detail['setting'], true));

			return A_T($view_path);
		}
	}

	public function t__field_config ($donde, $model_id, $field_name, $data = [], $config = []) {
		global $_W, $_GPC;
		if (is_bra_access(0)) {
			if (empty($data)) {
				return bra_res(500, 'data数据必须填写', $data, $_GPC);
			}

			return D($model_id)->set_field_config($donde, $field_name, $data);
		} else {
			list($config_data, $detail) = D($model_id)->get_field_config($donde, $field_name);
			$ret['config'] = $config_data;
			A($ret);
			if ($config['tpl'] && strpos($config['tpl'], '{') !== false) {
				$tpl = BraString::parse_param_str($config['tpl'], $detail);
			}

			return A_T($tpl ?? '');
		}
	}

	public function t__config ($name, $config_sign, $module_sign, $area_id = 0) {
		global $_W, $_GPC;
		$config_m = D('config');
		$donde['title'] = $config_sign;
		$donde['area_id'] = $area_id;
		$detail = $config_m->with_site()->bra_one($donde);
		$module = module_exist($module_sign);
		if (is_bra_access(0, 'post')) {
			$ist = $donde;
			$ist['data'] = $_GPC['data'] ? $_GPC['data'] : [];
			$ist['module_id'] = $module['id'];
			$ist['site_id'] = $_W['site']['id'];
			$ist['type'] = $_GPC['type'] ?? 0;
			$ist['data'] = json_encode($ist['data']);
			$ist['desc'] = $name;
			if ($detail) {
				$config_m->bra_where($donde)->update($ist);
			} else {
				$config_m->insert($ist);
			}
			$ret['code'] = 1;
			$ret['msg'] = '更新成功';
			$ret['ret'] = $_GPC;

			return $ret;
		} else {
			A('config', json_decode($detail['data'], 1));
			A('config_name', $detail['desc']);
			if ($detail && $detail['type'] == 1) {
				return T("bra_admin.bra_config.editor");
			} else {
				return T($module_sign . ".".ROUTE_C."." . $config_sign);
			}
		}
	}

	public function t__editor_config ($name, $config_sign, $module_sign, $area_id = 0) {
		global $_W, $_GPC;
		$config_m = D('config');
		$donde['title'] = $config_sign;
		$donde['area_id'] = $area_id;
		$detail = $config_m->with_site()->bra_one($donde);
		$module = module_exist($module_sign);
		if (is_bra_access(0, 'post')) {
			$ist = $donde;
			$ist['data'] = $_GPC['data'] ? $_GPC['data'] : [];
			$ist['module_id'] = $module['id'];
			$ist['site_id'] = $_W['site']['id'];
			$ist['type'] = 1;
			$ist['data'] = json_encode($ist['data']);
			$ist['desc'] = $name;
			if ($detail) {
				$config_m->bra_where($donde)->update($ist);
			} else {
				$config_m->insert($ist);
			}
			$ret['code'] = 1;
			$ret['msg'] = '更新成功';
			$ret['ret'] = $_GPC;

			return $ret;
		} else {
			A('config', json_decode($detail['data'], 1));
			A('config_name', $detail['desc']);

			return T("bra_admin.bra_config.editor");
		}
	}

	public function t__same_tree_del ($pmid, $p_item_id) {
		$parent = (array)D($pmid)->find($p_item_id);
		$res = $this->t__del($p_item_id, $pmid);
		if (!is_error($res)) {
			$donde['id'] = ['IN', explode(',', $parent['arrchild'])];
			D($pmid)->bra_where($donde)->limit(10000)->delete();
		}

		return $res;
	}

	public function t_other_tree_del ($pmid, $p_item_id, $cmid, $c_pid_key = false) {
		if($pmid == $p_item_id){
			return bra_res(500 , "不能跟自己进行操作! ", "");
		}
		$c_pid_key = $c_pid_key !== false ? $c_pid_key : $pmid . "_id";
		$res = $this->t__del($p_item_id, $pmid);
		if (!is_error($res)) {
			$donde[$c_pid_key] = $p_item_id;
			D($cmid)->bra_where($donde)->limit(10000)->delete();
		}

		return $res;
	}
}
