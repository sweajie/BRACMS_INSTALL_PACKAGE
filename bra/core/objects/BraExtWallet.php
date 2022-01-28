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


use App\Models\User;
use Bra\core\utils\BraException;
use Illuminate\Support\Facades\DB;

class BraExtWallet {
    private $module_id;
    /**
     * @var $user User
     */
    public $user;
    private static $coin_types = null;
    private $pay_logs_model;
    private $admin_uid;

    public function __construct (int $user_id, $module_id = 0) {
        $this->user = refresh_user($user_id , true);
        if (!$this->user) {
            Throw new BraException('错误的用户!');
        }
        $this->pay_logs_model = D('pay_logs');
        $this->module_id = $module_id;
        if (self::$coin_types === null) {
            self::$coin_types = self::coin_types();
        }
    }

    public function set_admin ($admin_uid) {
        $this->admin_uid = $admin_uid;
    }

    public static function coin_types () {
        if (self::$coin_types === null) {
            self::$coin_types = config('bra_coin');
        }

        return self::$coin_types;
    }

    public static function get_draw_coin () {
        $coins = self::coin_types();
        $wallet_config = BraArray::get_config('wallet_config');

        return $coins[$wallet_config['draw_coin']];
    }

    public static function get_coin_type ($field) {
        $coins = self::coin_types();
        foreach ($coins as $unit_type => $coin) {
            if (is_numeric($field)) {
                if ($coin['id'] == $field) {
                    return $coin;
                    break;
                }
            } else {
                if ($coin['field'] == $field) {
                    return $coin;
                    break;
                }
            }
        }
        return false;
    }

    /**
     * 0 consume 1 draw 5 transfer_spend
     * @param $amount
     * @param $coin_type_or_id
     * @param $unit_type
     * @param $pay_type
     * @param $note
     * @param array $extra
     * @return mixed
     */
    public function ex_spend ($amount, $coin_type_or_id, $pay_type, $note, $extra = []) {
        $coin = self::get_coin_type($coin_type_or_id);
        if(empty($coin)){
            return bra_res(500 , "未知币种 ", "");
        }
        $coin_field = $coin['field'];
        $unit_type = $coin['id'];
        if (is_numeric($amount) && $amount > 0) {
            $extra['unit_type'] = $unit_type;
            $extra['operate'] = 1;
            if ($this->user[$coin_field] >= $amount) {
                $res = $this->user->decrement($coin_field, $amount);
                if ($res) {
                    $log_id = $this->wallet_ex_log(-$amount, $coin_field, $pay_type, $note, $extra);
                    if ($log_id) {
                        return bra_res(1, '消费余额操作成功!', '', $log_id);
                    } else {
                        return bra_res(500, '操作失败,日志无法写入!!');
                    }
                }
            }

            return bra_res(6, $coin['title'] . '余额不足!', $coin_field, $coin);
        } else {
            return bra_res(500, '错误的金额!' , '' , $amount);
        }
    }

    public function wallet_ex_log ($amount, $coin_type, $pay_type, $note, $extra = []) {
        global $_W;
        $insert = [
            "balance" => $this->user[$coin_type],
            "user_id" => $this->user['id'],
            "total_fee" => $amount,
            "pay_type" => $pay_type,
            "note" => $note,
            "create_at" => date("Y-m-d H:i:s"),
            "site_id" => $_W['site']['id'],
            'module_id' => $this->module_id
        ];
        if (!empty($extra)) {
            $insert = array_merge($insert, $extra);
        }
        if ($this->admin_uid) {
            $insert['admin_uid'] = $this->admin_uid;
        }
        $log_id = $this->pay_logs_model->insertGetId($insert);

        return $log_id;
    }

    /**
     * @param $amount
     * @param $coin_type_or_id
     * @param $unit_type
     * @param $pay_type
     * @param $note
     * @param array $extra
     * @return mixed
     */
    public function ex_deposit ($amount, $coin_type_or_id, $pay_type, $note, $extra = []) {
        $coin = self::get_coin_type($coin_type_or_id);
        if(empty($coin)){
            return bra_res(500 , "未知币种 ", "");
        }
        $coin_field = $coin['field'];
        $unit_type = $coin['id'];
        if (is_numeric($amount) && $amount > 0) {
            $extra['unit_type'] = $unit_type;
            $extra['operate'] = 2;
            $res = $this->user->increment($coin_field, $amount);
            if ($res) {
                $log_id = $this->wallet_ex_log($amount, $coin_field, $pay_type, $note, $extra);
                if ($log_id) {
                    return bra_res(1, '存款操作操作成功!' . $note, '', $log_id);
                } else {
                    return bra_res(500, '操作失败,日志无法写入!!');
                }
            } else {
                return bra_res(500, '操作失败 , 用户数据写入失败!');
            }
        } else {
            return bra_res(500, '错误的金额!');
        }
    }

    /**
     * @param string $from_coin
     * @param string $to_coin
     * @param $from_amount
     * @param $note
     * @param $exc_fee
     * @param $exc_fee_mode
     * @param $exc_fee_max
     * @param array $extra
     * @param float $exchange_rate
     * @return mixed
     */
    public function exchange (string $from_coin, string $to_coin, $from_amount, $note, $exc_fee, $exc_fee_mode, $exc_fee_max, $extra = [], float $exchange_rate = 1, float $discount = 1) {
        $from_data = explode("|", $from_coin);
        $to_data = explode("|", $to_coin);
        $spend_amount = 0;
        switch ($exc_fee_mode) {
            case 'rate':
                $fee = $from_amount * $exc_fee / 100;
                if ($exc_fee_max) {
                    $fee = min($exc_fee_max, $fee);
                }
                $spend_amount = $from_amount + $fee;
                break;
            case 'fixed':
                $spend_amount = $from_amount + $exc_fee;
                break;
            case 'free':
                $spend_amount = $from_amount;
                break;
            default :
                return bra_res(500, "手续费模式未知!! ", "");
        }
        $to_amount = $exchange_rate * $from_amount;
        // spend
        $spend_res = $this->ex_spend($spend_amount * $discount, $from_data[0], 7, $note, $extra);
        if (is_error($spend_res)) {

            $spend_res['total'] = $to_amount;
            $spend_res['spend_amount'] = $spend_amount;
            return $spend_res;
        }

        return $this->ex_deposit($to_amount, $to_data[0], 18, $note, $extra);
    }


    public function change_pass ($pass ,array $extra = []) {
        if($pass){
			if(strlen($pass) < 6){
				return bra_res(500 ,'密码必须为6位或者以上');
			}
			$extra['pass'] =  md5($pass);
        }
        $test = D('users_wallet')->where('id' , '=' , $this->user['id'])->bra_one();
        if($test){
            $res=D('users_wallet')->where('id' , '=' , $this->user['id'])->update($extra);
            if($res){
                return bra_res(1 ,'设置成功!');
            }else{
                return bra_res(1 ,'设置失败!');
            }
        }else{
			$extra['id'] = $this->user['id'];
            if(D('users_wallet')->insert($extra)){
                return bra_res(1 ,'设置成功！！');
            }else{
                return bra_res(1 ,'设置失败！！');
            }
        }
    }

}
