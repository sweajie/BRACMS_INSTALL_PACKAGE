<?php

namespace Bra\bra_admin\pages;

use Bra\core\pages\BraAdminController;
use Bra\core\utils\BraTree;
use Illuminate\Support\Facades\DB;

class AdminAreaPage extends BraAdminController {
    public function bra_admin_admin_area_area_idx ($query) {
        global $_W , $_GPC;
        $parent_id = (int)$query['parent_id'];
        $config = [];
        if ($parent_id) {
            $_W['mapping']['parent_id'] = $config['parent_id'] = $parent_id;
        }

        return $this->page_data = $this->t__bra_table_tree_idx("area", [], $config);
    }

    public function bra_admin_admin_area_area_edit ($query) {
        return $this->page_data = $this->t__edit_iframe($query['id'], "area");
    }

    public function bra_admin_admin_area_area_del ($query) {
        DB::beginTransaction();
        $id = (int)$query['id'];
        $area = (array)D('area')->find($id);
        $res = $this->t__del($id, "area");
        if (!is_error($res)) {
            $donde['id'] = ['IN', explode(',', $area['arrchild'])];
            $amount = D('area')->bra_where($donde)->limit(10000)->delete();
            DB::commit();
        } else {
            DB::rollBack();
        }

        return $this->page_data = $res;
    }

    public function bra_admin_admin_area_area_add ($query) {
        $parent_id = (int)$query['parent_id'];
        $base_info = [];
        if ($parent_id) {
            $base_info['parent_id'] = $parent_id;
        }

        return $this->page_data = $this->t__add_iframe('area', $base_info);
    }

    public function bra_admin_admin_area_area_cache ($id = 0) {
        $info = BraTree::cache_sub_list("area", $id);

        return $this->page_data =  ['code' => 1, 'msg' => "ok", 'data' => $info];
    }
}
