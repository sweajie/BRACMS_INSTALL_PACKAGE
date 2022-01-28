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

use Bra\wechat\medias\WechatMedia;
use EasyWeChat\Factory;

class  micropay extends factory_pay {

    const WAY_ID = 1;
    const GATEWAY = 'micropay';

    public function __construct (int $media_id,int  $media_mid, $callback_url = '') {
        parent::__construct($media_id, $media_mid, $callback_url);
        if ($media_mid == 1174) {
            $this->callback_url = make_url('bra_app/bra_app/pay_callback');
        } else {
            $this->callback_url = make_url('wechat/bra_wechat/pay_callback');
        }
        $this->pay = Factory::payment($this->get_config());
    }

    public function get_config () {
        $done = [];
        $done['pay_way_id'] = self::WAY_ID;
        $pay_way = (array)D('pay_way_config')->with_site()->bra_where($done)->first();
        $this->pay_config = json_decode($pay_way['config'], 1);
        if ($this->pay_config['apiclient_cert']) {
            $cert_path = annex_url($this->pay_config['apiclient_cert'], true);
        }
        if ($this->pay_config['apiclient_key']) {
            $key_path = annex_url($this->pay_config['apiclient_key'], true);
        }
        $config = [// 必要配置
            'app_id' => $this->media['app_id'],
            'mch_id' => $this->pay_config['mchid'],
            'key' => $this->pay_config['apikey'],            // API 密钥
            'cert_path' => $cert_path ?? '', // XXX: 绝对路径！！！！
            'key_path' => $key_path ?? '',      // XXX: 绝对路径！！！！
            'notify_url' => $this->callback_url,     //
        ];
        return $config;
    }

    /**
     * @param $user_id
     * @param $order_id
     * @param array $config 传入的参数 ['openid' , 'trade_type']
     * @return mixed
     */
    public function get_pay_params ($user_id, $order_id, $config = []) {
        $order_m = D('orders');
        $order = (array)$order_m->find($order_id);
        if (empty($order) || $order['status'] != 1 ) {

            return bra_res(502, "您好，当前订单 $order_id 状态无法进行在线支付！");
        }
        $test = $this->query_order_out_trade($order['out_trade_no']);
        if (!empty($test['trade_state']) && $test['trade_state'] == 'SUCCESS') {
            return bra_res(502, "您好，订单已经支付完成了，无需重复支付！");
        }
        $openid = $config['openid'];
        if (!$openid) {
            //get openid
            $donde = [];
            $donde['user_id'] = $user_id;
            $donde['media_id'] = $this->media_id;
            $res = (array)D("wechat_fans")->bra_where($donde)->first();
            if (!$res) {
                return bra_res(502, "您好，支付需要openid  ！", '', $donde);
            } else {
                $openid = $res['openid'];
            }
        }
        $trade_type = $config['trade_type'];
        $update['pay_user_id'] = $user_id;
        if ((!empty($test['trade_state']) && $test['trade_state'] == "CLOSED") || !$order['out_trade_no']) {
            $order['out_trade_no'] = $update['out_trade_no'] = $order['id'] . "|" . time();
        }
        $update['gateway'] = self::GATEWAY;
        $update['media_id'] = $this->media_id;
        $update['media_mid'] = $this->media_mid;
        $order_m->bra_where($order['id'])->update($update);
        $order_config = [
            'body' => $order['note'],
            'out_trade_no' => $order['out_trade_no'],
            'total_fee' => $order['total_fee'] * 100,
            'trade_type' => $trade_type
        ];
        if ($trade_type == "JSAPI") {
            $order_config['openid'] = $openid;
        }
        $result = $this->pay->order->unify($order_config);
        if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
            if ($this->media_mid == 1174) {
                return $this->pay->jssdk->appConfig($result['prepay_id']);
            } else {
                return $this->pay->jssdk->bridgeConfig($result['prepay_id'], false);
            }
        } else {
            return bra_res(500, '下单错误！', $order_config, $result);
        }
    }

    public function query_order_out_trade ($out_trade_no) {
        return $this->pay->order->queryByOutTradeNumber($out_trade_no);
    }

    public function refund ($sys_order, $amount) {
        global $_W, $_GPC;
        $totalFee = $sys_order['total_fee'] * 100;
        $refundFee = $amount * 100;
        $result = $this->pay->refund->byOutTradeNumber($sys_order['out_trade_no'], $sys_order['id'], $totalFee, $refundFee, [
            'refund_desc' => '用户取消预约',
        ]);
        /**
         * ^ array:9 [▼
         * "return_code" => "SUCCESS"
         * "return_msg" => "OK"
         * "appid" => "wx4efa4c6f6a79f981"
         * "mch_id" => "1588733771"
         * "nonce_str" => "nGN2jkvqzjnTronp"
         * "sign" => "06E06F1DAD59DDA4BC2A48EF14BEC6D9"
         * "result_code" => "FAIL"
         * "err_code" => "NOTENOUGH"
         * "err_code_des" => "基本账户余额不足，请充值后重新发起"
         * ]
         */
        if ($result['result_code'] === "SUCCESS") {
            return bra_res(1, "退款成功 ", "");
        } else {
            return bra_res(500, "退款失败, " . $result['err_code_des'], "");
        }
    }
}
