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


use Bra\core\annex\AnnexEngine;
use Illuminate\Http\UploadedFile;
use Intervention\Image\Facades\Image;

class BraAnnex {
	public $annex_id;
	public $annex;
	public $annex_m;

	public function __construct (int $annex_id) {
		$this->annex_id = $annex_id;
		$this->annex_m = D('annex');
		try {
			$this->annex = $this->annex_m->get_item($annex_id , true , ['with_old' => true]);
		} catch (\Exception $e) {
			$this->annex = [];
		}
		//todo  get storage
	}

	public static function shrink_img ($save_path, $keep_clear = false) {
		global $_W;
		$info = [];
		try {
            // open an image file
//            $img = Image::make('public/foo.jpg');
//
//// now you are able to resize the instance
//            $img->resize(320, 240);
//
//// and insert a watermark for example
//            $img->insert('public/watermark.png');
//
//// finally we save the image as a new file
//            $img->save('public/bar.jpg');

			$image = Image::make($save_path);
			$info['width'] = $width = $image->width();
			$info['height'] = $height = $image->height();
			$is_image = true;
		} catch (\Exception $e) {
			$is_image = false;
			$width = $height = 0;
		}
		if ($is_image) {
			$annex_cfg = $_W['site']['config']['attach'] ?? [];
			$info['annex_cfg'] = $annex_cfg;
			if ($annex_cfg['compress_img'] && !$keep_clear) {
				// make thumb
				if ($width > $annex_cfg['width'] || $height > $annex_cfg['height']) {
					$image->resize($annex_cfg['width'], $annex_cfg['height']);
					if (function_exists('exif_read_data')) {
						try {
							$exif = exif_read_data($save_path);
							if (!empty($exif['Orientation'])) {
								switch ($exif['Orientation']) {
									case 8:
										$image->rotate(90)->save($save_path);
										break;
									case 3:
										$image->rotate(180)->save($save_path);
										break;
									case 6:
										$image->rotate(-90)->save($save_path);
										break;
								}
							}
						} catch (\Exception $e) {
						}
					}

					return bra_res(1, 'ok');
				}
			}

			return bra_res(500, '无需缩放', '', $info);
		} else {
			return bra_res(500, 'not an image', '', $info);
		}
	}

	/**
	 * @return  AnnexEngine
	 */
	public static function get_default () {
		global $_W;
		$donde['default'] = 1;
		$annex_config = (array) D('annex_config')->with_site()->bra_where($donde)->first();
		if (!$annex_config['config']) {
			end_resp(bra_res('bra_10006', lang('please set an default annex!')));
		}
		$provider_id = $annex_config['provider_id'];
		$provider = (array) D('annex_provider')->find($provider_id);
		$class_name = "\\Bra\\core\\annex\\" . $provider['sign'];
		$annex_engine = new $class_name(json_decode($annex_config['config'], 1));

		return $annex_engine;
	}

	/**
	 * @param $path
	 * @param $org_name
	 * @return BraAnnex
	 */
	public static function save_annex ($path, $org_name) {
		global $_W;
		$file_types = [
			'image' => 1,
			'video' => 2,
			'audio' => 3,
			'application' => 4,
			'other' => 5
		];

		$annex_m = D('annex');
		$md5 = md5_file(PUBLIC_ROOT . $path);
        $inst = (array) $annex_m->bra_where(['md5' => $md5])->first();
		if (!$inst) {
            $file = new UploadedFile($path, $org_name);
			$inst['user_id'] = $_W['user']['id'];
			$inst['filemime'] = $file->getMimeType();
			$filemime = explode('/', $inst['filemime']);
			$inst['file_type'] = $file_types[$filemime[0]] ?? '5';
			$inst['filesize'] = $file->getSize();
			$inst['create_at'] = date('Y-m-d H:i:s');
			$inst['site_id'] = $_W['site']['id'];
			// save to annex
			$inst['url'] = $path;
			$inst['provider_id'] = 1;
			$inst['id'] = $annex_m->insertGetId($inst);
		}
		$storge = new BraAnnex($inst['id']);

		return $storge;
	}

	public function annex_del_local ($id) {
		$config = $this->get_provider_config();
	}

