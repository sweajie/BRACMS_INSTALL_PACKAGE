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
namespace Bra\core\utils;

use App\Models\User;
use Bra\core\objects\BraArray;
use Bra\core\objects\BraExtWallet;
use Bra\core\objects\BraNotice;
use Bra\core\objects\BraString;

class BraDis {
    static $distribute_config = null;
    public static function make_down_line(User $user, $parent_id , $protect_uid = 0) {

        $distribute_config = self::get_dis_config();

        if($user['parent_id'] && !$distribute_config['re_refer']){
            return bra_res(500, '已经有上线!');
        }

        if ($user['id'] == $parent_id) {
            return bra_res(500, '不能成为自己的下线!');
        }

        $parent = User::find($parent_id);
        if (!$parent) {
            return bra_res(500, '上级被删除了!');
        }

        if (!BraDis::is_distributor($parent_id)) { // 转发的 转发
            if (isset($distribute_config['protect_parent'])) {
                $p_parent = BraDis::is_distributor($parent['parent_id']);
                if (isset($distribute_config['protect_parent']) && $p_parent) {
                    return self::make_down_line($user, $p_parent['id']);
                } else {
                    return bra_res(500, '上级分发未开启保护!');
                }
            } else {
                return bra_res(500, '上级不是分销员!');
            }
        } else {
            $user->parent_id = $parent_id;
            $res = $user->save();
            if ($res) {
                $params = [];
                $params['user'] = $user;
                $bn = new BraNotice(5, $params);
                $bn->send($parent_id);
                $rec_point = (int)$distribute_config['point_new'];
                if ($rec_point && $distribute_config['unit_type']) {
                    $wallet = new BraExtWallet($parent_id);
                    if ($distribute_config['unit_type'] == 2) {
                        return $wallet->ex_deposit($rec_point, 'balance', 8, '推荐奖励');
                    }
                    if ($distribute_config['unit_type'] == 1) {
                        return $wallet->ex_deposit($rec_point, 'point', 8, '推荐奖励');
                    }
                }

                return bra_res(1, '没有设置奖励!');
            } else {
                return bra_res(500, '上级被删除了!');
            }
        }
    }
    public static function get_dis_config() {
        return self::$distribute_config = self::$distribute_config ?? BraArray::get_config('bra_dis');
    }


    public static function make_global_web_share($path, $module_id) {
        global $_W;
        if ($_W['user']['dis_status'] == 99) {
            $share_data['client'] = $_W['bra_client'];
            $share_data['item_id'] = 0;
            $share_data['model_id'] = 0;
            $share_data['module_id'] = $module_id;
            $share_data['type'] = 1;
            $share_data['views'] = 0;
            $share_data['annex_id'] = 0;
            $share_data['user_id'] = $_W['user']['id'];
            $xt['path'] = $path;
            $share_res = D('share')->bra_foc($share_data, $xt, true);
            $share = $share_res['data'];

            return bra_res(1, '', '', $share);
        } else {
            return abort(403, '无分销权限');
        }
    }

    public static function make_bra_node_share($item_id, $model_id, $path, $module_id) {
        global $_W;
        if ($_W['user']['dis_status'] == 99) {
            $share_data['client'] = $_W['bra_client'];
            $share_data['item_id'] = $item_id;
            $share_data['model_id'] = $model_id;
            $share_data['module_id'] = $module_id;
            $share_data['type'] = 2;
            $share_data['views'] = 0;
            $xt['path'] = $path;
            $share_res = D('share')->bra_foc($share_data, $xt);
            $share = $share_res['data'];

            return bra_res(1, '', '', $share);
        } else {
            return bra_res(500, '无分销权限');
        }
    }

