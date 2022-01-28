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

/**
 * Class BraArray
 * @package Bra\core\objects
 */
class BraArray {

    public static function to_array ($collection) {
        return json_decode(json_encode($collection), 1);
    }

    public static function sort_by_val_with_key (&$arr, $key = 'listorder', $o = 'desc') {
        uasort($arr, function ($a, $b) use ($key, $o) {
            if ($o == 'desc') {
                return ($b[$key] ?? 0) <=> ($a[$key] ?? 0 );
            } else {
                return ($a[$key] ?? 0)  <=> ( $b[$key] ?? 0) ;
            }
        });
    }

    public static function array_to_query ($array) {
        return http_build_query($array);
    }

    /**
     * 把数组转换为 semantic 远程下拉的结构
     * @param $res_arr
     * @param string $name_key
     * @param string $id_key
     * @return mixed
     */
    public static function to_semantic_resp ($res_arr, $name_key = 'title', $id_key = 'id') {
        $new_res = $_res = [];
        foreach ($res_arr as $result) {
            $_res['value'] = $result[$id_key];
            $_res['name'] = $_res['text'] = BraView::display($name_key, $result);
            $new_res[] = $_res;
        }
        // Expected server response
        $ret['results'] = $new_res;

        return $ret;
    }

    /**
     * 布拉CMS 配置数组
     * @param $title
     * @return array|mixed
     */
    public static function get_config ($title, $area_id = 0) {
        global $_W;
        $cache_llave = $title . "_config_" . $_W['site']['id'] . '_' . $area_id;
        $config = BraCache::get_cache($cache_llave);
        if (empty($config) && $config !== []) {
            $config_m = D('config');
            $donde['title'] = $title;
            $donde['area_id'] = $area_id;
            $detail = $config_m->with_site()->bra_where($donde)->bra_one();
            if ($detail) {
                $config = json_decode($detail['data'], 1);
            } else {
                $config = [];
            }
            BraCache::set_cache($cache_llave, $config, 'bra_config');
        }

        return $config;
    }

    /**
     * 给数组做一个md5的值 , 用来缓存数据 , 目前该数据咱不能被单个清除
     * 请慎用
     * @param $a
     * @return string
     */
    public static function md5_array ($a) {
        $res2 = [];
        foreach ($a as $k => $val) {
            $_res = "$k-";
            if (is_array($val)) {
                $_res .= self::md5_array($val);
            } else {
                $_res .= "#$val";
            }
            $res2[] = $_res;
        }

        return md5(join('|', $res2));
    }

    /**
     * 从数组里面的值 得到另一个数组 可以保留原始下标 可以使用值(或者任意值)作为下标
     * @param $array
     * @param $key
     * @param int $keep_mode 0 auto   ! old  2 val with arr val
     * @param string $key_from_val
     * @return array
     */
    public static function get_array_from_array_vals ($array, $key, $keep_mode = 0, $key_from_val = '') {
        $o = [];
        foreach ($array as $k => $a) {
            $val = $a[$key];
            if ($keep_mode == 0) {// no key
                $o[] = $val;
            } else if ($keep_mode == 1) {// old key
                $o[$k] = $val;
            } else if ($keep_mode == 2) { // key from val
                $o[$a[$key_from_val]] = $val;
            }
        }

        return $o;
    }

    /**
     * 重一个数组里按照一个值和key取出一个元素
     * @param $array
     * @param $val
     * @param string $k
     * @return bool
     */
    public static function get_item ($array, $val, $k = 'id') {
        foreach ($array as $a) {
            if ($a[$k] == $val) {
                return $a;
            }
        }

        return false;
    }

    /**
     * 给数组按照某个值分组
     * @param $inputs
     * @param $key
     * @return array
     */
    public static function regroup_arr ($inputs, $key, $use_item = false) {
        $new_output = [];
        foreach ($inputs as &$input) {
            if ($use_item && isset($input['old_data'][$key])) {
                $new_output[$input['old_data'][$key]][] = $input;
            } else {
                $new_output[$input[$key]][] = $input;
            }
        }

        return $new_output;
    }

    /**
     * 给数组加上下标
     * @param $inputs
     * @param $key
     * @return array
     */
    public static function reform_arr ($inputs, $key) {
        $new_output = [];
        foreach ($inputs as &$input) {
            $input = (array)$input;
            $new_output[$input[$key]] = $input;
        }

        return $new_output;
    }

    /**数组模板 和 变量替换
     * @param $tpl_arr
     * @param $params
     * @return array
     */
    public static function parse_param_arr ($tpl_arr, $params) {
        return array_map(function ($tpl_str) use ($params) {
            return BraString::parse_param_str($tpl_str, $params);
        }, $tpl_arr);
    }

    /**
     * 按照某个字段排序
     * @param $Entrada
     * @param string $Llave
     * @param bool $desc
     * @return array
     */
    public static function Ordenar_Array (&$Entrada, $Llave = 'listorder', $desc = false) {
        $listorder = [];
        foreach ($Entrada as $key => $row) {
            $listorder[$key] = (int)$row[$Llave];
        }
        $sort = $desc == false ? SORT_ASC : SORT_DESC;
        array_multisort($listorder, $sort, $Entrada);

        return $listorder;
    }

    /**
     * 布拉CMS对各种客户端的定义以及识别
     * @return array
     */
    public static function bra_client () {
        $client = [
            ['id' => 1, 'title' => '电脑浏览器'],
            ['id' => 2, 'title' => '手机浏览器'],
            ['id' => 3, 'title' => '微信小程序'],
            ['id' => 4, 'title' => '微信手机浏览器'],
            ['id' => 5, 'title' => '微信电脑浏览器'],
            ['id' => 6, 'title' => 'H5 SPA'],
            ['id' => 8, 'title' => 'APP']
        ];

        return $client;
    }

    public static function bra_os () {
        return [
            ['id' => 1, 'title' => 'WIN'],
            ['id' => 2, 'title' => '安卓'],
            ['id' => 3, 'title' => 'IOS'],
            ['id' => 4, 'title' => 'Linux'],
            ['id' => 8, 'title' => '其他']
        ];
    }
}
