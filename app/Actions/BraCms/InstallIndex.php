<?php

namespace App\Actions\BraCms;

use Bra\core\objects\BraCurl;
use Bra\core\objects\BraFS;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Request;

error_reporting(E_ALL & !E_WARNING);

class  InstallIndex {
	public $page_data;
	public $current_step;

	public function __construct () {
		global $_GPC;
		define("API_URL", "https://www.bracms.com/");
		$query = $_GPC = $_REQUEST;
		$this->current_step = (int)$query['step'];
		$action = "step_" . $this->current_step;
		$this->$action();
	}

	public function action_old ($query) {
		ModelSync::$host = $query['server_url'];
		ModelSync::$pass = $query['password'];
		$action = $query['action'] ?? 'list_models';
		switch ($action) {
			case  'list_models' :
				return $this->page_data = bra_res(1, '', '', ModelSync::get_module_models($query['module_sign']));
				break;
			case  'sync_model' :
				return $this->page_data = bra_res(1, '', '', ModelSync::sync_model(trim($query['table']), 'install'));
			case  'sync_menu' :
				return $this->page_data = bra_res(1, '', '', ModelSync::sync_menu($query['module_sign']));
			case  'sync_roles' :
				return $this->page_data = bra_res(1, '', '', ModelSync::sync_user_roles($query['module_sign']));
			case 'lock_install':
				file_put_contents(config_path() . '/bracms_install.lock', 1);
		}
	}

	public function _check_licence ($licence) {
		$url = API_URL . 'free/product/check_licence';
		$curl = new BraCurl([], 'ajax');
		$response = $curl->fetch($url, 'POST', ['query' => $licence]);
		$body = $response->getBody();
		$content = json_decode($body, 1);

		return $content;
	}

	private function step_0 () {
		$this->page_data = view('install.install_step_' . (int)$this->current_step);
	}

	private function step_6 () {
		$admin_user = Db::table('users')->find(1);
		$admin_info = Cache::get('admin_install_info');
		$admin_info['role_id'] = 1;
		$admin_info['status'] = 99;
		$admin_info['id'] = 1;
		if (!$admin_user) {
			// create admin account
			$res = D('users')->item_add($admin_info);
		} else {
			$res = D('users')->item_edit($admin_info, 1);
		}
		//创建root
		$res2 = Db::table('roots')->find(1);
		$domain_info = explode(".", $_SERVER['HTTP_HOST']);
		if (!$res2) {
			$root_info = [];
			$root_info['id'] = 1;
			$root_info['site_id'] = 1;
			$root_info['title'] = "默认域名";
			if (count($domain_info) == 2) {
				$root_info['root_domain'] = $_SERVER['HTTP_HOST'];
			}
			if (count($domain_info) == 3) {
				$root_info['root_domain'] = $domain_info[1] . "." . $domain_info[2];
			}
			$root_id = Db::table('roots')->insert($root_info, true);
		} else {
			$root_id = $res2->id;
		}
		//site root
		$res3 = Db::table('sites')->find(1);
		if (!$res3) {
			$default_site = [];
			$default_site['id'] = 1;
			$default_site['title'] = "布拉内容管理系统";
			$default_site['site_domain'] = $domain_info[0];
			$default_site['default'] = 1;
			$default_site['root_id'] = $root_id;
			$site_id = Db::table('sites')->insert($default_site, true);
		} else {
			$default_site = [];
			$default_site['id'] = 1;
			$default_site['title'] = "布拉内容管理系统";
			$default_site['site_domain'] = $domain_info[0];
			$default_site['default'] = 1;
			$default_site['root_id'] = $root_id;
			Db::table('sites')->where(['id' => 1])->update($default_site);
			$site_id = $res3->id;
		}
		if ($root_id && $site_id) {
			// write install.lock
			file_put_contents(CONFIG_PATH . 'bracms_install.lock', "BRACMS 安装锁定文件");
		} else {
			dd("对不起，初始化数据失败！");
		}
		$this->page_data = view('install.install_step_' . (int)$this->current_step);
	}

