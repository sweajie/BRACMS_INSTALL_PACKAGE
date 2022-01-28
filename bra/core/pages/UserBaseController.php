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
namespace Bra\core\pages;

use Bra\core\objects\BraPage;
use Bra\wechat\medias\WechatMedia;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;

class UserBaseController extends BraController {

    public function __construct (BraPage $bra_page) {
        global $_W, $_GPC;
        parent::__construct($bra_page);

		if($_W['action_sign'] == "bra_share_entry"){
			$share = D('share')->bra_one((int)$_GPC['id']);
			if($share){
				Cookie::queue('refer', $share['user_id']);
			}
		}

        $wechat_module = module_exist('wechat');
        if ($wechat_module) {
            $login_url = WechatMedia::init_wechat();
        } else {
            $login_url = url("bra/passport/login");
        }
        if (!$_W['user']) {
            $this->page_data = redirect($login_url);
            end_resp(bra_res($_W['bra_client'] == 3 ? 4300 : 43000 , '请先登录'));
        }
    }

    protected function list_item ($query, $model_id, int $limit = 10, $page = 1) {
        $model = D($model_id);
        $q = $query['q'];
        $query['page'] = $page;
        $query['order'] = 'id desc';
        $name_key = $model->_TM['search_keys'] ? $model->_TM['search_keys'] : $model->_TM['name_key'];
        $name_key = str_replace(',', '|', $name_key);
        //设置了搜索域的 必须提供关键字才可以进行数据操作
        $query['input_like_fields'] = ['' => $name_key];
        $query['bra_tree_fields'] = ['area_id' => 'area'];
        $query["_bra_q"] = $query['q'];
        $lists = $model->list_bra_resource($query);
        $new_res = [];
        $ret['code'] = 1;
        $ret['success'] = true;
        $lists->transform(function ($item) use ($model) {
            return $this->get_model_data($model->get_item($item['id']), $model->_TM['id_key'], $model->_TM['name_key']);
        });

        return $new_res;
    }

    protected function get_model_data ($result, $id_key, $name_key) {
        $_res = [];
        if (strpos($name_key, '|') !== false) {
            $name_keys = explode("|", $name_key);
            foreach ($name_keys as $_name_key) {
                if ($result[$_name_key]) {
                    $_res['name'] = $result[$_name_key];
                    break;
                }
            }
        }
        if (strpos($name_key, ',') !== false) {
            $name_keys = explode(",", $name_key);
            foreach ($name_keys as $_name_key) {
                if ($result[$_name_key]) {
                    $_res['name'] .= $result[$_name_key];
                }
            }
        }
        if (empty($_res['name'])) {
            $_res['name'] = $result[$name_key];
        }
        $_res['value'] = $result[$id_key];
        $_res['text'] = $_res['name'];
        $_res['name'] = isset($_res['name']) ? $_res['name'] : $result[$name_key];

        return $_res;
    }

}