    /**
     * 分销提成 $uid 是触发人 , 奖励他的上级  自身不奖励
     * $dis_level 奖励分销的层次 1级分销填写2
     *
     * $left_to_uid 剩余的转给 目标用户
     * @param $uid
     * @param $total
     * @param $item_id
     * @param $model_id
     * @param $dis_level
     * @param int $left_to_uid
     * @param string $to_uid_note
     * @param int $module_id
     * @return mixed
     */
    public static function dis_reward_user($uid, $total, $item_id, $model_id, $dis_level, $left_to_uid = 0, $to_uid_note = '收入', $module_id = 5) {
        global $_W, $_GPC;
        $self_user = refresh_user($uid);
        if (!$self_user) {
            return bra_res(5001, "奖励发放错误");
        }
        $total_down_level_amount = 0;
        BraDis::group_buddy($uid, $buddies, $dis_level);
        $i = 0;
        foreach ($buddies as $buddy) {
            if ($buddy == $uid) {
            } else {
                $user = D('users')->bra_one($buddy);
                $i++;
                if ($user) {
                    $_dis_user_level = D('dis_level')->bra_one($user['dis_level_id']);
                    $rate = $_dis_user_level['commission_' . $i];
                    if (!$rate) {
                        $rate = $_dis_user_level['commission_3'];
                    }
                    $commission = $total * $rate / 100;
                    $total_down_level_amount += $commission;
                    if ($commission > 0) {
                        $note = $i . '分销提成,编号：#' . $item_id;
                        $res = BraDis::t__dis_com_reward($buddy, $commission, $i, $module_id, $note, $item_id, $model_id);
                        if (is_error($res)) {
                            return bra_res(5002, "奖励发放错误", '', $res);
                        } else {
                            $params = [];
                            $params['user'] = $user;
                            $params['commission'] = $commission;
                            $params['time'] = date('Y-m-d H:i:s');
//                            $bn = new BraNotice(4, $params);
//                            $bn->send($user['id']);
                            // todo add msg queue
                        }
                    }
                }
            }
        }
        $left_reword = number_format($total - $total_down_level_amount, 2);
        $data['total'] = $total_down_level_amount;
        $data['left'] = $left_reword;
        if ($left_reword < 0) {
            return bra_res(50040, "奖励发放错误,下线的奖励已经超过总佣金!");
        } else {
            if ($left_to_uid) {
                $wallet = new BraExtWallet($left_to_uid);
                $self_res = $wallet->ex_deposit($left_reword, 'balance', 6, $to_uid_note);
                if (is_error($self_res)) {
                    return bra_res(5001, "奖励发放错误");
                } else {
                    return bra_res(1, "发放奖励成功", '', $self_res);
                }
            } else {
                return bra_res(1, "发放奖励成功");
            }
        }
    }


    public static function dis_reward_user_total($uid, $total, $item_id, $model_id, $dis_level, $left_to_uid = 0, $to_uid_note = '收入', $module_id = 5) {
        $rest = [];
        $self_user = refresh_user($uid);
        if (!$self_user) {
            return bra_res(5001, "奖励发放错误");
        }
        $total_down_level_amount = 0;
        BraDis::group_buddy($uid, $buddies, $dis_level);

        $buddies = array_reverse($buddies);
        $i = count($buddies);
        foreach ($buddies as $buddy) {
            if ($buddy == $uid) {
            } else {
                $user = D('users')->bra_one($buddy);
                $i--;
                if ($user) {
                    $_dis_user_level = D('dis_level')->bra_one($user['dis_level_id']);
                    $rate = $_dis_user_level['commission_' . $i];
                    if (!$rate) {
                        $rate = $_dis_user_level['commission_3'];
                    }
                    $commission = $total * $rate / 100;

                    if ($commission > 0) {
                        $note = $i . '分销提成,编号：#' . $item_id;
                        $rest[] = $res = BraDis::t__dis_com_reward($buddy, $commission - $total_down_level_amount, $i, $module_id, $note, $item_id, $model_id);
                        if (is_error($res)) {
                            return bra_res(5002, "奖励发放错误", '', $res);
                        } else {
                            $total_down_level_amount += $commission - $total_down_level_amount;
                            $params = [];
                            $params['user'] = $user;
                            $params['header'] = "佣金到账";
                            $params['footer'] = "感谢您的支持";
                            $params['commission'] = $commission;
                            $params['time'] = date('Y-m-d H:i:s');
                            $bn = new BraNotice(4, $params);
                            $bn->send($user['id']);
                        }
                    }
                }
            }
        }
        $left_reword = number_format($total - $total_down_level_amount, 2);
        $data['total'] = $total_down_level_amount;
        $data['left'] = $left_reword;
        if ($left_reword < 0) {
            return bra_res(50040, "奖励发放错误,下线的奖励已经超过总佣金!");
        } else {
            if ($left_to_uid) {
                $wallet = new BraExtWallet($left_to_uid);
                $self_res = $wallet->ex_deposit($left_reword, 'balance', 6, $to_uid_note);
                if (is_error($self_res)) {
                    return bra_res(5001, "奖励发放错误");
                } else {
                    return bra_res(1, "发放奖励成功", '', $self_res);
                }
            } else {
                return bra_res(1, "发放奖励成功" , '' , $rest);
            }
        }
    }
    /**
     * 上下级路径
     * @param $user_id
     * @param array $uids
     * @param int $l
     * @return array
     */
    public static function group_buddy($user_id, &$uids = [], $l = 0) {
        static $i;
        $base = D('users');
        $user = $base->bra_one($user_id);
        if ($user) {
            $uids[$user_id] = $user_id;
            if ($l == 0 || count($uids) <= $l) {
                if($user['parent_id']){
                    $parent_user = $base->bra_one($user['parent_id']);
                    if ($parent_user) {
                        if(!$uids[$parent_user['id']]){
                            $uids[$parent_user['id']] = $parent_user['id'];
                            return self::group_buddy($parent_user['id'], $uids, $l);
                        }
                    }
                }
            }
        }

        return $uids;
    }

