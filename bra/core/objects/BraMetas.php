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

use Bra\wechat\medias\WechatMedia;

/**
 * 用于获取推送对象的信息
 * Class BraMetas
 * @package app\bra\objects
 */
class BraMetas {
    public $user_id;
    public $openid_wx;
    public $openid_wx_mini;
    public $app_cid;
    public $mobile;

    public function __construct(int $user_id) {
        $this->user_id = $user_id;
        if (empty($user_id)) {
            abort(500 ,"try to get metas of empty");
        }
        $this->get_metas();
    }

    public function get_metas() {
        global $_W;
        //公众号
        if (module_exist("wechat")) {
            $media = WechatMedia::get_media_official();
            $_W['WX'] = $media->app;
            $donde['user_id'] = $this->user_id;
            $donde['media_id'] = $media->media['id'];
            $fans = D("wechat_fans")->bra_one($donde);
            if ($fans) {
                $this->openid_wx = $fans['openid'];
            }
        }

        //wx mini app
        if (module_exist("wechat")) {
            $donde['user_id'] = $this->user_id;
            $donde['media_id'] = $_W['mini_app']['id'];
            $fans = D("wechat_fans")->bra_one($donde);
            if ($fans) {
                $this->openid_wx_mini = $fans['openid'];
            }
        }

        //sms
        $user = D('users')->bra_one($this->user_id);
        if (BraString::is_phone($user['mobile'])) {
            $this->mobile = $user['mobile'];
        }elseif (BraString::is_phone($user['user_name'])){
            $this->mobile = $user['user_name'];
        }

        //bra app
        if (module_exist('bra_app')) {
            $donde = [];
            $donde['user_id'] = $this->user_id;
            $fans = D('app_fans')->bra_one($donde);
            if ($fans) {
                $this->app_cid = $fans['cid'];
            }
        }
    }
}
