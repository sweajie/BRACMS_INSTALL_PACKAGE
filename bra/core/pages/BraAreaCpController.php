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

use Bra\core\objects\BraPage;
use Bra\core\utils\BraAdminItemPage;
use Illuminate\Support\Facades\Cookie;

class BraAreaCpController extends UserBaseController {
    use BraAdminItemPage;

    public $object;

    public function __construct (BraPage $bra_page) {
        global $_W, $_GPC;
        $this->bra_page = $bra_page;
        if (!$_W['user']) {

            Cookie::queue('login_forward_' . $_W['site']['id'], $_W['current_url'], 8646000);

            return $this->page_data = redirect(url('bra/passport/login'));
        }
        //init area
        $this->init_cp_auth();
        $no_area_acts = [static::prefix . 'index', static::prefix . 'main', static::prefix . 'switch_object', static::prefix . 'load_menu'];  // noneed area acts
//        dd(ROUTE_C . '_' . ROUTE_A , $no_area_acts);
        if (!in_array(ROUTE_C . '_' . ROUTE_A, $no_area_acts)) {
            if (!$_GPC['menu_id']) {
                end_resp(bra_res(500, '无法辨别的授权访问!' . ROUTE_C . '_' . ROUTE_A));
            }
            if (!$this->object['id']) {
                end_resp(bra_res(500, '区域错误 , 请先在右上角切换区域!'));
            }
        } else {
            if (ROUTE_C . '_' . ROUTE_A == static::prefix . 'load_menu' && !$this->object['id']) {
                return $this->page_data = bra_res(500, '区域错误!' . ROUTE_C . '_' . ROUTE_A);
            }
        }
        $_GPC['__parent_area_id'] = $this->object['id'];
    }



    public function init_cp_auth () {
        global $_W, $_GPC;
        $donde = ['role_id' => static::role_id];
        $current_area_id = request()->cookie(static::prefix . 'area_id');
        if ($current_area_id) {
            $donde['area_id'] = $current_area_id;
            $_W['users_admin'] = $this->users_admin = (array)D('users_admin')->bra_where($donde)->get_user_data();

            $this->object = (array)D('area')->find($this->users_admin['area_id']);
        } else {
            //rand get
            $test = D('users_admin')->bra_where($donde)->get_user_data();
            if (!$test) {
                end_resp(bra_res(500, '越权访问!'));
            }
            $this->object = [];
        }
        $donde = ['role_id' => static::role_id];
        $donde['user_id'] = $_W['user']['id'];
        $builds = D('users_admin')->bra_where($donde)->list_item(true, ['with_old' => true]);
        A('objects', $builds);
        A('current_object_id', $this->object['id']);
        A('current_object', $this->object);
    }
}
