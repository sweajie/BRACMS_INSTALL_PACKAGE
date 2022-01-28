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

/**
 * 通用的树型类，可以生成任何树型结构
 */
class BraTree {
    public $data_set = array();
    public $parent_key = 'parent_id';
    public $id_key = 'id';
    public $name_key = 'name';
    public $icon = array('│', '├', '└');
    public $nbsp = "&nbsp;";
    public $ret = '';
    public $is_parent = false;

    public function __construct ($data_set, $icon = array('   │ ', '   ├─ ', '   └─ '), $nbsp = "&nbsp;", $field_name = '--') {
        if ($field_name) {
            $this->field_name = $field_name;
        }
        if (is_array($data_set)) {
            foreach ($data_set as &$set) {
                if (!isset($set['parent_id'])) {
                    $this->is_parent = false;
                } else {
                    $this->is_parent = true;
                }
                break;
            }
            $this->data_set = $data_set;
            $this->nbsp = $nbsp;
            $icon = $icon ? $icon : array('   │ ', '   ├─ ', '   └─ ');
            $this->icon = $icon;
        } else {
            dd($field_name);
        }
    }

    public static function cache_sub_list ($model_id, $pid = 0) {
        $model = D($model_id)->with_site();
        $letter = false;
        if ($model->field_exits("letter")) {
            $letter = true;
        }
        $all_datas = $model->orderBy('listorder', 'desc')->get()->toArray();

        $fix_update = [];
        $fix_update['parent_id'] = 0;
        foreach ($all_datas as $k => $r) {
            $r = (array)$r;
            $fix = false;
            if ($r['parent_id']) {
                $parent = (array)$model->find($r['parent_id']);
                if ($r['id'] == $r['parent_id']) {
                    $fix = true;
                }
                if ($parent['parent_id'] == $r['id']) {
                    $fix = true;
                }
                if ($fix) {
                    $r['parent_id'] = 0;
                    $model->bra_where(['id' => $r['id']])->update($fix_update);
                }
            }
        }
        foreach ($all_datas as $item) {
            if ($pid && $item['id'] != $pid) {
                continue;
            } else {
                $item = (array)$item;
                $arrchild = self::get_tree_child_arr_ids($item['id'], $all_datas);
                $update = ['has_child' => !(int)is_numeric($arrchild), 'arrchild' => $arrchild];
                if ($letter) {
                    $update['letter'] = BraString::get_cn_first_char($item['title']);
                }
                $model->item_edit($update, $item['id']);
            }
        }

        return $arrchild;
    }

    public static function get_tree_child_arr_ids ($id, $datas, $pk_key = "id", $parent_id_key = "parent_id") {
        static $i = 0, $pids = [];
        $arrchildid = $id;
        if (is_array($datas)) {
            foreach ($datas as $k => $data) {
                $data = (array)$data;
                //!in_array($data['id'] , $pids) && 贪吃蛇
                if ($data[$parent_id_key] && $data[$pk_key] != $id && $data[$parent_id_key] == $id) {
                    $pids[] = $data['id'];
                    $arrchildid .= ',' . self::get_tree_child_arr_ids($data[$pk_key], $datas, $pk_key, $parent_id_key);
                }
            }
        }

        return $arrchildid;
    }

    public static function get_cate_tree ($pid, $data_all, $module, $pk_key = "id", $name_key = "cate_name", $parent_id_key = "parent_id", $icon_key = "icon") {
        $menus = [];
        if (is_array($data_all)) {
            foreach ($data_all as $item) {
                if (isset($item[$parent_id_key]) && $item[$parent_id_key] == $pid) {
                    $item['title'] = $item[$name_key];
                    $menus[] = $item;
                }
            }
        }
        foreach ($menus as $key => &$item) {
            // 多语言
            $item['id'] = $item[$pk_key];
            $item['name'] = $item[$name_key];
            unset($item['arrchild']);
            unset($item['site_id']);
            unset($item['listorder']);
            unset($item['has_child']);
            unset($item['lat']);
            unset($item['lat']);
            unset($item['map']);
            if (isset($item[$icon_key])) {
                $item['icon'] = $item[$icon_key];
            }
            if (strpos(ROUTE_C, 'app') === 0) {
            } else {
                $item['target'] = "sub_frame";
                if ($module) {
                    $item['url'] = self::get_cate_front_url($item, $module);
                }
            }
            $item['children'] = self::get_cate_tree($item[$pk_key], $data_all, $module, $pk_key, $name_key);
        }

        return $menus;
    }

