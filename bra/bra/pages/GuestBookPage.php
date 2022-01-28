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

class GuestBookPage extends UserBaseController {
	public function bra_guest_book_advice_add($query) {
		if(is_bra_access(0)){
			$query['status'] = 1;
			$this->page_data['res'] = D('advice')->item_add($query);
		}else{
			$els['fields'] = D('advice')->load_publish_opts();
			$this->page_data = $els;
		}
	}
	public function bra_guest_book_comp_add($query) {

		if(is_bra_access(0)){
			$this->page_data['res'] = D('wechat_company')->item_add($query);
		}else{
			$els['fields'] = D('wechat_company')->load_publish_opts();
			$els['tips'] = "小程序限时优惠个人公司均可申请，包含服务器和软件更新服务，每个行政区域仅招募一个合伙人，个人合作请加微信 bracmf";
			$this->page_data = $els;
		}
	}
}
