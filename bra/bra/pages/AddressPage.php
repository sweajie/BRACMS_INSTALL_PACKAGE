<?php

namespace Bra\bra\pages;

use Bra\core\pages\UserBaseController;
use Bra\core\traits\BraFav;
use Bra\core\utils\BraAdminItemPage;

class AddressPage extends UserBaseController {


	public function bra_user_address_del($query) {
		if (is_bra_access(0, 'post')) {
			return $this->page_data = D("users_address")->with_user()->item_del($query['id']);
		} else {
			return $this->page_data = bra_res(500, '不合法的访问！');
		}
	}

	public function bra_user_user_address($query) {
		global $_W;
		if (is_bra_access(0, 'post')) {
			/**
			 * cityName: "广州市"
			 * countyName: "海珠区"
			 * detailInfo: "新港中路397号"
			 * errMsg: "chooseAddress:ok"
			 * nationalCode: "510000"
			 * postalCode: "510000"
			 * provinceName: "广东省"
			 * telNumber: "020-81167888"
			 * userName: "张三"
			 */
			$address = $query['address'];
			if (is_array($address)) {
				$test = D('users_address')->get_user_data();
				if (!$test) {
					$i_data['is_default'] = 1;
				} else {
					$i_data['is_default'] = 0;
				}
				$i_data['address'] = $address['detailInfo'];
				$i_data['city'] = $address['cityName'];
				$i_data['province'] = $address['provinceName'];
				$i_data['title'] = $address['userName'];
				$i_data['county'] = $address['countyName'];
				$i_data['mobile'] = $address['telNumber'];
				$res = D('users_address')->item_add($i_data);
				$res['i_data'] = $i_data;
				return $this->page_data =  $res;
			} else {
				$query['city'] = " - ";
				$query['county'] = " - ";
				$query['province'] = ' - ';
				return $this->page_data = D('users_address')->item_add($query);
			}
			//$this->page_data =  bra_res(1,'','',$address);
		} else {
			$els['address_list'] = D('users_address')->bra_where(['user_id' => $_W['user']['id']])->get();
			$els['user'] = $_W['user'];
			$this->page_data = $els;
		}
	}

}
