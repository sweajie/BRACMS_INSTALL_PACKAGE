<?php

namespace Bra\bra\pages;

use App\Models\User;
use Bra\core\facades\BraCache;
use Bra\core\objects\BraArray;
use Bra\core\pages\BraController;
use Bra\wechat\medias\WechatMedia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Request;

class IndexPage extends BraController {
	public function bra_index_reg_term () {
		global $_W;
		$config['bra_user_term'] = BraArray::get_config('bra_user_term');
		$config['bra_user_privacy'] = BraArray::get_config('bra_user_privacy');
		$this->page_data = $config;
	}
	public function bra_index_notice() {
	}


	public function bra_index_refresh_vcode() {
		global $_W, $_GPC;
		$llave = $_W['uuid'] . '_sms_v-code';
		$els['v_code'] = mt_rand(1000, 9999);
		$els['uuid'] = $_W['uuid'];
		BraCache::set_cache($llave, $els['v_code'], 3600);
		$els['time'] = $_W['time'];
		return $this->page_data = $els;
	}
}
