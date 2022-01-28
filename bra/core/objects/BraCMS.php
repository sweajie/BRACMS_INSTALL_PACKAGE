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

class BraCMS {

	protected static array $wireds = [];
	protected static string $wired = "";

	/**
	 * @param $wired
	 * @param $wired_two
	 */
	public static function add ($wired, $wired_two) {
		if (empty(static::$wireds[$wired])) {
			$wired = static::safeName($wired);
			static::$wireds[$wired] = $wired_two;
			static::$wired .= sprintf('
        function %s() {
            return call_user_func_array(
                %s::get(__FUNCTION__),
                func_get_args()
            );
        }
', $wired, __CLASS__);
		}
	}

	/**
	 * @param $wired
	 * @return false|string
	 */
	protected static function safeName ($wired) {
		$wired = substr($wired, 0, 64);

		return $wired;
	}

	/**
	 * @param $wired
	 * @return mixed
	 */
	public static function get ($wired) {
		return self::$wireds[$wired];
	}

	/**
	 * @return string
	 */
	public static function wired () {
		return self::$wired;
	}
}
