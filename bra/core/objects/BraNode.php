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

use Bra\core\utils\BraException;
use Illuminate\Support\Facades\Log;

class BraNode {
    public $bra_m;
    public $model_id;
    public $item_id;
    public $node;
    public $hit;
    public $update;
    public $in_list;

    public function __construct($item_id, $model_id, $in_list = false) {
        if ((preg_match('/([^a-z0-9_\-]+)/i', $model_id))) end_resp(bra_res('500', '非法数据mid'));
        if ((preg_match('/([^a-z0-9_\-]+)/i', $item_id))) end_resp(bra_res('500', '非法数据item_id'));
        $this->bra_m = D($model_id);
        $this->item_id = $item_id;
        $this->model_id =  $this->bra_m->_TM['id'];
        $this->in_list = $in_list;
    }

    /**
     * 点一下 views
     * 赞一下 zan
     * 评论 comment
     * up comment
     */
    public function hit($type = 'views') {
        global $_W;
        $SYS_TIME = time();
        $update_date = date("Y-m-d H:i:s");;
        $hit_m = D('hits');
        $hit = $this->get_hit();

        if ($type == 'views') {
            $update_at = strtotime($hit['update_at']);
            $views = isset($hit['views']) && $hit['views'] ? $hit['views'] + 1 : 1;
            $yesterdayviews = (date('Ymd', $update_at) == date('Ymd', strtotime('-1 day'))) ? $hit['today'] : $hit['yesterday'];
            $dayviews = (date('Ymd', $update_at) == date('Ymd', $SYS_TIME)) ? ($hit['today'] + 1) : 1;
            $weekviews = (date('YW', $update_at) == date('YW', $SYS_TIME)) ? ($hit['week'] + 1) : 1;
            $monthviews = (date('Ym', $update_at) == date('Ym', $SYS_TIME)) ? ($hit['month'] + 1) : 1;
            $sql_data = array('views' => $views, 'yesterday' => $yesterdayviews, 'today' => $dayviews, 'week' => $weekviews, 'month' => $monthviews, 'update_at' => $update_date);
            $res = $hit_m->item_edit($sql_data, $hit['id'], true, false);

            $hit = $res['data'];
            if ($this->bra_m->field_exits('views')) {
                $this->save(['views' => $views]);
            }
        }
        if ($type == 'zan') {
            $hits_like_m = D('hits_likes');
            if (!$_W['user']) {
                return bra_res(4041, lang('need login'));
            }
            $where_zan = array('hits_id' => $hit['id']);
            $zan_test = D("hits_likes")->with_user()->bra_one($where_zan);
            if (!$zan_test) {
                //点赞目标
                $insert_zan['user_id'] = $_W['user']['id'];
                $insert_zan['hits_id'] = $hit['id'];
                $hits_like_m->item_add($insert_zan);
                $sql_data['likes'] = $hit['likes'] + 1;
                $is_good = true;
            } else {
                $hits_like_m->bra_where(['id' => $zan_test['id']])->delete();
                //攒过  取消点赞
                $sql_data['likes'] = $hit['likes'] - 1;
                $is_good = false;
            }
            $res = $hit_m->item_edit($sql_data, $hit['id'], true, false);
            $hit = $res['data'];
            $hit['is_good'] = $is_good;
        }
        if ($type == 'comment') {
            $where = [];
            $where['item_id'] = $hit['item_id'];
            $where['model_id'] = $hit['model_id'];
            $sql_data['comments'] = D('comment')->where($where)->count();
            $res = $hit_m->item_edit($sql_data, $hit['id'], true, false);
            $hit = $res['data'];
        }

        return $hit;
    }

    /**
     * 获取点击系统的信息
     */
    public function get_hit() {
        global $_W;
        $model_id = $this->bra_m->_TM['id'];
        if ($model_id == 59) {
            return [];
        }
        $where = array('item_id' => $this->item_id, "model_id" => $model_id);
        $hit_m = D('hits');
        $r = $hit_m->bra_one($where);
        if (!$r) {
            $sql_data['item_id'] = $this->item_id;
            $sql_data['model_id'] = $model_id;
            $sql_data['site_id'] = $_W['site']['id'];
            $sql_data['update_at'] = date("Y-m-d H:i:s");
            $sql_data['views'] = 1;
            if ($_W['site']['config']['hit']['hit_base']) {
                $sql_data['base'] = rand(0, 10);
            }

            $res_add = D('hits')->item_add($sql_data);
            if (is_error($res_add)) {
				Log::info( $res_add);
                throw new BraException('未处理的点击系统异常`:' );
            }
            $hits = $res_add['data'];
        } else {
            if ($r['base'] < $_W['site']['config']['hit']['hit_base']) {
                $update['base'] = $r['base'] + rand(0, 10);
                $res_edit = D('hits')->item_edit($update, $r['id'], true, false);
                if (!is_error($res_edit)) {
                    $hits = $res_edit['data'];
                }else{
                    $hits = $res_edit;
                }
            } else {
                $hits = $r;
            }
        }

        $this->hit = $hits;

        return $this->hit;
    }

    public function save($update = false) {
        if ($update) {
            $this->update = $update;
        }
        if (is_array($this->update) && $this->update) {
            $res = $this->bra_m->item_edit($this->update, $this->item_id, true, false , false);

            if (is_error($res)) {
                return false;
            } else {
                $this->get_node(true, false);
            }
            $this->update = [];
        } else {
            return false;
        }
    }

    public function get_node($render = true, $is_update = false) {
        $this->node = $this->bra_m->get_item($this->item_id, $render, [
            'in_list' => $this->in_list,
            'update' => $is_update
        ]);
        return $this->node;
    }

    /**
     * 每天可以赞
     * @param $is_good
     * @return array|mixed|  null
     */
    public function zan() {
        global $_W;
        $hit = $this->get_hit();
        $hit_m = D('hits');
        $hits_like_m = D('hits_likes');
        if (!$_W['user']) {
            return bra_res(4041, lang('need login'));
        }
        $where_zan = array('hits_id' => $hit['id']);
        $zan_test = D("hits_likes")->with_user()->bra_one($where_zan);
        if (!$zan_test) {
            //点赞目标
            $insert_zan['user_id'] = $_W['user']['id'];
            $insert_zan['hits_id'] = $hit['id'];
            $insert_zan['likes'] = $hit['id'];
            $hits_like_m->item_add($insert_zan);

            $hit_m->bra_where(['id' => $hit['id']])->increment('likes' , 1);
            $hit['likes'] += 1;
            $is_good = true;
        } else {
            $hits_like_m->bra_where(['id' => $zan_test['id']])->delete();
            //攒过  取消点赞
            $is_good = false;
            $res = $hit_m->bra_where(['id' => $hit['id']])->decrement('likes' , 1);
            $hit['likes'] -= 1;
        }

        $hit['is_good'] = $is_good;
        D('hits')->clear_item($hit['id']);
        return $hit;
    }

    public function comment() {
        global $_W;
        $hit = $this->get_hit();
        $hit_m = D('hits');
        $hit_m->bra_where(['id' => $hit['id']])->increment('comments' , 1);
        $hit['comments'] += 1;

        D('hits')->clear_item($hit['id']);
        return $hit;
    }

    public function update($render = true) {
        return $this->get_node($render, true);
    }
}
