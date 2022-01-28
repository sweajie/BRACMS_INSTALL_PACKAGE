<?php

namespace Bra\bra_admin\pages;

use Bra\core\facades\BraCache;
use Bra\core\pages\BraAdminController;
use Bra\core\utils\BraAdminItemPage;

class AdminBraPage extends BraAdminController {

    use BraAdminItemPage;

	public function bra_admin_admin_bra_module_setting($query) {

		return $this->page_data = $this->t__module_setting($query['module'] , $query);
	}

    public function bra_admin_admin_bra_icon_idx () {
        return $this->page_data = $this->t__bra_table_idx('icon');
    }

    public function bra_admin_admin_bra_icon_add () {
        return $this->page_data = $this->t__add_iframe('icon');
    }

    public function bra_admin_admin_bra_seo_tpl_idx () {
        return $this->page_data = $this->t__bra_table_idx('seo');
    }

    public function bra_admin_admin_bra_seo_edit ($query) {
        return $this->page_data = $this->t__edit_iframe($query['id'], 'seo');
    }

    public function bra_admin_admin_bra_nav_idx () {
        return $this->page_data = $this->t__bra_table_idx('nav');
    }

    public function bra_admin_admin_bra_nav_add () {
        return $this->page_data = $this->t__add_iframe('nav');
    }

    public function bra_admin_admin_bra_module_notice () {
        return $this->page_data = $this->t__bra_table_idx('module_notice');
    }

    public function bra_admin_admin_bra_icon_del ($query) {
        return $this->page_data = $this->t__del($query['id'], 'icon');
    }

    public function bra_admin_admin_bra_icon_edit ($query) {
        return $this->page_data = $this->t__edit_iframe($query['id'], 'icon');
    }

    public function bra_admin_admin_bra_site_config ($query) {
        global $_W;
        $res = $this->page_data = $this->t__field_config($_W['site']['id'], 'sites', 'config', $query);
        BraCache::del_cache('sites_' . $_W['site']['id']);

        return $res;
    }

    public function bra_admin_admin_bra_nav_edit ($query) {
        return $this->page_data = $this->t__edit_iframe($query['id'], 'nav');
    }

    public function bra_admin_admin_bra_dis_user_idx () {
        global $_W, $_GPC;
        $config = [
            'show' => ['id', 'user_name', 'nickname', 'dis_level_id', 'dis_status']
        ];
        $ext = [];
        $ext['dis_status'] = 99;
        $config['admin_columns'] = ['dis_create_at'];

        return $this->page_data = $this->t__bra_table_idx("users", $ext, $config);
    }


    public function bra_admin_admin_bra_share_idx () {

        return $this->page_data = $this->t__bra_table_idx("share");
    }

	public function bra_admin_admin_bra_config($query) {
		if($query['title'] != 'bra_dis'){
			return $this->page_data = $this->t__editor_config(' - ', $query['title'], 'bra');
		}else{

			return $this->page_data = $this->t__config("分销配置", 'bra_dis', 'bra_admin');
		}

	}

	public function BRA_ADMIN_ADMIN_BRA_MEMBER_CONFIG ($query) {
		return $this->page_data = $this->t__config("会员配置", 'member_config', 'bra_admin');
	}

	public function BRA_ADMIN_ADMIN_BRA_WALLET_CONFIG($query) {
		return $this->page_data = $this->t__config("财务配置", 'wallet_config', 'bra_admin');
	}
}
