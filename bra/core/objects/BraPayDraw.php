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
namespace Bra\core\objects;

use Bra\core\utils\BraException;
use Bra\core\utils\pay\bra_micropay;
use Bra\wechat\medias\WechatMedia;
use EasyWeChat\Factory;

class BraPayDraw {
    public $pay_draw;
    public $pay_draw_m;
    public $desc = '提现';

    public function __construct($draw_id) {
        $this->pay_draw_m = D('pay_draw');
        $this->pay_draw = D('pay_draw')->with_site()->bra_one($draw_id);
    }

	public static function bra_pay_draw_fee_coin (int $user_id, $amount, $coin, $card, $pay_way_id = 3 , $fee_coin = false) {
		global $_W;
		$wallet = new BraExtWallet($user_id);
		$user = $wallet->user;
		$draw_m = D("pay_draw");
		$wallet_config = BraArray::get_config('wallet_config');
		//每天提现次数
		$max_draw = $wallet_config['max_draw'];
		if (!empty($max_draw) && $max_draw > 0) {
			$where = [];
			$where['user_id'] = $user_id;
			$where['create_at'] = ['DAY', 'today'];
			$today_count = $draw_m->bra_where($where)->count();
			if ($today_count >= $max_draw) {
				return bra_res(500, "申请失败 ， 您今天已经超过最大提现次数!!");
			}
		}
		if (!is_numeric($amount)) {
			return bra_res(500,
				"申请失败 ， 请输入提现数量!");
		}
		if ($amount < $wallet_config['min_withdraw']) {
			return bra_res(500,
				"申请失败 ， 最低提现数量为!" . $wallet_config['min_withdraw'] .  $coin['field']);
		}
		list($got_amount, $draw_fee , $fee_coin) = self::get_draw_fee($amount, $coin , $fee_coin);
		//spend fee

		if($draw_fee > 0){
			$s_res = $wallet->ex_spend($draw_fee, $fee_coin['id'], 1, "提现");
			if (is_error($s_res)) {
				return bra_res(500, '手续费余额不足!!!', '', $s_res);
			}
		}


		$coin_field = $coin['field'];
		if ($amount > $user[$coin_field]) {
			return bra_res(500, "申请失败 ， 你的{$coin['title']}不足!", $wallet_config['draw_coin'], $coin);
		}
		$insert = [];
		$insert['user_id'] = $user_id;
		$insert['status'] = 1;
		$rec = $draw_m->bra_one($insert);
		if ($rec) {
			return bra_res(500, "申请失败 ， 您已经有一个申请在处理中了!");
		}
		$insert['card_number'] = $card['card_no'];
		$insert['bank_address'] = '无';
		$insert['note'] = '提现';
		$insert['type'] = $pay_way_id;
		$insert['real_name'] = $real_name ?? "匿名";
		$insert['media_id'] = "0";
		$insert['client'] = $_W['bra_client']; // 客户端
		$insert['amount'] = $got_amount;
		$insert['bank_id'] = $card['bank_id'];
		$insert['trans_fee'] = $draw_fee;
		$insert['unit_type'] = $coin['id'];
		$insert['draw_fee_coin_id'] = $fee_coin ? $fee_coin['id'] : 0;
		$s_res = $wallet->ex_spend($amount, $coin_field, 1, "提现");
		if (is_error($s_res)) {
			return bra_res(500, '账户余额不足!!!', '', $s_res);
		} else {
			$res = $draw_m->item_add($insert);
			if (is_error($res)) {
				return $res;
			} else {
				return bra_res(1, "申请成功,请等待审核处理");
			}
		}
	}

	/**
	 * @param $amount
	 * @param $draw_coin
	 * @return array
	 */
	public static function get_draw_fee ($amount, $draw_coin , $fee_coin) {
		if ($draw_coin['draw_fee_mode'] == 1) {
			$draw_fee = $amount * $draw_coin['draw_fee'] / 100;
		} else {
			$draw_fee = $draw_coin['draw_fee'];
		}
		if (!$fee_coin && $draw_coin['draw_fee_coin_id'] && $draw_coin['draw_fee_coin_id'] != $draw_coin['id']) {
			$fee_coin = D("coins")->bra_one($draw_coin['draw_fee_coin_id']);
		}
		if (isset($fee_coin) && $fee_coin && $fee_coin['id'] != $draw_coin['id']) {
			if($fee_coin['price_' . $draw_coin['field']] == 0){
				throw new BraException("Draw Coin fee not set");
			}else{
				$draw_fee *= 1 / $fee_coin['price_' . $draw_coin['field']];
				$got_amount = $amount;
			}
		} else {
			$got_amount = $amount - $draw_fee;
		}

		return [$got_amount, $draw_fee , $fee_coin];
	}

