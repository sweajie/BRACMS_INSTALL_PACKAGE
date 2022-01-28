<?php

namespace Bra\bra\pages;

use Bra\core\objects\BraArray;
use Bra\core\objects\BraExtWallet;
use Bra\core\objects\BraNotice;
use Bra\core\objects\BraPayDraw;
use Bra\core\pages\UserBaseController;
use Exception;
use Illuminate\Support\Facades\DB;

class WalletPage extends UserBaseController {

	public function bra_wallet_index ($query) {
		global $_W;
		$ret = [];
		if (is_bra_access(0)) {
			$bra_q = $query;
			$bra_q['user_id'] = $_W['user']['id'];

			$act_m = D('pay_logs');
			$bra_q['__simple'] = true;
			$bra_q['order'] = 'id desc';
			$bra_q['bra_int_fields'] = ['user_id', 'unit_type'];
			$config = [
				"list_fields" => ['id', 'note', 'create_at', 'amount', 'pay_type', 'total_fee'],
				"show_old_data" => false,
			];
			$ret = $act_m->list_bra_resource($bra_q, true, $config);
			$this->page_data['list'] = $ret;
		} else {
			$ret['user'] = $_W['user'];
			$this->page_data = $ret;
		}
	}

    public function tavern_wallet_card_add($query) {
        global $_W;
        if ($query['coin_id']) {
            $page_data['coin'] = $coin = D('coins')->bra_one($query['coin_id']);
        }
        $page_data = [];
        if (is_bra_access(0)) {
            if (!$query['real_name']) {
                return $this->page_data['res'] = bra_res(500, "请输入姓名' ", "" , $query);
            }

            if (!$query['card_no']) {
                return $this->page_data['res'] = bra_res(500, "请输入卡号' ", "" , $query);
            }
            //find card number
            $card = D('users_card')->bra_one(['card_no' => $query['card_no']]);
            if ($card) {
                return $this->page_data['res'] = bra_res(500, "该地址已经被绑定过了,请输入其他地址' ", "");
            }
            $card = D('users_card')->bra_one(['user_id' => $_W['user']['id'], 'is_default' => 1]);
            if (empty($card)) {
                $query['is_default'] = 1;
            } else {
                $query['is_default'] = 0;
            }
            $page_data['res'] = D('users_card')->item_add($query);
        } else {
            $page_data['bank_id_opts'] = collect(D('users_card')->load_options('bank_id'))->filter(function ($item) use ($coin) {
                if (!$coin) {
                    return true;
                } else {
                    return $item['bank_sign'] == strtoupper($coin['field']);
                }
            });
        }
        $this->page_data = $page_data;
    }

    public function tavern_wallet_offline_deposit($query) {
        $els = [];
        // $coin
        $els['coin'] = $coin = D('coins')->bra_one($query['unit_type']);

        if (is_bra_access(0)) {
            $bank_id = $query['bank_id'];
            if (!$bank_id) {
                return $this->page_data['res'] = bra_res(500, '请选择钱包网络1');
            }

            $bank = D('bank')->bra_one($bank_id);

            if (!$bank) {
                return $this->page_data['res'] = bra_res(500, '请选择钱包网络2');
            }

            //load card_no
            $card_id = $query['card_id'];

            $card = D('users_card')->with_user()->bra_one($card_id);

            if (!$card) {
                return $this->page_data['res'] = bra_res(500, '请选择钱包');
            }

            if ($card['bank_id'] != $bank['id']) {
//                return $this->page_data['res'] = bra_res(500, '选择的网络跟您钱包所在的网络不一致!');
            }

            $query['coin_id'] = $query['unit_type'];
            $query['card_no'] = $card['card_no'];
            $query['bank_id'] = $bank['id'];
            $query['status'] = 1;
            $els['res'] = D('pay_deposit')->item_add($query);
            $els['query'] = $query;
        } else {
            // tavern_wallet_offline_deposit
            $els['cards'] = array_values(collect(D('users_card')->with_user()->list_item())->filter(function ($item) use ($coin) {
                $bank = D('bank')->bra_one($item['bank_id']);
                if ($bank['bank_sign'] == strtoupper($coin['field'])) {
                    return $item;
                }
            })->toArray());

            $els['banks'] = collect(D('bank')->list_item())->filter(function ($bank) use ($coin) {
                if ($bank['bank_sign'] == strtoupper($coin['field'])) {
                    return $bank;
                }
            });
        }

        $this->page_data = $els;
    }

