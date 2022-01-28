<?php

namespace Bra\bra_admin\pages;

use Bra\core\objects\BraString;
use Bra\core\pages\BraAdminController;
use Bra\core\utils\BraAdminItemPage;

class AdminSearchPage extends BraAdminController{

    use BraAdminItemPage;
    public function bra_admin_admin_search_model_idx () {
        global $_W, $_GPC;
        $ext['is_index'] = BraString::bra_isset($_GPC['is_index']) ? $_GPC['is_index'] : 1;

        return $this->page_data = $this->t__bra_table_idx("models", $ext);
    }
}