    /**
     * 分销佣金奖励
     * @param $user_id
     * @param $commission
     * @param $i
     * @param int $module_id
     * @param string $note
     * @param int $item_id
     * @param int $mid
     * @param int $status
     * @return mixed
     */
    public static function t__dis_com_reward($user_id, $commission, $i, int $module_id, $note = '分销佣金', $item_id = 0, $mid = 0, $coin_id = 1 , $status = 99) {
        global $_W;
        $dis_order_data = [];
        $dis_order_data['user_id'] = $user_id;
        $dis_order_data['site_id'] = $_W['site']['id'];
        $dis_order_data['amount'] = $commission;
        $dis_order_data['create_at'] = date("Y-m-d H:i:s", time());
        $dis_order_data['level'] = $i;
        $dis_order_data['module_id'] = $module_id;
        $dis_order_data['item_id'] = $item_id;
        $dis_order_data['model_id'] = $mid;
        $dis_order_data['note'] = $note;
        $dis_order_data['pay_time'] = date("Y-m-d H:i:s", time());
        $dis_order_data['status'] = $status;
        $dis_order_data['unit_type'] = $coin_id;
        $os = D("dis_order")->insert($dis_order_data);
        if ($os) {
            if ($dis_order_data['status'] == 99) {
                $wallet = new BraExtWallet($user_id);
                $wallet_res =  $wallet->ex_deposit($commission, $coin_id, 4, $note);
                $wallet_res['commission'] = $commission;
                return $wallet_res;
            } else {
                return bra_res(1, '延迟发放!');
            }
        } else {
            return bra_res(500, '系统错误', '', $os);
        }
    }

    /**
     * 判断用户是否为分销权限
     */
    public static function is_distributor($user_id) {
        if(self::$distribute_config['mode'] == 0){
            return  true;
        }else{
            $user = refresh_user($user_id);

            return $user['dis_status'] == 99;
        }

    }

    /**
     * 初始化分销
     */
    public static function init_dis() {
        global $_W;
        $_W['bra_dis'] = BraArray::get_config('bra_dis');
        if (!isset($_W['bra_dis']['status']) || $_W['bra_dis']['status'] != 1) {
            BraLog::write('dis closed', $_W['bra_dis'], 'info');
        } else {
            self::get_refer();
            if (!empty($_W['bra_dis']) && $_W['bra_dis']['mode'] == "0") {
                $res = self::apply($_W['user']['id']);
                BraLog::write('app_dis_res', $res, 'info');
            }
        }
    }

