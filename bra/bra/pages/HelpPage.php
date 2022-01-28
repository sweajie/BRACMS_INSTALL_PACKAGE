<?php

namespace Bra\bra\pages;

use App\Models\User;
use Bra\core\facades\BraCache;
use Bra\core\objects\BraArray;
use Bra\core\objects\BraExtWallet;
use Bra\core\objects\BraNode;
use Bra\core\objects\BraNotice;
use Bra\core\objects\BraString;
use Bra\core\pages\BraController;
use Bra\core\utils\BraDis;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HelpPage extends BraController {



	public function bra_help_index($query) {
		global $_W;
		$data = [];
		$act_m = D('help');
		if (is_bra_access(0)) {
			$query['__simple'] = true;
			$query['status'] = 99;
			$query['title'] = $query['keyword'];
			$query['order'] = "listorder desc";
			$query['bra_int_fields'] = ['cate_id', 'status'];
			$query['input_like_fields'] = ['title'];
			$ret = $act_m->list_bra_resource($query);
			$this->page_data['list'] = $ret;
		} else {
			if ($query['ad_gid']) {
				$data['ads'] = render_ad_group($query['ad_gid']);
			}
			$data['cate_ids'] = $act_m->load_options('cate_id');
			$data['user'] = $_W['user'];
			$this->page_data = $data;
		}
	}

	public function bra_help_detail($query) {
		global $_W;
		$data = [];

		if (is_bra_access(0, 'post')) {
			switch ($query['action']) {
				case 'update_length':
					$donde['item_id'] = $query['item_id'];
					$donde['model_id'] = D('help')->_TM['id'];
					$donde['date'] = date('Y-m-d');
					$extra['seconds'] = $query['amount'];
					$res = D('users_view_length')->bra_foc($donde, $extra);
					if (!is_error($res) && $res['data']) {
						$this->page_data['res'] = D('users_view_length')->bra_where(['id' => $res['data']['id']])->increment('seconds', $query['amount']);
					} else {
						$this->page_data['res'] = $res;
					}
					break;
				default :
			}
		} else {
			$node = new BraNode($query['id'], 'help');
			$node->hit();
			$data['detail'] = $node->get_node();
			$data['detail']['hits'] = $node->get_hit();
			$this->page_data = $data;
		}
	}
}
