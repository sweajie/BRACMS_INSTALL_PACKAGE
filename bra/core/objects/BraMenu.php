<?php

namespace Bra\core\objects;

use Bra\core\models\UserMenu;
use Exception;
use Illuminate\Support\Facades\DB;

class BraMenu {
    public static function get_menu_tpl_url ($menu_id, $is_admin = 1, $mapping = []) {
        $menu = D('user_menu')->get_item($menu_id);
		if(!$menu){
			return  "";
		}
        $mapping = array_merge($menu, $mapping);
        preg_match_all("/{(.*?)}/i", $menu['params'] ?? '', $machs);
        foreach ($machs['1'] as $mach) {
            $mapping[$mach] = "{{d.old_data.{$mach}}}";
        }
        if ($is_admin == 1) {
            return urldecode(build_back_link($menu['id'], $menu['params'], $mapping));
        } else {
            return build_back_link($menu['id'], $menu['params'], $mapping);
        }
    }

    public static function get_admin_menu_path ($admin_menu_id) {
        static $path_str = '';
        if ($admin_menu_id) {
            $sub_menu = (array) D('user_menu')->find($admin_menu_id);
            $path_str = "  <li><a> " . $sub_menu['menu_name'] . " </a>  </li>" . $path_str;
            if ($sub_menu['parent_id'] != 0) {
                static::get_admin_menu_path($sub_menu['parent_id']);
            }

            return $path_str;
        }
    }

    /**
     * @bra ExecUtil bra/BraCache#c
     * @param $role_id
     * @param array $only
     * @param array $exclude
     * @return mixed
     */
    static public function load_menu ($is_admin, $role_id, $only = [], $exclude = []) {
        $menuList = self::getUserMenus($is_admin, $role_id, $only, $exclude);
        $menus = self::getAllChild(0, $menuList);
        foreach ($menus as $k => $menu) {
            if (!$menus[$k]['children']) {
                unset($menus[$k]);
            } else {
                $new_menus[] = $menu;
            }
        }
        $ret['code'] = 1;
        $ret['msg'] = 'success';
        $new_menus = BraArray::reform_arr($new_menus, 'id');
        BraArray::sort_by_val_with_key($new_menus);
        $ret['data'] = $new_menus;

        return $ret;
    }

    static public function getUserMenus (int $is_admin, int $role_id, $only_module = '', $exclude = '') {
        global $_W;
        $menuList = Db::table('user_menu')->where('user_menu.is_admin', '=', $is_admin)
            ->where('user_menu.display', '=', 1)
            ->where('user_menu.is_right', '=', 0);
        if ($role_id != 1) {
            $menuList = $menuList->leftJoin('user_menu_access', function($join) use($role_id){
                $join->on('user_menu_access.menu_id', '=', "user_menu.id");
            })->where('user_menu_access.role_id', '=', $role_id);

        }
        $set = config('bra_set');
        if ($set['menus']) {
            $menuList = $menuList->whereNotIn('user_menu.id', $set['menus']);
        }

        if ($only_module) {
            $menuList = $menuList->whereIn('user_menu.module', $only_module);
        }
        if ($exclude) {
            $menuList = $menuList->whereNotIn('user_menu.module', $exclude);
        }
        $menuList = $menuList->orderBy('listorder', 'desc')->get();

        return $menuList;
    }

    public static function getAllChild ($pid = 0, $menus_all = []) {
        global $_W;
        $top_menus = [];
        foreach ($menus_all as $k => $menu) {
            $menu = (array)$menu;
            if ($menu['parent_id'] == $pid) {
                $top_menus[] = $menu;
            }
        }
        foreach ($top_menus as $key => &$menu) {
            // fix alias params bug
            $alias = [];
            if ($menu['alias'] > 0) {
                $alias = UserMenu::find($menu['alias'], 'params');
            }
            $user_menu_params = $menu['params'] ?? $alias['params'];
            $user_menu_params = self::str_to_url_params($user_menu_params);
            $user_menu_params['menu_id'] = $menu['id'];
            $menu['url'] = self::back_url($menu, $user_menu_params);
            $menu['children'] = self::getAllChild($menu['id'], $menus_all);
        }
        return $top_menus;
    }

    public static function str_to_url_params ($str) {
        $new_params = [];
        $params = explode("&", $str);
        foreach ($params as $param) {
            $param = explode("=", $param);
            if (isset($param[1])) {
                $new_params[$param[0]] = $param[1];
            }
        }

        return $new_params;
    }

