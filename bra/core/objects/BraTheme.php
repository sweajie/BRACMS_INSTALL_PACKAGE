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

/**
 * Class BraArray
 * @package app\bra\objects
 */
class BraTheme {

    public static function get_module_themes_list ($module) {
        $themes = D("theme")->get();
        self::collect_theme();
        $ret['desktop'] = $ret['mobile'] = 0;
        $new_themes = [];
        foreach ($themes as $theme) {
            $theme = (array)$theme;
            $ret = self::get_theme_tpls($module, $theme['theme_dir']);
            if (is_array($ret['desktop']) || is_array($ret['mobile'])) {
                $new_themes[] = $theme;
            }
        }

        return $new_themes;
    }

    public static function collect_theme () {
        $themes = static::get_themes_list();
        $model = D("theme");
        foreach ($themes as $theme) {
            $res = self::get_theme_ini($theme);
            $insert = [];
            $insert['theme_dir'] = $theme;
            $test = (array)$model->bra_where($insert)->first();
            if (!$test) {
                $insert['theme_name'] = $res['theme_name'];
                $insert['description'] = $res['description'];
                $insert['author'] = $res['author'];
                $model->insert($insert);
            }
        }
    }

    public static function get_themes_list () {
        $themes = BraFS::get_sub_dir_names(USER_VIEW_ROOT);

        return $themes;
    }

    public static function get_theme_ini ($theme) {
        $path = USER_VIEW_ROOT . $theme . DS . 'bra_theme.php';
        if (is_file($path)) {
            $dict = require_once($path);
        } else {
            $dict = [
                'theme_name' => $theme,
                'theme_dir' => $theme,
                'desktop' => true,
                'mobile' => false,
                'description' => '官方提供默认的主题,仅供开发参考!',
                'author' => 'BraCms Team'
            ];
        }

        return $dict;
    }

    public static function get_theme_tpls ($module, $theme) {
        $ret['desktop'] = $ret['mobile'] = 0;
        $prefix = USER_VIEW_ROOT;
        $mobile_path = $prefix . DS . $theme . DS . 'mobile' . DS . $module . DS;
        if (is_dir($mobile_path)) {
            $ret['mobile'] = [];
        }
        $desktop_path = $prefix . DS . $theme . DS . 'desktop' . DS . $module . DS;
        if (is_dir($desktop_path)) {
            $ret['desktop'] = [];
        }

        return $ret;
    }

    /**
     * module theme config device
     * @return array
     */
    public static function get_module_device_theme ($module_sign) {
        global $_W;
        $real_device = self::get_device();//当前客户端
        $module_config = $_W["_{$module_sign}"]["config"] ?? [];
        $theme = $module_config['theme'] ?? 'default';
        if (is_weixin()) {
            $device = "mobile";
        } else {
            if (isset($module_config['fixed_device']) && $module_config['fixed_device'] != 'auto') {
                $device = $module_config['fixed_device'];
            }else{
                $device = $real_device;
            }
        }
        return [$device, $theme];
    }

    /**
     * current device
     * @return string
     */
    public static function get_device () {
        static $device = '';
        if (!$device) {
            $device = BraString::is_mobile() ? "mobile" : "desktop";//当前客户端
        }

        return $device;
    }
}
