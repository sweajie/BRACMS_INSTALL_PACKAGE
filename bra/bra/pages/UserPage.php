<?php

namespace Bra\bra\pages;

use Bra\bra\utils\BraUsersLevelLog;
use Bra\core\facades\BraCache;
use Bra\core\objects\BraArray;
use Bra\core\objects\BraExtWallet;
use Bra\core\objects\BraOrder;
use Bra\core\pages\UserBaseController;
use Bra\core\utils\IdentityCard;
use Illuminate\Support\Facades\Auth;

class UserPage extends UserBaseController {

	public function house_user_user_info ($query) {
		global $_W;
		if (is_bra_access(0, 'post')) {
			$update['nickname'] = $query['nickname'];
			$update['avatar'] = $query['avatar'];
			$page_data['res'] = D('users')->item_edit($update, $_W['user']['id']);

			return $this->page_data = $page_data;
		} else {
			$this->page_data['user'] = D("users")->get_item($_W['user']['id']);

			return T();
		}
	}

	public function bra_user_info ($query) {
		global $_W;
		$data = [];
		$data['user'] = $_W['user'];
		$data['user_cache'] = D('users')->get_item($_W['user']['id']);

		$this->page_data = $data;
	}


	public function bra_user_verify ($query) {
		global $_W;
		$data = [];
		$test = D('users_verify')->bra_where(['user_id' => $_W['user']['id']])->bra_one();
		if (is_bra_access(0)) {
			if (!IdentityCard::isValid($query['passport_id'])) {
				return $this->page_data = bra_res(500, '身份证号码不正确!');
			}
			$test2 = D('users_verify')->bra_where(['passport_id' => $query['passport_id']])->bra_one();
			if ($test2) {
				return $this->page_data = bra_res(500, '这个身份证已经被其他账号认证过了!');
			}
			if (!IdentityCard::isValid($query['passport_id'])) {
				return $this->page_data = bra_res(500, '身份证号码不合法!!');
			}
			$member_config = BraArray::get_config('member_config');
			if ($member_config['use_verify_api']) {
				$api = $member_config['verify_api'] ?? "c3";
				$v_res = PassportNameCert::$api($query['passport_id'], $query['real_name']);
				if (is_error($v_res)) {
					return $this->page_data = $v_res;
				} else {
					$query['status'] = 99;
				}
			} else {
				$query['status'] = 99;
			}
			if ($test) {
				$res = D('users_verify')->item_edit($query, ['user_id' => $_W['user']['id']]);
			} else {
				$res = D('users_verify')->item_add($query);
			}
			if (is_error($res)) {
				return $this->page_data = $res;
			} else {
				return $this->page_data = bra_res(1, '申请成功,请等待审核');
			}
		} else {
			if ($test['status'] == 1) {
				return $this->page_data = bra_res(500, '您的申请审核中,请耐心等待');
			}
			$data['user'] = $_W['user'];
			$data['user_verify'] = $test;

			return $this->page_data = $data;
		}
	}
}
