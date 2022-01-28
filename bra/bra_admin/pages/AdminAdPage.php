<?php

namespace Bra\bra_admin\pages;

use Bra\core\pages\BraAdminController;

class AdminAdPage extends BraAdminController {
    public function bra_admin_admin_ad_hits_edit($query) {
        return $this->page_data = $this->t__edit_iframe($query['id'] , 'hits');
    }
    public function bra_admin_admin_ad_announce_idx() {
        return $this->page_data = $this->t__bra_table_idx("announce");
    }

    public function bra_admin_admin_ad_announce_add() {
        return $this->page_data = $this->t__add_iframe('announce');
    }

    public function bra_admin_admin_ad_announce_edit($query) {
        return $this->page_data = $this->t__edit_iframe($query['id'], 'announce');
    }

    public function bra_admin_admin_ad_announce_del($query) {
        return $this->page_data = $this->t__del($query['id'], 'announce');
    }

    public function bra_admin_admin_ad_hits_idx() {
        return $this->page_data = $this->t__bra_table_idx("hits");
    }

    public function bra_admin_admin_ad_group_idx() {
        return $this->page_data = $this->t__bra_table_idx("ad_group");
    }

    public function bra_admin_admin_ad_ad_item_idx($query) {
        $ext['group_id'] = (int)$query['group_id'];

        return $this->page_data = $this->t__bra_table_idx("ad_item", $ext);
    }

    public function bra_admin_admin_ad_ad_item_edit($query) {
        return $this->page_data = $this->t__edit_iframe($query['id'], 'ad_item');
    }

    public function bra_admin_admin_ad_ad_item_add($query) {
        $ext['group_id'] = (int)$query['group_id'];

        return $this->page_data = $this->t__add_iframe('ad_item', $ext);
    }

    public function bra_admin_admin_ad_ad_item_del($query) {
        return $this->page_data = $this->t__del($query['id'], 'ad_item');
    }

    public function bra_admin_admin_ad_comment_idx() {
        return $this->page_data = $this->t__bra_table_idx("o2o_rate");
    }

    public function bra_admin_admin_ad_group_edit($query) {
        return $this->page_data = $this->t__edit_iframe((int)$query['id'], "ad_group");
    }

    public function bra_admin_admin_ad_group_add() {
        return $this->page_data = $this->t__add_iframe('ad_group');
    }

    public function bra_admin_admin_ad_group_del($query) {
        if (D('ad_item')->bra_where(['groud_id' => $query['id']])->first()) {
            return $this->page_data = bra_res(500, '请先删除该广告位下面的广告');
        } else {
            return $this->page_data = $this->t__del($query['id'], 'ad_group');
        }
    }

    public function bra_admin_admin_ad_dis_order_idx() {
        return $this->page_data = $this->t__bra_table_idx("dis_order");
    }

    public function bra_admin_admin_ad_dis_level_idx() {
        return $this->page_data = $this->t__bra_table_idx("dis_level");
    }

    public function bra_admin_admin_ad_dis_level_add($query) {
        return $this->page_data = $this->t__add_iframe("dis_level");
    }

    public function bra_admin_admin_ad_dis_level_edit($query) {
        return $this->page_data = $this->t__edit_iframe((int)$query['id'], "dis_level");
    }

    public function bra_admin_admin_ad_dis_level_del($query) {
        return $this->page_data = $this->t__del($query['id'], "dis_level");
    }

    public function bra_admin_admin_ad_dis_apply_idx() {
        return $this->page_data = $this->t__bra_table_idx("dis_apply");
    }

    public function bra_admin_admin_ad_poster_idx() {
        return $this->page_data = $this->t__bra_table_idx("poster");
    }

    public function bra_admin_admin_ad_poster_add() {
        return $this->page_data = $this->t__add_iframe('poster');
    }

    public function bra_admin_admin_ad_poster_edit($query) {
        return $this->page_data = $this->t__edit_iframe($query['id'], "poster");
    }

    public function bra_admin_admin_ad_setting($query) {
        global $_W, $_GPC;
        $id = $query['id'];
        $model = D("poster");
        $detail = $model->get_item($id, true, ['update' => true]);
        if (is_bra_access()) {
            $res = $model->bra_where($id)->update(['poster_data' => $query['data']]);
            if ($res) {
                return $this->page_data = bra_res(1, "ok ", "''", '', "'reload_page()'");
            } else {
                return $this->page_data = bra_res(2, "error");
            }
        } else {
            $assign['field_list'] = $field_list = $model->get_admin_publish_fields($detail['old_data'], []);
            $detail['data'] = json_decode($detail['data'], 1);
            $assign['detail'] = $detail;
            A($assign);

            return $this->page_data = T();
        }
    }
}
