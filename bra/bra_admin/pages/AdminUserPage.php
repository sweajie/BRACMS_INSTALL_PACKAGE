<?php

namespace Bra\bra_admin\pages;

use App\Models\User;
use Bra\core\facades\BraCache;
use Bra\core\objects\BraArray;
use Bra\core\objects\BraExtWallet;
use Bra\core\objects\BraFS;
use Bra\core\objects\BraString;
use Bra\core\objects\BraView;
use Bra\core\pages\BraAdminController;
use Bra\core\utils\BraAdminItemPage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Intervention\Image\Facades\Image;
use ZipArchive;

class AdminUserPage extends BraAdminController {

    use BraAdminItemPage;

    public function bra_admin_admin_user_verify_idx ($query) {
        return $this->page_data = $this->t__bra_table_idx("users_verify");
    }

    public function bra_admin_admin_user_withdraw () {
        return $this->page_data = $this->t__bra_table_idx("pay_draw");
    }

	public function bra_admin_admin_user_fund_logs () {
		$ret =  $this->t__bra_table_idx("pay_logs", [], ['tpl' => 'bra_admin.admin_user.fund_logs']);
		if (is_bra_access(0)) {
			$ret['unit_types'] = $unit_types = D("pay_logs")->load_options("unit_type");
			$ret['pay_types'] = $pay_types = collect(D("pay_logs")->load_options("pay_type")) ->keyBy('id');
			$log_s = D("pay_logs")->selectRaw('unit_type,pay_type,SUM(total_fee)')->bra_where($this->idx_where)->groupByRaw("unit_type,pay_type")->bra_get();
			$new_logs = [];
			foreach ($log_s as $k=>$val){
				$kkk = $pay_types[$val['pay_type']]['title'] . ' ' . $unit_types[$val['unit_type']]['title'];
				$new_logs[$kkk] = $val['SUM(total_fee)'];
			}
			$ret['new_logs'] = $new_logs;
		}

		return $this->page_data = $ret;
	}

    public function bra_admin_admin_user_user_index () {
        return $this->page_data = $this->t__bra_table_idx("users", [], [
            'hide' => ['dis_level_id', 'dis_create_at', 'dis_status', 'area_id', 'level_expire']
        ]);
    }

    public function bra_admin_admin_user_user_add () {
        return $this->page_data = $this->t__add_iframe('users');
    }

    public function bra_admin_admin_user_user_edit ($query) {
        return $this->page_data = $this->t__edit_iframe((int)$query['id'], "users");
    }

    public function bra_admin_admin_user_user_del ($query) {
        $user_ids = $query['ids'];
        $enable = $disable = 0;
        foreach ($user_ids as $user_id) {
            if ($user_id == 1) {
                return $this->page_data = bra_res(500, '无法操作超级管理员');
            }
            $tmp_user = D('users')->with_site()->find($user_id);
            if ($tmp_user) {
                $update['status'] = $tmp_user->status == 2 ? 99 : 2;
                $res = D('users')->item_edit($update, $user_id);
                if (!is_error($res)) {
                    if ($update['status'] == 99) {
                        $enable++;
                    } else {
                        $disable++;
                    }
                    D('users')->get_item($user_id, 1, ['update' => true]);
                } else {
                    return $this->page_data = $res;
                }
            }
        }

        return $this->page_data = bra_res(1, "'操作成功！启用:$enable 个' , 禁用 : $disable 个 ");
    }

    public function bra_admin_admin_user_user_destroy () {
        global $_W, $_GPC;
        $user_ids = $_GPC['ids'];
        $success_count = $error_count = 0;
        $extra = $this->map_auth([], 'id');
        foreach ($user_ids as $user_id) {
            Db::beginTransaction();
            if ($user_id == 1) {
                Db::rollback();

                return $this->page_data = bra_res(500, '无法操作超级管理员');
            }
            //todo  check admin auth if has role operate
            $res = D('users')->item_del($user_id, $extra, true);
            if (!is_error($res)) {
                //delete model data
                $models = BraArray::to_array(D('models')->get());
                $delete_models = BraArray::get_array_from_array_vals($models, 'table_name');
                foreach ($delete_models as $delete_model) {
                    $model = D($delete_model);
                    if (Schema::hasTable($delete_model) && $model->field_exits('user_id')) {
                        $res = D($delete_model)->bra_where(['user_id' => $user_id])->delete();
                        if ($res) {
                            $success_count++;
                        } else {
                            $error_count++;
                        }
                    } else {
                        $error_count++;
                    }
                }
                Db::commit();
            } else {
                Db::rollback();
            }
        }

        return $this->page_data = bra_res(1, '操作完成 , 删除成功: ' . $success_count, '');
    }

    public function bra_admin_admin_user_user_role_idx () {
        $where['is_admin'] = 0;

        return $this->page_data = $this->t__bra_table_idx("user_roles", $where);
    }

