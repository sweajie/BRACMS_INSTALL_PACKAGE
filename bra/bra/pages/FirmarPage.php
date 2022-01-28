<?php

namespace Bra\bra\pages;

use Bra\core\objects\BraArray;
use Bra\core\objects\BraExtWallet;
use Bra\core\pages\UserBaseController;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FirmarPage extends UserBaseController {
	/* firmar */
	public function bra_firmar_sign () {
		global $_W;
		$config = BraArray::get_Config('firmar_grabar');
		if (!$_W['user']) {
			return $this->page_data['res'] = bra_res(1, '您未登录');
		}
		if ($config['allow_firmar'] != 1) {
			return $this->page_data['res'] = bra_res(1, '管理员还没有开启签到功能' , $config);
		}
		$firmar = D("firmar");
		$user_firmar = $firmar->with_user()->bra_one();
		if (!$user_firmar) {
			$add_firmar['user_id'] = $_W['user']['id'];;
			$add_firmar['continua'] = 1;
			$add_firmar['last_firmar'] = date('Y-m-d H:i:s');
			$res_a = $firmar->item_add($add_firmar);
			if (is_error($res_a)) {
				return $this->page_data['res'] = bra_res(500, $res_a['msg']);
			} else {
				$user_firmar = $res_a['data'];
			}
		}
		$update = [];
		if ($user_firmar['code'] == 1) {
			$user_firmar = $user_firmar['item'];
			if (date("Y-m-d", strtotime("-1 day")) == date("Y-m-d", strtotime($user_firmar['last_firmar']))) {
				$update['continua'] = $user_firmar['continua'] + 1;
			} else {
				$update['continua'] = 1;
			}
			$update['firmar'] = $user_firmar['firmar'] + 1;
		} else {
			$update['continua'] = 1;
			$update['firmar'] = 1;
		}
		$donde = [];
		$donde['continua'] = ['<=', $update['continua']];



		$firmar_regla = D("firmar_regla")->bra_where($donde)->order('continua desc')->bra_one();
		if ($firmar_regla) {
			$firmar_grabar = D("firmar_grabar");
			Db::beginTransaction();
			$update['last_firmar'] = date('Y-m-d H:i:s');
			if (!$firmar->with_user()->update($update)) {
				Db::rollback();

				return $this->page_data['res'] = bra_res(500, '更新数据错误!' );
			}
			$ist = [];
			$ist['regla_id'] = $firmar_regla['id'];
			$where = [];
			$where['user_id'] = (int)$_W['user']['id'];
			$where['create_at'] = ['DAY', Carbon::today()]; // " user_id = {$_W['user']['id']} and date( create_at )  = '$date'"
			$test = $firmar_grabar->bra_where($where)->bra_one();
			if (!$test) {
				$res = $firmar_grabar->item_add($ist);
				if (!is_error($res)) {
					$msg = '签到成功! ';
					$bra_wallet = new BraExtWallet($_W['user']['id']);
					if ($firmar_regla['point'] > 0) {
						$deposit_res = $bra_wallet->ex_deposit($firmar_regla['point'], 'point' , 2, '签到奖励');
						if (is_error($deposit_res)) {
							Db::rollback();

							return $this->page_data = bra_res(500, '签到失败! ', '', $deposit_res);
						}
						$msg .= '积分+' . $firmar_regla['point'];
					}
					if ($firmar_regla['amount'] > 0) {
						$deposit_res = $bra_wallet->ex_deposit($firmar_regla['amount'], 'balance' ,2, '签到奖励');
						if (is_error($deposit_res)) {
							Db::rollback();

							return $this->page_data = bra_res(500, '签到失败! ', '', $deposit_res);
						}
						$msg .= '金币+' . $firmar_regla['amount'];
					}
					Db::commit();

					return $this->page_data['res'] = bra_res(1, $msg);
				} else {
					Db::rollback();

					return $this->page_data['res'] = bra_res(500, '签到失败');
				}
			} else {
				Db::rollback();

				return $this->page_data['res'] = bra_res(500, '您今天已经签到过了哦');
			}
		} else {
			return $this->page_data['res'] = bra_res(500, '管理员还没有设置签到规则' ,'' , $donde);
		}
	}
}
