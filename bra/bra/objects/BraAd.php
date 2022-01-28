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
namespace Bra\bra\objects;

class BraAd {
	public $group_id;

	public function __construct ($group_id) {
		$this->group_id = $group_id;
	}

	public function render ($ext = []) {
		$where = $ext;
		$where['group_id'] = $this->group_id;
		$where['status'] = 99;
		$config = [
			"list_fields" => ['id', 'link_web', 'image', 'title'],
			"show_old_data" => false,
			"show_full_annex" => false
		];
		$ads = D('ad_item')->with_site()->bra_where($where)->orderBy('listorder' , 'desc')->list_item(true, $config);

		return $ads;
	}
}