    public function bra_admin_admin_user_admin_role_idx () {
        $where['is_admin'] = 1;

        return $this->page_data = $this->t__bra_table_idx("user_roles", $where);
    }

    public function bra_admin_admin_user_role_add () {
        $where['is_admin'] = 0;

        return $this->page_data = $this->t__add_iframe('user_roles', $where);
    }

    public function bra_admin_admin_user_user_level_add () {
        return $this->page_data = $this->t__add_iframe('users_level');
    }

    public function bra_admin_admin_user_user_level_edit ($query) {
        return $this->page_data = $this->t__edit_iframe($query['id'], 'users_level');
    }

    public function bra_admin_admin_user_user_level_idx () {
        return $this->page_data = $this->t__bra_table_idx('users_level');
    }

    public function bra_admin_admin_user_user_level_del ($query) {
        return $this->page_data = $this->t__del($query['id'], "users_level");
    }

    public function bra_admin_admin_user_role_del ($query) {
        return $this->bra_admin_admin_user_admin_role_del($query);
    }

    public function bra_admin_admin_user_admin_role_del ($query) {
        Db::beginTransaction();
        $res = $this->t__del($query['id'], 'user_roles');
        if ($res['code'] != 1) {
            Db::rollback();

            return $this->page_data = $res;
        } else {
            $donde = $update = [];
            $donde['role_id'] = $query['id'];
            $update['role_id'] = 2;
            D('users')->bra_where($donde)->update($update);
            Db::commit();

            return $this->page_data = bra_res(1, 'ok');
        }
    }

    public function bra_admin_admin_user_admin_role_edit ($query) {
        return $this->page_data = $this->t__edit_iframe($query['id'], 'user_roles');
    }

    public function bra_admin_admin_user_role_edit ($query) {
        return $this->page_data = $this->t__edit_iframe($query['id'], 'user_roles');
    }

    public function bra_admin_admin_user_users_admin_idx () {
        return $this->page_data = $this->t__bra_table_idx('users_admin');
    }

    public function bra_admin_admin_user_users_admin_add () {
        return $this->page_data = $this->t__add_iframe('users_admin');
    }

    public function bra_admin_admin_user_users_admin_del ($query) {
        global $_W, $_GPC;
        $ids = $_GPC['ids'];
        $success_count = $error_count = 0;
        foreach ($ids as $id) {
            $res = D('users_admin')->item_del($id);
            if (!is_error($res)) {
                $success_count++;
            } else {
                $error_count++;
            }
        }

        return $this->page_data = bra_res(1, '操作完成 , 删除成功: ' . $success_count, '');
    }

    public function bra_admin_admin_user_access_index ($query) {
        $extra['user_id'] = $query['user_id'];

        return $this->page_data = $this->t__bra_table_idx('log', $extra);
    }