    /**
     * 获取介绍人
     */
    public static function get_refer() {
        global $_W, $_GPC;
        $_W['refer'] = 0;
        $refer_hard = $refer_law = 0;
        //hard refer
        if (isset($_GPC['refer']) && is_numeric($_GPC['refer'])) {
            $refer_hard = (int)$_GPC['refer'];
        }
        $refer_soft = (int)Cookie::get("refer");
        //hard first  soft later
        $ext_refer = $refer_hard ? $refer_hard : $refer_soft;
        if ($_W['user']) {
            /**
             * @var Users $user
             */
            $user = $_W['user'];
            $refer_law = $_W['user']['parent_id'];
            if ($refer_law) {
                $_W['refer'] = $refer_law;
            } else {
                //no parent
                if ($ext_refer && !empty($_W['bra_dis']['re_refer'])) {
                    $_W['refer'] = $ext_refer;
                    Cookie::set("refer", $ext_refer);
                    $user->make_down_line($_W['refer']);
                } else {
                    $_W['refer'] = 0;
                }
            }
        } else {
            if ($ext_refer) {
                $_W['refer'] = $ext_refer;
                Cookie::set("refer", $ext_refer);
            }
        }
    }

    /**
     * 申请分销权限
     * @param $user_id
     * @param string $mobile
     * @param string $real_name
     * @param string $reason
     * @param string $level_id
     * @return array|mixed
     */
    public static function apply($user_id, $mobile = '', $real_name = '', $reason = '', $level_id = '') {
        global $_W;

        if (empty($user_id)) {
            return bra_res(500, '申请用户编号不合法!');
        }
        $user = User::find($user_id);
        if (empty($user)) {
            return bra_res(500, '申请用户编号不合法!');
        }
        $bra_dis_config = $_W['bra_dis'] ? $_W['bra_dis'] : BraArray::get_config('bra_dis');
        if (!isset($bra_dis_config['status']) || $bra_dis_config['status'] != 1) {
            return bra_res(600, '未开启分销功能 , 无法发起申请!');
        }
        if (empty($mobile) && $user['is_mobile_verify'] == 1) {
            $mobile = $user['mobile'];
        }
        if ($user['dis_status'] == 1) {
            return bra_res(600, '您的申请已经提交 , 请等待管理员审核!!');
        }
        if ($user['dis_status'] == 99) {
            return bra_res(1, '您已经是分销商!!');
        }
        $idata = [];
        // load default level
        if (!$level_id) {
            $bra_dis_level_model = D('dis_level');
            $default_level = $bra_dis_level_model->bra_where(['default' => 1])->bra_one();
            if (!$default_level) {
                return bra_res(600, '管理员还没有设置默认的分销商等级!!');
            } else {
                $idata['target_level_id'] = $default_level['id'];
            }
        } else {
            $bra_dis_level_model = D('dis_level');
            $default_level = $bra_dis_level_model->with_site()->bra_one($level_id);
            if (!$default_level) {
                return bra_res(600, '管理员还没有设置默认的分销商等级!!');
            } else {
                $idata['target_level_id'] = $default_level['id'];
            }
        }
        /* apply data */
        $idata['mobile'] = $mobile;
        $idata['real_name'] = $real_name;
        $idata['reason'] = $reason;
        switch ($bra_dis_config['mode']) {
            case 0:  //auto
                $idata['status'] = $user->dis_status = 99;
                $user->dis_level_id = $idata['target_level_id'];
                $idata['approve_at'] = $user->dis_approve_at = date("Y-m-d H:i:s");
                $apply_res = D('dis_apply')->item_add($idata);
                break;
            case 1:  //apply
                if (!BraString::is_phone($mobile)) {
                    return bra_res(500, '手机号码不合法!!');
                }
                // add to dis apply
                $idata['status'] = $user->dis_status = 99;
                $user->dis_level_id = $idata['target_level_id'];
                $idata['approve_at'] = $user->dis_approve_at = date("Y-m-d H:i:s");
                $apply_res = D('dis_apply')->item_add($idata);
                break;
            case 2: //check
                if (!BraString::is_phone($mobile)) {
                    return bra_res(500, '手机号码不合法!!');
                }
                $idata['status'] = $user->dis_status = 1;
                $apply_res = D('dis_apply')->item_add($idata);
                break;
        }
        if (!isset($apply_res)) {
            return bra_res(500, '申请失败 , 模式未设置', '', $apply_res);
        }
        if (is_error($apply_res)) {
            return bra_res(500, '申请失败 , 资料验证未通过', $idata, $apply_res);
        }
        $res = $user->save();
        if (!$res) {
            return bra_res(500, '申请失败!!');
        } else {
            if ($user->dis_status == 99) {
                return bra_res(1, '申请成功!!');
            } elseif ($user->dis_status == 1) {
                return bra_res(1, '申请成功,请等待管理员审核!!');
            } else {
                return bra_res(500, '系统错误!!', $user->dis_status, $bra_dis_config);
            }
        }
    }