	/**
     * create a pay_draw for a user
     * @param int $user_id
     * @param $amount
     * @param $gate_way
     * @param $card_id
     * @return mixed
     */
    public static function create_draw(int $user_id, $amount, $gate_way, int $card_id = 0) {
        global $_W;
        $user = refresh_user($user_id);
        $wallet_config = BraArray::get_config('wallet_config');
        $uv = D('users_verify')->bra_where(['status' => 99])->get_user_data();
        if ($wallet_config['draw_need_verify'] && !$uv) {
            return bra_res(500, "申请失败 ， 提现需要实名认证!");
        }
        $draw_m = D("pay_draw");
        //每天提现次数
        $max_draw = $wallet_config['max_draw'];
        if (!$gate_way) {
            return bra_res(500, "申请失败 ， 请选择提现方式!");
        }
        if (!empty($max_draw) && $max_draw > 0) {
            $where = [];
            $where['user_id'] = $user_id;
            $where['create_at'] = ['DAY', 'today'];
            $today_count = $draw_m->bra_where($where)->count();
            if ($today_count >= $max_draw) {
                return bra_res(500, "申请失败 ， 您今天已经超过最大提现次数!!");
            }
        }
        if (!is_numeric($amount)) {
            return bra_res(500,
                "申请失败 ， 请输入提现数量!");
        }
        if ($amount < $wallet_config['min_withdraw']) {
            return bra_res(500,
                "申请失败 ， 最低提现数量为!" . $wallet_config['min_withdraw'] . '元');
        }

        $draw_fee = 0;
        if ($wallet_config['drawfee_way'] == 1) {
            $draw_fee = $amount * $wallet_config['drawfee'] / 100;
        }
        if ($wallet_config['drawfee_way'] == 2) {
            $draw_fee = $wallet_config['drawfee'];
        }
        $draw_fee = Number_format($draw_fee, 2, '.', '');
        $total_spend = $amount + $draw_fee;
        $coin = BraExtWallet::get_draw_coin();
        $coin_field = $coin['field'] ?? 'balance';
        if ($total_spend > $user[$coin_field]) {
            return bra_res(500, "申请失败 ， 你的{$coin['title']}不足!", $wallet_config['draw_coin'], $coin);
        }
        $insert = [];
        $insert['user_id'] = $user_id;
        $insert['status'] = 1;
        $rec = $draw_m->bra_one($insert);
        if ($rec) {
        //    return bra_res(500, "申请失败 ， 您已经有一个申请在处理中了!");
        }

        $insert['card_number'] = '无';
        $insert['bank_address'] = '无';
        $insert['note'] = '提现';
        switch ($gate_way) {
            case "micropay" : //微信
                $bank_sign = "micropay";
                $pay_way_id = 1;
                break;
                break;
            case "alipay" : // 支付宝
                $bank_sign = "alipay";
                $pay_way_id = 2;
                break;
            case "bra_offline" : // 线下
                $bank_sign = "bra_offline";
                $pay_way_id = 3;

                if (!$card_id) {
                    $bank = D('bank')->bra_one(['bank_sign' => $bank_sign]);
                    $card_inf = D('users_card')->bra_where(['bank_id' => $bank['id']])->get_user_data();
                } else {
                    $card_inf = D('users_card')->bra_where(['user_id' => $user_id])->bra_one($card_id);
                    $bank = D('bank')->bra_one($card_inf['bank_id']);
                }

                if (!$card_inf) {
                    return bra_res(500, "申请失败 ， 银行卡不存在!!");
                }
                if (!$bank) {
                    return bra_res(500, "申请失败 ， 该银行已经暂停合作!!!");
                }
                if (!empty($bank['min_amount']) && $bank['min_amount'] > 0 && $amount < $bank['min_amount']) {
                    return bra_res(500, "申请失败 ， !" . $bank['title'] . '最低提现' . $bank['min_amount'] . '元');
                }
                if (!empty($bank['max_amount']) && $bank['max_amount'] > 0 && $amount > $bank['max_amount']) {
                    return bra_res(500, "申请失败 ， !" . $bank['title'] . '最大提现' . $bank['max_amount'] . '元');
                }
                $insert['card_number'] = $card_inf['card_no'];
                $insert['bank_address'] = $card_inf['bank_address'];

                $real_name = $card_inf['real_name'] ? $card_inf['real_name'] : $uv['real_name'];

                break;
            case "bra_micropay" : //微信
                $bank_sign = "micropay";
                $pay_way_id = 4;
                break;
            default:
                return bra_res(500, "申请失败 ， 该银行已经暂停合作!!!");
        }

        // type 1
        $insert['type'] = $pay_way_id;
        $insert['real_name'] = $real_name ?? "匿名";
        $insert['media_id'] = "0";
        $insert['client'] = $_W['bra_client']; // 客户端
        $insert['amount'] = $amount;
        $insert['bank_id'] = $bank['id'] ?? 0;
        $insert['trans_fee'] = $draw_fee;
        $wallet = new BraExtWallet($user_id);
        $s_res = $wallet->ex_spend($total_spend, $coin_field, 1, "提现");
        if (is_error($s_res)) {
            return bra_res(500, '余额不足!!!', '', $s_res);
        } else {
            $res = $draw_m->item_add($insert);
            if (is_error($res)) {
                return $res;
            } else {
                return bra_res(1, "申请成功,请等待审核处理");
            }
        }
    }

