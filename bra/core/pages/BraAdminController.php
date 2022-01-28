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
namespace Bra\core\pages;

use Bra\core\models\UserRoles;
use Bra\core\objects\BraArray;
use Bra\core\objects\BraMenu;
use Bra\core\objects\BraModule;
use Bra\core\objects\BraPage;
use Bra\core\objects\BraString;
use Bra\core\utils\BraAdminItemPage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use phpDocumentor\Reflection\Types\This;

class BraAdminController extends BraController {

    use BraAdminItemPage;
    public function __construct (BraPage $bra_page) {
        define('BRA_ADMIN' , true);
        parent::__construct($bra_page);
        if(!self::init_admin()){

           $this->page_data = bra_res(500 , '您的登录已过期!');
        }
        self::check_admin_security(); // init security
    }

    public static function init_admin () {
        global $_W;
        if(!$_W['user']){
            return false;
        }
        if ($_W['user']['id'] == 1) {
            $_W['super_power'] = 1;
        } else {
            $_W['super_power'] = 0;
        }
        if (!$_W['super_power']) {
            $donde = ['auth_type' => 1];
            $users_admin = D('users_admin')->with_site()->bra_where($donde)->get_user_data();
            if (!$users_admin || $_W['site']['id'] != $users_admin['site_id']) {
                abort(403, "users_admin Error");
            } else {
                $assign['users_admin'] = $_W['users_admin'] = $users_admin;
                $_W['admin_role'] = $assign['admin_role'] =   UserRoles::where(['id' => $users_admin['role_id']])->first();
            }

            if ($_W['user']['status'] != 99 || $assign['admin_role']['status'] != 99) {
                abort(403, "this door is not open for you , please wait or call the administrator！");
            }

        } else {
            $_W['admin_role'] = UserRoles::find(1)->toArray();
        }
        return $_W['admin'] = $_W['user'];

    }

    public static function check_admin_security () {
        global $_W;
        $req = app()->request;
        $ip = $req->ip();
        if(empty($_W['site']['config'])){
            return ;
        }
        if ($_W['site']['config']['white_ip']) {
            $ips = explode(',', $_W['site']['config']['white_ip']);
            if (!in_array($ip, $ips)) {
                end_resp(bra_res(500, 'sorry,It\'s an Bra Error,that\'s all we know!Code:' . 501, [], 'xml'));
            }
        }
        if ($_W['site']['config']['bad_ip']) {
            $ips = explode(',', $_W['site']['config']['bad_ip']);
            if (in_array($ip, $ips)) {
                end_resp(bra_res(500, 'sorry,It\'s an Bra Error,that\'s all we know!Code:' . 502, [], 'xml'));
            }
        }
        if ($_W['super_power']) {
            $safe = config('bra_safe');
            $ips = explode(',', $safe['super_admin_ip']);
            if ($safe['super_admin_ip'] && !in_array($ip, $ips)) {
                end_resp(bra_res(500, 'sorry,It\'s an Bra Error,that\'s all we know!Code:' . 504, [], 'xml'));
            }
        }

		if (isset($_W['site']['config']['limit_admin_domain'])) {
			$safe = config('bra_safe');
			$host = request()->getHttpHost();
			if (!empty($safe['limit_admin_domain']) && $host != $safe['limit_admin_domain'])  {
				end_resp(bra_res(500, 'sorry,It\'s an Bra Error,that\'s all we know!Code:'.$safe['limit_admin_domain'] . 5033, [], 'xml'));
			}
		}
    }

}
