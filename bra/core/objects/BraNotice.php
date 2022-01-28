<?php
// +----------------------------------------------------------------------
// | BraCms [ New Better  ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2017 http://www.bracms.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( 您必须获取授权才能进行使用 )
// +----------------------------------------------------------------------
// | Author: new better <1620298436@qq.com>
// +----------------------------------------------------------------------

namespace Bra\core\objects;


use Illuminate\Support\Carbon;

class BraNotice {
	public $module_notice;

	public $notice_id;
	public $notice;
	public $notice_m;
	public $notice_tpl_m;
	public $params;
	public $old_params;
	public $sender;
	public $gap = 120;
	/***
	 * @var $bra_metas  BraMetas Array
	 */
	public $bra_metas;

	/**
	 * BraNotice constructor.
	 * @param $m_notice_id
	 * @param array $params
	 */
	public function __construct ($m_notice_id, $params = []) {
		$this->module_notice = D('module_notice')->bra_one($m_notice_id);
		$this->notice_m = D('sms_notice');
		$this->notice = $this->notice_m->bra_one(['title' => $m_notice_id]);
		$this->notice_tpl_m = D('sms_notice_tpl');
		$this->params = $params;
		if ($this->notice) {
			$this->notice_id = $this->notice['id'];
			if (isset($params['old_data'])) {
				$this->old_params = $params['old_data'];
			} else {
				$this->old_params = $params;
			}
		}
	}

	public static function verify_code ($mobile, $code) {
		if (BraString::is_phone($mobile)) {
			$log_m = D('sms_notice_log');
			$w = ['type' => 1, 'target' => $mobile];
			$data_sms = $log_m->bra_where($w)->order('id desc')->bra_one();
			if(!$data_sms){
				return bra_res(500, '验证码验证失败!');
			}
			if($data_sms['status'] > 1){
				return bra_res(500, '验证码已经过期!' , '' , $data_sms);
			}
			$content = json_decode($data_sms['content'], 1);
			if (time() - strtotime($data_sms['create_at']) > 960) {
				$update['status'] = 3;
				$log_m->update($update, $data_sms['id']);

				return bra_res(500, '验证码过期!' , '' , $data_sms);
			}
			//  && app()->request->ip() == $data_sms['ip']
			if ($data_sms && $code == current($content)) {
				$update['status'] = 99;

				$res = $log_m->item_edit($update, $data_sms['id']);
				if(is_error($res)){
					return bra_res(500, "状态修复失败 ！" , '' , $res );
				}
				return  $res;
			} else {
				return bra_res(500, "您的手机验证码不正确 ！" );
			}
		} else {
			return bra_res(500, "手机号码不正确！");
		}
	}

	public function send ($user_id, $pips = ['wxtpl', 'sms', 'email', 'wxmsg', 'braapp', 'wxapp', 'site_msg']) {
		if (empty($this->notice_id)) {
			return bra_res(404, '没有需要发送的通知');
		}
		$this->set_user($user_id);
		$data = [];
		foreach ($pips as $pip) {
			$sender = 'send_' . $pip;
			$data[$pip] = $this->$sender();
		}

		return $data;
	}

	public function set_user ($user_id) {
		global $_W;
		$this->add_user($user_id);
		$admin_targets = array_filter(explode(',', $this->notice['admin_target']));
		foreach ($admin_targets as $admin_target) {
			switch ($admin_target) {
				case 1: //site super admin
					$this->add_user($_W['site']['user_id'] ? $_W['site']['user_id'] : $_W['site']['config']['admin_id']);
					break;
				case 2:
					//area admin uids
					if (isset($this->old_params['area_id']) && $this->old_params['area_id']) {
						$donde = [];
						$donde['area_id'] = $this->params['area_id'];
						$admins = D('users_admin')->bra_where($donde)->bra_get();
						foreach ($admins as $admin) {
							$this->add_user($admin['user_id'] ?? 0);
						}
					}
					break;
				case 3: // module admin
					$set = BraModule::get_module_setting($this->params['module_id']);
					if ($set['admin_openid']) {
						$h_admin_ids = explode(',', $_W['module_config']['admin_id']);
						foreach ($h_admin_ids as $h_admin_id) {
							$this->add_user($h_admin_id);
						}
					}
					break;
			}
		}
	}

