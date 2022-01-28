<?php
// +----------------------------------------------------------------------
// | BraUi [ New Better  ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2017 http://www.bracms.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( 您必须获取授权才能进行使用 )
// +----------------------------------------------------------------------
// | Author: new better <1620298436@qq.com>
// +----------------------------------------------------------------------

use App\Models\User;
use Bra\bra\objects\BraAd;
use Bra\core\facades\BraCache;
use Bra\core\objects\BraAnnex;
use Bra\core\objects\BraArray;
use Bra\core\objects\BraMenu;
use Bra\core\objects\BraModel;
use Bra\core\objects\BraModule;
use Bra\core\objects\BraString;
use Bra\core\objects\BraView;
use Bra\core\utils\BraException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;


function html ($html) {
	if (is_array($html)) {
		return "";
	} else {
		return html_entity_decode($html);
	}
}

function A ($key, $name = '') {
	if (is_array($key)) {
		return View::share($key);
	}

	if ($key && $name!= null) {
		View::share($key, $name);
	}
}

function lang ($key = null, $replace = [], $locale = null) {
	return trans($key, $replace = [], $locale = null);
}

/**
 * @param $model_id
 * @param string $scope
 * @param bool $new_ins
 * @return BraModel | Model | Builder
 */
function D ($model_id, $scope = '', $new_ins = false) {
	//return new BraModel($model_id);
	if (empty($model_id)) {
		throw new BraException('bra_10005 布拉模型未指定' . $model_id);
	}
	if ($new_ins) {
		return new BraModel($model_id, $scope);
	}

	static $bra_models = [];
	if (!isset($bra_models[$model_id . $scope]) || !$bra_models[$model_id . $scope]) {
		$bra_models[$model_id . $scope] = new BraModel($model_id, $scope);
	}
	return $bra_models[$model_id . $scope];
}

/**
 * User Template
 * @param string $tpl
 * @param string $device
 * @return \Illuminate\Contracts\View\View | array
 */
function T ($tpl = '', $device = '') {
	global $_W, $_GPC;
	if (defined("BRA_ADMIN")) {
		return A_T($tpl);
	} else {
		$resp_clients = [3 , 6 , 8 , 9];
		if (!in_array($_GPC['bra_client'] , $resp_clients)) {
			$tpl = $tpl ? $tpl : ROUTE_M . '.' . ROUTE_C . '.' . ROUTE_A;
			$device = $device ? $device : $_W['device'];

			return View::make($_W['theme'] . '.' . $device . '.' . $tpl, ['_W' => $_W, '_GPC' => $_GPC]);
		} else {
			$els = view()->getShared();
			unset($els['app']);
			unset($els['errors']);
			unset($els['__env']);

			return $els;
		}
	}
}

/**
 * for admin template
 * @param string $tpl
 * @return \Illuminate\Contracts\View\View
 */
function A_T ($tpl = '') {
	global $_W, $_GPC;
	$tpl = strtolower($tpl ? $tpl : ROUTE_M . '.' . ROUTE_C . '.' . ROUTE_A);
	$theme = $_W['theme'] ? $_W['theme'] . '.' : "default.";

	return View::make("themes." . $theme . $tpl, ['_W' => $_W, '_GPC' => $_GPC]);
}

function module_exist ($sign) {
	$module = BraModule::module_exist($sign);
	if (is_error($module)) {
		return false;
	} else {
		return $module['data'];
	}
}

function bra_res ($code = 0, $msg = '', $url = '', $data = [], $js = '') {
	$ret['code'] = $code;
	$ret['msg'] = $msg;
	$ret['url'] = $url;
	$ret['data'] = $data;
	$ret['javascript'] = $js;

	return $ret;
}

function is_error ($res) {
	return !$res || $res['code'] !== 1;
}

function make_url ($route, $params = [], $mapping = []) {
	if (count(explode('/', $route)) % 2 != 1) {
		abort(403, 'params not allowed for ' . $route);
	}
	if (!is_array($params)) {
		$new_vars = [];
		$vars = explode("&", $params);
		foreach ($vars as $var) {
			$var = explode("=", $var);
			$new_vars[$var[0]] = $var[1];
		}
	} else {
		$new_vars = $params;
	}
	if ($mapping) {
		foreach ($new_vars as &$new_var) {
			$new_var = BraString::parse_param_str($new_var, $mapping);
		}
	}
	$new_vars = BraArray::array_to_query($new_vars);
	if ($new_vars) {
		return url($route . '?' . $new_vars);
	} else {
		return url($route);
	}
	//Generate URL
}

function build_back_a ($menu, $vars, $mapping, $badge_str = '') {
	$url = BraMenu::back_url($menu, $vars, $mapping);
	$m = $c = $h = $w = '';
	if (!empty($menu['mini'])) {
		$m = ' bra-mini="' . $menu['mini'] . '"  ';
	}
	if (!empty($menu['class'])) {
		$c = ' class="' . $menu['class'] . ' " ';
	}
	if ($badge_str) {
		$badge_str = "<span class='badge'>$badge_str</span>";
	}
	if (isset($_W['develop']) && $_W['develop'] != 1) {
		$link = "data-href='$url' ";
	} else {
		$link = "href='$url' ";
	}

	return '<a type="button" ' . $link . $m . $c . $w . $h . ' >' . $menu['menu_name'] . $badge_str . '</a>';
}

