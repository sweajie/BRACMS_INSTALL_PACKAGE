<?php

namespace Bra\bra_admin\pages;

use Bra\core\pages\BraAdminController;
use Bra\core\utils\BraAdminItemPage;

class AdminOrdersPage extends BraAdminController {

    use BraAdminItemPage;
    public function bra_admin_admin_orders_index () {

        return $this->page_data = $this->t__bra_table_idx("orders");
    }

    public function bra_admin_admin_user_offline_deposit () {

    }
}
