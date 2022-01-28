<?php

namespace Bra\bra_admin\pages;

use Bra\core\pages\BraAdminController;
use Bra\core\utils\BraAdminItemPage;
use Bra\wechat\medias\WechatMedia;

class AdminSmsPage extends BraAdminController {

    use BraAdminItemPage;

    public function bra_admin_admin_sms_notice_idx () {
        global $_W;
        $res = $this->t__bra_table_idx('sms_notice');
        if (is_bra_access(0, 'post')) {
            foreach ($res['data'] as &$item) {
                //$yxm_lyy = D('module_notice')->get_item($item['old_data']['title']);
                $item['title'] .= $item['old_data']['title'];
            }
        }

        return $this->page_data = $res;
    }

    public function bra_admin_admin_sms_sms_notice_add () {
        global $_W, $_GPC;
        if (is_bra_access(0, 'post')) {
            $donde = [];
            $donde['title'] = $_GPC['data']['title'];
            $det = D('sms_notice')->with_site()->bra_where($donde)->first();
            if ($det) {
                return bra_res(500, '对不起,该通知已经添加过了');
            }
        }

        return $this->page_data = $this->t__add_iframe('sms_notice');
    }

    public function bra_admin_admin_sms_sms_provider_idx () {
        return $this->page_data = $this->t__bra_table_idx("sms_provider");
    }

    public function bra_admin_admin_sms_provider_config ($query) {
        global $_W;
        $provider_id = (int)$query['provider_id'];
        $model = D("sms_provider_config");
        $provider = (array)D("sms_provider")->find($provider_id);
        $where = ['provider_id' => $provider['id']];
        $detail = (array)$model->with_site()->bra_where($where)->first();
        if (is_bra_access(0, 'post')) {
            $data = $query['data'];
            $data['config'] = json_encode($data['config']);
            if ($detail) {
                $res = $model->item_edit($data, $where);
            } else {
                $_data['provider_id'] = $provider_id;
                $res = $model->item_add($_data);
            }
            if (!is_error($res)) {
                return $this->page_data = bra_res(1, "ok", '', $data);
            } else {
                return $this->page_data = bra_res(2, $res['msg'], '', $res);
            }
        } else {
            $detail['config'] = json_decode($detail['config'], true);
            A('detail', $detail);

            return $this->page_data = A_T('bra_admin.admin_sms.provider_' . strtolower($provider['sign']));
        }
    }

    public function bra_admin_admin_sms_set_default_provider ($query) {
        global $_W;
        $provider_id = (int)$query['provider_id'];
        $update['default'] = 0;
        D('sms_provider_config')->bra_where(['id' => ['>', 0]])->update($update);
        $donde['provider_id'] = $provider_id;
        $update['default'] = 1;
        D('sms_provider_config')->bra_where($donde)->update($update);

        return $this->page_data = bra_res(1, '操作完成!');
    }

    public function bra_admin_admin_sms_tpl_config ($query) {
        global $_W, $_GPC;
        // 推送设置
        $notice_id = $query['notice_id'];
        $this->notice = D('sms_notice')->bra_one($notice_id);
        A('notice', $this->notice);
        // 枚举信息
        $push = D('module_notice')->bra_one($this->notice['title']);
        A('push', $push);
        A('bar_text', $push['title']);
        //推送配置数据
        $where['notice_id'] = $notice_id;
        $where['site_id'] = $_W['site']['id'];
        $where['type'] = $query['type'];
        $this->tpl_config_m = D('sms_notice_tpl');
        $detail = $test = $this->tpl_config_m->bra_one($where);
        if (!$detail) {
            $detail['content'] = [];
        } else {
            $detail['content'] = json_decode($detail['content'], 1);
        }
        A('detail', $detail);
        $call = $query['type'] . '_config';

        return $this->page_data = $this->$call($test, $query['type']);
    }

    public function bra_admin_admin_sms_sms_notice_edit ($query) {
        return $this->page_data =  $this->t__edit_iframe($query['id'], 'sms_notice');
    }

    /**
     * 公众号模板消息推送
     * @param $test
     * @param $type
     * @return mixed|string
     */
    private function wxtpl_config ($test, $type) {
        global $_W, $_GPC;
        if (is_bra_access(0, 'post')) {
            $insert = array();
            $content = $_GPC['content'];
            $data = [];
            $_GPC['keyword'] = $_GPC['keyword'] ?? [];
            for ($i = 0; $i < count($_GPC['keyword']); $i++) {
                if ($_GPC['keyword'][$i]) {
                    $data[$_GPC['keyword'][$i]] = array(
                        "value" => $_GPC['value'][$i],
                        "color" => $_GPC['color'][$i],
                    );
                }
            }
            $content['data'] = $data;
            $insert['notice_id'] = $this->notice['id'];
            $insert['content'] = json_encode($content);
            $insert['status'] = (int)$_GPC['status'];
            $insert['type'] = $type;
            if (!$test) {
                $insert['site_id'] = $_W['site']['id'];
                $res = $this->tpl_config_m->insert($insert);

                return bra_res($res, "ok");
            } else {
                $trs = $this->tpl_config_m->bra_where($test['id'])->update($insert);

				if($trs){

					return bra_res(1 , "操作成功");
				}else{

					return bra_res(500 , "操作失败，可能没修过任何数据");
				}
            }
        } else {
            $offical = WechatMedia::get_media_official();
            $resp = $offical->app->template_message->getPrivateTemplates();
            A('tpls', $resp['template_list']);

            return T("bra_admin.admin_sms." . $type . '_config');
        }
    }


	private function sms_config ($test, $type) {
		global $_W, $_GPC;
		if (is_bra_access(0, 'post')) {
			$content = $_GPC['content'];
			$data = [];
			$_GPC['keyword'] = $_GPC['keyword'] ?? [];
			for ($i = 0; $i < count($_GPC['keyword']); $i++) {
				if ($_GPC['keyword'][$i]) {
					$data[$_GPC['keyword'][$i]] = array(
						"value" => $_GPC['value'][$i],
						"color" => $_GPC['color'][$i],
					);
				}
			}
			$content['data'] = $data;
			$content['notice_id'] = $this->notice['id'];
			$insert = array();
			$insert['content'] = json_encode($content);
			$insert['status'] = $_GPC['status'];
			$insert['notice_id'] = $this->notice['id'];
			$insert['type'] = $type;
			if (!$test) {
				$insert['site_id'] = $_W['site']['id'];
				$this->tpl_config_m->item_add($insert);
			} else {
				$this->tpl_config_m->update($insert, $test['id']);
			}

			return bra_res(1, "ok");
		} else {
			return T("bra_admin.admin_sms." . $type . '_config');
		}
	}
}
