<?php
// +----------------------------------------------------------------------
// | BraCMS [ New Better  ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2017 http://www.bracms.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( 您必须获取授权才能进行使用 )
// +----------------------------------------------------------------------
// | Author: new better <1620298436@qq.com>
// +----------------------------------------------------------------------

namespace Bra\core\objects;

use Bra\core\facades\BraCache;
use Bra\core\models\Modules;
use Bra\core\utils\BraException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BraModule {

    public static function create_bra_processor ($module_sign, $engine, $args = []) {
        $module_class = Str::studly($module_sign);
        $classname = "\\Bra\\{$module_sign}\\utils\\{$module_class}{$engine}Processor";
        if (!class_exists($classname)) {
            Log::write('error' ,"----classname-------");
            Log::write('error' , $classname . " Definition File Not Found $module_sign @ $engine" );
            Throw new BraException($classname . " Definition File Not Found $module_sign @ $engine");
        } else {
            return new $classname(...$args);
        }
    }
    /**
     * 初始化一个模块获得其相关配置参数 放在全局表里里面
     * _module_sign
     * _module_sign_config
     * _module_sign_navs
     * @param $module_sign
     * @param bool $set_cookie
     * @return mixed
     */
    public static function module_init ($module_sign) {
        global $_W , $_GPC;
        if(!is_numeric($module_sign)  && !is_string($module_sign)){
            throw  new BraException('module not exist');
        }
        $init_res = BraModule::module_exist($module_sign);
        if (is_error($init_res)) {
			return bra_res(403, "模块已经禁用" . $module_sign);
        } else {
            $_W['_' . $module_sign] = $init_res['data'];
            $_W['_' . $module_sign]['config'] = self::get_module_setting($module_sign);
            $last_ex_modules = ["bra", "wechat", "bra_admin"];
            if (!in_array("bra_admin", $last_ex_modules)) {
                cookie('last_m_' . $_W['site']['id'], $module_sign);
            }

            return bra_res(1, $module_sign , '' , $_W['_' . $module_sign]);
        }
    }

    public static function module_exist ($module_sign) {
        static $modules;
        $cache_llave = "modules/module_$module_sign";
        $modules[$module_sign] = $module = BraCache::get_cache($cache_llave);
        if (!$module && $module !== false) {
            if (is_numeric($module_sign)) {
                $modules[$module_sign] = Modules::find($module_sign);
            } else {
                $modules[$module_sign] = Modules::where(['module_sign' => $module_sign])->first();
            }
            $modules[$module_sign] = $modules[$module_sign] ? $modules[$module_sign] : false;
            BraCache::set_cache($cache_llave, $modules[$module_sign]);
        }
        if ($modules[$module_sign] && $modules[$module_sign]['status'] == 1) {
            return bra_res(1, '', '', $modules[$module_sign]);
        } else {
            return bra_res(403 , '模块不存在');
        }
    }

    public static function get_module_setting ($module_sign) {
        static $module_configs;
        if (!isset($module_configs[$module_sign])) {
            $module_configs[$module_sign] = self::get_module_set($module_sign);
        }

        return $module_configs[$module_sign];
    }

    /** get module set **/
    public static function get_module_set ($module_sign) {
        global $_W;
        $chche_llave = $module_sign . "_modules_setting_" . $_W['site']['id'];

        return Cache::remember($chche_llave, 3600, function () use ($module_sign) {
            $module_res = static::module_exist($module_sign);
            if (!is_error($module_res)) {
                $module = $module_res['data'];
                $where = [];
                $where['module_id'] = $module['id'];
                $modules_set = (array)D("modules_setting")->with_site()->bra_where($where)->first();

                return $config = $modules_set ? json_decode($modules_set['setting'], 1) : [];
            } else {
                return [];
            }
        });
    }


    public static function get_nav(int $module_id, $pos = '' , $route = '' , $__mapping = []){
        global $_W , $_GPC;
        $cache_llave = "modules/module_$module_id" . '_' . $_W['site']['id'] . $pos . $route;
        $__mapping['site'] = $_W['site'];
        $navs = BraCache::get_cache($cache_llave);
        if (!$navs  && $navs!== []) {
            $where = [];
            $where['module_id'] = $module_id;
            $where['show_menu'] = 1;
            $navs = D('nav')->bra_where($where)->orderBy('listorder' , 'desc')->list_item(true , ['with_old' => true]);

            if($navs){
                foreach ($navs as $k => &$data) {
                    $__mapping['id'] = $data['id'];
                    if (strpos($data['link_url'], '}')) {
                        $data['link_url'] = BraString::parse_param_str($data['link_url'], $__mapping);
                    }
                    if (strpos($data['link_url'], 'http') === 0) {
                        $data['__url'] = $data['link_url'];
                    } else {
                        $data['__url'] = make_url($data['link_url'], ['nav_id' => $data['id']]);
                    }

                    $data['mini_path'] = BraString::parse_param_str($data['mini_path'], $__mapping);

                    if ($pos) {
                        if (strpos($data['old_data']['position'], ",$pos,") === false) {
                            unset($navs[$k]);
                        }
                    }
                }

                if (empty($navs)) {
                    $navs = [];
                }

            }
            BraCache::set_cache($cache_llave, $navs , "bra_nav");
        }

        return $navs;
    }
}