    public static function get_cate_front_url ($cate, $module) {
        $url = "";
        if (isset($cate['cate_type'])) {
            switch ($cate['cate_type']) {
                case 1 :
                case 2:
                    $url = make_url($module . "/content/cate", ['cate_id' => $cate['id']]);
                    break;
                case 3:
                    $url = $cate['link_url'];
                    break;
                default :
                    $url = make_url($module . "/content/cate", ['cate_id' => $cate['id']]);
            }
        }

        return $url;
    }

    public function get_bra_tree ($parent_id, $tree_str, $selected_id = '', $prefix = '', $str_group = '') {
        $number = 1;
        $children = $this->get_children($parent_id);
        if (is_array($children)) {
            $total = count($children);
            foreach ($children as $id => $child) {
                $j = $k = '';
                if ($number == $total) {
                    $j .= $this->icon[2];
                } else {
                    $j .= $this->icon[1];
                    $k = $prefix ? $this->icon[0] : '';
                }
                $child['spacer'] = $prefix ? $prefix . $j : '';
                $child['selected'] = BraString::bra_isset($selected_id) && $id == $selected_id ? 'selected' : '';
                if ($child['selected'] == 'selected') {
                }
                $this->ret .= BraString::parse_param_str($tree_str, $child);
                $nbsp = $this->nbsp;
                $this->get_bra_tree($id, $tree_str, $selected_id, $prefix . $k . $nbsp, $str_group);
                $number++;
            }
        }

        return $this->ret;
    }

    public function get_children ($parent_id) {
        $new_arr = array();
        if ($this->is_parent) {
            foreach ($this->data_set as $id => $child) {
                if ($child[$this->parent_key] == $parent_id) {
                    $new_arr[$id] = $child;
                }
            }
        } else {
            if ($parent_id == 0) {
                static $no_parents;
                if (isset($no_parents[$this->field_name])) {
                    $new_arr = false;
                } else {
                    $no_parent = [];
                    foreach ($this->data_set as $id => $child) {
                        $no_parent[$id] = $child;
                    }
                    $new_arr = $no_parents[$this->field_name] = $no_parent;
                }
            } else {
                $new_arr = false;
            }
        }
        if (is_array($new_arr)) {
            $new_ret = [];
            foreach ($new_arr as $n => $v) {
                $new_ret[$v[$this->id_key]] = $v;
            }

            return $new_ret;
        } else {
            return false;
        }
    }

    public function get_tree ($parent_id, $tree_str, $selected_id = 0, $prefix = '', $str_group = '') {
        $number = 1;
        $children = $this->get_children($parent_id);
        if (is_array($children)) {
            $total = count($children);
            foreach ($children as $id => $child) {
                $j = $k = '';
                if ($number == $total) {
                    $j .= $this->icon[2];
                } else {
                    $j .= $this->icon[1];
                    $k = $prefix ? $this->icon[0] : '';
                }
                $spacer = $prefix ? $prefix . $j : '';
                $selected = $id == $selected_id ? 'selected' : '';
                @extract($child);
                $nstr = "";
                if ($parent_id == 0 && $str_group) {
                    eval("\$nstr = \"$str_group\";");
                } else {
                    eval("\$nstr = \"$tree_str\";");
                }
                $this->ret .= $nstr;
                $nbsp = $this->nbsp;
                $this->get_tree($id, $tree_str, $selected_id, $prefix . $k . $nbsp, $str_group);
                $number++;
            }
        }

        return $this->ret;
    }

    public function get_tree_multi ($parent_id, $tree_str, $selected_id = 0, $prefix = '') {
        $number = 1;
        $child = $this->get_children($parent_id);
        if (is_array($child)) {
            $total = count($child);
            foreach ($child as $id => $a) {
                $j = $k = '';
                if ($number == $total) {
                    $j .= $this->icon[2];
                } else {
                    $j .= $this->icon[1];
                    $k = $prefix ? $this->icon[0] : '';
                }
                $nstr = "";
                $spacer = $prefix ? $prefix . $j : '';
                $selected = $this->have($selected_id, $id) ? 'selected' : '';
                @extract($a);
                eval("\$nstr = \"$tree_str\";");
                $this->ret .= $nstr;
                $this->get_tree_multi($id, $tree_str, $selected_id, $prefix . $k . '&nbsp;');
                $number++;
            }
        }

        return $this->ret;
    }

    private function have ($list, $item) {
        return (strpos(',,' . $list . ',', ',' . $item . ','));
    }

}
