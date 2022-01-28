<?php
// +----------------------------------------------------------------------
// | BraCMS [ 布拉CMS ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2017 http://www.bra.ac All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( 您必须获取授权才能进行使用 )
// +----------------------------------------------------------------------
// | Author: 鸣鹤 <1620298436@qq.com>
// +----------------------------------------------------------------------
namespace Bra\core\utils;

use Bra\core\utils\pay\factory_pay;

class BraPay {

    /**
     * @var $pay_way factory_pay
     */
    public $pay_way;

    public $gate_way_name;

    public function __construct ($gate_way_name, $m_id, $m_mid) {
        $this->gate_way_name = $gate_way_name;
        $class_name = self::get_name($gate_way_name);
        if (class_exists($class_name)) {
            $this->pay_way = new $class_name($m_id, $m_mid);
        }
    }

    public static function get_name ($gate_way) {
        return "\\Bra\\core\\utils\\pay\\" . $gate_way;
    }

    public function set ($pay_way) {
        $this->pay_way = $pay_way;
    }

    public function refund ($amount, $note) {
    }
}
