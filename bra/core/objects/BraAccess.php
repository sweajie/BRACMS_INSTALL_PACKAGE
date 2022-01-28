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
use Bra\core\utils\BraException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;

class BraAccess extends BraCMS {


    public static function define_client () {
        global $_W, $_GPC;
        if (is_weixin()) {
            if (BraString::is_mobile()) {
                $_W['bra_client'] = 4;
            } else {
                $_W['bra_client'] = 5;
            }
        } else {
            if (BraString::is_mobile()) {
                $_W['bra_client'] = 2;
            } else {
                $_W['bra_client'] = 1;
            }
        }
    }

    /**
     * 网页模式站点区分
     * @param int $site_id
     */
    public static function identify_site () {
        global $_W, $_GPC;
		$request_host = request()->getHost();
		$domain_data = explode(".", $request_host);
		if (module_exist('sites')) {
			$request_root_host = $domain_data[1] . "." . $domain_data[2];
			$_W['root'] = self::get_root($domain_data);
			$d_domain_model = false;
			// if not root found this will not running in app mode
			if (!$_W['root'] || $_W['root']['root_domain'] != $request_root_host) {
				$d_domain_model = true;
			}
			$group_mode = $_W['root']['groups_mode'] ? $_W['root']['groups_mode'] : 1;
			//site group mode
			switch ($group_mode) {
				case 1:
					if ($_W['root']) {
						$id = $_W['root']['site_id'];
					} else {
						$id = 1;
					}
					$site = BraCache::cache_datos_obtener_M_unico('id', $id, 'Sites');
					break;
				case 2 :
					if (count($domain_data) != 3) {
						die("error domain");
					}
					if (!$d_domain_model) {
						$_W['root_id'] = $_W['root']['id'];
						$where['site_domain'] = $domain_data[0];
						$site = BraCache::cache_datos_obtener($where, "sites");
					} else {
						$_W['sites_domain'] = $d_domain = BraCache::cache_datos_obtener(['domain' => $request_host], "sites_domain");
						if (!$d_domain) {
							die("D: Sorry , It's an Error , That's all we know! !");
						} else {
							$site = BraCache::cache_datos_obtener_unico("id", $d_domain['site_id'], "sites");
						}
					}
					break;
				case 3:
					/**
					 * Analyze current request domain info
					 * 非强制模式
					 */
					if (!isset($_GPC['site_id']) || empty($_GPC['site_id'])) {
						//读取历史
						$site_id = (int)Cookie::get('site_id');
						if ($site_id) {
							$where['id'] = $site_id;
							$site = Sites::find($where);
						}
					} else {
						//如果强制了站点
						if ((int)$_GPC['site_id']) {
							$where['id'] = (int)$_GPC['site_id'];
							$site = Sites::find($where);
						}
					}
					//如果没有找到站点  load_ default site
					if (!isset($site) || !$site) {
						$where['default'] = 1;
						$site = Sites::find($where);
					}
					break;
			}
		} else {
			$bra_safe = config('bra_safe');
			$site_id = $bra_safe['site_id'];
		}

        $_W->site = Cache::remember('sites_' . $site_id, 3600, function () use ($site_id) {
			$site =  D('sites')->bra_one($site_id);
			$site['config'] = json_decode($site['config'] , 1);
			if (!$site['config']) {
				$site['config'] = [];
			}
			return $site;
        });

        if ($bra_safe['area_sites'] ?? false) {
            $where['domain'] = $domain_data[0];
            $_W->area = Cache::remember('area_' . $where['domain'], 3600, function () use ($where) {
                return (array)D('area')->bra_where($where)->first();
            });
        }

        if (!$_W['site']) {
            BraCache::clear_cache();
            throw new BraException(" Sorry , It's an Error , That's all we know! !");
        }
    }


}

