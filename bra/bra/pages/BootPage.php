<?php
// +----------------------------------------------------------------------
// | 鸣鹤CMS [ New Better  ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2017 http://www.bracms.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( 您必须获取授权才能进行使用 )
// +----------------------------------------------------------------------
// | Author: new better <1620298436@qq.com>
// +----------------------------------------------------------------------
namespace Bra\bra\pages;

use App\Models\User;
use Bra\core\facades\BraCache;
use Bra\core\objects\BraArray;
use Bra\core\objects\BraExtWallet;
use Bra\core\objects\BraNode;
use Bra\core\objects\BraNotice;
use Bra\core\objects\BraString;
use Bra\core\pages\BraController;
use Bra\core\utils\BraDis;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BootPage extends BraController {

	public function bra_boot_boot_up ($query) {
		global $_W, $_GPC;
//		$elements['area'] = $this->detect_default_area();
		$scene = $query['scene'];
		if ($scene) {
			$elements['share'] = $share = D('share')->bra_one(['id' => $scene]);
			if ($share) {
				$_W['refer'] = $share['user_id'];
			}
		}
		if (!empty($share)) {    //hit share
			$share_click = [];
			$share_click['share_id'] = $share['id'];
			$data = D($share['model_id'])->get_item($share['item_id']);
			if ($_W['user']) {
				$share_click['user_id'] = $_W['user']['id'];
				//todo send notice
				$params['header'] = "扫码通知 ". $data['content'];
				$params['footer'] = "{$_W['user']['nickname']}点击了您的分享";
				$params['app_id'] = $this->mini_app['app_id'];
				$params['scene_id'] = $share['id'];

				$bn = new BraNotice('50005', $params);
				$elements['bn'] = $bn->send($share['user_id']);
				$test = D('share_click')->with_site()->bra_where($share_click)->bra_one();
				if (!$test) {
					$share_click['refer'] = 'scan_mini';
					$share_click['ip'] = app()->request->ip();
					$share_click['client'] = $_W['bra_client'];
					$share_click['times'] = 1;
					D('share_click')->item_add($share_click);
				}
			}else{
				$params['header'] = "通知 ". $data['content'];
				$params['footer'] = "游客点击了您的分享";
				$params['app_id'] = $this->mini_app['app_id'];
				$params['scene_id'] = $share['id'];

				$bn = new BraNotice('50005', $params);
				$elements['bn'] = $bn->send($share['user_id']);
			}

			D('share_click')->where('id', '=', $test['id'])->increment('times');
		}
		$elements['site_set'] = $_W['site']['config'];
		$elements['ads'] = render_ad_group(1);
		$elements['user'] = $_W['user'];
		$elements['ad_count'] = 5;
		$elements['is_checking'] = $this->is_checking;
		A($elements);
	}

	private function detect_default_area () {
		global $_W, $_GPC;
		$this->mini_app = (array)D('wechat')->find($_GPC['app_id']);

		return D('area')->bra_one($this->mini_app['area_id']);
	}

	public function bra_user_guest_book($query) {
		if(is_bra_access(0)){
			$this->page_data = bra_res(1 , '留言成功');
		}
	}
}