    public function tavern_wallet_draw_coin($query) {
        global $_W, $_GPC;
        $page_data['coin'] = $coin = D('coins')->bra_one($query['unit_type']);
        $page_data['wallet'] = $wallet_config = BraArray::get_config('wallet_config');
        $page_data['user'] = $user = refresh_user();
        $draw_m = D("pay_draw");
        if ($user && is_bra_access(0)) {
            //load card_no
            $card_id = $query['card_id'];
            if (!$card_id || !is_numeric($card_id)) {
                return $this->page_data['res'] = bra_res(500, '请选择钱包');
            }

            $card = D('users_card')->with_user()->bra_one($card_id);

            if (!$card) {
                return $this->page_data['res'] = bra_res(500, '请选择钱包');
            }

            if (!$card['card_no']) {
                return $this->page_data['res'] = bra_res(500, "请输入您的提现地址!");
            }

            if (!$query['amount'] || !is_numeric($query['amount'])) {
                return $this->page_data['res'] = bra_res(500, "请输入您的提现金额!");
            }

            Db::beginTransaction();
            try {
                $s_res = self::bra_pay_draw_coin($user['id'], $query['amount'], $coin, $card);
                if (is_error($s_res)) {
                    Db::rollback();

                    return $this->page_data['res'] = $s_res;
                } else {
                    Db::commit();

                    return $this->page_data['res'] = bra_res(1, "申请成功,请等待审核处理");
                }
            } catch (Exception $e) {
                Db::rollback();
                return $this->page_data['res'] = bra_res(500, "DataNotFoundException!");
            }
        } else {
            $donde['bank_id'] = ['EXIST', function ($db) use ($coin) {
                D('bank', '', true)->set_db($db)->bra_where(['bank_sign' => $coin['field']])->whereColumn('bank.id', '=', 'users_card.bank_id');
            }];
            $page_data['cards'] = D('users_card')->with_user()->bra_where($donde)->list_item(true);

            $page_data['balance'] = $user[$coin['field']];
            return $this->page_data = $page_data;
        }
    }