	/**
	 * 为了获取某一个存储配置
	 * 如果提供了标注 那么就获取标志存储的配置
	 * 如果没有提供 获取默认的存储 那么获取当前附件的存储的配置
	 * 如果设置了默认的 就获取默认存储的配置
	 * @param int $is_default
	 * @param int $provider_id
	 * @return array|mixed|Model|null
	 */
	public function get_provider_config ($is_default = 0, $provider_id = 0) {
		static $annex_provider_configs = [];
		if (!$provider_id) {
			if ($is_default) {
				$donde['default'] = 1;
				$annex_config = (array) D('annex_config')->with_site()->bra_where($donde)->first();
				if (!$annex_config['config']) {
					dd('provider config not found!', $this->annex, $donde);
				}
				$provider_id = $annex_config['provider_id'];
			} else {
				$provider_id = $this->annex['old_data']['provider_id'];
			}
		}
		if (empty($annex_provider_configs[$provider_id])) {
			$annex_config = D('annex_config');
			$config = (array) $annex_config->bra_where(['provider_id' => $provider_id])->first();
			$config['config'] = json_decode($config['config'], 1);
			$config['provider'] = D('annex_provider')->get_item($provider_id);;
			$annex_provider_configs[$provider_id] = $config;
		}
		$config = $annex_provider_configs[$provider_id];
		if (!$config['provider']) {
			return $this->get_provider_config(1);
		}
		if (!$config['config']) {
			return $this->get_provider_config(1);
		}

		return $config;
	}

	public static function get_sys_provider ($provider_id = false) {
		if (!$provider_id) {
			$donde['default'] = 1;
			$annex_config = (array) D('annex_config')->bra_where($donde)->first();
			if (!$annex_config['config']) {
				abort(4.3 , 'provider config not found!');
			}
			$provider_id = $annex_config['provider_id'];
		} else {
			$annex_config = D('annex_config')->find($provider_id);
		}
		$annex_config = D('annex_config');
		$config = (array) $annex_config->bra_where(['provider_id' => $provider_id])->first();
		$config['config'] = json_decode($config['config'], 1);
		$config['provider'] = D('annex_provider')->get_item($provider_id);;
		$annex_provider_configs[$provider_id] = $config;

		return $config;
	}

	/**
	 * 删除附件
	 * @return mixed
	 */
	public function delete () {
		//if upload is needed
		$config = $this->get_provider_config();
		$provider = $config['provider'];
		if (false && $this->annex['old_data']['provider_id'] == $provider['id']) {
			return bra_res(1, 'the file is alreay in this location!');
		} else {
			$class_name = "\\app\\bra\\annex\\" . $provider['sign'];
			$annex_engine = new $class_name($config['config']);
			$res = $annex_engine->delete($this->annex);

			return $res;
		}
	}

	public function upload () {
		//if upload is needed
		$config = $this->get_provider_config(1);
		$provider = $config['provider'];
		if (false && $this->annex['old_data']['provider_id'] == $provider['id']) {
			return bra_res(1, 'the file is alreay in this location!');
		} else {
			$class_name = "\\Bra\\core\\annex\\" . $provider['sign'];
			$annex_engine = new $class_name($config['config']);
			$res = $annex_engine->upload($this->annex);

			return $res;
		}
	}

	public function get_url ($force_local = false, $apply_style = true) {
		if ($this->annex) {
			if ($force_local) {
				return PUBLIC_ROOT . $this->annex['url'];
			}
			$file_type = $this->annex['file_type'];
			switch ($file_type) {
				case  "1":
					return $this->to_media($apply_style);
				default :
					return $this->to_media(false);
			}
		} else {
			return '';
		}
	}


	public function to_media ($apply_style) {
		global $_W;
		$storge_config = $this->get_provider_config();
		$url_prefix = $storge_config['config']['url'];
		//不是url
		if (strpos($this->annex['url'], "://") !== false) {
			$url = $this->annex['url'];
		} else {
			switch ($this->annex['file_type']) {
				case 'image':
					$url_suffix = $this->annex['url'];
					if (isset($storge_config['fix_orient'])) {
						$url_suffix = $url_suffix . $storge_config['fix_orient'];
					}
					break;
				case 'video':
					if ($this->annex['convert'] == 1) {
						$url_suffix = $this->annex['play_url'];
					} else {
						$url_suffix = $this->annex['url'];
					}
					break;
				default:
					$url_suffix = $this->annex['url'];
			}


            if ($apply_style && ($storge_config['config']['img_style'] ?? false) && $storge_config['config']['img_style'] && $this->annex['file_type'] != "2") {
                $url_suffix = $url_suffix . '?' . $storge_config['config']['img_style'];
            }

			$url = $url_prefix . $url_suffix;
		}
		$this->annex_url = $url;

		return $url;
	}
}
