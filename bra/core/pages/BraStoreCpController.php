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

class BraStoreCpController extends UserBaseController {
    use BraAdminItemPage;

    public $object;

    public function __construct (BraPage $bra_page) {
        global $_W, $_GPC;
        $this->bra_page = $bra_page;
        if (!$_W['user']) {

            Cookie::queue('login_forward_' . $_W['site']['id'], $_W['current_url'], 8646000);

            return $this->page_data = redirect(url('bra/passport/login'));
        }
        //init store
        $this->init_cp_auth();
        $no_menu_acts = [static::prefix . '_index', static::prefix . '_main', static::prefix . '_switch_object', static::prefix . '_load_menu'];  // noneed menu acts
//        dd(ROUTE_C . '_' . ROUTE_A , $no_menu_acts);
        if (!in_array(ROUTE_C . '_' . ROUTE_A, $no_menu_acts)) {
            if (!$_GPC['menu_id']) {
                end_resp(bra_res(500, '无法辨别的授权访问!' . ROUTE_C . '_' . ROUTE_A));
            }
            if (!$this->object['id']) {
                end_resp(bra_res(500, '区域错误 , 请先在右上角切换区域!'));
            }
        } else {
            if (ROUTE_C . '_' . ROUTE_A == static::prefix . 'load_menu' && !$this->object['id']) {
                return $this->page_data = bra_res(500, '店铺错误!' . ROUTE_C . '_' . ROUTE_A);
            }
        }
    }

}
