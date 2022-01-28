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

class BraOrder {
    public $order;
    /**
     * status
     * 1|未支付
     * 2|待处理
     * 6|订单过期
     * 97|已退款
     * 98|已取消
     * 99|已支付
     */
    private $order_id;
    private $order_m;

    public function __construct ($order_id) {
        $this->order_id = $order_id;
        $this->order_m = D('orders');
        $this->order = (array) $this->order_m->find($this->order_id);
    }

    /**
     * @param int $uid
     * @param $amount
     * @param int $module_id
     * @param string $note
     * @param array $ext_data
     * $extra = [ 'act' => '回掉通知的行为', 'item_id' => '相关节点', 'model_id' => '模型编号', 'total_money' => '实际需要手续的总金额'
     * ];
     *
     * @param int $is_online
     * @return mixed
     */
    public static function create_order (int $uid, $amount, int $module_id, string $note, $ext_data = [], $is_online = 1) {
        global $_W;
        $data = [];
        $data['note'] = $note;
        $data['amount'] = $amount;
        if (!$data['note']) {
            return bra_res(501, '对不起,请填写订单备注');
        }
        if (!is_numeric($data['amount']) || $data['amount'] < 0) {
            return bra_res(500, '对不起,订单金额错误:' . $amount);
        }
        //免费订单
        if ($data['amount'] == 0) {
            $status = 99;
        } else {
            $status = 1;
        }
        $order_insert = $ext_data;
        $order_insert['id'] = BraString::create_sn();
        $order_insert['out_trade_no'] = $order_insert['id'] . "|" . time();
        $order_insert['pay_user_id'] = 0;
        $order_insert['user_id'] = $uid;
        $order_insert['gateway'] = $order_insert['gateway'] ?? '';
        $order_insert['total_fee'] = $data['amount'];
        $order_insert['create_at'] = date("Y-m-d H:i:s");
        $order_insert['create_ip'] = app()->request->ip();
        $order_insert['note'] = $data['note'];
        $order_insert['site_id'] = $_W['site']['id'];
        $order_insert['module_id'] = $module_id;
        $order_insert['is_online'] = $is_online;
        $order_insert['status'] = $status;
        $order_res = D('orders')->item_add($order_insert);
        if (!is_error($order_res)) {
            $order_insert = $order_res['data'];
            $order_insert['id'] .= '';

            return bra_res(1, '订单创建成功', '', $order_insert);
        } else {
            return bra_res(50001, '对不起,订单创建失败', '', $order_res);
        }
    }

    public function get_pay_gateway () {
        $name_space = "\\app\\bra\\utils\\pay\\" . $this->order['gateway'];
    }

    public function get_order ($si_render = false) {
        if ($si_render) {
            return $this->order_m->get_item($this->order_id);
        } else {
            return $this->order_m->find($this->order_id);
        }
    }

    /**
     * 订单过期
     */
    public function order_expire () {
        $update = [];
        $update['status'] = 6;

        return $this->order_m->update($update, $this->order['id']);
    }

    /**
     * 订单支付
     */
    public function order_pay () {

    }

    public function order_refund ($note, $amount = null) {
        //status check
        if ($this->order['status'] != 99) {
            return bra_res(500 , "该订单不是已支付状态! ", "");
        }
        $gate_way_name = "\\Bra\\core\\utils\\pay\\" . $this->order['gateway'];//get pay gateway
        if (class_exists($gate_way_name)) {
            $online_amount = $this->order['total_fee'];
            $offline_amount = $this->order['total_money'] - $online_amount;
        } else {
            $online_amount = 0;
            $offline_amount = $this->order['total_money'];
        }
        if ($online_amount > 0) {
            $gateway = new $gate_way_name($this->order['media_id'], $this->order['media_mid']);
            $res = $gateway->refund($this->order, $this->order['total_fee']);
            if (is_error($res)) {
                return $res;
            }
        }
        //  update sys  order
        $update['status'] = 97;
        $res = $this->order_m->item_edit($update, $this->order_id);
        if (!is_error($res) && $this->order['status'] == 99) {
            if ($offline_amount > 0) {
                $user_wallet = new BraExtWallet($this->order['pay_user_id']);

                return $user_wallet->ex_deposit($amount, 'balance' , 10, $note);
            } else {
                return bra_res(1, '退款成功 ', '', ['全款在线']);
            }
        } else {
            return $res;
        }
    }
}
