<?php

namespace Bra\bra_app\pages;

use App\Models\User;
use Bra\core\facades\BraCache;
use Bra\core\objects\BraArray;
use Bra\core\objects\BraExtWallet;
use Bra\core\objects\BraModule;
use Bra\core\objects\BraNotice;
use Bra\core\objects\BraString;
use Bra\core\pages\BraController;
use Bra\core\utils\BraDis;
use Bra\core\utils\GoogleAuthenticator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PassportPage extends BraController {
	public function bra_app_passport_register ($query) {
		global $_W, $_GPC;
		$els = [];
		$refer_mob = $query['refer'] ?? $query['invite_code'];
		$refer_mob = BraString::invite_code($refer_mob, 'decode');
		$parent = User::Orwhere('user_name', '=', $refer_mob)->Orwhere('mobile', '=', $refer_mob)->first();
		if (!$query['refer'] || !$parent) {
//            abort(403 , '无效的访问!');
		}
		if (is_bra_access(0)) {
			$refer_id = $parent['id'];
			if (!$parent) {
				return $this->page_data = bra_res(500, '对不起 , 您的邀请码不正确!', $refer_mob);
			}
			if (!BraDis::is_distributor($parent['id'])) {
				return $this->page_data = bra_res(500, '对不起 , 邀请人无权分享!', $refer_mob, $parent);
			}
			Db::beginTransaction();
			$mobile = $query['mobile'];
			$code = $query['code'];
			$res = BraNotice::verify_code($mobile, $code);
			if (is_error($res)) {
				Db::rollback();

				return $this->page_data = bra_res(500, '手机验证码错误');
			} else {
				$user = User::orWhere('user_name', '=', $mobile)->orWhere('mobile', '=', $mobile)->first();// find user
				if ($user) {
					Db::rollback();

					return $this->page_data = bra_res(500, '手机号码已注册!');
				} else {
					//创建手机用户
					$user_data = [];
					$user_data['user_name'] = $mobile;
					$user_data['mobile'] = $mobile;
					$user_data['is_mobile_verify'] = 1;
					$user_data['parent_id'] = $refer_id;
					$user_data['status'] = 99;
					$user_data['password'] = $query['password'];
					$res = D('users')->item_add($user_data);
					if (!is_error($res)) {
						Db::commit();

						return $this->page_data = bra_res(1, '验证成功', '', $user);
					} else {
						Db::rollback();

						return $this->page_data = $res;
					}
				}
			}
		} else {
			$llave = $_W['uuid'] . '_sms_v-code';
			$els['v_code'] = mt_rand(1000, 9999);
			$els['uuid'] = $_W['uuid'];
			$term = BraArray::get_config('bra_user_term');
			$els['term'] = $term['data'];
			BraCache::set_cache($llave, $els['v_code'], 600);
			if ($_GPC['refer']) {
				$parent = refresh_user($_GPC['refer']);
				if (BraDis::is_distributor($parent['id'])) {
					$els['invite_code'] = BraString::invite_code($parent['user_name']);
				} else {
					$els['invite_code'] = '';
				}
			} else {
				$els['invite_code'] = '';
			}
			$els['query'] = $query;
			$els['GPC'] = $_GPC;
//            $els['invite_code'] = $_GPC['refer'];

			$els['area_ops'] = D('area')->load_tree_options();
			A($els);

			return $this->page_data = T();
		}
	}

	public function bra_app_passport_register_mibao ($query) {
		global $_W, $_GPC;
		$els = [];
		$refer_mob = $query['invite_code'] ?? '';
		$refer = BraString::invite_code($refer_mob, 'decode');
		$parent = User::find($refer);

		if (is_bra_access(0)) {
			if (!$parent) {
				return $this->page_data = bra_res(500, '对不起 , 您的邀请码不正确!');
			}
			if (!BraDis::is_distributor($parent['id'])) {
				return $this->page_data = bra_res(500, '对不起 , 邀请人无权分享!');
			}
			if (empty($query['question_id'])) {
				return $this->page_data = bra_res(500, '对不起 , 请选择密保问题!');
			}
			if (empty($query['answer'])) {
				return $this->page_data = bra_res(500, '对不起 , 请填写密保答案!');
			}

			if (!BraString::is_username($query['user_name'])) {
				return $this->page_data = bra_res(500, '对不起 , 用户名不合法!',$query);
			}

			Db::beginTransaction();
			$user = User::Where('user_name', '=', $query['user_name'])->first();// find user
			if ($user) {
				Db::rollback();

				return $this->page_data['res'] = bra_res(500, '该账号已经被已注册!');
			} else {
				//创建手机用户
				$user_data = [];
				$user_data['user_name'] = $query['user_name'];
				$user_data['mobile'] = '';
				$user_data['is_mobile_verify'] = 0;
				$user_data['role_id'] = 2;
				$user_data['parent_id'] = $parent['id'];
				$user_data['status'] = 99;
				$user_data['password'] = $query['password'];
				$res = D('users')->item_add($user_data);
				if (!is_error($res)) {
					// wallet pass
//
					$wallet = new BraExtWallet($res['data']['id']);
					$extra = [
						'question_id' => $query['question_id'] ,
						'answer' => $query['answer'] ,
					];
					$res_1 = $wallet->change_pass($query['wallet_pass'] , $extra);


					if (!is_error($res_1)) {
						Db::commit();

						self::reward_reg($res['data']['id']);
						return   $this->page_data['res'] = bra_res(1, '注册成功', '', $user);
					}else{
						Db::rollback();
						return $this->page_data['res'] = $res_1;
					}

				} else {
					Db::rollback();

					return $this->page_data['res'] = $res;
				}
			}
		} else {
			$llave = $_W['uuid'] . '_sms_v-code';
			$els['v_code'] = mt_rand(1000, 9999);
			$els['uuid'] = $_W['uuid'];
			$term = BraArray::get_config('bra_user_term');
			$els['term'] = $term['data'] ?? '';
			BraCache::set_cache($llave, $els['v_code'], 600);

			$els['question_id_opts'] = array_values( D("users_wallet")->load_options('question_id'));
			$els['GPC'] = $_GPC;
			A($els);

			return $this->page_data = T();
		}
	}

	public function bra_passport_login ($query) {
		global $_W, $_GPC;
		if (is_bra_access(0, 'post')) {
			$user = (array)D('users')->bra_where(['user_name' => $query['user_name']])->first();
			if(!$user){
				return $this->page_data = bra_res(500, "登录失败 , 账号不存在!"  );
			}
			if (Hash::check($query['password'], $user['password'])) {
				//get users admin
				if ($user['status'] != 99) {
					return $this->page_data = bra_res(500, "登录失败 , 您无权访问!");
				}
				Auth::loginUsingId($user['id'], true);

				return $this->page_data = bra_res(1, "登录成功");
			} else {
				return $this->page_data = bra_res(500, "登录失败", '', Hash::make($query['password']));
			}
		} else {
			$this->page_data['allow_account'] = 1;
			$this->page_data['allow_reg'] = 1;
			A($this->page_data);
			return $this->page_data = T();
		}
	}

	public function bra_passport_login_app ($query) {
		global $_W , $_GPC;
		$llave = request()->ip() . "pass_retry";
		if (is_bra_access(0, 'post')) {



			$i = (int) BraCache::get_cache($llave);
			if($i > 5){
				return $this->page_data['res'] = bra_res(500, "您的IP异常!");
			}

			$user = (array)D('users')->bra_where(['user_name' => $query['user_name']])->first();


			if ($user['status'] != 99) {
				return $this->page_data['res'] = bra_res(500, "登录失败 , 您的账号异常，请联系客服!");
			}
			if(!$user){
				return $this->page_data['res'] =bra_res(500, "登录失败 ,您的账号密码不正确!", '', $query['user_name']);
			}
			if (version_compare($_GPC['version'], "1.0.30", '>=')) {
				$ga = new GoogleAuthenticator();

				if($user['google_key']){
					if(!$query['google_code']){
						return $this->page_data['res'] = bra_res(50060, "请输入谷歌验证码!"  );
					}
					$checkResult = $ga->verifyCode($user['google_key'], $query['google_code'], 2);    // 2 = 2*30sec clock tolerance
					if (!$checkResult) {
						return $this->page_data['res'] = bra_res(50060, "登录失败 , 谷歌验证码不正确!"  );
					}
				}
			}



			if (Hash::check($query['password'], $user['password'])) {
				//get users admin
				if ($user['status'] != 99) {
					return $this->page_data['res'] = bra_res(500, "登录失败 , 您的账号异常，请联系客服!");
				} else{
					$user = User::find($user['id']);
					foreach ($user->tokens as $token) {
						$token->revoke();
						$token->delete();
					}
					Auth::logoutOtherDevices($query['password']);
					$elements['auth_str'] = $user->createToken('BRACMS')->accessToken;
					$elements['user'] = $user;
					$elements['tokens'] = $user->tokens;
					return $this->page_data['res'] = bra_res(1, "", '' , $elements);
				}
			} else {
				BraCache::set_cache($llave, $i + 1);
				$left = 5 - $i ;
				return $this->page_data['res'] = bra_res(500, "登录失败,您的密码不正确，您还有 {$left} 次机会!", '' , $query);
			}
		}else{

			$this->page_data['allow_account'] = 1;
			$this->page_data['allow_reg'] = 1;
			return $this->page_data;
		}
	}

	/**
	 * @param $query
	 * @return array|\Illuminate\Contracts\View\View|mixed
	 */
	public function bra_app_passport_register_mi ($query) {
		global $_W, $_GPC;
		$els = [];
		$invite_code = $query['refer'];
		$refer = BraString::invite_code($invite_code, 'decode');

		$parent = User::find($refer);

		if (!$query['refer'] || !$parent) {
            abort(403 , '无效的访问!');
		}

		if (is_bra_access(0)) {

			if (!$parent) {
				return $this->page_data = bra_res(500, '对不起 , 您的邀请码不正确!', $refer);
			}
			if (!BraDis::is_distributor($parent['id'])) {
				return $this->page_data = bra_res(500, '对不起 , 邀请人无权分享!', $refer, $parent);
			}
			$data = $query['data'];
			$user_name = trim($data['user_name']);
			if(!BraString::is_username($user_name)){
				return $this->page_data = bra_res(500, '对不起 , 您的用户名包含非法字符!', $refer);
			}
			$user = User::Where('user_name', '=', $user_name)->first();// find user
			if ($user) {

				return $this->page_data = bra_res(500, '用户名已注册!');
			} else {
				if(!BraString::is_password($data['password'])){
					return $this->page_data = bra_res(500, '账号密码不够强壮，请不要包含连续数字的简单密码!');
				}

				if(!BraString::is_password($data['wallet_pass'])){
					return $this->page_data = bra_res(500, '支付密码不够强壮，请不要包含连续数字的支付密码!');
				}
				//创建手机用户
				$user_data = [];
				$user_data['user_name'] = $user_name;
				$user_data['mobile'] = "";
				$user_data['is_mobile_verify'] = 0;
				$user_data['parent_id'] = $parent['id'];
				$user_data['status'] = 99;
				$user_data['password'] = $data['password'];
				$user_data['role_id'] = 2;
				$res = D('users')->item_add($user_data);
				if (!is_error($res)) {
					//
					$wallet = new BraExtWallet($res['data']['id']);
					$extra = [
						'question_id' => $data['question_id'] ,
						'answer' => $data['answer'] ,
					];
					$wallet->change_pass($data['wallet_pass'] , $extra);

					//todo deposit usdt_locked
					self::reward_reg($res['data']['id']);

					return $this->page_data = bra_res(1, '注册成功', '', $user);
				} else {
					return $this->page_data = $res;
				}
			}
		} else {
			$llave = $_W['uuid'] . '_sms_v-code';
			$els['v_code'] = mt_rand(1000, 9999);
			$term = BraArray::get_config('bra_user_term');
			$els['term'] = $term['data'];
			BraCache::set_cache($llave, $els['v_code'], 600);

			$els['uuid'] = $_W['uuid'];
			$els['invite_code'] = $query['refer'];
			$els['app'] = (array) D('app')->first();
			$els['fields'] = D('users_wallet')->get_admin_publish_fields();

			A($els);
			return $this->page_data = T();
		}
	}

	public static function reward_reg ($user_id , $coin_type = 'usdt_locked') {

		$lottery_set = BraArray::get_config('lottery_card_set');

		if(isset($lottery_set['reg_reward_status']) && $lottery_set['reg_reward_status']){
			$wallet = new BraExtWallet($user_id);
			return $wallet->ex_deposit($lottery_set['reg_reward'] , $coin_type , 8 , '注册奖励');
		}

	}


	public function bra_app_passport_download () {

		$els['app'] = (array) D('app')->first();
		A($els);
		return $this->page_data = T();
	}

	/** APP 版本检测
	 * @return mixed
	 */
	public function bra_app_passport_check_version ($query) {
		global $_W, $_GPC;

		$_W['nolog'] = true;

		$last_page = BraCache::get_cache('last_page' . $_W['user_id'] );
		if($last_page == "zzk_user_index"){
			return $this->page_data = $res = bra_res(404 ,  '请先返回，再检测 :');
		}


		BraCache::set_cache('last_page' . $_W['user_id'] , 'zzk_user_index');

		if(!$_W['user']){
			return $this->page_data = $res = bra_res(404 ,  '检测到最新版 :');
		}

		$app = (array)D('app')->first();
		$app['ios_url'] = $app['publish_address'];
		$app['app'] = $app;


		if (version_compare($query['version'], $app['version'], '<')) {
			$res = bra_res(1 ,  '检测到最新版,IOS用户请先卸载旧版再更新 :' . $app['version'] , '' ,$app );
		} else {
			$res = bra_res(404 ,  '您已经是最新版本 :', '' ,$app );
		}
		$res['app'] = $app;

		return $this->page_data = $res;
	}
	/** APP 版本检测
	 * @return mixed
	 */
	public function bra_app_passport_check_version_hot ($query) {
		global $_W, $_GPC;

		$_W['nolog'] = true;
		if(!$_W['user']){
			return $this->page_data = $res = bra_res(404 ,  '检测到最新版 :');
		}

		$app = (array)D('app')->first();
		$app['ios_url'] = $app['publish_address'];
		$app['app'] = $app;


		if (version_compare($query['version'], $app['version'], '<')) {
			$res = bra_res(1 ,  '检测到最新版,IOS用户请先卸载旧版再更新 :' . $app['version'] , '' ,$app );
		} else {
			$res = bra_res(404 ,  '您已经是最新版本 :', '' ,$app );
		}
		$res['app'] = $app;

		return $this->page_data = $res;
	}
	public function bra_app_passport_check_version_force ($query) {
		global $_W, $_GPC;
		$app = (array)D('app')->first();
		$app['ios_url'] = $app['publish_address'];
		$app['app'] = $app;


		if (version_compare($query['version'], $app['version'], '<')) {
			if($query['from']){
				$res = bra_res(1 ,  '检测到最新版 :' . $app['version'] , '' ,$app );
				$res['message'] = '检测到最新版 :' . $app['version'] ;
				return $this->page_data = $res;
			}else{
				$res = bra_res(404 ,  '检测到最新版,IOS用户请先卸载旧版再更新 :' . $app['version'] , '' ,$app );
			}

		} else {
			$res = bra_res(404 ,  '您已经是最新版本 :', '' ,$app );
		}
		$res['app'] = $app;

		return $this->page_data = $res;
	}
}
