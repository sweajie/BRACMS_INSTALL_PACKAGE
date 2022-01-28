<?php

namespace Bra\bra\pages;

use App\Models\User;
use Bra\core\facades\BraCache;
use Bra\core\pages\BraController;
use Bra\wechat\medias\WechatMedia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Request;

class PassportPage extends BraController {

	public function bra_user_union_login ($query) {
		global $_W, $_GPC;
		if ($_GPC['bra_uuid']) {
			$this->page_data['_GPC'] = $_GPC;
			BraCache::set_cache("bra_login_uuid:" . $_GPC['bra_uuid'], false);
			$this->page_data['login_uri'] = make_url('bra/passport/union_login', ['uuid' => $_GPC['bra_uuid']]);
		}
	}

	public function bra_passport_logout (Request $request) {
		global $_W , $_GPC;

		Auth::guard('web')->logout();
		Auth::guard('api')->logout();
//		Auth::logout();
//		app()->request->session()->invalidate();
//		app()->request->session()->regenerateToken();
		DB::table('oauth_access_tokens')->where('user_id' , '=' , $_W['user']['id'])->delete();
		return $this->page_data = redirect(url('/bra/passport/login'));
	}

	public function bra_passport_login ($query) {
		global $_W, $_GPC;
		$forward = request()->cookie('login_forward_' . $_W['site']['id']);
		if (is_bra_access(0, 'post')) {
			if (!captcha_check($_GPC['code'])) {
				return $this->page_data = bra_res(503, lang('验证码错误!'));
			}
			$data = $query['data'];
			$user = (array)D('users')->bra_where(['user_name' => $data['user_name']])->first();
			if (Hash::check($data['password'], $user['password'])) {
				//get users admin
				if ($user['status'] != 99) {
					return $this->page_data = bra_res(500, "登录失败 , 您无权访问!");
				}
				Auth::loginUsingId($user['id'], true);

				return $this->page_data = bra_res(1, "登录成功", $forward);
			} else {
				return $this->page_data = bra_res(500, "登录失败", '', Hash::make($data['password']));
			}
		} else {
			return $this->page_data = T();
		}
	}

	public function bra_passport_login_sms ($query) {
		global $_W, $_GPC;
		$forward = request()->cookie('login_forward_' . $_W['site']['id']);
		if (is_bra_access(0, 'post')) {
		} else {
			return $this->page_data = T();
		}
	}

	public function bra_passport_wx_login ($query) {
		global $_W, $_GPC;
		$forward = request()->cookie('login_forward_' . $_W['site']['id']);
		if (is_weixin()) {
			$app = WechatMedia::get_media_official();
			$oauth = $app->app->oauth;
			if (empty($_GPC['code'])) {
				return $this->page_data = redirect($oauth->redirect($_W['current_url']));
			} else {
				$oa_user = $oauth->userFromCode($_GPC['code']);
			}
			$user_res = $app->get_bra_user($oa_user->getRaw());
			if (is_error($user_res)) {
				return $this->page_data = end_resp($user_res);
			} else {
				$user = $user_res['data'];
				$res = Auth::loginUsingId($user['id'], true);
				if (!$res) {
					abort(403, '未知错误!!!');
				}

				return $this->page_data = redirect($forward);
			}
		} else {
			//32位随机代码
			BraCache::set_cache("bra_wx_login:" . $this->uuid, $this->uuid);
//            $login_url = make_url('bra/passport/bra_wx_login', ['uuid' => $this->uuid]);
//            $assign['code'] = url('core/public_service/str_to_qr') . "?str=" . urlencode($login_url);
			$assign['uuid'] = $this->uuid;
			$assign['forward'] = urldecode($forward);

			return T();
		}
	}

	public function bra_passport_union_login ($query) {
		global $_W, $_GPC;
		if (is_weixin()) {
			$app = WechatMedia::get_media_official();
			$oauth = $app->app->oauth;
			if (empty($_GPC['code'])) {
				return $this->page_data = redirect($oauth->redirect($_W['current_url']));
			} else {
				$oa_user = $oauth->userFromCode($_GPC['code']);
			}
			$user_res = $app->get_bra_user($oa_user->getRaw());
			if (is_error($user_res)) {
				return $this->page_data = end_resp($user_res);
			} else {
				$user = $user_res['data'];
				BraCache::set_cache("bra_login_uuid:" . $_GPC['uuid'], $user['id']);
				return $this->page_data = T();
			}
		}
	}

	public function bra_passport_wx_scan_login ($query) {
		BraCache::set_cache("bra_wx_login:" . $this->uuid, $this->uuid);
//            $login_url = make_url('bra/passport/bra_wx_login', ['uuid' => $this->uuid]);
//            $assign['code'] = url('core/public_service/str_to_qr') . "?str=" . urlencode($login_url);
		$assign['uuid'] = $this->uuid;
		$assign['forward'] = urldecode($forward);

		return T();
	}

	public function bra_passport_check_login ($query) {
		global $_GPC;
		$assign['uuid'] = $login = BraCache::get_cache("bra_login_uuid:" . $_GPC['bra_uuid']);
		if ($login) {
			$user = User::find($login);
			$assign['auth_str'] = $user->createToken('Laravel')->accessToken;
			$assign['user_login'] = $user;
			BraCache::set_cache("bra_login_uuid:" . $_GPC['bra_uuid'], false);
		}

		return $this->page_data = $assign;
	}
}
