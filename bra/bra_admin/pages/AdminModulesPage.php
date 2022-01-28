<?php

namespace Bra\bra_admin\pages;

use App\Actions\BraCms\FileSync;
use App\Actions\BraCms\ModelSync;
use Bra\core\pages\BraAdminController;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminModulesPage extends BraAdminController {

	public function BRA_ADMIN_ADMIN_MODULES_INDEX ($query) {
		return $this->page_data = $this->t__bra_table_idx("modules");
	}

	public function BRA_ADMIN_ADMIN_MODULES_UPLOAD_MODULE_DATA ($query) {
		//get models
		$module = D("modules")->bra_one($query['id']);
		$models = D('models')->bra_where(['module_id' => $query['id']])->bra_get();
		$menus = D('user_menu')->bra_where(['module' => $module['module_sign']])->bra_get();
		foreach ($menus as $menu) {
			if (is_error($res = $this->fix_menus($module['id'], $menu))) {
				return $this->page_data = $res;
			}
		}


		foreach ($models as $model) {
			if (is_error($res = $this->fix_models($module['id'], $model))) {
				return $this->page_data = $res;
			}
		}
		//upload module
		$res = ModelSync::requestApi("/free/developer/upload_module" , ModelSync::$host , "" , ['module' => json_encode($module)]);

		$models[] = D('models')->bra_where(['table_name' => 'wechat'])->bra_one();
		$schemas = [];
		$this->config = config('database.connections.mysql');
		foreach ($models as $model){
			$table = $this->config['prefix'] . $model['table_name'];
			$schemas[$model['table_name']]['fields'] = DB::select("show COLUMNS FROM $table");
			$schemas[$model['table_name']]['indexes'] = DB::select("SHOW INDEX FROM $table;");
		}
		//upload models schema data

		$res = ModelSync::requestApi("/free/developer/upload_models_schema" , ModelSync::$host , ""  , ['schemas' =>  json_encode($schemas)]);

//upload models data
		$res = ModelSync::requestApi("/free/developer/upload_models" , ModelSync::$host , "" , ['models' => json_encode($models)]);


//upload menus

		$res = ModelSync::requestApi("/free/developer/upload_menus" , ModelSync::$host, ""  , ['menus' => json_encode($menus)]);

		return $this->page_data = bra_res(1 , "OK");
	}

	public function fix_menus ($module_id, $menu) {
		$range_start = $module_id * 1000;
		if ($menu['id'] < $range_start || $menu['id'] > $range_start + 999) {
			//update
			$pre_menu = D("user_menu")->bra_where(['id' => ['BET', [$range_start, $range_start + 999]]])->order("id desc")->bra_one();
			if (!$pre_menu) {
				$next_menu_id = $range_start;
			} else {
				$next_menu_id = $pre_menu['id'] + 1;
			}
			if ($next_menu_id > $range_start + 999) {
				return bra_res(500, "无法修复，超出模块允许的菜单范围! ", "");
			} else {
				$update['id'] = $next_menu_id;
				$res = D('user_menu')->item_edit($update, $menu['id']);
				if (!is_error($res)) {
					D('user_menu')->bra_where(['parent_id' => $menu['id']])->update(['parent_id' => $next_menu_id]);
					D('user_menu')->bra_where(['alias' => $menu['id']])->update(['alias' => $next_menu_id]);

					return bra_res(1, '修正完成!');
				} else {
					return $res;
				}
			}
		} else {
			return bra_res(1, '无需修正!');
		}
	}

	/**
	 * @param $module_id
	 * @param $model
	 * @return array|mixed
	 * @throws \Exception
	 */
	public function fix_models ($module_id, $model) {
		$range_start = $module_id * 1000;
		if ($model['id'] < $range_start || $model['id'] > $range_start + 999) {
			$pre_menu = D("models")->bra_where(['id' => ['BET', [$range_start, $range_start + 999]]])->order("id desc")->bra_one();
			if (!$pre_menu) {
				$next_model_id = $range_start;
			} else {
				$next_model_id = $pre_menu['id'] + 1;
			}
			if ($next_model_id > $range_start + 999) {
				return bra_res(500, "无法修复，超出模块允许的模型范围! ", "");
			} else {
				$update['id'] = $next_model_id;
				return D('models')->item_edit($update, $model['id']);
			}
		} else {
			return bra_res(1, '无需修正!');
		}
	}

	public function BRA_ADMIN_ADMIN_MODULES_UPLOAD_MODULE_FILES ($query) {
		$module = D("modules")->bra_one($query['id']);
		$file_sync = new FileSync();
		$file_sync->gen_module_file_list($module['module_sign']);
		$client = new Client();
		$licence = config('licence');
		foreach ($file_sync->md5_org_arr as $path => $md5) {
			if (file_exists(SYS_ROOT . $path)) {
				$res = $this->upload_file($path, $module['module_sign']);
				$body = $res->getBody();
				$content = json_decode($body, 1);
				if (is_error($content)) {
					return $this->page_data = $content;
				}
			}
		}

		return $this->page_data = bra_res(1, "OK");
	}

	public function upload_file ($path, $module) {
		define("API_URL", "https://www.bracms.com/");
		$url = API_URL . 'free/product/upload_file';
		$client = new Client([
			'verify' => false
		]);

		return $client->request('POST', $url, [
			'multipart' => [
				[
					'name' => 'path',
					'contents' => $path
				],
				[
					'name' => 'module',
					'contents' => $module
				],
				[
					'name' => 'file',
					'contents' => Utils::tryFopen(SYS_ROOT . $path, 'r')
				],
			]
		]);
	}
}
