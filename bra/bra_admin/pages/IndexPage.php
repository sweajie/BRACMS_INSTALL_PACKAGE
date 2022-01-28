<?php

namespace Bra\bra_admin\pages;

use Bra\core\pages\BraAdminController;

class IndexPage extends BraAdminController {

    public function bra_admin_index_index ($query) {
        $this->page_data = A_T();
    }

}
