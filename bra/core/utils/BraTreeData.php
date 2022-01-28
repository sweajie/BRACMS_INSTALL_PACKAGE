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
namespace Bra\core\utils;

use Bra\core\objects\BraString;


class BraTreeData {

	public $tree;

	public function __construct ($tree) {
		$this->tree = $tree;
	}

	public function get_node_path ($leaf_id, $id_key = 'id') {
		foreach ($this->tree as $form_data) {
			$path = [];
			$path[] = $form_data['id'];
			if ($leaf_id == $form_data['id']) {
				return $path;
			}
			$path = self::get_children_path($leaf_id, $form_data['children'], $id_key, $path);
			if ($path) {
				break;
			}
		}
		return $path;
	}

	public static function get_children_path ($leaf_id, $children, $id_key = 'id', $path = []) {
		foreach ($children as $child) {
			if (strpos($child['old_data']['arrchild'], $leaf_id) !== false) {
				array_unshift($path, $child['id']);
				if ($leaf_id != $child[$id_key]) {
					if (!empty($child['children'])) {
						return self::get_children_path($leaf_id, $child['children'], $id_key, $path);
					}
				} else {
					return $path;
				}
			}
		}
		return $path;
	}

}
