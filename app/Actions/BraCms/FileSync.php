<?php

namespace App\Actions\BraCms;

use Bra\core\objects\BraCurl;
use Bra\core\objects\BraFS;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use JetBrains\PhpStorm\ArrayShape;

class FileSync {
	public array $md5_arr = [];
	public array $md5_org_arr = [];
	public array $include_dirs;
	public static array $system_modules = ['bra', 'core', 'bra_admin', 'update'];

	#[ArrayShape(['code' => "int", 'msg' => "string"])] public static function down_file ($module, $file_path): array {
		global $_GPC;
		$url = API_URL . 'free/product/download_file';
		$licence = config('licence');
		$licence['domain'] = $_SERVER['HTTP_HOST'];
		$licence['module'] = $module;
		$licence['file_path'] = $file_path;
		// $licence['method'] = 'application.shipping';
		$licence['gz'] = function_exists('gzcompress') && function_exists('gzuncompress') ? 'true' : 'false';
		$licence['download'] = 'true';
		$headers = array('content-type' => 'application/x-www-form-urlencoded');
		$response = Http::withHeaders($headers)->asForm()
			->post($url , $licence);

		$headers = $response->headers();
		$vs = $response->body();
//		dd($url , $response->body() , $licence);

//		$bra_curel = new BraCurl([] , 'ajax');

//		$content = $bra_curel->test_url($url, 'POST', ['headers' => $headers, 'query' => $licence], false);

//		$vs = $content->getBody();
		if (!isset($headers['BRACMS_file_ok'])) {
			return bra_res(0 ,  '下载文件失败' . $file_path , $headers);
		}
		$res = $vs;
		$res = self::write_file(SYS_ROOT . $file_path, $res);
		if (false !== $res) {
			return bra_res(1 ,  '下载成功');
		} else {
			return bra_res(0 ,  '下载文件成功，文件写入权限不足' . $file_path);
		}

	}

	public static function write_file ($path, $data): bool|int {
		BraFS::mkdirs(dirname($path), true);

		return file_put_contents($path, $data, LOCK_EX);
	}

	public function file_diff ($module = 'system'): array {
		$this->gen_module_file_list($module);

		$server_res = $this->get_server_module_files($module);

		$ret_res['server_files'] = $server_md5s = $server_res['data'];
		if (!is_array($server_md5s)) {
			return [];
		}
		if (!is_array($this->md5_arr)) {
			$this->md5_arr = [];
		} else {
			$ret_res['local_files'] = $this->md5_arr;
		}
		$ret_res['diffs'] = $diffs = array_diff_assoc($server_md5s, $this->md5_arr);
		//丢失文件列表
		$lostfiles = array();
		foreach ($server_md5s as $k => $v) {
			if (!in_array($k, array_keys($this->md5_arr))) {
				$lostfiles[] = $k;
				unset($diffs[$k]);
			}
		}
		$files_to_update = [];
		foreach ($diffs as $k => $diff) {
			$files_to_update[] = base64_decode($k);
		}
		foreach ($lostfiles as $k => $lostfile) {
			$files_to_update[] = base64_decode($lostfile);
		}
		$ret_res['files_to_update'] = $files_to_update;

		return $ret_res;
	}

	/**
	 * 读取本地文件列表
	 * @param string $module
	 */
	public function gen_module_file_list (string $module = 'system') {
		$sys_include_dirs = $include_dirs = [];
		$themes = BraFS::get_sub_dir_names(SYS_ROOT . 'bra_views' . DS . 'themes' . DS);
		if (empty($themes)) {
			$themes = ['default'];
		}
		if ($module == "system") {
			$include_dirs = [
				'public' . DS . 'statics' . DS, //静态文件
				'bra_views' . DS . 'components' . DS, //components 文件
			];
			$modules = self::$system_modules;
		} else {
			$modules = [$module];
		}

		foreach ($modules as $_module) {
			$module_include_dirs = $this->get_bracms_module_dirs($themes, $_module);
			$sys_include_dirs = array_merge($sys_include_dirs , $module_include_dirs);
		}

		$this->include_dirs = array_merge($sys_include_dirs, $include_dirs);

		$this->read_dir(SYS_ROOT, $this->include_dirs);
		//add files
		if($module == 'system'){
			$this->add_file(SYS_ROOT . 'bra' . DS . 'bracms.php');
			$this->add_file(SYS_ROOT . 'app'. DS . 'Actions'. DS . 'BraCms' . DS . 'FileSync.php');
			$this->add_file(SYS_ROOT . 'app'. DS . 'Actions'. DS . 'BraCms' . DS . 'ModelSync.php');
			$this->add_file(SYS_ROOT . 'app'. DS . 'Actions'. DS . 'BraCms' . DS . 'UpdateIndex.php');
		}

	}

	private function read_dir ($path = '', $include_dirs = []) {
		$path = str_replace("//", "/", $path);
		$path = str_replace("\\\\", "\\", $path);
		$encode_prefix = $path;
		if (is_dir($path)) {
			$skip_paths = [
				'vendor' ,
				'config' ,
				'storage' ,
			];
			foreach ($skip_paths as $skip_path){
				if ( str_contains($path, SYS_ROOT . $skip_path) || str_contains($path, SYS_ROOT . $skip_path)) {

					return;
				}
			}

			$handler = opendir($path);
			while (($filename = @readdir($handler)) !== false) {
				if (!str_starts_with($filename, ".")) {
					$target_dir = $path . DS . $filename;
					self::read_dir($target_dir, $include_dirs);
				}
			}
			closedir($handler);
		} else {
			$found = 0;
			foreach ($include_dirs as $include_dir) {
				if (str_contains($path, SYS_ROOT . $include_dir)) {
					$found = 1;
					break;
				}
			}
			if ($found == 1) {
				$md5 = md5_file($path);
				$encode_prefix = str_replace(SYS_ROOT, "", $encode_prefix);
				$encode_prefix = str_replace("\\", "/", $encode_prefix);
				$this->md5_arr[base64_encode($encode_prefix)] = $md5;
				$this->md5_org_arr[$encode_prefix] = $md5;
			}

		}
	}

	public function add_file ($file_path) {
		$md5 = md5_file($file_path);
		$encode_prefix = str_replace(SYS_ROOT, "", $file_path);
		$encode_prefix = str_replace("\\", "/", $encode_prefix);
		$this->md5_arr[base64_encode($encode_prefix)] = $md5;
		$this->md5_org_arr[$encode_prefix] = $md5;
	}

	public function get_server_module_files ($module = 'system') {
		global $_GPC;
		$url = API_URL . 'free/product/list_files';
		$licence = config('licence');
		$licence['domain'] = $_SERVER['HTTP_HOST'];
		$licence['module'] = $module;
		$bra_curl = new BraCurl( [] , 'ajax');
		$res = $bra_curl->get_content($url, 'POST', ['query' => $licence]);

		return $res;
	}


	public function get_bracms_module_dirs ($themes, $module): array {
		$sys_include_dirs[] = 'bra' . DS . $module . DS; //application files
		foreach ($themes as $theme){

			$sys_include_dirs[] = 'bra_views' . DS . 'themes' . DS . "$theme" . DS . "desktop" . DS . "public" . DS; //app desktop public views
			$sys_include_dirs[] = 'bra_views' . DS . 'themes' . DS . "$theme" . DS . "desktop" . DS . $module . DS; //app desktop views

			$sys_include_dirs[] = 'bra_views' . DS . 'themes' . DS . "$theme" . DS . "mobile" . DS . "public" . DS; //app mobile public views
			$sys_include_dirs[] = 'bra_views' . DS . 'themes' . DS . "$theme" . DS . "mobile" . DS . $module . DS; //app mobile views

			$sys_include_dirs[] = 'bra_views' . DS . 'themes' . DS . "$theme" . DS . $module . DS; //app admin views

			$sys_include_dirs[] = 'public' . DS . 'themes' . DS . "$theme" . DS . "mobile" . DS . $module . DS; //app static mobile assets
			$sys_include_dirs[] = 'public' . DS . 'themes' . DS . "$theme" . DS . "desktop" . DS . $module . DS; //app static desktop assets

			$sys_include_dirs[] = 'admin_views'  . DS . 'themes'. DS . "$theme" . DS . "public" . DS; //public admin views
			$sys_include_dirs[] = 'admin_views'  . DS . 'themes'. DS . "$theme" . DS . $module . DS; //admin views
		}


		return $sys_include_dirs;
	}
}