	public function add_user ($user_id) {
		if (!isset($this->bra_metas[$user_id]) && $user_id) {
			$this->bra_metas[$user_id] = new BraMetas($user_id);
		}
	}

	public function send_wxtpl () {
		global $_W;
		if (empty($this->notice_id)) {
			return bra_res(404, '没有需要发送的通知');
		}
		$res = $open_ids = [];
		foreach ($this->bra_metas as $bra_meta) {
			if ($bra_meta->openid_wx) {
				$open_ids[] = $bra_meta->openid_wx;
			}
		}
		if (empty($open_ids)) {
			return bra_res(404, '没有需要发送的openid');
		}
		//get config
		$config = $this->get_sms_tpl('wxtpl');
		if (!is_error($config)) {
			$msg_data = $config['data']['content'];
			$data = [];
			if (isset($msg_data['data']) && $msg_data['data']) {
				foreach ($msg_data['data'] as $k => &$v) {
					if (is_array($v)) {
						$data[$k] = BraView::compile_blade_arr($v, $this->params);
					} else {
						$data[$k] = BraView::compile_blade_str($v, $this->params);
					}
				}
			}
			foreach ($msg_data as $k => &$v) {
				if (is_array($v)) {
					if ($k == 'miniprogram') {
						$v = BraView::compile_blade_arr($v, $this->params);
					} else {
						$data[$k] = BraView::compile_blade_arr($v, $this->params);
					}
				} else {
					$v = BraView::compile_blade_str($v, $this->params);
				}
			}
			$msg_data['data'] = $data;
			unset($msg_data['data']['data'], $msg_data['first'], $msg_data['remark']);
			foreach ($open_ids as $open_id) {
				$msg_data['touser'] = $open_id;
				$res[] = $r = $_W['WX']->template_message->send($msg_data);
				$this->log_msg(2 , $open_id , 0 , $config['tpl_id'] , $msg_data , json_encode($r) , 99 );
			}

			return $res;
		} else {
			return $config;
		}
	}

	public function get_sms_tpl ($type) {
		global $_W;
		$donde = [];
		$donde['type'] = $type;
		$donde['site_id'] = $_W['site']['id'];
		$donde['notice_id'] = $this->notice_id;
		$donde['status'] = 1;
		$config = $this->notice_tpl_m->bra_one($donde);
		if ($config) {
			$config['content'] = json_decode($config['content'], 1);

			return bra_res(1, '', '', $config);
		} else {
			return bra_res(500, '请先设置模板');
		}
	}

	public function log_msg ($type , $target , $provider_id ,$tpl_id , $params , $log , $status = 1) {
		global $_W , $_GPC;
		$log_m = D('sms_notice_log');

		$report_log = [];
		$report_log['type'] = $type; // sms
		$report_log['notice_id'] = $this->module_notice['id'];
		$report_log['tpl_id'] = $tpl_id;
		$report_log['provider_id'] = $provider_id;
		$report_log['log'] = $log;
		$report_log['target'] = $target;
		$report_log['status'] = $status;
		$report_log['content'] = json_encode($params);
		$report_log['sender_uid'] = $_W['user']['id'];
		$report_log['module_id'] = $this->module_notice['module_id'];
		$report_log['ip'] = app()->request->ip();
		$report_log['cate_id'] = 0;
		$report_log['role_id'] = 0;
		$report_log['title'] = '';
		$report_log['playload'] = '';
		return $log_m->item_add($report_log);
	}

