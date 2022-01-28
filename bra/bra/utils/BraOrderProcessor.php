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
namespace Bra\bra\utils;


use Bra\core\objects\BraExtWallet;
use Bra\core\utils\processor\OrderProcessor;

class BraOrderProcessor extends  OrderProcessor {
    function pay () {
        if ($this->order['act'] == 'pay_user_level') {
            return $this->pay_user_level();
        }
    }

    function pay_user_level () {
        $wallet = new BraExtWallet($this->order['pay_user_id']);
        $s_res = $wallet->ex_spend($this->order['total_money'], 'balance' ,0, '升级会员' . $this->order['id']);// spend
        if (is_error($s_res)) {
            return $s_res;
        } else {
            return (new BraUsersLevelLog($this->order['item_id']))->onPay();
        }
    }
}

