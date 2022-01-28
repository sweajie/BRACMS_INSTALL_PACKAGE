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
use think\facade\Log;

interface  interface_pay {

	public function get_pay_params($user_id, $order_id, $config = []);

	public function refund ($sys_order , $amount);
}