	public function send_sms ($mobile = '', $params = []) {
		global $_W;
		if (empty($params)) {
			$params = $this->params;
		}
		if (empty($mobile)) {
			foreach ($this->bra_metas as $bra_meta) {
				$mobile = $bra_meta->mobile;
			}
		}
		if (empty($mobile)) {
			return bra_res(500, '手机号码不合法', '', $this->bra_metas);
		}
		$log_m = D('sms_notice_log');
		if ($this->module_notice['id'] == 2) {
			//->group('target')
			$donde_1['ip'] = app()->request->ip();
			$amount = $log_m->bra_where($donde_1)
                ->whereDay('create_at' , '=' , Carbon::make('today'))->count();
			if ($amount > 50) {
				return bra_res(500, "您今天发送短信的次数达到上限!");
			}

			//->group('target')
			$donde_2['target'] = $mobile;
			$amount = $log_m->bra_where($donde_2)->whereDay('create_at' , '=' , Carbon::make('today'))->count();
			if ($amount > 50) {
				return bra_res(500, "您今天发送短信的次数达到上限!");
			}

			// 同一个IP 切换账号数量
			$donde['target'] = $mobile;
			//$donde['status'] = 1;
			$report = $log_m->bra_where($donde)->order('id desc')->bra_one();
			if ($report) {
				$sec = strtotime(date("Y-m-d H:i:s")) - strtotime($report['create_at']);
				$gap = $this->gap;
				if ($sec < $gap) {
					return bra_res(3, "还要等" . ($gap - $sec) . " 秒才可以再次发送！");
				} else {
					$clear_donde = $donde;
					$clear_donde['create_at'] = ['<', date('Y-m-d', strtotime('-7 days'))];
					$clear_update = [];
					$clear_update['status'] = 99;
					//todo clear expired
				}
			}
		}

		//create provider obj
		if (!BraString::is_phone($mobile)) {
			return [
				'code' => 3,
				'msg' => "您的手机号码不正确"
			];
		}
		//get config
		$config = $this->get_sms_tpl('sms');
		if (!is_error($config)) {
			$msg_data = $config['data']['content'];
			$tpl_id = $msg_data['template_id'];

			$p = $msg_data['data'];

			foreach ($p as $k => $v) {
			    if(is_array($v)){
                    $params_tosend[$k] = BraView::compile_blade_str($v['value'], $params);
                }
			}
			$where = [];
			$where['default'] = 1;
			$where['site_id'] = $_W['site']['id'];
			$sms_site_config = D('sms_provider_config')->bra_where($where)->bra_one();
			$sms_config = json_decode($sms_site_config['config'], 1);
			if (empty($sms_site_config['provider_id'])) {
				return [
					'code' => 3,
					'msg' => "您的没有设置默认的短信供应商!"
				];
			}
			$provider = D('sms_provider')->bra_one($sms_site_config['provider_id']);
			$class = "\Bra\core\utils\sms\providers\\" . $provider['sign'];
			$provider_controller = new $class($sms_config);
			$report_log = [];
			$report_log['type'] = 1; // sms
			$report_log['notice_id'] = $this->module_notice['id'];
			$report_log['tpl_id'] = $tpl_id;
			$report_log['provider_id'] = $provider['id'];
			$report_log['log'] = "-";
			$report_log['target'] = $mobile;
			$report_log['status'] = 0;
			$report_log['content'] = json_encode($params_tosend);
			$report_log['sender_uid'] = 0;
			$report_log['module_id'] = 0;
			$report_log['ip'] = app()->request->ip();
			$report_log['cate_id'] = 0;
			$report_log['role_id'] = 0;
			$report_log['title'] = '';
			$report_log['playload'] = '';
			$log_res = $log_m->item_add($report_log);
			if (!is_error($log_res)) {
				$resp = $provider_controller->send($mobile, $params_tosend, $tpl_id);
				if (!is_error($resp)) {
					$edit_res = $log_m->item_edit(['status' => 1] , $log_res['data']['id']);
					return bra_res(1, '发送成功', $edit_res, $resp);
				} else {
					return bra_res(500, '发送短信失败!', '', $resp);
				}
			} else {
                $log_res['337'] = 337;
				return $log_res;
			}
		} else {
			return $config;
		}
	}

	public function send_email () {
	}

	//客服消息
	public function send_wxmsg () {
	}

