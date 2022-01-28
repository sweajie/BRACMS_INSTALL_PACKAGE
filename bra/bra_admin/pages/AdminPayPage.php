<?php

namespace Bra\bra_admin\pages;

use Bra\core\objects\BraExtWallet;
use Bra\core\pages\BraAdminController;
use Bra\core\utils\BraAdminItemPage;
use Bra\core\utils\mach\BraSubMach;
use Illuminate\Support\Facades\DB;

class AdminPayPage extends BraAdminController {

    use BraAdminItemPage;

    public function bra_admin_admin_pay_payment_idx () {
        return $this->page_data = $this->t__bra_table_idx('pay_way');
    }

    public function bra_admin_admin_pay_config_way ($query) {
        global $_W;
        $id = $query['id'];
        $pay_way = (array)D('pay_way')->find($id);
        $pay_way_config = D('pay_way_config' , '' , true);
        //get config
        $where = ['pay_way_id' => $id, 'site_id' => $_W['site']['id']];
        $config = (array)$pay_way_config->bra_where($where)->first();
        if (is_bra_access(0, 'post')) {
            $config_keys = $query['data'];
            $data_config = $where;

            if ($pay_way['sign'] == 'bra_micropay') {
                $mach = new BraSubMach($config_keys);
                $config_keys = array_merge($config_keys, $mach->get_cert());
            }



            $data_config['config'] = json_encode($config_keys);


            if ($config) {

                return $this->page_data = $pay_way_config->item_edit($data_config, $where);
            } else {
                return $this->page_data = $pay_way_config->item_add($data_config);
            }
        } else {
            if ($config) {
                $pay_cofig = json_decode($config['config'], 1);
            } else {
                $pay_cofig = [];
            }
            A('pay_config', $pay_cofig);
            A('detail', $config);

            return $this->page_data = A_T("bra_admin.admin_pay.pay_" . $pay_way['sign']);
        }
    }

    public function bra_admin_admin_sms_set_default_provider ($provider_id) {
        global $_W;
        $donde = [];
        $donde['site_id'] = $_W['site']['id'];
        $update = [];
        $update['default'] = 0;
        D('sms_provider_config')->bra_where($donde)->update($update);
        $donde['provider_id'] = $provider_id;
        $update['default'] = 1;
        $res = D('sms_provider_config')->bra_where($donde)->update($update);

        return bra_res(1, 'ok');
    }

	public function bra_admin_admin_pay_bank_idx() {
		return $this->page_data = $this->t__bra_table_idx('bank');
	}

	public function bra_admin_admin_pay_bank_add() {
		return $this->page_data = $this->t__add_iframe('bank');
	}

	public function bra_admin_admin_pay_bank_edit($query) {
		return $this->page_data = $this->t__edit_iframe($query['id'], 'bank');
	}

	public function bra_admin_admin_pay_bank_del($query) {
		return $this->page_data = $this->t__del($query['id'], 'bank');
	}


	public function bra_admin_admin_pay_deposit_pass($query) {
		global $_W , $_GPC;
		$detail = D('pay_deposit')->bra_one($query['id']);
		if ($detail['status'] == 1) {
			DB::beginTransaction();

			$res = D('pay_deposit')->item_edit(['status' => 99 , 'admin_uid' => $_W['admin']['id']], $query['id']);
			if (!is_error($res)) {
				$wallet = new BraExtWallet($detail['user_id']);
				$res = $wallet->ex_deposit($detail['amount'], $detail['coin_id'], 2, '离线充值');
				if (!is_error($res)) {
					DB::commit();
					return $this->page_data = $res;
				}
			}
			DB::rollBack();
			return $this->page_data = $res;
		} else {
			return $this->page_data = bra_res(500, "读不起, 不是待审核状态!");
		}
	}

	public function bra_admin_admin_pay_deposit_deny($query) {

		$detail = D('pay_deposit')->bra_one($query['id']);
		if ($detail['status'] == 1) {
			DB::beginTransaction();

			$res = D('pay_deposit')->item_edit(['status' => 2], $query['id']);
			if (!is_error($res)) {
				DB::commit();
				return $this->page_data = $res;
			}
			DB::rollBack();
			return $this->page_data = $res;
		} else {
			return $this->page_data = bra_res(500, "读不起, 不是待审核状态!");
		}
	}
}
