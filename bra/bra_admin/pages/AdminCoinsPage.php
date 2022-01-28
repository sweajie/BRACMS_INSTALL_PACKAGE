<?php

namespace Bra\bra_admin\pages;

use Bra\core\pages\BraAdminController;

class AdminCoinsPage extends BraAdminController {
    public function BRA_ADMIN_ADMIN_COINS_COINS_IDX($query) {
        $coins = config('bra_coin');

        if (!is_bra_access(0, 'post')) {
            foreach ($coins as $coin) {
                D("coins")->updateOrInsert(['id' => $coin['id']] , ['field' => $coin['field'] , 'title' => $coin['title']]);
            }
        }
        $extra['id'] = ['IN', collect($coins)->keys()->all()];
        return $this->page_data = $this->t__bra_table_idx("coins", $extra);
    }

    public function BRA_ADMIN_ADMIN_COINS_coin_edit($query) {
        return $this->page_data = $this->t__edit_iframe($query['id'] , "coins");
    }

}
