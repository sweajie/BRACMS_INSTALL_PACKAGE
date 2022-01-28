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

use Exception;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;

/**
 * Class BraView
 * @package app\bra\objects
 */
class BraView {
    public static function parse_param_obj($tpl_arr, $params) {
        $tpl_arr = (array)$tpl_arr;

        return self::parse_param_arr($tpl_arr, $params);
    }

    public static function parse_param_arr($tpl_arr, $params) {
        return array_map(function ($tpl_str) use ($params) {
            return BraString::parse_param_str($tpl_str, $params);
        }, $tpl_arr);
    }

    public static function assign($assigns) {
        View::share($assigns);
    }

    public static function compile_blade_arr($tpl_arr, $params) {

        return array_map(function ($tpl_str) use ($params) {

            return self::compile_blade_str($tpl_str, $params);
        }, $tpl_arr);
    }

    public static function compile_blade_str($tpl_str, array $params = array()) {
        $generated = Blade::compileString($tpl_str);
        ob_start() and extract($params, EXTR_SKIP);
        // We'll include the view contents for parsing within a catcher
        // so we can avoid any WSOD errors. If an exception occurs we
        // will throw it out to the exception handler.
        try {
            eval('?>' . $generated);
        }

            // If we caught an exception, we'll silently flush the output
            // buffer so that no partially rendered views get thrown out
            // to the client and confuse the user with junk.
        catch (Exception $e) {
            ob_get_clean();
            throw $e;
        }
        $content = ob_get_clean();

        return $content;
    }
}