    public function online_pay($provider_id = 1) {
        if ($this->pay_draw['status'] != 1) {
            return bra_res(500, '对不起,操作状态不是待审核的!');
        }
        switch ($provider_id) {
            case 3:
            case 1 :
                $res = $this->finish_wechat();
                break;
            default :
                $res = bra_res(500, '对不起,暂不支持该种打款方式!');
        }

        return $res;
    }

    public function finish_wechat() {
        $draw = $this->pay_draw;
        switch ($draw['client']) {
            case 8 :
                $data[] = $res = $this->finish_wechat_app();
                break;
            case 3 :
                $data[] = $res = $this->finish_wechat_mini();
                break;
            case 2 :
                $data[] = $res = $this->finish_wechat_offical();
                break;
            default :
                return bra_res(500, '暂不支持该种客户端提现');
        }
        if (!isset($res['result_code']) || $res['result_code'] == 'FAIL') {
            return bra_res(500, $res['err_code_des']);
        } else if ($res['result_code'] == 'SUCCESS') {
            $u['status'] = 99;

            return D("pay_draw")->update($u, $draw['id']);
        } else {
            return bra_res(500, '未知错误', '', $res);
        }
    }

    private function finish_wechat_app() {
        $media = D('app')->find($this->pay_draw['media_id']);
        $app = $this->init_micropay_pay($media['wx_app_id']);
        $app_fans = D('app_fans')->order('id desc')->bra_one(['user_id' => $this->pay_draw['user_id']]);
        if (!$app_fans || empty($app_fans['wx_openid'])) {
            return [
                'result_code' => 'FAIL',
                'err_code_des' => "该用户尚未绑定微信 , 无法使用微信打款!",
                'data' => $app_fans
            ];
        }
        $res = $app->transfer->toBalance([
            'partner_trade_no' => $this->pay_draw['id'], // 商户订单号，需保持唯一性(只能是字母或者数字，不能包含有符号)
            'openid' => $app_fans['wx_openid'],
            'check_name' => 'FORCE_CHECK', // NO_CHECK：不校验真实姓名, FORCE_CHECK：强校验真实姓名
            're_user_name' => $this->pay_draw['real_name'], // 如果 check_name 设置为FORCE_CHECK，则必填用户真实姓名
            'amount' => $this->pay_draw['amount'] * 100, // 企业付款金额，单位为分
            'desc' => $this->desc, // 企业付款操作说明信息。必填
        ]);

        return $res;
    }

