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

use App\Models\User;
use Illuminate\Support\Facades\Log;

class BraUsersLevelLog {
    public $log;

    public function __construct($log_id) {
        $this->log = D('users_level_log')->bra_one($log_id);
    }

    public function onPay() {
        if ($this->log['status'] == 1) {
            $update['status'] = 99;
            D('users_level_log')->item_edit($update, $this->log['id']);
            $user = refresh_user($this->log['user_id']);
            $u = $this->upgrade_level($user , $this->log['level_id'], $this->log['days']);

            if ($u) {
                return bra_res(1, '升级');
            } else {
                return bra_res(500, '升级失败啦!');
            }
        } else {
            return bra_res(500, '已经升级过啦!');
        }
    }


	public function upgrade_level (User $user , $level_id, $days) {
		if ($this->is_level_expire($user['level_expire'])) {
			$level_expire = time() + $days * 86400;
		} else {
			$level_expire = strtotime($user->level_expire) + $days * 86400;
		}

		$user->level_expire = date("Y-m-d H:i:s", $level_expire);
		$user->level_id = $level_id;

		return $user->save();
	}

	/**
	 * 会员过期检测
	 * @return bool
	 */
	public static function is_level_expire ($level_expire) {
		$timer = strtotime($level_expire);
		if ($timer < time()) {
			return true;
		} else {
			return false;
		}
	}
}
