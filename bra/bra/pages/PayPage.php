<?php

namespace Bra\bra\pages;

use Bra\core\pages\UserBaseController;
use Bra\core\utils\pay\bra_micropay;
use Bra\core\utils\pay\micropay;
use Bra\wechat\medias\WechatMedia;

class PayPage extends UserBaseController {

    public function get_pay_params ($query) {
        global $_W, $_GPC;
        $page_data = [];
        $gateway = $query['payment'];
        $ooooo = $this->$gateway($query);
        $page_data['res'] = bra_res(1, $query, '', $ooooo);
        A($page_data);
        $this->page_data = $page_data;
    }

	public function bra_micropay ($query) {
		global $_W , $_GPC;
		$media_mid = D('wechat')->_TM['id'];
		//xcx

		if ($_W['bra_client'] == 3) {
			$mini_app = WechatMedia::get_media_mini($_GPC['app_id']);
			$media_id = $mini_app->media['id'];
			$trade_type = 'JSAPI';
		} else if ($_W['bra_client'] == 8) {
			//app
			$media_id = $_W['BraApp']['id'];
			$trade_type = 'APP';
		} else {
			//gzh

			$media = WechatMedia::get_media_official();

			$media_id = $media->media['id'];
			if (is_weixin()) {
				$trade_type = 'JSAPI';
			} else {
				$trade_type = 'NATIVE';
			}
		}
		$payment = new bra_micropay($media_id, $media_mid);

		return $payment->get_pay_params($_W['user']['id'], $query['order_id'], [
			'openid' => $query['openid'], 'trade_type' => $trade_type
		]);
	}

    public function micropay ($query) {
        global $_W , $_GPC;
        $media_mid = D('wechat')->_TM['id'];
        //xcx

        if ($_W['bra_client'] == 3) {
            $mini_app = WechatMedia::get_media_mini($_GPC['app_id']);
            $media_id = $mini_app->media['id'];
            $trade_type = 'JSAPI';
        } else if ($_W['bra_client'] == 8) {
            //app
            $media_id = $_W['BraApp']['id'];
            $trade_type = 'APP';
        } else {
            //gzh

            $media = WechatMedia::get_media_official();

            $media_id = $media->media['id'];
            if (is_weixin()) {
                $trade_type = 'JSAPI';
            } else {
                $trade_type = 'NATIVE';
            }
        }
        $payment = new micropay($media_id, $media_mid);

        return $payment->get_pay_params($_W['user']['id'], $query['order_id'], [
            'openid' => $query['openid'], 'trade_type' => $trade_type
        ]);
    }
}
