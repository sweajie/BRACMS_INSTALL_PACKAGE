<?php

namespace Bra\core\utils\debug;

use Illuminate\Support\Facades\DB;

class DataBase {

	public static function log_query () {
		DB::listen(function ($query) {
			global $_W, $_GPC;
			$res = [];
			$res['sql'] = $query->sql;
			$res['bindings'] = $query->bindings;
			$res['time'] = $query->time;
			$_W['sqls'][] = $res;
		});
	}



}
