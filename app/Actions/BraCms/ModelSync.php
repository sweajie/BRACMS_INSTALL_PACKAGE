<?php

namespace App\Actions\BraCms;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ModelSync {
	public static string $host = "www.bracms.com";

	public static function get_module_models ($module_sign) {
		$params['module'] = $module_sign;

		return self::requestApi('/free/product/list_models', self::$host, '', $params);
	}

	public static function get_modules () {

		return self::requestApi('/free/product/list_modules', self::$host, '', []);
	}

	/**
	 * @param $api 'bra/bra_cloud/cloud_api'
	 * @param $host
	 * @param $page_name
	 * @param $params
	 * @param bool $https
	 * @return mixed
	 */
	public static function requestApi ($api, $host, $page_name, $params, $https = false) {
		$params = self::make_params($page_name, $params);
		$api_url = self::make_url($api, $host, $https);

		return self::do_api($api_url, $params);
	}

	public static function make_params ($page_name, $params) {
		$params['product_licence_code'] = config("licence.product_licence_code");
		$params['domain'] =  $_SERVER['HTTP_HOST'];
		return $params;
	}

	public static function make_url ($api, $host, $https = true) {
		$https = $https ? 'https://' : 'http://';
		$api_url = $https . $host . $api;

		return $api_url;
	}

	public static function do_api ($api_url, $params) {
		$response = Http::withHeaders([
			'Accept' => 'application/json, text/javascript, */*; q=0.01',
			'x-requested-with' => 'XMLHttpRequest'
		])->post($api_url, $params);

		return $response->json();
	}

	public static function sync_install_model (string $table, $module ,$data_type = 'update') {
		$host = self::$host;
		$params["table_name"] = $table;
		$params["data_type"] = $data_type;
		$params["module"] = $module;
		$res = self::requestApi('/free/product/model_schema', $host, '', $params);

		if (is_error( $res)) {
			return $res;
		}

		$exc_res = self::sql_execute($res['data']);
		return bra_res(1);
	}

	public static function sync_update_model (string $table, $module ,$data_type = 'update') {
		$host = self::$host;
		$params["table_name"] = $table;
		$params["data_type"] = $data_type;
		$params["module"] = $module;
		$res = self::requestApi('/free/product/update_schema', $host, '', $params);

		$m_db_config = Db::connection()->getConfig();
		$prefix = $m_db_config['prefix'];
		$table_name = $prefix . $table;

		if ($res['code'] == 1) {
			/**
			 * create table
			 */
			if (!self::tableExists($table)) {
				$res['code'] = self::sql_execute($res['data']['struct_sql']);
			}
			$fields = $res['data']['fields'];
			foreach ($fields as $field) {
				if ($field['Field'] == "id") {
					continue;
				}
//			"Field" => "account_name"
//          "Type" => "varchar(255)"
//          "Null" => "NO"
//          "Key" => ""
//          "Default" => ""
//          "Extra" => ""
				$has = Schema::hasColumn($table, $field['Field']);
				$types = "bigInteger,binary,boolean,date,dateTime,dateTimeTz,decimal,integer,json,longText,mediumText,smallInteger,string,text,time,unsignedBigInteger,unsignedInteger, unsignedSmallInteger,uuid";
				$types = explode(",", $types);
				$type = Schema::getColumnType($table, $field['Field']);
				if ($has and !in_array($type, $types)) {
					continue;
				}
				Schema::table($table, function (Blueprint $table) use ($field, $has) {
					if (str_contains($field['Type'], 'varchar')) {
						$col_define = $table->string($field['Field'])->nullable($field['Null'] != "NO")->default($field['Default']);
					}
					if (str_contains($field['Type'], 'int')) {
						if (str_contains($field['Type'], 'tinyint')) {
							$col_define = $table->smallInteger($field['Field'])->nullable($field['Null'] != "NO")->default($field['Default']);
						} else {
							$col_define = $table->integer($field['Field'])->nullable($field['Null'] != "NO")->default($field['Default']);
						}
						if (str_contains($field['Type'], 'unsigned')) {
							$col_define->unsigned();
						}
					}
//
					if (str_contains($field['Type'], 'text')) {
						$col_define = $table->longText($field['Field'])->nullable($field['Null'] != "NO")->default($field['Default']);
					}
					if (str_contains($field['Type'], 'datetime')) {
						$col_define = $table->dateTime($field['Field'])->nullable($field['Null'] != "NO")->default($field['Default']);
					}
					if ($col_define && $has) {
						$col_define->change();
					}
				});
			}



			//data sql
			if($res['data']['data_sql']){

				$exc_res = self::sql_execute($res['data']['data_sql']);
			}



			echo json_encode($res);
		} else {
			$res['code'] = 0;
			echo json_encode($res);
		}
	}

	public static function sync_menu ($module_sign) {

		$params['bra_action'] = 'post';
		$params["module_sign"] = $module_sign;
		$res = self::requestApi('/free/product/list_users_menu', self::$host, '', $params);

		foreach ($res['data'] as $re) {
			$_res = DB::table('user_menu')->updateOrInsert($re, ['id' => $re['id']]);
		}

		return "";
	}

	public static function sync_user_roles ($module_sign) {
		$params['bra_action'] = 'post';
		$params["module_sign"] = $module_sign;
		$res = self::requestApi('/update/sync/list_user_roles', self::$host, '', $params);

		foreach ($res as $re) {
			$_res = DB::table('user_roles')->updateOrInsert($re, ['id' => $re['id']]);
		}

		return "";
	}

	/**
	 * 执行mysql.sql文件，创建数据表等
	 * @param string $sql sql语句
	 * @return bool
	 */
	public static function sql_execute ($sql) {
		$sqls = self::sql_split($sql);
		if (is_array($sqls)) {
			foreach ($sqls as $sql) {
				if (trim($sql) != '') {
					Db::statement($sql);
				}
			}
		} else {
			Db::statement($sqls);
		}

		return true;
	}

	/**
	 * 处理sql语句，执行替换前缀都功能。
	 * @param string $sql 原始的sql，将一些大众的部分替换成私有的
	 * @return array
	 */
	public static function sql_split ($sql) {
		global $_W;
		$ret = array();
		$num = 0;
		$queriesarray = explode(";\n", trim($sql));
		unset($sql);
		foreach ($queriesarray as $query) {
			$ret[$num] = '';
			$queries = explode("\n", trim($query));
			$queries = array_filter($queries);
			foreach ($queries as $query) {
				$str1 = substr($query, 0, 1);
				if ($str1 != '#' && $str1 != '-') $ret[$num] .= $query;
			}
			$num++;
		}

		return $ret;
	}

	/**
	 * 表是否存在
	 * @param $table
	 * @return bool
	 */
	public static function tableExists ($table) {
		return Schema::hasTable($table);
	}
}