	private function step_5 () {
		global $_GPC;
		if ($_GPC['a']) {
			switch ($_GPC['a']) {
				case "install_model":
					return $this->page_data = ModelSync::sync_install_model($_GPC['table'], $_GPC['module'], 'install');
			}
		} else {
			$res = ModelSync::get_module_models('system');
			$assigns['sys_tables'] = array_values($res['data']);
			$this->page_data = view('install.install_step_' . (int)$this->current_step, $assigns);
		}
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
			$file_diff = $updater->file_diff("system");

			A($file_diff);
			$this->page_data = view('install.install_step_' . (int)$this->current_step, $file_diff);
		}
	}

	private function step_2 () {
		global $_GPC;
		if (Request::method() == "POST") {
			$config = [
				'domain' => Request::getHttpHost(),
				'product_licence_code' => trim($_GPC["product_licence_code"]),
				'product_sign' => "laracms"
			];
			$licence_info = $this->_check_licence($config);
			if ($licence_info['code'] == 1) {
				BraFS::write_config('licence', $config);
				$this->page_data = bra_res(1, 'OK');
			} else {
				$this->page_data = $licence_info;
			}
		} else {
			$this->page_data = view('install.install_step_' . (int)$this->current_step);
		}
	}

	private function step_3 () {
		global $_GPC;
		if (Request::method() == "POST") {
			$_GPC['type'] = 'mysql';
			$database = $_GPC['database'];
//			DB_HOST=127.0.0.1
//			DB_PORT=3306
//			DB_DATABASE=
//			DB_USERNAME=
//			DB_PASSWORD=
			if (!$database['DB_HOST']) {
				return $this->page_data = bra_res(500, "请输入主机IP");
			}
			if (!$database['DB_PORT']) {
				return $this->page_data = bra_res(500, "请输入端口号");
			}
			if (!$database['DB_DATABASE']) {
				return $this->page_data = bra_res(500, "请输入数据库名");
			}
			if (!$database['DB_USERNAME']) {
				return $this->page_data = bra_res(500, "请输入用户名");
			}
			if (!$database['DB_PASSWORD']) {
				return $this->page_data = bra_res(500, "请输入数据库密码");
			}
			$this->put_env($database);
			$db_connect = Db::connection('mysql');
			try {
				$db_connect->query("CREATE DATABASE IF NOT EXISTS `{$database['DB_DATABASE']}` DEFAULT CHARACTER SET utf8mb4");
			} catch (Exception $e) {
				return $this->page_data = bra_res(503, $e->getMessage());
			}
			try {
				$test = DB::select("show databases like '{$database['DB_DATABASE']}';");
				if (count($test) == 0) {
					return $this->page_data = bra_res(501, "对不起，链接数据库失败，请检查账号权限！");
				}
			} catch (Exception $e) {
				return $this->page_data = bra_res(502, $e->getMessage(), "show databases like '{$database['DB_DATABASE']}';");
			}

			return $this->page_data = ['code' => 1, 'msg' => '数据库连接成功'];
		} else {
			$this->page_data = view('install.install_step_' . (int)$this->current_step);
		}
	}

	private function step_1 () {
		$data['env'] = self::checkNnv();
		$data['dir'] = self::checkDir();
		$data['func'] = self::checkFunc();
		A("data", $data);
		$this->page_data = view('install.install_step_' . (int)$this->current_step);
	}

	public static function checkNnv () {
		$items = [
			'os' => ['操作系统', '不限制', '类Unix', PHP_OS, 'ok'],
			'php' => ['PHP版本', '8.0', '8.0及以上', PHP_VERSION, 'ok'],
			'gd' => ['GD库', '2.0', '2.0及以上', '未知', 'ok'],
		];
		if ($items['php'][3] < $items['php'][1]) {
			$items['php'][4] = 'no';
		}
		$tmp = function_exists('gd_info') ? gd_info() : [];
		if (empty($tmp['GD Version'])) {
			$items['gd'][3] = '未安装';
			$items['gd'][4] = 'no';
		} else {
			$items['gd'][3] = $tmp['GD Version'];
		}

		return $items;
	}

	public static function checkDir () {
		$chmod_file = "chmod.txt";
		$files = file(config_path() . DS . $chmod_file);
		foreach ($files as $_k => $file) {
			$write_able = false;
			$file = str_replace('*', '', $file);
			$file = trim($file);
			if (is_dir(SYS_ROOT . $file)) {
				$is_dir = '1';
				$cname = '目录';
				//继续检查子目录权限，新加函数
//				$write_able = BraFS::writable_check(SYS_ROOT . $file);
//				if (is_error($write_able)) {
//					$write_able = false;
//				}
			} else {
				$is_dir = '0';
				$cname = '文件';
			}
			//新的判断
			if ($is_dir == '0' && is_writable(SYS_ROOT . $file)) {
				$is_writable = 1;
			} elseif ($is_dir == '1') {
				$is_writable = BraFS::dir_writeable(SYS_ROOT . $file);
			} else {
				$is_writable = 0;
			}
			$filesmod[$_k]['file'] = $file;
			$filesmod[$_k]['is_dir'] = $is_dir;
			$filesmod[$_k]['cname'] = $cname;
			$filesmod[$_k]['is_writable'] = $is_writable;
		}

		return $filesmod;
	}

	public static function checkFunc () {
		$items = [
			['pdo', '支持', 'yes', '类'],
			['pdo_mysql', '支持', 'yes', '模块'],
			['fileinfo', '支持', 'yes', '模块'],
			['curl', '支持', 'yes', '模块'],
			['xml', '支持', 'yes', '函数'],
			['file_get_contents', '支持', 'yes', '函数'],
			['mb_strlen', '支持', 'yes', '函数'],
			['gzopen', '支持', 'yes', '函数'],
		];
		foreach ($items as &$v) {
			if (('类' == $v[3] && !class_exists($v[0])) || ('模块' == $v[3] && !extension_loaded($v[0])) || ('函数' == $v[3] && !function_exists($v[0]))) {
				$v[1] = '不支持';
				$v[2] = 'no';
				session('install_error', true);
			}
		}

		return $items;
	}

	public function put_env ($data) {
		$envPath = base_path() . DIRECTORY_SEPARATOR . '.env';
		$contentArray = collect(file($envPath, FILE_IGNORE_NEW_LINES));
		$contentArray->transform(function ($item) use ($data) {
			foreach ($data as $key => $value) {
				if (str_contains($item, $key)) {
					return $key . '=' . $value;
				}
			}

			return $item;
		});
		$content = implode("\n", $contentArray->toArray());
		File::put($envPath, $content);
	}
}