    public static function bra_pay_draw_coin(int $user_id, $amount, $coin, $card) {
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

        if ($coin['draw_fee_mode'] == 1) {
            $draw_fee = $amount * $coin['draw_fee'] / 100;
        } else {
            $draw_fee = $coin['draw_fee'];
        }

        $draw_fee = Number_format($draw_fee, 2, '.', '');
        $total_spend = $amount;
        $got_amount = $total_spend - $draw_fee;
        $coin_field = $coin['field'];
        if ($total_spend > $user[$coin_field]) {
            return bra_res(500, "申请失败 ， 你的{$coin['title']}不足!", $wallet_config['draw_coin'], $coin);
        }
        $insert = [];
        $insert['user_id'] = $user_id;
        $insert['status'] = 1;
        $rec = $draw_m->bra_one($insert);
        if ($rec) {
         //   return bra_res(500, "申请失败 ， 您已经有一个申请在处理中了!");
        }

        $insert['card_number'] = $card['card_no'];
        $insert['bank_address'] = '无';
        $insert['note'] = '提现';
        $pay_way_id = 3;
        $insert['type'] = $pay_way_id;
        $insert['real_name'] = $real_name ?? "匿名";
        $insert['media_id'] = "0";
        $insert['client'] = $_W['bra_client']; // 客户端
        $insert['amount'] = $got_amount;
        $insert['bank_id'] = $card['bank_id'];
        $insert['trans_fee'] = $draw_fee;
        $insert['unit_type'] = $coin['id'];
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

    public function bra_wallet_pass($query) {
        global $_W;
        $data = [];
        $user = refresh_user();
        if (is_bra_access(0)) {
            Db::beginTransaction();
            $res = BraNotice::verify_code($user['mobile'], $query['code']);
            if (!is_error($res)) {
                $wallet = new BraExtWallet($_W['user']['id']);
                $change_res = $wallet->change_pass($query['pass']);
                Db::commit();

                return $this->page_data = $change_res;
            } else {
                Db::commit();

                return $this->page_data = $res;
            }
        } else {
            $data['user'] = $_W['user'];

            return $this->page_data = $data;
        }
    }

	public function bra_wallet_wallet_draw ($query) {
		global $_W, $_GPC;

		$coins = config('bra_coin');
		$coin = $coins[$query['unit_type']];
		$page_data['wallet'] = $wallet_config = BraArray::get_config('wallet_config');
		$page_data['user'] = $user = refresh_user();
		$page_data['coin'] = $coin;
		if ($user && is_bra_access(0)) {
			if(!is_numeric($query['card_id'])){
				return $this->page_data['res'] = bra_res(500, "请选择银行卡!");
			}
			Db::beginTransaction();
			try {
				if ($coin['id'] == 2) {
					$s_res = self::pay_draw($user['id'], $query['amount'], $coin , $query['card_id']);
				} else {
					$s_res = BraPayDraw::wechat_pay_draw_micropay($user['id'], $query['amount'], $coin);
				}
				if (is_error($s_res)) {
					Db::rollback();

					return $this->page_data['res']  = $s_res;
				} else {
					Db::commit();

					return $this->page_data['res']  = bra_res(1, "申请成功,请等待审核处理");
				}
			} catch (Exception $e) {
				Db::rollback();

				return $this->page_data['res'] = bra_res(500, "DataNotFoundException!" , '' , $query);
			}
		} else {
			$page_data['cards'] = D('users_card')->bra_where(['user_id' => $_W['user']['id']])->list_item(true);
			$page_data['balance'] = $user[$coin['field']];

			return $this->page_data = $page_data;
		}
	}

	public function bra_wallet_cards ($query) {
		global $_W;
		$data = [];
		$data['cards'] = D('users_card')->bra_where(['user_id' => $_W['user']['id']])->list_item(true);
		$this->page_data = $data;
	}


	public function bra_wallet_card_add ($query) {
		global $_W;
		if ($query['coin_id']) {
			$page_data['coin'] = $coin = D('coins')->bra_one($query['coin_id']);
		}
		$page_data = [];
		if (is_bra_access(0)) {
			if (!$query['real_name']) {
				return $this->page_data['res'] = bra_res(500, "请输入姓名' ", "", $query);
			}
			if (!$query['card_no']) {
				return $this->page_data['res'] = bra_res(500, "请输入卡号' ", "", $query);
			}
//find card number
			$card = D('users_card')->bra_one(['card_no' => $query['card_no']]);
			if ($card) {
				return $this->page_data['res'] = bra_res(500, "该地址已经被绑定过了,请输入其他地址' ", "");
			}
			$card = D('users_card')->bra_one(['user_id' => $_W['user']['id'], 'is_default' => 1]);
			if (empty($card)) {
				$query['is_default'] = 1;
			} else {
				$query['is_default'] = 0;
			}
			$page_data['res'] = D('users_card')->item_add($query);
		} else {
			$page_data['bank_id_opts'] = collect(D('users_card')->load_options('bank_id'))->filter(function ($item) use ($coin) {
				if (!$coin) {
					return true;
				} else {
					return $item['bank_sign'] == strtoupper($coin['field']);
				}
			});
		}
		$page_data['use_wallet_pass'] = false;
		$this->page_data = $page_data;
	}

	public function bar_wallet_users_card_del ($query) {
		if (is_bra_access(0, 'post')) {
			return $this->page_data = D("users_card")->with_user()->item_del($query['id']);
		} else {
			return $this->page_data = bra_res(500, '不合法的访问！');
		}
	}


	public function bra_wallet_draw_list ($query) {
		global $_W;
		$els = [];
		$bra_m = D('pay_draw');
		if (is_bra_access(0)) {
			$query['bra_int_fields'] = ['status', 'user_id'];
			$query['user_id'] = $_W['user']['id'];
			$query['order'] = "id desc";
			$els = $bra_m->list_bra_resource($query);
			$this->page_data['list'] = $els;
		} else {
			$els['user'] = $_W['user'];
			$this->page_data = $els;
		}
	}



	public function bra_wallet_deposit($query) {
		global $_W, $_GPC;
		$page_data = [];
		if (is_bra_access(0)) {
			$order_res = BraOrder::create_order($_W['user']['id'], $query['amount'], $_W['_bra']['id'], '余额充值', ['total_money' => $query['amount'], 'act' => 'deposit']);

			return $this->page_data = $order_res;
		} else {
			$ways = D('pay_way_config')->bra_where(['status' => 1])->list_item();
			foreach ($ways as &$way) {
				$way['pay_way'] = D('pay_way')->find($way['pay_way_id']);
				unset($way['config']);
			}
			$page_data['ways'] = $ways;
			$page_data['user'] = $_W['user'];
			$page_data['wechat_fans'] = D('wechat_fans')->get_user_data();
			$page_data['sm'] = number_format(mt_rand(1, 99) / 100, 2);
			$page_data['config'] = BraArray::get_config('wallet_config');

			return $this->page_data = $page_data;
		}
	}

	public function bra_wallet_exchange ($query) {
		global $_W, $_GPC;
		$allow_pair = ['usdt_fic'];
		$pairs = explode('_', $query['pair']);
		if (!in_array($query['pair'], $allow_pair)) {
			end_resp(bra_res(500, '仅支持余额与虚拟币兑换!'));
		}
		$els['to_coin'] = $to_coin = D('coins')->bra_where(['field' => $pairs[1]])->bra_one();
		$els['from_coin'] = $from_coin = D('coins')->bra_where(['field' => $pairs[0]])->bra_one();
		$els['config'] = $config = $_W['_star']['config'];
		if (is_bra_access(0)) {
			if ($query['amount'] < 100) {
//				return $this->page_data['res'] = bra_res(500, '最少兑换数量是 100');
			}
			Db::beginTransaction();
			//检查最低数量
			$rate = 1;
			if ($from_coin['field'] == 'usdt') {
				$rate = 1 / $to_coin['price_' . $pairs[0]];
			}
			if ($to_coin['field'] == 'balance') {
				$rate = $to_coin['price_' . $to_coin["price_" . $pairs[0]]];
			}
			$ex_wallet = new BraExtWallet($_W['user']['id']);
			$res = $ex_wallet->exchange($pairs[0], $pairs[1], $query['amount'], '兑换', 0, 'free', 0, [], $rate);
			//spend score
			if (is_error($res)) {
				Db::rollback();

				return $this->page_data['res'] = $res;
			} else {
				Db::commit();

				return $this->page_data['res'] = bra_res(1, '兑换成功!');
			}
		} else {
			$els['user'] = refresh_user();
			$els['tips'] = "1 " . $to_coin['title'] . " = " . $to_coin["price_" . $pairs[0]] . $from_coin['title'];

			return $this->page_data = $els;
		}
	}

	public function bra_wallet_offline_deposit ($query) {
		$els = [];
		// $coin
		$els['coin'] = $coin = D('coins')->bra_one($query['unit_type'] , true);
		if (is_bra_access(0)) {
			$bank_id = $query['bank_id'];
			if (!$bank_id) {
				return $this->page_data['res'] = bra_res(500, '请选择钱包网络1');
			}
			$bank = D('bank')->bra_one($bank_id);
			if (!$bank) {
				return $this->page_data['res'] = bra_res(500, '请选择钱包网络2');
			}
			//load card_no
			$card_id = $query['card_id'];
			if($card_id){
				$card = D('users_card')->with_user()->bra_one($card_id);
				if (!$card) {
					return $this->page_data['res'] = bra_res(500, '请选择钱包');
				}
				if ($card['bank_id'] != $bank['id']) {
					return $this->page_data['res'] = bra_res(500, '选择的网络跟您钱包所在的网络不一致!');
				}
				$query['card_no'] = $card['card_no'];
			}else{

				$query['card_no'] = "-";
			}

			$query['coin_id'] = $query['unit_type'];
			$query['bank_id'] = $bank['id'];
			$query['status'] = 1;
//			$test = D('pay_deposit')->with_user()->bra_where(['amount' => $query['amount']])->bra_one();
//			if($test){
//				return $this->page_data['res'] = bra_res(500, '您已经充值过相同的金额了， 请换不同的数量充值!');
//			}
			$els['res'] = D('pay_deposit')->item_add($query);
			$els['query'] = $query;
		} else {
			// tavern_wallet_offline_deposit
			$els['cards'] = array_values(collect(D('users_card')->with_user()->list_item())->filter(function ($item) use ($coin) {
				$bank = D('bank')->bra_one($item['bank_id']);
				if ($bank['bank_sign'] == strtoupper($coin['field'])) {
					return $item;
				}
			})->toArray());
			$els['banks'] = collect(D('bank')->list_item())->filter(function ($bank) use ($coin) {
				if ($bank['bank_sign'] == strtoupper($coin['field']) && $bank['address'] != '') {
					return $bank;
				}
			});
		}
		$this->page_data = $els;
	}

	public function bra_wallet_draw_bank_coin ($query) {
		global $_W, $_GPC;
		$coin = D('coins')->bra_one((int)$query['unit_type'] );
		$page_data['coin'] = D('coins')->get_item($coin['id'] , true);

		$page_data['fee_coin'] = D('coins')->bra_one($coin['draw_fee_coin_id']);
		$page_data['wallet'] = BraArray::get_config('wallet_config');
		$page_data['user'] = $user = refresh_user();
		if ($user && is_bra_access(0)) {
			//load card_no
			$card_id = $query['card_id'];
			if (!$card_id || !is_numeric($card_id)) {
				return $this->page_data['res'] = bra_res(500, '请选择钱包');
			}
			$card = D('users_card')->with_user()->bra_one($card_id);
			if (!$card) {
				return $this->page_data['res'] = bra_res(500, '请选择钱包');
			}
			if (!$card['card_no']) {
				return $this->page_data['res'] = bra_res(500, "请输入您的提现地址!");
			}
			if (!$query['amount'] || !is_numeric($query['amount'])) {
				return $this->page_data['res'] = bra_res(500, "请输入您的提现金额!");
			}
			Db::beginTransaction();
			try {
				$s_res = BraPayDraw::bra_pay_draw_fee_coin($user['id'], $query['amount'], $coin, $card);
				if (is_error($s_res)) {
					Db::rollback();

					return $this->page_data['res'] = $s_res;
				} else {
					Db::commit();

					return $this->page_data['res'] = bra_res(1, "申请成功,请等待审核处理");
				}
			} catch (Exception $e) {
				Db::rollback();

				return $this->page_data['res'] = bra_res(500, "DataNotFoundException!" . $e->getMessage());
			}
		} else {
			$donde['bank_id'] = ['EXIST', function ($db) use ($coin) {
				D('bank', '', true)->set_db($db)->bra_where(['unit_type' => $coin['id']])->whereColumn('bank.id', '=', 'users_card.bank_id');
			}];
			$page_data['cards'] = D('users_card')->with_user()->bra_where($donde)->list_item(true);
			$page_data['balance'] = $user[$coin['field']];
			$page_data['use_wallet_pass'] = false;

			return $this->page_data = $page_data;
		}
	}

	public function bra_wallet_del_users_card($query) {
		return $this->page_data['res'] = bra_res(500, "不支持解绑地址!");
		global $_W, $_GPC;
		$this->page_data = D('users_card')->with_user()->item_del($query['id']);
		if (!is_error($this->page_data)) {
			(new BraNotice(3))->send($_W['user']['id']);
		}
	}
}
