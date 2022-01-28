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


class BraTea
{
    protected $rootId = 0;
    protected $idKey;
    protected $nameKey;
    protected $parentKey;

    protected $nodes;

    public function __construct($data, $idKey = 'id', $nameKey = 'title', $parentKey = 'parent_id')
    {
        $this->nodes = $data;
        foreach (['idKey', 'nameKey', 'parentKey'] as $k) {
            $this->set($k, $$k);
        }
    }

    public function set($k, $val)
    {
        $this->$k = $val;
    }

    public function build($pid)
    {
        return $this->build_tree($pid);
    }

    public function build_tree($pid = 0)
    {
        $menus = [];
        foreach ($this->nodes as $k => $item) {
            if (isset($item[$this->parentKey]) && $item[$this->parentKey] == $pid) {
                $item['title'] = $item[$this->nameKey];
                $menus[] = $item;
                unset($this->nodes[$k]);
            }
        }

        foreach ($menus as $key => &$item) {
            // 多语言
            $item['id'] = $item[$this->idKey];
            $item['name'] = $item[$this->nameKey];
            $item['children'] = $this->build_tree($item[$this->idKey]);
        }
        return $menus;
    }

    public function build_cached($pid)
    {
        $this->nodes = BraArray::reform_arr($this->nodes, 'id');
        return $this->build_tree_cached($pid);
    }

    public function build_tree_cached($pid = 0, $sub_ids = [])
    {
        $menus = [];
        if ($sub_ids) {

            foreach ($sub_ids as $sub_id) {
                if (isset($this->nodes[$sub_id])) {
                    $item = $this->nodes[$sub_id];
                    if (isset($item[$this->parentKey]) && $item[$this->parentKey] == $pid) {
                        $menus[] = $this->nodes[$sub_id];
                    }
                }
            }
        } else {
            foreach ($this->nodes as $k => $item) {
                if (isset($item[$this->parentKey]) && $item[$this->parentKey] == $pid) {
                    $menus[] = $item;
                    unset($this->nodes[$k]);
                }
            }
        }

        //throw  new BraException("1");

        foreach ($menus as $key => &$item) {
            // 多语言
            $item['id'] = $item[$this->idKey];
            $item['name'] = $item[$this->nameKey];


            unset($item['site_id']);
            unset($item['area_name']);
            unset($item['listorder']);
            unset($item['has_child']);
            unset($item['lat']);
            unset($item['lng']);
            unset($item['map']);
            unset($item['name']);
            unset($item['content']);
            if($item['parent_id'] != 0){
                unset($item['is_hot']);
                unset($item['letter']);
            }

            unset($item['parent_id']);

            $sub_ids = explode(',', $item['arrchild']);
            array_shift($sub_ids);

            if ($sub_ids) {
                $item['children'] = $this->build_tree_cached($item[$this->idKey], $sub_ids);
                unset($item['arrchild']);
            }else{
                unset($item['arrchild']);
            }
        }
        return $menus;
    }

    public function build_cached_rendered($pid)
    {
        $this->nodes = BraArray::reform_arr($this->nodes, 'id');
        return $this->build_tree_cached_rendered($pid);
    }
    public function build_tree_cached_rendered($pid = 0, $sub_ids = [])
    {
        $menus = [];
        if ($sub_ids) {

            foreach ($sub_ids as $sub_id) {
                if (isset($this->nodes[$sub_id])) {
                    $item = $this->nodes[$sub_id];
                    if (isset($item['old_data'][$this->parentKey]) && $item['old_data'][$this->parentKey] == $pid) {
                        $menus[] = $this->nodes[$sub_id];
                    }
                }
            }
        } else {
            foreach ($this->nodes as $k => $item) {
                if (isset($item['old_data'][$this->parentKey]) && $item['old_data'][$this->parentKey] == $pid) {
                    $menus[] = $item;
                    unset($this->nodes[$k]);
                }
            }
        }

        //throw  new BraException("1");

        foreach ($menus as $key => &$item) {
            // 多语言
            $item['id'] = $item[$this->idKey];
            $item['name'] = $item[$this->nameKey];


            unset($item['site_id']);
            unset($item['area_name']);
            unset($item['listorder']);
            unset($item['has_child']);
            unset($item['lat']);
            unset($item['lng']);
            unset($item['map']);
            unset($item['name']);
            unset($item['content']);
            if($item['parent_id'] != 0){
                unset($item['is_hot']);
                unset($item['letter']);
            }

            unset($item['parent_id']);

            $sub_ids = explode(',', $item['arrchild']);
            array_shift($sub_ids);

            if ($sub_ids) {
                $item['children'] = $this->build_tree_cached_rendered($item[$this->idKey], $sub_ids);
                unset($item['arrchild']);
            }else{
                unset($item['arrchild']);
            }
        }
        return $menus;
    }

}

