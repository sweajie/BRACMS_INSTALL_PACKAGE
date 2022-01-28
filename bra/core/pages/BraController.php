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

use App\Http\Controllers\Controller;
use Bra\core\objects\BraArray;
use Bra\core\objects\BraPage;

class BraController extends Controller {

    public $page_data = [];

    public $bra_page;

    public function __construct (BraPage $bra_page) {
		global $_W , $_GPC;

        $this->bra_page = $bra_page;
    }

    public function map_auth ($map_old = [], $uid_field = 'user_id') {
        if (defined('BRA_ADMIN')) {
            return $this->map_admin_auth($map_old, $uid_field);
        } else {
            return $this->map_user_auth($map_old, $uid_field);
        }
    }

    private function map_admin_auth ($map_old = [], $uid_field = 'user_id') {
        global $_W;
        $map = [];
        if (!$_W['super_power']) {
            if (!$_W['users_admin'] && ($_W['admin']['is_admin'] == 0 || $_W['admin_user_role']['is_admin'] != 1)) {
                $map[$uid_field] = $_W['admin']['id'];
            } else {
                //admin  use scope
                switch ($_W['users_admin']['del_range']) {
                    case 0 :
                        $map[$uid_field] = $_W['admin']['id'];
                        break;
                    case 2 :
                        $users = D('users')->bra_where(['parent_id' => $_W['admin']['id']])->select('id')->get();
                        $ids = BraArray::get_array_from_array_vals($users, 'id');
                        $ids[] = $_W['admin']['id'];
                        $map[$uid_field] = ['IN', $ids];
                        break;
                    case 5 :
                        unset($map[$uid_field]);
                        $map = array_filter($map);
                        break;
                }
            }
        }

        return array_merge($map, $map_old);
    }

    private function map_user_auth ($map_old = [], $uid_field = 'user_id') {
        global $_W;
        $map = [];
        $uid = $_W['user']['id'];
        switch ($_W['users_admin']['del_range']) {
            case 0 :
                $map[$uid_field] = $uid;
                break;
            case 2 :
                $users = D('users')->bra_where(['parent_id' => $uid])->select('id')->get();
                $ids = BraArray::get_array_from_array_vals($users, 'id');
                $ids[] = $uid;
                $map[$uid_field] = ['IN', $ids];
                break;
            case 5 :
                // unset($map_old[$uid_field]);
                break;
        }

        return array_merge($map, $map_old);
    }

    protected function get_model_data ($result, $id_key, $name_key) {
        $_res = [];
        if (strpos($name_key, '|') !== false) {
            $name_keys = explode("|", $name_key);
            foreach ($name_keys as $_name_key) {
                if ($result[$_name_key]) {
                    $_res['name'] = $result[$_name_key];
                    break;
                }
            }
        }
        if (strpos($name_key, ',') !== false) {
            $name_keys = explode(",", $name_key);
            foreach ($name_keys as $_name_key) {
                if ($result[$_name_key]) {
                    $_res['name'] .= $result[$_name_key];
                }
            }
        }
        if (empty($_res['name'])) {
            $_res['name'] = $result[$name_key];
        }
        $_res['value'] = $result[$id_key];
        $_res['text'] = $_res['name'];
        $_res['name'] = isset($_res['name']) ? $_res['name'] : $result[$name_key];

        return $_res;
    }
}
