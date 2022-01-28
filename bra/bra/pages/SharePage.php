<?php

namespace Bra\bra\pages;

use App\Models\User;
use Bra\core\facades\BraCache;
use Bra\core\objects\BraArray;
use Bra\core\pages\BraController;
use Bra\core\pages\UserBaseController;
use Bra\wechat\medias\WechatMedia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Cookie;


class SharePage extends UserBaseController {
	public function bra_share_entry ($query) {
		global $_W , $_GPC;
		if (!$query['id']) {
			return redirect('/');
		}
		$share = D('share')->bra_one($query['id']);
		Cookie::queue('refer', $share['user_id']);
		//todo click share

		if (!empty($share) && $_W['user']['id'] !=  $share['user_id']) {    //hit share
			$share_click = [];
			$share_click['share_id'] = $share['id'];
			$share_click['user_id'] = $_W['user']['id'];
			$test = D('share_click')->bra_where($share_click)->bra_one();
			if (!$test) {
				$share_click['refer'] = 'scan_mini';
				$share_click['ip'] = app()->request->ip();
				$share_click['client'] = $_W['bra_client'];
				$share_click['times'] = 1;
				D('share_click')->item_add($share_click);
			} else {
				D('share_click')->where('id', '=', $test['id'])->increment('times');
			}
		}
		//process share zhuli
		return $this->page_data = redirect($share['path']);
	}
}
