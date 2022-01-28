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
namespace Bra\core\utils;

define('X_PI', 3.14159265358979324 * 3000.0 / 180.0);

use Bra\core\objects\BraCurl;

class BraCoordinate
{
    private static $pi = 3.14159265358979324;  // 圆周率
    private static $a = 6378245.0; // WGS 长轴半径
    private static $ee = 0.00669342162296594323; // WGS 偏心率的平方

    public static function wgs_bd($x, $y)
    {
        $res = self::wgs_gcj($x, $y);
        return self::gcj_bd($res[0], $res[1]);
    }

    public static function wgs_gcj($wgs_lon, $wgs_lat)
    {

        if (self::outOfChina($wgs_lon, $wgs_lat)) {
            return [$wgs_lon, $wgs_lat];
        }
        $x_pi = X_PI;
        $dLat = self::transformLat($wgs_lon - 105.0, $wgs_lat - 35.0);
        $dLon = self::transformLon($wgs_lon - 105.0, $wgs_lat - 35.0);
        $radLat = $wgs_lat / 180.0 * $x_pi;
        $magic = sin($radLat);
        $magic = 1 - self::$ee * $magic * $magic;
        $sqrtMagic = sqrt($magic);
        $dLat = ($dLat * 180.0) / ((self::$a * (1 - self::$ee)) / ($magic * $sqrtMagic) * self::$pi);
        $dLon = ($dLon * 180.0) / (self::$a / $sqrtMagic * cos($radLat) * self::$pi);

        $mgLat = $wgs_lat + $dLat;
        $mgLon = $wgs_lon + $dLon;

        $gcj_loc = [$mgLon, $mgLat];
        return $gcj_loc;
    }

    private static function outOfChina($lon, $lat)
    {
        if ($lon < 72.004 || $lon > 137.8347)
            return true;
        if ($lat < 0.8293 || $lat > 55.8271)
            return true;

        return false;
    }

    private static function transformLat($x, $y)
    {
        $ret = -100.0 + 2.0 * $x + 3.0 * $y + 0.2 * $y * $y + 0.1 * $x * $y + 0.2 * sqrt(abs($x));
        $ret += (20.0 * sin(6.0 * $x * self::$pi) + 20.0 * sin(2.0 * $x * self::$pi)) * 2.0 / 3.0;
        $ret += (20.0 * sin($y * self::$pi) + 40.0 * sin($y / 3.0 * self::$pi)) * 2.0 / 3.0;
        $ret += (160.0 * sin($y / 12.0 * self::$pi) + 320 * sin($y * self::$pi / 30.0)) * 2.0 / 3.0;
        return $ret;
    }

    private static function transformLon($x, $y)
    {
        $ret = 300.0 + $x + 2.0 * $y + 0.1 * $x * $x + 0.1 * $x * $y + 0.1 * sqrt(abs($x));
        $ret += (20.0 * sin(6.0 * $x * self::$pi) + 20.0 * sin(2.0 * $x * self::$pi)) * 2.0 / 3.0;
        $ret += (20.0 * sin($x * self::$pi) + 40.0 * sin($x / 3.0 * self::$pi)) * 2.0 / 3.0;
        $ret += (150.0 * sin($x / 12.0 * self::$pi) + 300.0 * sin($x / 30.0 * self::$pi)) * 2.0 / 3.0;

        return $ret;
    }

    public static function gcj_bd($x, $y)
    {
        $x_pi = X_PI;
        $z = sqrt($x * $x + $y * $y) + 0.00002 * sin($y * $x_pi);
        $theta = atan2($y, $x) + 0.000003 * cos($x * $x_pi);
        $bd_x = $z * cos($theta) + 0.0065;
        $bd_y = $z * sin($theta) + 0.006;
        $bg_loc = [$bd_x, $bd_y];
        return $bg_loc;
    }

    public static function bd_gcj($bd_loc)
    {
        $x_pi = X_PI;
        $x = $bd_loc->x - 0.0065;
        $y = $bd_loc->y - 0.006;
        $z = sqrt($x * $x + $y * $y) - 0.00002 * sin($y * $x_pi);
        $theta = atan2($y, $x) - 0.000003 * cos($x * $x_pi);
        $gc_x = $z * cos($theta);
        $gc_y = $z * sin($theta);
        $gc_loc = [$gc_x, $gc_y];
        return $gc_loc;
    }

    public static function gcj_wgs($lon, $lat)
    {
        $to = self::wgs_gcj($lon, $lat);
        $gcl_lon = $to[0];
        $gcl_lat = $to[1];
        $d_lat = $gcl_lat - $lat;
        $d_lon = $gcl_lon - $lon;
        return [$lon - $d_lon, $lat - $d_lat];
    }

    public static function t__lng_lat_2_address($lng, $lat, $coordtype)
    {
        $baidu_api = "http://api.map.baidu.com/geocoder/v2/?ak=MKqiLv4hVg6G9gU6tIzbR9OBASGBt4zW&coordtype=$coordtype&callback=&location=$lat,$lng&output=json&pois=1&extensions_road=true&radius=150";

        $curl = new BraCurl();
        $res = $curl->get_content($baidu_api);

        return $res;
    }
}
