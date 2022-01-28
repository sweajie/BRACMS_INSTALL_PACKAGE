<?php

namespace Bra\bra_admin\pages;

use Bra\core\facades\BraCache;
use Bra\core\objects\BraMenu;
use Bra\core\pages\BraAdminController;
use Bra\core\utils\BraAdminItemPage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminApiPage extends BraAdminController {
    use BraAdminItemPage;

    public function bra_admin_admin_api_change_pass($query) {
        if (is_bra_access(0, 'post')) {
            $data = $query['data'];
            if (!$data['password'] || $data['password'] != $data['password2']) {
                return $this->page_data = bra_res(500, '新密必须填写两次必须一致！');
            }

            $user = refresh_user();

            if (!$data['pass_old'] || !Hash::check($data['pass_old'], $user['password'])) {
                return $this->page_data = bra_res(500, '旧密码不正确！');
            }
            $update['password'] = $data['password'];
            return $this->page_data = D('users')->item_edit($update, $user['id']);
        } else {
            $this->page_data = A_T();
        }
    }

    public function bra_admin_admin_api_load_menu($query, BraMenu $menu) {
        global $_W;
        $this->page_data = $menu::load_menu(1, $_W['admin_role']['id']);
    }

	public function BRA_ADMIN_ADMIN_API_MAIN() {
		if(is_bra_access(0 , 'post')){
			$total = 0;
			$dn['title'] = '会员';
			$dn['menu_id'] = '50125';
			$dn['amount'] =  D("users")->count();
			$todos[] = $dn;

			$dn1['title'] = '分销商';
			$dn1['menu_id'] = '50125';
			$dn1['amount'] =  D("users")->bra_where(['dis_level_id' => ['>'  , 0]])->count();
			$todos[] = $dn1;

			$dn2['title'] = '活跃会员';
			$dn2['menu_id'] = '50125';
			$dn2['amount'] =  D("log")->selectRaw('user_id')->whereDay('create_at' , '=' , Carbon::today())->DISTINCT ()->count('user_id');
			$todos[] = $dn2;



			$tx['title'] = '待审提现';
			$tx['menu_id'] = '390';
			$total +=  $tx['amount'] =  D("pay_draw")->bra_where(['status' => 1])->count();
			$todos[] = $tx;




			$rest['todos'] = $todos;
			$rest['total'] = $total;
			$this->page_data = $rest;
		}else{

			$this->page_data = A_T();
		}
	}
    public function bra_admin_admin_api_list_item($query) {
        global $_W, $_GPC;
        $model_id = $query['model_id'];
        $limit = 10;
        $page = 1;
        $model = D($model_id);
        $page = max((int)$page, 1);
        //todo check if the user has the auth to search the api
        //todo : check if the user has auth
        $user_id = isset($query['user_id']) ? (int)$query['user_id'] : 0;
        $q = $query['q'];
        $limit = (int)$limit;
        $limit = min(20, $limit);
        $where = [];
        if ($user_id && $model->field_exits('user_id')) {
            $where['user_id'] = $user_id;
        }
        $search_keys = $model->_TM['search_keys'] ? $model->_TM['search_keys'] : $model->_TM['name_key'];
        $search_keys = explode('|', $search_keys);
        $name_key = $model->_TM['name_key'];
        //设置了搜索域的 必须提供关键字才可以进行数据操作
        $model->with_site()->where($where);
        if ($q && $search_keys) {
            foreach ($search_keys as $search_key) {
                $model->orWhere($search_key, 'LIKE', "%$q%");
            }
        }
        $lists = $model->forPage($page)->orderBy('id', 'desc')->limit($limit)->get();
        $new_res = [];
        foreach ($lists as $result) {
            $result = (array)$result;
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
            $_res['value'] = $result[$model->_TM['id_key']];
            $_res['text'] = $_res['name'];
            $_res['name'] = isset($_res['name']) ? $_res['name'] : $result[$model->_TM['name_key']];
            $new_res[] = $_res;
        }
        if (!$new_res) {
            $_res['value'] = "";
            $_res['text'] = "暂无结果";
            $_res['name'] = "暂无结果";
            $new_res[] = $_res;
        }
        // Expected server response
        $ret['code'] = 1;
        $ret['success'] = true;
        $ret['results'] = $new_res;
        $this->page_data = $ret;
    }

    public function bra_admin_admin_api_clear_cache() {
        BraCache::clear_cache();
        return $this->page_data = bra_res(1, "OK 缓存清理完毕!", "");
    }

    public function bra_admin_admin_api_list_icon() {
        global $_W, $_GPC;
        A('field_name', $_GPC['field_name']);
        A('max_select', $_GPC['max_select']);
        A('pick_type', $_GPC['pick_type']);

        return $this->page_data = $this->t__bra_table_idx('icon', [], ['tpl' => 'bra_admin.admin_api.list_icon']);
    }
}
