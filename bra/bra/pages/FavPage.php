<?php

namespace Bra\bra\pages;

use Bra\core\pages\UserBaseController;
use Bra\core\utils\pay\bra_micropay;
use Bra\core\utils\pay\micropay;
use Bra\wechat\medias\WechatMedia;

class FavPage extends UserBaseController {

	public function bra_fav_add ($query) {
		global $_W;
		$module = module_exist($query['module']);
		$bra_m = D($query['model_id']);
		$page_data = self::fav_add($module['id'], $query['item_id'], $bra_m->_TM['id'], $_W['user']['id']);

		return $this->page_data = $page_data;
	}

	public static function fav_add (int $module_id, int $item_id, int $model_id, int $user_id, int $client = 0) {
		if (empty($user_id)) {
			return bra_res(2, "加入收藏夹失败 , !");
		}
		$fav_m = D('users_fav');
		if ($item_id && $model_id) {
			$where = [];
			$where['model_id'] = $model_id;
			$where['item_id'] = $item_id;
			$where['user_id'] = $user_id;
			$test = $fav_m->bra_where($where)->first();
			if ($test) {
				$fav_m->bra_where($where)->delete();

				return bra_res(2, "取消收藏成功!");
			} else {
				$where['client'] = $client;
				$where['module_id'] = $module_id;
				$fav_m->item_add($where);

				return bra_res(1, "加入收藏成功!");
			}
		} else {
			return bra_res(404, "数据处理失败,无法收藏!");
		}
	}

	public static function fav_si (int $item_id, int $model_id, int $user_id) {
		$fav_m = D('users_fav');
		$where['model_id'] = $model_id;
		$where['item_id'] = $item_id;
		$where['user_id'] = $user_id;

		return $fav_m->bra_where($where)->count();
	}

	public function bra_fav_my_fav ($query) {
		global $_W, $_GPC;
		$act_m = D('users_fav');
		$query['user_id'] = $_W['user']['id'];
		$query['model_id'] = D($query['model_id'])->_TM['id'];
		$query['bra_int_fields'] = ['user_id', 'model_id'];
		$lists = $act_m->list_bra_resource($query);
		$lists->transform(function ($item) use ($act_m) {
			$item['_data'] = D($item['model_id'])->get_item($item['item_id']);

			return $item;
		});
		$assigns['list'] = $lists;
		A($assigns);
		$this->page_data = $assigns;

		return T();
	}


	public static function foot_print ($model_id , $item_id, $user_id, $module_id) {
		global $_W;
		if (!$user_id) {
			return;
		}
		$where = [];
		$where['item_id'] = $item_id;
		$where['model_id'] = D($model_id)->_TM['id'];
		$where['user_id'] = $user_id;
		$res = D('users_footprint')->bra_one($where);
		if ($res) {
			unset($res['id']);
			$res['num']++;
			$res['last_visit'] = date("y-m-d H:i:s");
			$insert['module_id'] = $module_id;

			return D('users_footprint')->update($res, $res['id']);
		} else {
			$insert = $where;
			$insert['create_at'] = $insert['last_visit'] = date("y-m-d H:i:s");
			$insert['site_id'] = $_W['site']['id'];
			$insert['module_id'] = $module_id;

			return D('users_footprint')->item_add($insert);
		}
	}
}
