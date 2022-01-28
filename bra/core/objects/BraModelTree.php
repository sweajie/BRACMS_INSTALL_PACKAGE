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
namespace Bra\core\objects;


class BraModelTree {
	public $bra_m;
	public $id_key = 'id';
	public $name_key = 'title';
	public $parent_key = 'parent_id';
	public $queue = [];

	public function __construct ($model_id, $parent_id = 0, $idKey = false, $nameKey = false, $parentKey = false) {
		$this->bra_m = D($model_id);
		if ($idKey) {
			$this->id_key = $idKey;
		}
		if ($nameKey) {
			$this->name_key = $nameKey;
		}
		if ($parentKey) {
			$this->parent_key = $parentKey;
		}
		if ($parent_id) {
			$this->queue[$parent_id] = '';
		}
	}

	public function build ($parent_id) {
		$this->queue[$parent_id] = '';
		$this->execute();
	}

	/**
	 * update queue's tree data
	 */
	public function execute () {
		if (!empty($this->queue)) {
			foreach ($this->queue as $id => $v) {
				$ids = $this->get_child_ids($id, $id);
				$update = [];
				$update['arrchild'] = join(',', $ids);
				$update['has_child'] = (int)count($ids) > 1;
				$this->bra_m->item_edit($update, $id);
				unset($this->queue[$id]);
			}
			$this->execute();
		}
	}

	public function get_child_ids ($pid, $pined_pid) {
		static $ids = [];
		if (empty($ids[$pined_pid])) {
			$ids[$pined_pid][] = $pined_pid;
		}
		foreach ($this->get_children($pid) as $node) {
			$ids[$pined_pid][] = $node[$this->id_key];
			$this->get_child_ids($node[$this->id_key], $pined_pid);
		}

		return $ids[$pined_pid];
	}

	public function get_children ($pid) {
		$nodes = $this->bra_m->bra_where([$this->parent_key => $pid])->select();
		foreach ($nodes as $node) {
			$this->queue[$node['id']] = '';
			yield $node;
		}
	}

    public function load_all ($pid , $where) {
        global $_W;
        $order = $this->bra_m->field_exits('listorder') ? 'listorder desc' : 'id desc';
        $lists = $this->bra_m->bra_where($where)->order($order)->list_item();
        $t = new BraTea($lists);
        return $t->build_cached($pid );
    }

    public function load_all_rendered ($pid , $where) {
        global $_W;
        $order = $this->bra_m->field_exits('listorder') ? 'listorder desc' : 'id desc';
        $lists = $this->bra_m->bra_where($where)->order($order)->list_item(true , ['with_old' => true]);

        $t = new BraTea($lists);
        return $t->build_cached_rendered($pid );
    }
	/**
	 * get tree data
	 * @param int $pid
	 * @param array $where
	 */

    public function load_tree_render($pid = 0 , $where = []) {
        return $this->load_all_rendered($pid ,$where);
	}
	public function load_tree ($pid = 0 , $where = []) {
		if($pid == 0){
			return $this->load_all($pid ,$where);
		}
		$order = "listorder desc";
		if ($this->bra_m->field_exits('listorder')) {
			$order = "listorder desc , id desc";
		}
		$donde = [];
		if (is_array($pid)) {
			$donde[$this->parent_key] = ['IN', $pid];
		} else {
			if ($pid <= 0) {
				$donde[$this->parent_key] = 0;
			} else {
				$donde[$this->id_key] = $pid;
			}
		}

		$donde = array_merge($where , $donde);
		$top_nodes = $this->bra_m->bra_where($donde)->order($order)->list_item(true);

		foreach ($top_nodes as &$top_node) {
			$donde = ['id' => ['IN', $top_node['arrchild']]];
			$donde = array_merge($where , $donde);
			$this->nodes = $this->bra_m->bra_where($donde)->list_item(true);
			$top_node['children'] = $this->build_cached($top_node['id']);
		}

		return $top_nodes;
	}

	public function build_cached ($pid) {
		$this->nodes = BraArray::reform_arr($this->nodes, 'id');

		return $this->build_tree_cached($pid);
	}

	public function build_tree_cached ($pid = 0, $sub_ids = []) {
		static $menus = [];
		if ($sub_ids) {
			foreach ($sub_ids as $sub_id) {
				if (isset($this->nodes[$sub_id])) {
					$item = $this->nodes[$sub_id];
					if (isset($item[$this->parent_key]) && $item[$this->parent_key] == $pid) {
						$menus[] = $this->nodes[$sub_id];
					}
				}
			}
		} else {
			foreach ($this->nodes as $k => $item) {
				if (isset($item[$this->parent_key]) && $item['old_data'][$this->parent_key] == $pid) {
					$menus[] = $item;
					unset($this->nodes[$k]);
				}
			}
		}
		//throw  new BraException("1");
		foreach ($menus as $key => &$item) {
			// 多语言
			$item['id'] = $item[$this->id_key];
			$item['name'] = $item[$this->name_key];
			unset($item['site_id']);
			unset($item['area_name']);
			unset($item['listorder']);
			unset($item['has_child']);
			unset($item['lat']);
			unset($item['lng']);
			unset($item['map']);
			unset($item['name']);
			unset($item['thumb']);
			if ($item['parent_id'] != 0) {
				unset($item['is_hot']);
				unset($item['letter']);
			}
			unset($item['parent_id']);
			$sub_ids = explode(',', $item['arrchild']);
			array_shift($sub_ids);
			if ($sub_ids) {
				$item['children'] = $this->build_tree_cached($item[$this->id_key], $sub_ids);
				unset($item['arrchild']);
			} else {
				unset($item['arrchild']);
			}
		}

		return $menus;
	}
}