    public function bra_admin_admin_user_admin_role_auth ($query) {
        global $_W, $_GPC;
        $user_menu_access = D('user_menu_access');
        $user_roles = D('user_roles');
        $role_id = $query['role_id'];
        $is_admin = !isset($_GPC['is_admin']) ?: (int)$_GPC['is_admin'];
        //当前菜单访问权限
        $formatted_admin_access = [];
        if (!$_W['super_power']) {
            $where_access['role_id'] = $_W['users_admin']['role_id'];
            $where_access['is_admin'] = 1;
            $current_admin_access = $user_menu_access->bra_where($where_access)->get();
            $formatted_admin_access = [];
            foreach ($current_admin_access as $access) {
                $formatted_admin_access[$access['menu_id']] = $access;
            }
            //todo if check users_admin role auth
        }
        $role = (array)$user_roles->with_site()->find($role_id);
        if (!$role || $role['id'] == 1) {
            return abort(403, "角色不存在!");
        }
        $tpl = "authorize_admin_ztree"; // "authorize_user_ztree"; // user or admin
        //target user role access
        $where_access = [];
        $where_access['role_id'] = $role_id;
        $where_access['is_admin'] = $is_admin;
        $current_target_access = D('user_menu_access')->bra_where($where_access)->get();
        $formatted_target_access = [];
        foreach ($current_target_access as $access) {
            $access = (array)$access;
            $formatted_target_access[$access['menu_id']] = $access;
        }
        $data = [];
        if (is_bra_access(0, 'post')) {
            $menu_ids = explode(",", $query['menu_ids']);
            //destroy old access we should separate the user  and admin menus
            D('user_menu_access')->bra_where($where_access)->delete();
            //check if current admin have the menus access
            foreach ($menu_ids as $val) {
                if (!$val) {
                    continue;
                }
                if ($_W['super_power'] || $formatted_admin_access[$val]) {
                    $_item = array('role_id' => $role_id, 'menu_id' => (int)$val,);
                    $_item['site_id'] = $_W['site']['id'];
                    $_item['is_admin'] = $is_admin;
                    $data[] = $_item;
                }
            }
            if (D('user_menu_access')->insert($data)) {
                return $this->page_data = bra_res(1, 'operate success !');
            } else {
                return $this->page_data = bra_res(1, '操作成功 , 本次操作没有新增授权', $data);
            }
        } else {
            //超级管理员列出所有
            if ($_W['super_power']) {
                $set = config('bra_set');
                $models = $set['menus'];
                if ($models) {
                    $no = join(',', $models) . ',0,';
                } else {
                    $no = '0,';
                }
                $map['id'] = ["!IN", $no];
                $map['is_admin'] = $is_admin;
                $menus = D('user_menu')->bra_where($map)->limit(10000)->get();
            } else {
                //可能拥有的菜单权限列出来
                foreach ($current_admin_access as $access) {
                    $menu_ids[] = $access['menu_id'];
                }
                $map['id'] = ['IN', $menu_ids];
                $map['is_admin'] = $is_admin;
                $menus = D('user_menu')->bra_where($map)->get();
            }
            $new_menus = [];
            foreach ($menus as $item) {
                $item = (array)$item;
                unset($item['icon']);
                $new_menus[$item['id']] = (array)$item;
            }
            //自动选取当前已经有的权限
            //format menu
            $newMenuIds = [];
            foreach ($formatted_target_access as $a) {
                $newMenuIds[] = $a['menu_id'];
                if (isset($new_menus[$a['menu_id']])) {
                    $new_menus[$a['menu_id']]['checked'] = true;
                }
            }
            $formated_menus = self::getAllChild(0, 0, $new_menus);
            D('user_menu')->load_tree();
            A('menuIds', $newMenuIds);
            A('role_id', $role_id);
            A('detail', $role);
            A('menus', $formated_menus);

            return $this->page_data = A_T("bra_admin.admin_user." . $tpl);
        }
    }

    public static function getAllChild ($pid = 0, $level = 0, $menus_all = []) {
        global $_W, $_GPC;
        $menus = [];
        foreach ($menus_all as $menu) {
            if ($menu['parent_id'] == $pid) {
                $menu['title'] = $menu['menu_name'];
                $menus[] = $menu;
            }
        }
        $level++;
        foreach ($menus as $key => &$menu) {
            // 多语言
            $menu['name'] = trans($menu['menu_name']);
            $menu['children'] = '';
            if (D('user_menu')->bra_where(['parent_id' => $menu['id']])->first()) {
                //$menu['open'] = true;
                if ($level <= 2) {
                    $menu['open'] = true;
                }
                $menu['children'] = self::getAllChild($menu['id'], $level, $menus_all);
            }
        }

        return $menus;
    }

    public function bra_admin_admin_user_change_fund ($query) {
        global $_W;
        $user_id = (int)$query['user_id'];
        $coin_types = config('bra_coin');
        $data = $query['data'];
        if (is_bra_access(0, 'post')) {
            DB::beginTransaction();
            $wallet = new BraExtWallet($user_id);
            $wallet->set_admin($_W['admin']['id']);
            if (!$coin_types[$data['unit_type']]['field']) {
                return $this->page_data = bra_res(500, "请选择正确的币种！");
            }
            if (!is_numeric($data['amount']) || $data['amount'] < 0) {
                return $this->page_data = bra_res(500, "请输入正确的金额！");
            }
            if (empty($data['note'])) {
                return $this->page_data = bra_res(500, "请输入备注！");
            }
            if ($data['operate'] == 1) {//1 消费
                $res = $wallet->ex_spend($data['amount'], $coin_types[$data['unit_type']]['field'], 3, $data['note']);
            }
            if ($data['operate'] == 2) {//2 充值
                $res = $wallet->ex_deposit($data['amount'], $coin_types[$data['unit_type']]['field'], 14, $data['note']);
            }
            if (is_error($res)) {
                DB::rollback();
            } else {
                D('users')->get_item($user_id, [], ['update' => true]);
                DB::commit();
            }

            D('users')->clear_item($user_id);
            return $this->page_data = $res;
        } else {
            $target = User::find($user_id);
            A('target', $target);
            A('coin_types', $coin_types);
            $bar_text = BraView::compile_blade_str('{{$target["nickname"]}} -  ');
            foreach ($coin_types as $coin_type) {
                $bar_text .= $coin_type['title'] . ":" . $target[$coin_type['field']] . '-';
            }
            A('bar_text', $bar_text);

            return $this->page_data = A_T();
        }
    }

    public function bra_admin_admin_user_admin_role_add () {
        $ext['is_admin'] = 1;

        return $this->page_data = $this->t__add_iframe('user_roles', $ext);
    }

