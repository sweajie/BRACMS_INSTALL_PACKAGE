<?php
// +----------------------------------------------------------------------
// | 鸣鹤CMS [ New Better  ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2017 http://www.bracms.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( 您必须获取授权才能进行使用 )
// +----------------------------------------------------------------------
// | Author: new better <1620298436@qq.com>
// +----------------------------------------------------------------------
namespace Bra\bra\pages;


use Bra\core\utils\BraCoordinate;
use Bra\core\pages\BraController;
use Bra\core\utils\BraArea;

class AreaPage extends BraController {
	use BraArea;
	// lng lat to address
	public function bra_area_lng_lat_area ($query) {
		$coordtype = $query['coordtype'];
		$lng = $query['lng'];
		$lat = $query['lat'];
		$data = BraCoordinate::t__lng_lat_2_address($lng, $lat, $coordtype);
		$addressComponent = $data['result']['addressComponent'];

		return $this->page_data = BraArea::st__detect_area($addressComponent);
	}

	public function bra_area_change_area ($module = '') {
		global $_W, $_GPC;
		$donde['parent_id'] = ['>' , 0];
		$donde['has_child'] = 1;
		A( 'areas' , self::load_top_area($donde , "arrchild=id"));
//		A('tree_areas', D('area')->load_tree());
		$els['wx_num'] = "bracmf";
		$this->page_data = $els;
			A($this->page_data);
		return T();
	}

}
