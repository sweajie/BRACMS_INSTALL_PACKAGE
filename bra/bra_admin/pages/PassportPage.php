<?php

namespace Bra\bra_admin\pages;

use Bra\core\pages\BraController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Request;
use Mews\Captcha\Facades\Captcha;

class PassportPage extends BraController {

	public function bra_admin_passport_logout (Request $request) {
		global $_W, $_GPC;
		Auth::guard('web')->logout();
//        Auth::logout();
//        app()->request->session()->invalidate();
//
//        app()->request->session()->regenerateToken();
		return $this->page_data = bra_res(1, '操作成功!', url('/bra_admin/passport/login'));
	}

	public function bra_admin_passport_login ($query) {
		if (Request::method() == 'POST') {
			//get users admin
			$rules = ['captcha' => 'required|captcha'];
			$validator = validator()->make(request()->all(), $rules);
			if ($validator->fails()) {
				Captcha::create();
				return $this->page_data = bra_res(500, "登录失败 , 验证码不正确!");
			}
			$data = $query['data'];
			$user = (array)D('users')->bra_where(['user_name' => $data['user_name']])->first();
			if (Hash::check($data['password'], $user['password'])) {
				$res = D('users_admin')->where('user_id', '=', $user['id'])->first();
				if (!$res && $user['id'] != 1) {
					return $this->page_data = bra_res(500, "登录失败 , 您无权访问!");
				} else {
					Auth::loginUsingId($user['id'], true);
					Auth::logoutOtherDevices($data['password']);
					Auth::loginUsingId($user['id'], true);

					return $this->page_data = bra_res(1, "登录成功", url('bra_admin/index/index'));
				}
			} else {
				Captcha::create();
				$this->page_data = bra_res(500, "登录失败", '', Hash::make($data['password']));
			}
		} else {
			$this->page_data = A_T('bra_admin.passport.login');
		}
	}

}