    public static function pass_check(User $user, $level_id = 0) {
        $bra_dis_level_model = D('dis_level');
        if (!$level_id) {
            $default_level = $bra_dis_level_model->bra_one(['default' => 1]);
        } else {
            $default_level = $bra_dis_level_model->bra_one($level_id);
        }
        if (!$default_level) {
            return bra_res(600, '管理员还没有设置默认的分销商等级!!');
        } else {
            $user->dis_level_id = $default_level['id'];
            $user->dis_status = 99;
            $res = $user->save();
            if ($res) {
                D('users')->clear_item($user['id']);

                return bra_res(1, '成功!!');
            } else {
                return bra_res(500, '系统错误!!', '', $res);
            }
        }
    }

    public static function remove_check(User $user) {
        $user->dis_level_id = 0;
        $user->dis_status = 0;
        $res = $user->save();
        if ($res) {
            //delete apply
            D('dis_apply')->with_user($user['id'])->delete();
            return bra_res(1, '成功!!');
        } else {
            return bra_res(500, '系统错误!!', '', $res);
        }
    }

    public function bra_user_my_team($query) {
        global $_W;
        $user_m = D('users');
        if (is_bra_post()) {
            $query['__simple'] = true;
            $query['bra_int_fields'] = ['parent_id'];
            $ret = [];
            $parent_id = $_W['user']['id'] == 0 ? -1 : $_W['user']['id'];
            if ($query['level'] == 1) {
                $ret = D('users')->bra_where(['parent_id' => $parent_id])->paginate([
                    'page' => $query['page']
                ], false)->toArray();
            }
            if ($query['level'] == 2) {
                $ret = D('users')->_m->where('parent_id', 'IN', function ($query) use ($parent_id) {
                    $query->name('users')->where(['parent_id' => $parent_id])->field('id');
                })->paginate([
                    'page' => $query['page']
                ], false)->toArray();
            }
            foreach ($ret['data'] as &$item) {
                if ($item['parent_id']) {
                    $parent = D('users')->get_item($item['parent_id']);
                }
                // get yxm
                $item = D('users')->get_item($item['id']);
                //amount_child
                if ($query['level'] == 1) {
                    $item['amount_child'] = D('users')->bra_where(['parent_id' => $item['id']])->count();
                }
                $item['mobile'] = BraString::encode_char(3, 4, $item['mobile']);
                $item['user_name'] = BraString::encode_char(3, 4, $item['user_name']);
                $item['parent_id'] = BraString::encode_char(3, 4, $item['parent_id']);
            }
            $this->page_data = $ret;
        } else {
            $llave = "get_bra_user_my_team" . $_W['user']['id'];
            $data = BraCache::get($llave);
            if (!$data) {
                $parent_id = $_W['user']['id'] == 0 ? -1 : $_W['user']['id'];
                //count level1
                $data = [];
                $data['level_1count'] = D('users')->bra_where(['parent_id' => $parent_id])->count();
                $data['level_2count'] = D('users')->_m->where('parent_id', 'IN', function ($query) use ($parent_id) {
                    $query->name('users')->where(['parent_id' => $parent_id])->field('id');
                })->count();
                BraCache::set_cache($llave, $data);
            }
            $this->page_data = $data;
        }
    }
}
