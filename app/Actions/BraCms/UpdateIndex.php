<?php

namespace App\Actions\BraCms;

use Illuminate\Support\Facades\Cache;

class  UpdateIndex {

	public $page_data;
	public $current_step;

	public function __construct () {
		global $_GPC;
		define("API_URL", "https://www.bracms.com/");
		$query = $_GPC = $_REQUEST;
		$this->current_step = (int)$query['step'];
		$action = "step_" . $this->current_step;
		A("module_sign", $this->module_sign = $_GPC['module'] ?? "system");
		$this->$action();
	}

	private function step_0 () {
		$this->page_data = view('update.update_step_' . (int)$this->current_step);
	}

	private function step_1 () {
		$data['env'] = InstallIndex::checkNnv();
		$data['dir'] = InstallIndex::checkDir();
		$data['func'] = InstallIndex::checkFunc();
		A("data", $data);
		$this->page_data = view('update.update_step_' . (int)$this->current_step);
	}

	private function step_4 () {
		global $_GPC;
		if ($_GPC['a']) {
			switch ($_GPC['a']) {
				case "download_file":
					return $this->page_data = FileSync::down_file($_GPC['module'], $_GPC['file_path']);
					break;
			}
		} else {
			//load sever files
//保存用户名//保存密码
			$admin_info['user_name'] = $_GPC['account'];
			$admin_info['password'] = $_GPC['password'];
			if ($admin_info['user_name'] && $admin_info['password']) {
				Cache::put('admin_install_info', $admin_info);
			}
			$updater = new FileSync();
			$file_diff = $updater->file_diff($this->module_sign);
			A($file_diff);
			$this->page_data = view('update.update_step_' . (int)$this->current_step, $file_diff);
		}
	}

	private function step_5 () {
		global $_GPC;
		if ($_GPC['a']) {
			switch ($_GPC['a']) {
				case "update_model":
					return $this->page_data = ModelSync::sync_update_model($_GPC['table'], $_GPC['module'], 'install');
			}
		} else {
			$res = ModelSync::get_module_models($_GPC['module']);
			$assigns['sys_tables'] = array_values($res['data']);
			$this->page_data = view('update.update_step_' . (int)$this->current_step, $assigns);
		}
	}

	private function step_6 () {
		global $_W , $_GPC;
		ModelSync::sync_menu($_GPC['module']);
		$assigns = [];
		$this->page_data = view('update.update_step_' . (int)$this->current_step, $assigns);
	}

}
