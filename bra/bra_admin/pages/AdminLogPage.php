<?php

namespace Bra\bra_admin\pages;

use Bra\core\pages\BraAdminController;
use Bra\core\utils\BraAdminItemPage;

class AdminLogPage extends BraAdminController {
    use BraAdminItemPage;
    public function bra_admin_admin_log_access_log_idx () {
        return $this->page_data = $this->t__bra_table_idx("log");
    }

    public function bra_admin_admin_log_sms_log_idx () {

        return $this->page_data = $this->t__bra_table_idx("sms_notice_log");
    }
}
