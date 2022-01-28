<?php

namespace Bra\bra_admin\pages;

use Bra\core\pages\BraAdminController;
use Bra\core\utils\BraAdminItemPage;
use Bra\core\utils\BraDis;

class AdminCorePage extends BraAdminController {
    use BraAdminItemPage;

	public function bra_admin_admin_core_firmar_grabar_idx () {
		return $this->page_data = $this->t__bra_table_idx("firmar_grabar");
	}

	public function bra_admin_admin_core_firmar_regla_idx () {
		return $this->page_data =  $this->t__bra_table_idx("firmar_regla");
	}

	public function bra_admin_admin_core_firmar_grabar_set ($query) {
		global $_W, $_GPC;
		$donde['title'] = $title = "firmar_grabar";
		return $this->page_data =   $this->t__field_config($donde , 'config' , 'data' , $query['data']);
	}

	public function bra_admin_admin_core_firmar_regla_add () {
		return  $this->page_data =$this->t__add_iframe('firmar_regla');
	}

	public function bra_admin_admin_core_firmar_regla_edit ($query) {
		return  $this->page_data = $this->t__edit_iframe($query['id'], 'firmar_regla');
	}

	public function bra_admin_admin_core_firmar_regla_del ($query) {
		return  $this->page_data = $this->t__del($query['id'], "firmar_regla");
	}

    public function bra_admin_admin_core_option_field_idx () {
        $assign['lists'] = D('modules')->list_item(true);
        A($assign);

        return $this->page_data = $this->t__bra_table_idx('modules', [], ['tpl' => 'bra_admin.admin_core.option_field_idx', 'page_size' => 10000]);
    }

    public function bra_admin_admin_core_option_models ($query) {
        global $_W;
        $model_id = $query['model_id'];
        $field_name = $query['field_name'];
        $bind_model = D($model_id);
        $ext['site_id'] = $_W['site']['id'];
        $ext['field_name'] = $field_name;
        $ext['model_id'] = $bind_model->_TM['id'];

        return $this->page_data = $this->t__bra_table_idx("option", $ext);
    }

    public function bra_admin_admin_core_option_add ($query) {
        $target_model = D($query['model_id']);
        $base_info['model_id'] = $target_model->_TM['id'];
        $base_info['field_name'] = $query['field_name'];

        return $this->page_data = $this->t__add_iframe("option", $base_info);
    }

    public function bra_admin_admin_core_option_delete ($query) {
        return $this->page_data = $this->t__del($query['id'], "option");
    }

    public function bra_admin_admin_core_option_edit ($query) {
        return $this->page_data = $this->t__edit_iframe($query['id'], "option");
    }

    public function bra_admin_admin_core_help_idx () {
        return $this->page_data = $this->t__bra_table_idx("help");
    }

    public function bra_admin_admin_core_help_add () {
        return $this->page_data = $this->t__add_iframe("help");
    }

    public function bra_admin_admin_core_dis_team_idx ($query) {
        global $_W, $_GPC;
        if (is_bra_access()) {
            $query = $_GPC;
            $query['__simple'] = true;
            $query['bra_int_fields'] = ['parent_id'];
            $ret = [];
            $parent_id = $query['team_id'];
            if ($query['level'] == 1) {
                $ret = D('users')->bra_where(['parent_id' => $parent_id])->select()->toArray();
            }
            if ($query['level'] == 2) {
                $ret = D('users')->_m->where('parent_id', 'IN', function ($query) use ($parent_id) {
                    $query->name('users')->where(['parent_id' => $parent_id])->field('id');
                })->select()->toArray();
            }

            return $this->page_data = $ret;
        } else {
            return $this->page_data = T();
        }
    }

    public function bra_admin_admin_core_dis_user_edit ($query) {
        $config['show'] = ['dis_level_id', 'dis_status'];

        return $this->page_data = $this->t__edit_iframe($query['id'], "users", [], $config);
    }

    public function bra_admin_admin_core_dis_user_del ($query) {
        $user = refresh_user($query['id']);

        return BraDis::remove_check($user);
    }

    public function bra_admin_admin_core_dis_user_chk () {
        global $_W, $_GPC;
        $success = $fails = 0;
        $msg = '';
        $ids = $_GPC['ids'];
        $update['status'] = 99;
        foreach ($ids as $id) {
            $dis_apply = D('dis_apply')->bra_one(['id' => $id , 'status' => 1]);
            $user = refresh_user($dis_apply['user_id']);
            if ($user['dis_status'] == 1) {
                $res = BraDis::pass_check($user , $dis_apply['target_level_id']);
                if (is_error($res)) {
                    $msg .= ' - ' . $res['msg'];
                    $fails++;
                } else {
                    // edit
                    D('dis_apply')->item_edit($update, $id);
                    $success++;
                }
            } else {
                $fails++;
            }
        }

        return bra_res(1, " 操作完成 审核成功 $success: , 审核失败:$fails " . $msg);
    }

	public function bra_admin_admin_core_help_edit ($query) {
		return  $this->page_data = $this->t__edit_iframe($query['id'] , "help");
    }

	public function bra_admin_admin_core_help_del ($query) {
		return $this->page_data = $this->t__del($query['id'] , "help");
    }
}