    private function init_micropay_pay($app_id) {
        static $ways = [];
        if (!$ways[$app_id]) {
            //todo

            if ($allow_tunnel = false) {
                $donde['bank_sign'] = 'micropay';
                $donde['is_default'] = 1;
                $set = D('pay_draw_way')->with_site()->bra_one($donde);
                if (empty($set)) { //todo : load default set
                    end_resp(bra_res(500, '提现通道无法获取'));
                } else {
                    $way_config = json_decode($set['config'], 1);
                    $ac = new BraAnnex($way_config['apiclient_cert']);
                    $ak = new BraAnnex($way_config['apiclient_key']);
                }
                $config = [
                    'app_id' => $app_id,
                    'mch_id' => $way_config['mchid'],
                    'key' => $way_config['apikey'],
                    'cert_path' => $ac->get_url(true),
                    'key_path' => $ak->get_url(true),
                    'notify_url' => url('wechat/bra_wechat/send_callback')
                ];
                $ways[$app_id] = Factory::payment($config);
            } else {
                $media = WechatMedia::get_media_official();
                $pay = new bra_micropay($media->media['id'], D('wechat')->_TM['id']);

                $ways[$app_id] = $pay->pay;
            }
        }

        return $ways[$app_id];
    }

    private function finish_wechat_mini() {
        $media = D('wechat')->find($this->pay_draw['media_id']);
        $app = $this->init_micropay_pay($media['app_id']);
        $donde = ['user_id' => $this->pay_draw['user_id']];
        $donde['media_id'] = $media['id'];
        $wechat_fans = D('wechat_fans')->bra_where($donde)->find();
        if (!$wechat_fans || empty($wechat_fans['openid'])) {
            return [
                'result_code' => 'FAIL',
                'err_code_des' => "该用户尚未绑定微信 , 无法使用微信打款!",
                'data' => $wechat_fans
            ];
        }
        $res = $app->transfer->toBalance([
            'partner_trade_no' => $this->pay_draw['id'], // 商户订单号，需保持唯一性(只能是字母或者数字，不能包含有符号)
            'openid' => $wechat_fans['openid'],
            'check_name' => 'FORCE_CHECK', // NO_CHECK：不校验真实姓名, FORCE_CHECK：强校验真实姓名
            're_user_name' => $this->pay_draw['real_name'], // 如果 check_name 设置为FORCE_CHECK，则必填用户真实姓名
            'amount' => $this->pay_draw['amount'] * 100, // 企业付款金额，单位为分
            'desc' => $this->desc, // 企业付款操作说明信息。必填
        ]);

        return $res;
    }

    private function finish_wechat_offical() {
        $media = D('wechat')->find($this->pay_draw['media_id']);
        $app = $this->init_micropay_pay($media['app_id']);
        $redpack = $app->redpack;
        $donde = ['user_id' => $this->pay_draw['user_id']];
        $donde['media_id'] = $media['id'];
        $wechat_fans = D('wechat_fans')->bra_where($donde)->bra_one();
        if (!$wechat_fans || empty($wechat_fans['openid'])) {
            return [
                'result_code' => 'FAIL',
                'err_code_des' => "该用户尚未绑定微信 , 无法使用微信打款!",
                'data' => $wechat_fans
            ];
        }

        $redpackData = [
            'mch_billno' => $this->pay_draw['id'],
            'send_name' => '活动中心',
            're_openid' => $wechat_fans['openid'],
            'total_num' => 1,  //固定为1，可不传
            'total_amount' => $this->pay_draw['amount'] * 100,  //单位为分，不小于100
            'wishing' => '祝您生活愉快',
            //'client_ip'    => '192.168.0.1',  //可不传，不传则由 SDK 取当前客户端 IP
            'act_name' => '红包活动',
            'remark' => '参与红包',
            // ...
        ];

        return $redpack->sendNormal($redpackData);
        dd($result);
        /**
         *
         * $res = $app->transfer->toBalance([
         * 'partner_trade_no' => $this->pay_draw['id'], // 商户订单号，需保持唯一性(只能是字母或者数字，不能包含有符号)
         * 'openid' => $wechat_fans['openid'],
         * 'check_name' => 'FORCE_CHECK', // NO_CHECK：不校验真实姓名, FORCE_CHECK：强校验真实姓名
         * 're_user_name' => $this->pay_draw['real_name'], // 如果 check_name 设置为FORCE_CHECK，则必填用户真实姓名
         * 'amount' => $this->pay_draw['amount'] * 100, // 企业付款金额，单位为分
         * 'desc' => $this->desc, // 企业付款操作说明信息。必填
         * ]);
         */
    }

