<?php

namespace Bra\bra\pages;

use App\Models\User;
use Bra\core\facades\BraCache;
use Bra\core\objects\BraNotice;
use Bra\core\objects\BraString;
use Bra\core\pages\BraController;
use Illuminate\Support\Facades\DB;

class SmsPage extends BraController {

	/**
	 * @param $query
	 * @return array
	 */
	public function bra_sms_send_register_code ($query): array {
		global $_W, $_GPC;
		$mobile = $query['mobile'];
		$v_code = $query['v_code'];

		if (!BraString::is_phone($mobile)) {
			return $this->page_data['res'] = bra_res(2, '对不起手机号码不正确');
		}
		$user = User::Orwhere('user_name', '=', $mobile)->Orwhere('mobile', '=', $mobile)->first();
		if ($user) {
			return $this->page_data['res'] = bra_res(2, '对不起手机号码已经被注册!');
		}

		$llave = $_W['uuid'] . '_sms_v-code';
		$s_code = BraCache::get_cache($llave);
		if ($s_code != $v_code) {
			return $this->page_data['res'] = bra_res(500, '您的安全码不正确!', $s_code);
		}
		if (is_bra_access(0)) {
			Db::beginTransaction();
			$notice = new BraNotice(2);
			$params['yzm'] = rand(100000, 999999);
			$resp = $notice->send_sms($mobile, $params);
			if (is_error($resp)) {
				Db::rollback();
			} else {
				Db::commit();
			}

			return $this->page_data['res'] = $resp;
		} else {
			return $this->page_data['res'] = bra_res(500, '您的会话不合法');
		}
	}
	/**
	 * @param $query
	 * @return array
	 */
	public function bra_sms_send_login_code ($query): array {
		global $_W, $_GPC;
		$mobile = $query['mobile'];
		$v_code = $query['v_code'];

		if (!BraString::is_phone($mobile)) {
			return $this->page_data['res'] = bra_res(2, '对不起手机号码不正确');
		}
		$user = User::where('user_name', '=', $mobile)->first();

		if (!$user) {
			return $this->page_data['res'] = bra_res(2, '对不起手机号码未注册!');
		}
//		$llave = $_W['uuid'] . '_sms_v-code';
//		$s_code = BraCache::get_cache($llave);
//		if ($s_code != $v_code) {
//			return $this->page_data['res'] = bra_res(500, '您的安全码不正确!', $s_code);
//		}
		if (is_bra_access(0)) {
			Db::beginTransaction();
			$notice = new BraNotice(2);
			$params['yzm'] = rand(100000, 999999);
			$resp = $notice->send_sms($mobile, $params);
			if (is_error($resp)) {
				Db::rollback();
			} else {
				Db::commit();
			}

			return $this->page_data['res'] = $resp;
		} else {
			return $this->page_data['res'] = bra_res(500, '您的会话不合法');
		}
	}
}
