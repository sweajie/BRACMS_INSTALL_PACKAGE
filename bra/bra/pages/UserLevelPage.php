<?php
// +----------------------------------------------------------------------
// | BraCMS [ 布拉CMS ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2017 http://www.bra.ac All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( 您必须获取授权才能进行使用 )
// +----------------------------------------------------------------------
// | Author: 鸣鹤 <1620298436@qq.com>
// +----------------------------------------------------------------------
namespace Bra\bra\pages;

use Bra\bra\utils\BraUsersLevelLog;
use Bra\core\objects\BraExtWallet;
use Bra\core\objects\BraOrder;
use Bra\core\pages\UserBaseController;

class UserLevelPage extends UserBaseController {


	public function bra_user_level_upgrade ($query) {
		global $_W, $_GPC;
		$user = refresh_user();
		$els = $donde =[];
		$c_level = false;
		if ($_W['user']['level_id'] && !BraUsersLevelLog::is_level_expire($user['level_expire'])) {
			$c_level = D('users_level')->bra_one($_W['user']['level_id']);

			$donde['listorder'] = ['>' ,  $c_level['listorder']];
		}
		$user_levels = D('users_level')->bra_where($donde)->order('listorder asc')->list_item(true);
		if (is_bra_access('0')) {
			$target_level = D('users_level')->get_item($query['level_id']);
			$target_level['data'] = json_decode($target_level['data'], 1);
			foreach ($target_level['data']['deposit_set'] as $k => $target_set) {
				if ($k == $query['target_set']) {
					$found_set = $target_set;
				}
			}
			if (!isset($found_set)) {
				return $this->page_data = bra_res(6, "系统错误,无法升级会员!");
			}
			// create level log
			$level_log_m = D('users_level_log');
			$level_log_data = [];
			$level_log_data['status'] = 1;
			$level_log_data['level_id'] = $query['level_id'];
			$level_log_data['days'] = $found_set['reward_days'];
			$level_log_data['price'] = $found_set['amount'];
			$level_log_data['order_id'] = 0;
			$coin_type = $found_set['coin_type'];
// create o2o oder
			$level_expire = strtotime($user['level_expire']);
			if ($c_level && $level_expire > time()) {
				$level_log_data['start_at'] = $user['level_expire'];
				// if ($c_level['listorder'] == $target_level['listorder']) {} //同级别续费
				if ($c_level['listorder'] < $target_level['listorder']) { //升级
					// 计算折旧价格
					$donde= [];
					$donde['status'] = 99;
					$test = D('users_level_log')->selectRaw("sum(price) as t_price , sum(days)as t_days")->with_user()->bra_where($donde)->bra_one();
					$daily_price = $test['t_price'] / $test['t_days'];
					$left_money = number_format(($level_expire - time()) / 86400 * $daily_price, 2, '.', '');
					$level_log_data['price'] -= $left_money;
				}
				if ($c_level['listorder'] > $target_level['listorder']) {
					return $this->page_data = bra_res(6, "您的等级已经已经高于所选等级无法升级!");
				}
			} else {
				//购买会员
				$level_log_data['start_at'] = date('Y-m-d H:i:s');
			}
			$level_log_data['expire_at'] = date('Y-m-d H:i:s', $level_log_data['start_at'] + $found_set['reward_days'] * 86400);
			//system info
			//deliver info
			$log_res = $level_log_m->item_add($level_log_data); // 创建订单
			if (is_error($log_res)) {
				return $this->page_data = $log_res;
			}
			if ($user['balance'] >= $level_log_data['price']) {
				//pay o2o order
				$wallet = new BraExtWallet($user['id']);
				$wallet_res = $wallet->ex_spend($level_log_data['price'], 'balance', 0, '升级会员');
				if (is_error($wallet_res)) {
					return $this->page_data = $wallet_res;
				}
				$update['status'] = 99;
				$res = $level_log_m->item_edit($update, $log_res['data']['id']);
				if (is_error($res)) {
					return $this->page_data = $res;
				}
				$res = $user->upgrade_level($query['level_id'], $level_log_data['days']);
				if (!$res) {
					return $this->page_data = bra_res(500, '升级失败');
				}

				return $this->page_data = bra_res(1, '升级成功');
			} else {
				// 余额不足 全额充值
				$deposit_amount = $level_log_data['price'] - $user['balance'];
				$extra = [
					'act' => "pay_user_level",
					'item_id' => $log_res['data']['id'],
					'model_id' => D('users_level_log')->_TM['id'],
					'total_money' => $level_log_data['price'],
					'cate_id' => 0
				];
				$order_res = BraOrder::create_order($user['id'], $deposit_amount, $_W['_bra']['id'], "升级会员", $extra);
				if (is_error($order_res)) {
					return $this->page_data = $order_res;
				} else {
					$order = $order_res['data'];

					return $this->page_data = bra_res(6, "order", '', $order);
				}
			}
		} else {
			if ($c_level) {
				foreach ($user_levels as $user_level) {
					if ($user_level['listorder'] >= $c_level['listorder']) {
						$user_level['data'] = json_decode($user_level['data'], 1);
						$levels[] = $user_level;
					}
				}
			} else {
				foreach ($user_levels as $user_level) {
					$user_level['data'] = json_decode($user_level['data'], 1);
					$levels[] = $user_level;
				}
			}
			$els['c_level'] = $c_level;
			$els['levels'] = $levels;
			$els['user'] = $_W['user'];

			return $this->page_data = $els;
		}
	}

}
