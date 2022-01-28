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
namespace Bra\core\utils\pay;

use EasyWeChat\Factory;

class  bra_micropay extends factory_pay {

    const WAY_ID = 4;
    const GATEWAY = 'bra_micropay';

    public function __construct ($media_id, $media_mid, $sub_mch_id = '') {
        parent::__construct($media_id, $media_mid, $sub_mch_id);
        if ($media_mid == 1174) {
            $this->callback_url = make_url('bra_app/bra_app/pay_callback');
        } else {
            $this->callback_url = make_url('wechat/bra_wechat/pay_callback');
        }
        $this->pay = Factory::payment($this->get_config());
    }

    private function get_config () {
        $done = [];
        $done['pay_way_id'] = self::WAY_ID;
        $pay_way = (array)D('pay_way_config')->with_site()->bra_where($done)->first();
        $this->pay_config = json_decode($pay_way['config'], 1);
        if (!$this->sub_mch_id) {
            $this->sub_mch_id = $this->pay_config['sub_mch_id'];
        }
        if ($this->pay_config['apiclient_cert']) {
            $cert_path = annex_url($this->pay_config['apiclient_cert'], true);
        }
        if ($this->pay_config['apiclient_key']) {
            $key_path = annex_url($this->pay_config['apiclient_key'], true);
        }
        $config = [// 必要配置
            'app_id' => $this->pay_config['app_id'],
            'mch_id' => $this->pay_config['mchid'],
            'key' => $this->pay_config['apikey'],            // API 密钥
            'sub_mch_id' => $this->sub_mch_id,            // API 密钥
            'cert_path' => $cert_path ?? '', // XXX: 绝对路径！！！！
            'key_path' => $key_path ?? '',      // XXX: 绝对路径！！！！
            'notify_url' => $this->callback_url,     //
        ];
        if ($this->media['app_id'] != $this->pay_config['app_id']) {
            //  "sub_appid" => $this->media['app_id'],
            $config["sub_appid"] = $this->media['app_id'];
        }


        return $config;
    }

    public function get_pay_params ($user_id, $order_id, $config = []) {
        $order_m = D('orders');
        $openid = $config['openid'];
        $trade_type = $config['trade_type'];
        $order = (array)$order_m->find($order_id);
        $test = $this->query_order_out_trade($order['out_trade_no']);
        if (!empty($test['trade_state']) && $test['trade_state'] == 'SUCCESS') {
            return bra_res(502, "您好，订单已经支付完成了，无需重复支付！");
        }
        $update['pay_user_id'] = $user_id;
        if ((!empty($test['trade_state']) && $test['trade_state'] == "CLOSED") || !$order['out_trade_no']) {
            $order['out_trade_no'] = $update['out_trade_no'] = $order['id'] . "|" . time();
        }
        $update['gateway'] = self::GATEWAY;
        $update['media_id'] = $this->media_id;
        $update['media_mid'] = $this->media_mid;
        $order_m->item_edit($update, $order['id'], false, false);
        $order_config = [
            //  和sub_appid
            'body' => $order['note'],
            'out_trade_no' => $order['out_trade_no'],
            'total_fee' => $order['total_fee'] * 100,
            'trade_type' => $trade_type,
        ];
        if ($trade_type == "JSAPI") {
            $order_config['sub_openid'] = $openid;
        }
        $result = $this->pay->order->unify($order_config);
        if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
            if ($this->media_mid == 1174) {
                return $this->pay->jssdk->appConfig($result['prepay_id']);
            } else {
                return $this->pay->jssdk->bridgeConfig($result['prepay_id'], false);
            }
        } else {
            return bra_res(500, '下单错误！', '', $result);
        }
    }

    public function query_order_out_trade ($out_trade_no) {
        return $this->pay->order->queryByOutTradeNumber($out_trade_no);
    }

    public function refund ($sys_order, $amount) {
        // TODO: Implement refund() method.
    }
}
