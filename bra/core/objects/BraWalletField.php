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

class BraWalletField {
	public $model_id;
	public $item_id;
	public $field;

	public $item;
	public $model;

	public $donde;

	public function __construct ($model_id, $item_id, $field , $lock = false) {
		$this->model_id = $model_id;
		$this->item_id = $item_id;
		$this->field = $field;
		$this->item = D($this->model_id)->lock($lock)->find($item_id);
		if (!$this->item) {
			Throw new BraException('错误的用户!');
		}
		$this->donde['id'] = $item_id;
	}

	public static function open_account ($model_id, $user_id, $init_amount) {
		$donde['user_id'] = $user_id;
		$ext['amount'] = $init_amount;
		$res = D($model_id)->bra_foc($donde , $ext);
		if (is_error($res)) {
			return bra_res(500, "系统错误 :  " . $res['msg'], "");
		} else {
			return bra_res(1, "开通成功 :  ", "");
		}
	}

	public static function is_opened ($model_id, int $user_id) {
		$res = D($model_id)->get_user_data($user_id);
		if (!$res) {
			return bra_res(500, "未开通 :  " . $res['msg'], "");
		} else {
			return bra_res(1, "开通 :  ", $user_id, $res);
		}
	}

	public function f_spend ($amount) {
		$coin_type = $this->field;
		if (is_numeric($amount) && $amount > 0) {
			$extra['operate'] = 1;
			if ($this->item[$coin_type] >= $amount) {
				$res = D($this->model_id)->bra_where($this->donde)->decrement($coin_type, $amount);
				if ($res) {
					return bra_res(1, '消费操作成功!');
				} else {
					return bra_res(500, '操作消费失败!!');
				}
			}

			return bra_res(6, '余额不足!');
		} else {
			return bra_res(500, '错误的金额!');
		}
	}

	public function f_deposit ($amount) {
		$coin_type = $this->field;
		if (is_numeric($amount) && $amount > 0) {
			$res = D($this->model_id)->bra_where($this->donde)->increment($coin_type, $amount);
			if ($res) {
				return bra_res(1, '存款操作操作成功!');
			} else {
				return bra_res(500, '操作失败 , 用户数据写入失败!');
			}
		} else {
			return bra_res(500, '错误的金额!');
		}
	}

}