    /**
     *
     * @param $menu
     * @param $vars
     * @param array $mapping
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\UrlGenerator|string
     * @throws Exception
     */
    public static function back_url ($menu, $vars, $mapping = []) {
        $menu = BraMenu::get_menu((array)$menu);
        $new_vars = [];
        $params_vars = explode("&", $menu['params']);
        foreach ($params_vars as $var) {
            $var = explode("=", $var);
            $new_vars[$var[0]] = $var[1] ?? '';
        }

        if (!is_array($vars)) {
            $vars = explode("&", $vars);
            foreach ($vars as $var) {
                $var = explode("=", $var);
                $new_vars[$var[0]] = $var[1] ?? '';
            }
        } else {
            $new_vars = array_merge( $new_vars , $vars);
        }

        foreach ($new_vars as &$new_var) {
            $new_var = BraString::parse_param_str($new_var, $mapping);
        }
        $new_vars['menu_id'] = $menu['id'];

        $new_vars = BraArray::array_to_query($new_vars);
        $url =  url($menu['app'] . '/' . $menu['ctrl'] . '/' . $menu['act'] . '?' . $new_vars);
        return  urldecode($url);
    }

    /**
     * @param $admin_menu_id
     * @return array|mixed
     * @throws Exception
     */
    public static function get_menu ($menu) {
        $set = config('bra_set');
        $menus = $set['diy_menus'];
        //$menu = D('user_menu')->get_item($menu['id'], false);
        if (isset($menus[$menu['id']])) {
            foreach ($menus[$menu['id']] as $k => $v) {
                $menu[$k] = $v;
            }
        }

        return $menu;
    }

    public static function init_menu ($user_menu_id) {
        global $_W;
        if (!$user_menu_id) {
            if (defined('BRA_ADMIN')) {
                if (ROUTE_C != "admin_api" && ROUTE_A != 'bra_admin_index_index' && ROUTE_M.ROUTE_C != 'bra_adminindex') {
                    abort(403, '非法访问 , 您要查找的页面必须要配置权限!');
                }
            }
            $assign['sub_menus'] = [];

            return $assign;
        }
        $_W['menu'] = $menu = (array) D('user_menu')->find($user_menu_id);
        //check the route is match the author
        if (ROUTE_M != $menu['app'] || ROUTE_C != $menu['ctrl'] || ROUTE_A != $menu['act']) {
            end_resp(bra_res(403, "BraCMS end point not found!"));
        }
        /**
         * sub menus
         */
        if ($menu['alias']) {
            $menu_id = $menu['alias'];
        } else {
            $menu_id = $menu['id'];
        }
        $map['parent_id'] = $menu_id;
        //加载显示的子菜单
        $sub_menus = D('user_menu')->bra_where($map)->orderBy('listorder', 'desc')->bra_get();

        //sub menu process params
        foreach ($sub_menus as $k => $sub_menu) {
            $sub_menu = (array)$sub_menu;
            $set = config('bra_set');
            $menus = $set['menus'] ?? [];
            if (in_array($sub_menu['id'], $menus)) {
                unset($sub_menus[$k]);
            }
            if ($_W['user']['id'] != 1 && !self::has_access_to_menu($sub_menu, $_W['user']['id'])) {
                unset($sub_menus[$k]);
            }
        }
        if (!$_W['super_power']) {
            if (!self::has_access_to_menu($menu , $_W['user']['id'])) {
                end_resp(bra_res(403, "BraCMS ERROR CODE 4030@". $menu['id'] . "for {$_W['user']['id']} : 您的账号没有权限进行操作"));
            }
        }
        $assign['sub_menus'] = $sub_menus;
        $assign['menu'] = $menu;
        $assign['page_title'] = $menu['menu_name'];
        A($assign);


        return $assign;
    }

    public static function fetch_menu ($menus_id) {
        $menu = D('user_menu')->get_item($menus_id, false);

        return self::get_menu($menu);
    }

    public static function has_access_to_menu (array $menu, $user_id) {
        $map['menu_id'] = $menu['id'];
        $map['role_id'] = ['IN', function (&$query) use ($user_id) {
            global $_W;
            D('users_admin' , '' , true)->set_db($query)->select('role_id')->where('user_id', '=', $user_id);
        }];

        return $user_menu_access = D('user_menu_access')->bra_where($map)->first();
    }

    public static function has_access_to_menu_id ($menu_id, $user_id) {
        $menu = self::fetch_menu($menu_id);

        return self::has_access_to_menu($menu, $user_id);
    }

}
