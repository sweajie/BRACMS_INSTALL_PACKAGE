<?php

namespace Bra\bra_app\pages;

use Bra\core\pages\BraAdminController;

class AdminBraAppPage extends BraAdminController {
    public function bra_app_admin_bra_app_app_idx($query) {
        return $this->page_data = $this->t__bra_table_idx('app');
    }

    public function bra_app_admin_bra_app_app_add($query) {
        return $this->page_data = $this->t__add_iframe('app');
    }

    public function bra_app_admin_bra_app_app_edit($query) {
        return $this->page_data = $this->t__edit_iframe($query['id'] , 'app');
    }

    public function bra_app_admin_bra_app_fans_idx($query) {
        return $this->page_data = $this->t__bra_table_idx("app_fans");
    }
}