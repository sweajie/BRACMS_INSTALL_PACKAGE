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

class BraFS {
	public static function move_file ($file_name, $file_path, $new_path, $rename_flag = true) {
		if ($rename_flag) {
			$file_name = md5_file($file_path) . '.' . explode('.', $file_name)[1];
		}
		$res = copy($file_path, $new_path . '/' . $file_name);
		if ($res) {
			//unline
			@unlink($file_path);

			return $new_path . $file_name;
		} else {
			return false;
		}
	}

	public static function move_folder ($fileFolder, $newPath, $reNameFlag = false) {
		$temp = @scandir($fileFolder);//1、首先先读取文件夹
		//遍历文件夹
		foreach ($temp as $v) {
			$a = $fileFolder . '/' . $v;
			if (is_dir($a)) {//如果是文件夹则执行
				//判断是否为系统隐藏的文件.和..  如果是则跳过否则就继续往下走，防止无限循环再这里。
				if ($v == '.' || $v == '..') {
					continue;
				}
				//因为是文件夹所以再次调用自己这个函数，把这个文件夹下的文件遍历出来
				self::move_folder($a, $newPath, $reNameFlag);
			} else {
				//echo $v,"<br/>";
				$newName = $v;
				if ($reNameFlag) {
					$newName = uniqid() . '.' . explode('.', $v)[1];
				}
				copy($a, $newPath . '/' . $newName);
			}
		}
	}

	public static function get_dir_files ($path, $debug = false) {
		global $_W;
		$_W['dir_num'] = $_W['dir_num'] ?? 0;
		if ($debug && $_W['dir_num'] > 10000) {
			abort(403, "too much rec " . $path);
		}
		if (!is_dir($path)) {
			return [];
		}
		$dirs = [];
		$handler = opendir($path);
		while (($filename = @readdir($handler)) !== false) {
			if (substr($filename, 0, 1) != ".") {
				$target_dir = $path . DS . $filename;
				if (is_file($target_dir)) {
					$dirs[] = $filename;
				}
			}
		}
		closedir($handler);

		return $dirs;
	}

	public static function get_sub_dir_names ($path, $debug = false) {
		global $_W;
		$_W['dir_num'] = $_W['dir_num'] ?? 0;
		if ($debug && $_W['dir_num'] > 10000) {
			dd($path);
		}
		$dirs = [];
		$handler = opendir($path);
		if($handler){
			if($handler){
				while (($filename = @readdir($handler)) !== false) {
					if (substr($filename, 0, 1) != ".") {
						$target_dir = $path . DS . $filename;
						if (is_dir($target_dir)) {
							$dirs[] = $filename;
						}
					}
				}
				closedir($handler);
			}
		}

		return $dirs;
	}

	public static function mkdirs ($path) {
		if (!is_dir($path)) {
			self::mkdirs(dirname($path));
			mkdir($path);
		}

		return is_dir($path);
	}

	/**
	 * 删除目录
	 * @param $dir_name
	 * @return bool
	 */
	public static function delete_dir ($dir_name) {
		$result = false;
		if (!is_dir($dir_name)) {
			return $result;
		}
		$handle = opendir($dir_name); //打开目录
		while (($file = readdir($handle)) !== false) {
			if ($file != '.' && $file != '..') { //排除"."和"."
				$dir = $dir_name . DIRECTORY_SEPARATOR . $file;
				//$dir是目录时递归调用deletedir,是文件则直接删除
				is_dir($dir) ? static::delete_dir($dir) : unlink($dir);
			}
		}
		closedir($handle);
		$result = rmdir($dir_name) ? true : false;

		return $result;
	}

	public static function write_config ($config_name, $data) {
		$path = config_path();
		$size = file_put_contents($path . DS . $config_name . ".php", '<?php return ' . var_export($data, true) . ';');

		return $size . '@' . $path . DS . $config_name . ".php";
	}

	public static function file_ext ($filename) {
		return strtolower(trim(substr(strrchr($filename, '.'), 1, 10)));
	}

	public static function download_long ($url, $save_path) {
		set_time_limit(0);
		$file = fopen($url, 'rb');
		if ($file) {
			$newf = fopen($save_path, 'wb');
			if ($newf) {
				while (!feof($file)) {
					fwrite($newf, fread($file, 1024 * 8), 1024 * 8);
				}
			}
		}
		if ($file) {
			fclose($file);
		} else {
			return bra_res(404, '下载文件失败1');
		}
		if ($newf) {
			fclose($newf);
		} else {
			return bra_res(506, '下载文件失败2');
		}
		if ($file && $newf) {
			$ret = [
				'code' => 1,
				'msg' => '文件处理成功！',
			];

			return $ret;
		} else {
			$ret = [
				'code' => 500,
				'msg' => '文件处理成功！',
			];

			return $ret;
		}
	}

	public static function writable_check ($path) {
		$is_writable = bra_res(1 , "OK");
		if (!is_dir($path)) {
			return bra_res(500 , 'NOT A DIR');
		}
		$dir = opendir($path);
		while (($file = readdir($dir)) !== false) {
			if ($file != '.' && $file != '..') {
				if (is_file($path . '/' . $file)) {
					//是文件判断是否可写，不可写直接返回0，不向下继续
					if (!is_writable($path . '/' . $file)) {
						return '0';
					}
				} else {
					//目录，循环此函数,先判断此目录是否可写，不可写直接返回0 ，可写再判断子目录是否可写
					$dir_wrt = static::dir_writeable($path . '/' . $file);
					if ($dir_wrt == '0') {
						return bra_res(500 , $path . '/' . $file . 'NOT WRITEABLE');
					}
					$is_writable = static::writable_check($path . '/' . $file);
				}
			}
		}

		return $is_writable;
	}

	public static function dir_writeable ($dir) {
		$writeable = 0;
		if (is_dir($dir)) {
			if ($fp = @fopen("$dir/chkdir.bra", 'w')) {
				@fclose($fp);
				@unlink("$dir/chkdir.bra");
				$writeable = 1;
			} else {
				$writeable = 0;
			}
		}

		return $writeable;
	}

	public function read_dir ($path = '') {
		static $count = 0;
		$count++;
		$path = str_replace("//", "/", $path);
		$path = str_replace("\\\\", "\\", $path);
		if (is_dir($path)) {
			$handler = opendir($path);
			while (($filename = @readdir($handler)) !== false) {
				if (substr($filename, 0, 1) != ".") {
					$target_dir = $path . DS . $filename;
					self::read_dir($target_dir);
				}
			}
			closedir($handler);
		} else {
			if (is_file($path)) {
				$this->files[] = $path;
			}
		}
	}

	public static function save_image ($avatar, $user_id, $host ,$pro =  "//") {
		if (!str_contains($avatar, $host)) {
			$headimg = file_get_contents($avatar);
			self::mkdirs(PUBLIC_ROOT . 'upload_file' . DS . $user_id . DS);
			$target = 'upload_file/' . $user_id . '/headimg_' . $user_id . '.jpg';
			file_put_contents(PUBLIC_ROOT . $target, $headimg);
			$avatar = "/" . $target;
		}

		return $pro .$host.$avatar;
	}
}