    public function bra_admin_admin_user_role_auth ($query) {
        global $_W, $_GPC;
        $user_menu_access = D('user_menu_access');
        $user_roles = D('user_roles');
        $role_id = $query['role_id'];
        $is_admin = 0;
        //当前菜单访问权限
        $formatted_admin_access = [];
        if (!$_W['super_power']) {
            $where_access['role_id'] = $_W['users_admin']['role_id'];
            $where_access['is_admin'] = $is_admin;
            $current_admin_access = $user_menu_access->bra_where($where_access)->get();
            $formatted_admin_access = [];
            foreach ($current_admin_access as $access) {
                $formatted_admin_access[$access['menu_id']] = $access;
            }
            //todo if check users_admin role auth
        }
        $role = (array)$user_roles->with_site()->find($role_id);
        if (!$role || $role['id'] == 1) {
            return abort(403, "角色不存在!");
        }
        $tpl = "authorize_admin_ztree"; // "authorize_user_ztree"; // user or admin
        //target user role access
        $where_access = [];
        $where_access['role_id'] = $role_id;
        $where_access['is_admin'] = $is_admin;
        $current_target_access = D('user_menu_access')->bra_where($where_access)->get();
        $formatted_target_access = [];
        foreach ($current_target_access as $access) {
            $access = (array)$access;
            $formatted_target_access[$access['menu_id']] = $access;
        }
        $data = [];
        if (is_bra_access(0, 'post')) {
            $menu_ids = explode(",", $query['menu_ids']);
            //destroy old access we should separate the user  and admin menus
            D('user_menu_access')->bra_where($where_access)->delete();
            //check if current admin have the menus access
            foreach ($menu_ids as $val) {
                if (!$val) {
                    continue;
                }
                if ($_W['super_power'] || $formatted_admin_access[$val]) {
                    $_item = array('role_id' => $role_id, 'menu_id' => (int)$val,);
                    $_item['site_id'] = $_W['site']['id'];
                    $_item['is_admin'] = $is_admin;
                    $data[] = $_item;
                }
            }
            if (D('user_menu_access')->insert($data)) {
                return $this->page_data = bra_res(1, 'operate success !');
            } else {
                return $this->page_data = bra_res(1, '操作成功 , 本次操作没有新增授权', $data);
            }
        } else {
            //超级管理员列出所有
            if ($_W['super_power']) {
                $set = config('bra_set');
                $models = $set['menus'];
                if ($models) {
                    $no = join(',', $models) . ',0,';
                } else {
                    $no = '0,';
                }
                $map['id'] = ["!IN", $no];
                $map['is_admin'] = $is_admin;
                $menus = D('user_menu')->bra_where($map)->limit(10000)->get();
            } else {
                //可能拥有的菜单权限列出来
                foreach ($current_admin_access as $access) {
                    $menu_ids[] = $access['menu_id'];
                }
                $map['id'] = ['IN', $menu_ids];
                $map['is_admin'] = $is_admin;
                $menus = D('user_menu')->bra_where($map)->get();
            }
            $new_menus = [];
            foreach ($menus as $item) {
                $item = (array)$item;
                unset($item['icon']);
                $new_menus[$item['id']] = (array)$item;
            }
            //自动选取当前已经有的权限
            //format menu
            $newMenuIds = [];
            foreach ($formatted_target_access as $a) {
                $newMenuIds[] = $a['menu_id'];
                if (isset($new_menus[$a['menu_id']])) {
                    $new_menus[$a['menu_id']]['checked'] = true;
                }
            }
            $formated_menus = self::getAllChild(0, 0, $new_menus);
            D('user_menu')->load_tree();
            A('menuIds', $newMenuIds);
            A('role_id', $role_id);
            A('detail', $role);
            A('menus', $formated_menus);

            return $this->page_data = A_T("bra_admin.admin_user." . $tpl);
        }
    }

    public function bra_admin_admin_user_users_admin_set ($query) {
        return $this->page_data = $this->t__edit_iframe($query['id'], 'users_admin', [], ['user_id']);
    }

    public function bra_admin_admin_user_user_level_set ($query) {
        global $_W;
        return $this->page_data = $this->t__field_config(['id' => $query['id']], 'users_level', 'data', $query['data']);

    }


	public function BRA_ADMIN_ADMIN_USER_WALLET_PASS () {
		global $_W, $_GPC;
		if (is_bra_access(0)) {
			$data = $_GPC['data'];
			$user = D('users')->where(['user_name' => $data['user_name']])->bra_one();
			if($user){
				$al = new BraExtWallet($user['id']);

				return $this->page_data = $al->change_pass(trim($data['pass']));
			}else{
				return $this->page_data =  bra_res(500 , '用户不存在');
			}

		} else {
			return $this->page_data =T();
		}
	}
}