	//app 推送
	public function send_braapp () {
		$config = $this->get_sms_tpl('braapp');
		$msg_data = $config['data']['content'];

		if(!$msg_data){
			return bra_res(500 , '对不起, 模板未设置' . $this->module_notice['id']);
		}

		foreach ($this->bra_metas as $bra_meta) {
			if ($bra_meta->app_cid) {
				$app_cids[] = $bra_meta->app_cid;
			}
		}

		$g = new Getui();

		foreach ($msg_data as $k => &$v) {
			if (is_array($v)) {
				$data[$k] = BraView::compile_blade_arr($v, $this->params);
			} else {
				$data[$k] = BraView::compile_blade_str($v, $this->params);
			}
		}
		$res = [];
		foreach ($app_cids as $app_cid){
			$res[]  = $g->pushMessageToSingle($app_cid , $data['title'] , $data['content'] , '');
		}
		return $res;
	}

	//xcx
	public function send_wxapp () {
		global $_W;
		if (empty($this->notice_id)) {
			return bra_res(404, '没有需要发送的通知');
		}
		$res = $open_ids = [];
		foreach ($this->bra_metas as $bra_meta) {
			if ($bra_meta->openid_wx_mini) {
				$open_ids[] = $bra_meta->openid_wx_mini;
			}
		}
		if (empty($open_ids)) {
			return bra_res(404, '没有需要发送的openid');
		}
		//get config
		$config = $this->get_sms_tpl('wxapp');
		if (!is_error($config)) {
			$msg_data = $config['data']['content'];
			$data = [];
			if (isset($msg_data['data']) && $msg_data['data']) {
				foreach ($msg_data['data'] as $k => &$v) {
					if (is_array($v)) {
						$data[$k] = BraView::compile_blade_arr($v, $this->params);
					} else {
						$data[$k] = BraView::compile_blade_str($v, $this->params);
					}
				}
			}
			foreach ($msg_data as $k => &$v) {
				if (is_array($v)) {
					if ($k == 'miniprogram') {
						$v = BraView::compile_blade_arr($v, $this->params);
						$msg_data['page'] = $v['pagepath'];
					} else {
						$data[$k] = BraView::compile_blade_str($v, $this->params);
					}
				} else {
					$v = BraView::compile_blade_str($v, $this->params);
				}
			}
			$msg_data['data'] = $data;
			unset($msg_data['data']['data'], $msg_data['first'], $msg_data['remark']);
			foreach ($open_ids as $open_id) {
				$msg_data['touser'] = $open_id;
				$res[] = $_W['MWX']->subscribe_message->send($msg_data);
			}

			return $res;
		} else {
			return $config;
		}
	}

	//send site msg
	public function send_site_msg (int $sender = 0, int $msg_type = 2) {
		global $_W;
		$sms_msg_m = D('sms_msg');
		if (empty($this->notice_id)) {
			return bra_res(404, '没有需要发送的通知');
		}
		$res = $uids = [];
		foreach ($this->bra_metas as $user_id => $bra_meta) {
			$uids[] = $user_id;
		}
		if (empty($uids)) {
			return bra_res(404, '没有需要发送的目标');
		}
		//get config
		$config = $this->get_sms_tpl('bramsg');
		if (!is_error($config)) {
			$msg_data = $config['data']['content'];
			if (!is_array($msg_data)) {
				return bra_res(404, '数据格式不合法', '', $msg_data);
			}
			foreach ($msg_data as $k => &$v) {
				if (!is_array($v)) {
					$v = BraView::compile_blade_str($v, $this->params);
				}
			}
			foreach ($uids as $uid) {
				$idata = [];
				$idata['sender'] = $sender;
				$idata['receiver'] = $uid;
				$idata['content'] = $msg_data['content'];
				$idata['title'] = $msg_data['title'];
				$idata['msg_type'] = $msg_type;
				$idata['status'] = 0;
				$idata['ip'] = app()->request->ip();
				$res = $sms_msg_m->item_add($idata);
			}

			return $res;
		} else {
			return $config;
		}
	}
}