function build_back_link ($menu, $vars, $mapping = []) {
	if (is_numeric($menu)) {
		$menu = BraMenu::fetch_menu($menu);
	}

	return BraMenu::back_url($menu, $vars, $mapping);
}

function is_weixin () {
	return strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger');
}

function annex_url ($annex_id, $force_local = false) {
	if (empty($annex_id)) {
		return "";
	}
	$annex = new BraAnnex((int)$annex_id);

	return $annex->get_url($force_local);
}

function render_ad_group ($gid) {
	global $_W;
	$llave = "ad_group_" . $_W['site']['id'] . "_" . $gid;
	$ads = BraCache::get_cache($llave);
	if (!$ads) {
		$bra_ad = new BraAd($gid);
		$ads = $bra_ad->render();
		BraCache::set_cache($llave, $ads);
	}

	return $ads;
}

function is_bra_access ($use_strict_mode = 2, $method = 'post', $check_token = false) {
	global $_GPC;
	$valid = false;
	switch ($use_strict_mode) {
		case 0:
			$valid = $_GPC['bra_action'] && ($_GPC['bra_action'] === $method);
			break;
		case 2:
			if ($method == 'post') {
				$valid = Request::method() == 'POST';
			}
			if ($method == 'get') {
				$valid = Request::method() == 'GET';
			}
			if ($method == 'patch') {
				$valid = Request::method() == 'PATCH';
			}
			if ($method == 'delete') {
				$valid = Request::method() == 'DELETE';
			}
			if ($method == 'put') {
				$valid = Request::method() == 'PUT';
			}
			if ($method == 'options') {
				$valid = Request::method() == 'OPTIONS';
			}
			if ($method == 'ajax') {
				$valid = Request::ajax();
			}
			break;
	}
//    app('session')->
	if ($valid && $check_token && false === check_token()) {
		end_resp(bra_res(4000, "对不起，请勿重复提交，刷新页面重试！！"));
	}

	return $valid;
}

function check_token () {
	global $_GPC;

	return csrf_token() == $_GPC['_token'];
}

function strip_date ($date, $type, $empty = '') {
	switch ($type) {
		case 'year' :
			return date('m-d H:i', strtotime($date));
		case 'time' :
			return date('Y-m-d', strtotime($date));
	}

	return $empty;
}

function encode_char ($start, $end, $str) {
	return BraString::encode_char($start, $end, $str);
}

function make_share_url () {
	global $_W;
	$url = BraString::url_set_value($_W['current_url'], "refer", $_W['user']['id']);

	return $url;
}

function bra_go_to ($url) {
	$response = redirect($url);
	$response->send();
	app()->terminate();
}

function end_resp ($bra_res, $type = null, $code = 200) {
	if (empty($type)) {
		if (app()->request->ajax()) {
			$type = 'json';
		} else {
			$type = 'html';
		}
	}
	$bra_res['url'] = $bra_res['url'] ? $bra_res['url'] : 'javascript:void(0);';
	bra_end_resp($bra_res['code'], $bra_res['msg'], $bra_res['data'], $type, $code, $bra_res['url']);
}

function bra_end_resp ($code, $msg, $data = [], $type = 'json', $status_code = 200, $url = 'javascript:void(0);') {
	$rest = [];
	$rest['code'] = $code;
	$rest['msg'] = $msg;
	$rest['data'] = $data;
	$rest['url'] = $url;
	if (!app()->request->ajax() && $type == 'html') {
		if ($code == 1) {
			$tpl = "bra_ok";
		} else {
			$tpl = "bra_error";
		}
		if (isset($data['time']) && $data['time'] == 0) {
			$rest = redirect($url);
		} else {
			$rest = View::make("public.common.$tpl", $rest);
		}
	}
	rest_api($rest, $type, $status_code);
}

function rest_api ($rest, $type, $status_code = 200) {
	$response = Response::make($rest, $status_code);
	$response->send();
	app()->terminate();
	exit();
}

/**
 * @param null $user_id
 * @return User
 */
function refresh_user ($user_id = null , $lock = false): User {
	global $_W;
	$user_id = $user_id ? $user_id : (int)$_W['user']['id'];


	return User::lock($lock)->find($user_id);
}

function bra_asset ($module_sign, $file_name, $secure = null) {
	global $_W;
	$path = 'themes/' . $_W['theme'] . '/' . $_W['device'] . '/' . $module_sign . '/' . $file_name;

	return app('url')->asset($path, $secure);
}



function log_events(){
	global $_W , $_GPC;
	foreach ($_W['events'] as $event){
		\Illuminate\Support\Facades\DB::table('sys_event')->insert($event);
	}
}
