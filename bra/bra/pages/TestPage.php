<?php

namespace Bra\bra\pages;

use App\Models\User;
use Bra\core\objects\BraString;
use Bra\core\pages\BraController;
use Bra\core\pages\UserBaseController;
use Bra\core\utils\pay\bra_micropay;
use Bra\core\utils\pay\micropay;
use Bra\wechat\medias\WechatMedia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class TestPage extends BraController {

	public function bra_test_like ($query) {
		DB::listen(function ($query) {
			 dump($query->sql);
			 dump($query->bindings);
			// $query->bindings;
			// $query->time;
		});

	}

}