    public function finish_deny($note) {
        if ($this->pay_draw['status'] != 1) {
            return bra_res(500, '对不起,状态不是待审核的 不能驳回!');
        } else {
            $wallet = new BraExtWallet($this->pay_draw['user_id']);
			if($this->pay_draw['draw_fee_coin_id'] && $this->pay_draw['draw_fee_coin_id'] != $this->pay_draw['unit_type']){
				$res = $wallet->ex_deposit(
					$this->pay_draw['amount'],
					$this->pay_draw['unit_type'] ,
					20,
					"提现申请驳回:" . $note);
				if (is_error($res)) {
					return $res;
				}else{
					$res = $wallet->ex_deposit(
						$this->pay_draw['trans_fee'],
						$this->pay_draw['draw_fee_coin_id'] ,
						20,
						"提现申请驳回:" . $note);
				}
			}else{
				$res = $wallet->ex_deposit(
					$this->pay_draw['amount'] + $this->pay_draw['trans_fee'],
					$this->pay_draw['unit_type'] ,
					20,
					"提现申请驳回:" . $note);
			}

			if (is_error($res)) {
				return $res;
			} else {
				$u['status'] = 2;
				$u['note'] = $note;

				return D("pay_draw")->item_edit($u, $this->pay_draw['id']);
			}
        }
    }


    public static function wechat_pay_draw_micropay(int $user_id, $amount, $coin) {
        global $_W;
        $user = refresh_user($user_id);
        $wallet_config = BraArray::get_config('wallet_config');
        $uv = D('users_verify')->bra_where(['status' => 99])->get_user_data();
        if ($wallet_config['draw_need_verify'] && !$uv) {
            return bra_res(500, "申请失败 ， 提现需要实名认证!");
        }
        $draw_m = D("pay_draw");
        //每天提现次数
        $max_draw = $wallet_config['max_draw'];

        if (!empty($max_draw) && $max_draw > 0) {
            $where = [];
            $where['user_id'] = $user_id;
            $where['create_at'] = ['DAY', 'today'];
            $today_count = $draw_m->bra_where($where)->count();
            if ($today_count >= $max_draw) {
                return bra_res(500, "申请失败 ， 您今天已经超过最大提现次数!!");
            }
        }
        if (!is_numeric($amount)) {
            return bra_res(500,
                "申请失败 ， 请输入提现数量!");
        }
        if ($amount < $wallet_config['min_withdraw']) {
            return bra_res(500,
                "申请失败 ， 最低提现数量为!" . $wallet_config['min_withdraw'] . '元');
        }

        $draw_fee = 0;
        if ($wallet_config['drawfee_way'] == 1) {
            $draw_fee = $amount * $wallet_config['drawfee'] / 100;
        }
        if ($wallet_config['drawfee_way'] == 2) {
            $draw_fee = $wallet_config['drawfee'];
        }
        $draw_fee = Number_format($draw_fee, 2, '.', '');
        $total_spend = $amount;
        $total_got = $amount - $draw_fee;
        $coin_field = $coin['field'];
        if ($total_spend > $user[$coin_field]) {
            return bra_res(500, "申请失败 ， 你的{$coin['title']}不足!", $wallet_config['draw_coin'], $coin);
        }
        $insert = [];
        $insert['user_id'] = $user_id;
        $insert['status'] = 1;
        $rec = $draw_m->bra_one($insert);
        if ($rec) {
        //    return bra_res(500, "申请失败 ， 您已经有一个申请在处理中了!");
        }

        $insert['card_number'] = '无';
        $insert['bank_address'] = '无';
        $insert['note'] = '提现';
        $pay_way_id = 1;
        $insert['type'] = $pay_way_id;
        $insert['real_name'] = $real_name ?? "匿名";
        $insert['media_id'] = "0";
        $insert['client'] = $_W['bra_client']; // 客户端
        $insert['amount'] = $total_got;
        $insert['bank_id'] = $bank['id'] ?? 0;
        $insert['unit_type'] = $coin['id'];
        $insert['trans_fee'] = $draw_fee;
        $wallet = new BraExtWallet($user_id);
        $s_res = $wallet->ex_spend($total_spend, $coin_field, 1, "提现");
        if (is_error($s_res)) {
            return bra_res(500, '余额不足!!!', '', $s_res);
        } else {
            $res = $draw_m->item_add($insert);
            if (is_error($res)) {
                return $res;
            } else {
                return bra_res(1, "申请成功,请等待审核处理");
            }
        }
    }
}
