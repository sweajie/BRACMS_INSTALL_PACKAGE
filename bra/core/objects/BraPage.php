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

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

class BraPage {
	public $page_info; // seo data
	public $page_data; // page data
	public $params = [];
	public $action_sign;
	private $module_sign;
	private $module;
	public $mapping = [];
	private $path_params = [];
	public $page_title = '暂无描述'; // bra_page
	private $pager_sign; // bra_pager

	public static function config_user () {
		global $_W;

		$user = Auth::user();
		if ($user) {
			$_W->user = $user;
		}
		$uuid = Cookie::get("uuid_" . $_W['site']['id']);
		if (!$uuid) {
			$uuid = BraString::uuid();
			cookie("uuid_" . $_W['site']['id'], $uuid);
		}
		$_W['uuid'] = $uuid;
	}

	public function debug () {
		global $_W , $_GPC;
		$bra_safe = config('bra_safe');
		if($bra_safe['sql_debug']){
			$_W->debug_sql();
		}
	}

	public function __construct ($path) {
		global $_W, $_GPC;

		$_W->_time['before_page'] = microtime(true) - BRACMS_START_TIME;
		$this->debug();
		$_GPC = Request::input();// process gpc
		$_W->current_url = url()->full();
		BraAccess::identify_site();//init site
		self::config_user();//init user
		BraAccess::define_client();//init user
		// parse url
		$this->parse_m_c_a_p($path);
		$this->params = array_merge($_GPC, $this->path_params);
		$this->action_sign = join('_', [ROUTE_M, ROUTE_C, ROUTE_A]);
		if (!Request::ajax()) {
			list($_W['device'], $_W['theme']) = BraTheme::get_module_device_theme($this->module_sign);
		}
		if (!preg_match('/^[\x{4e00}-\x{9fa5}_0-9a-z]{1,50}$/iu', $this->action_sign)) {
			end_resp(bra_res('bra_10003', '非法页面@' . $this->action_sign));
		}
		if ($this->action_sign) {
			$this->page_data = $this->base_data();
			$page_data = is_array($this->page_data) ? $this->page_data : [];
			$this->mapping = array_merge($_W['mapping'] ?? [], $this->params, $page_data);
		}
		if (!app()->request->ajax()) {
			$seo = $this->init_page_info();
			$seo['seo_title'] = $seo['seo_title'] ?? $this->page_title;
			$seo = BraView::compile_blade_arr($seo, $this->mapping);
			A('seo', $seo);
		}
	}

	private function parse_m_c_a_p ($result) {
		// 获取操作名
		if (is_string($result)) {
			$result = explode('/', $result);
			$result = array_filter($result);
			if (count($result) < 3) {
				$safe = config('bra_safe');
				switch (count($result)) {
					case 0:
						$result = [
							$safe['default_app'],
							$safe['default_ctrl'],
							$safe['default_act']
						];
						break;
					case 1:
						$result[1] = $safe['default_ctrl'];
						$result[2] = $safe['default_act'];
						break;
					case 2:
						$result[2] = $safe['default_act'];
						break;
				}
			} else {
				if (count($result) % 2 != 1) {
					abort(403, '无效访问参数!' . count($result));
				}
			}
		} else {
			abort(403, '无效访问地址!');
		}
		$this->module_sign = strip_tags($result[0] ?? 'index');
		$res = BraModule::module_init($this->module_sign);//init module
		if (is_error($res)) {
			end_resp(bra_res('bra_10003', '模块不存在！'));
		} else {
			define("ROUTE_M", $this->module_sign);
			$this->module = $res['data'];
		}
		//获取控制器名
		$controller = strip_tags($result[1] ?? 'index');
		define("ROUTE_C", $controller);
		$controller = Str::studly($controller);
		if (!preg_match("/[a-z_A-Z0-9]+/", $controller)) {
			abort(403, '无效页面访问!');
		}
		$actionName = strip_tags($result[2] ?? 'index');
		define("ROUTE_A", $actionName);
		if (str_contains(ROUTE_A, '__')) {
			abort(403, '非法的无效访问 , __ 无效字符!');
		}
		if (!preg_match("/[a-z_A-Z0-9]+/", ROUTE_A)) {
			abort(rand(520, 530), '无效访问程序!' . ROUTE_A);
		}
		$keys = $vals = [];
		for ($i = 3; $i < count($result); $i++) {
			if ($i % 2 == 0) {
				$vals[] = $result[$i];
			} else {
				$keys[] = $result[$i];
			}
		}
		if ($keys && $vals) {
			$this->path_params = array_combine($keys ?? [], $vals ?? []);
		}
	}

	private function base_data () {
		global $_W, $_GPC;
		$this->pager_sign = $this->pager_sign ? $this->pager_sign : ROUTE_C;
		$class = Str::studly($this->pager_sign . "_page");
		$pager_name = "\\Bra\\{$this->module['module_sign']}\\pages\\$class";
		$log = [];
		$log['app'] = $this->module['module_sign'];
		$log['ctrl'] = $class;
		$log['act'] = $this->params['page_name'] ?? ROUTE_A;
		$log['user_id'] = $_W['user']['id'] ?? $_W['admin']['id'] ?? 0;
		$log['create_at'] = date("Y-m-d H:i:s");
		$log['ip'] = app()->request->ip();
		$log['client'] = $_W['bra_client'] ?? 0;
		$log['params'] = json_encode($_GPC);
		$log['menu_id'] = $_W['menu']['id'] ?? 0;
		$log['url'] = Request::url();
		$log['device'] = BraTheme::get_device() == 'desktop' ? 1 : 2;
		$log['module_id'] = $this->module['id'] ?? 0;
		$log['cate_id'] = 0;
		$log['method'] = Request::method() == 'POST' ? 2 : 1;
		if (!class_exists($pager_name)) {
			if ($_W['allow_view'] ?? false) {
				return T();
			} else {
				return bra_res(500, '对不起页面不存在#@' . $pager_name, $_GPC);
			}
		} else {
			$action_sign = $this->action_sign;
			//menu
			$container = Container::getInstance();
			$bra_page = new $pager_name($this);
			if($bra_page->page_data){
				end_resp($bra_page->page_data);
			}
			BraMenu::init_menu($this->params['menu_id'] ?? 0);
			if (!defined('BRA_ADMIN')) {
				//last module
				Cookie::queue('last_m_' . $_W['site']['id'], ROUTE_M);
			}
			if (!$page_data = $bra_page->page_data) {
				if (!method_exists($pager_name, $action_sign)) {
					if ($_W['allow_view'] ?? false) {
						$page_data = T();
					} else {
						end_resp(abort(403, $pager_name . "@" . $action_sign), null, 403);
					}
				} else {
					$container->call([$bra_page, $action_sign], [
						'query' => $this->params
					]);
					$page_data = $bra_page->page_data;
				}
			}
			@$log['res'] = '';
			$log['size'] = strlen(json_encode($bra_page->page_data));
			$log['note'] = microtime(true) - BRACMS_START_TIME;
			Db::table('log')->insert($log);

			return $page_data;
		}
	}

	private function init_page_info () {
		global $_W;
		//IN_ADMIN
		if (defined("BRA_ADMIN")) {
			return $this->page_info = [];
		}
		$seo_tpl_m = D("seo_tpl");
		$seo_m = D("seo");
		$where = ['seo_key' => $this->action_sign];
		$where['module_id'] = $this->module['id'];
		if ($_W['develop']) {
			$sel_tpl = Cache::remember("sel_tpl" . BraArray::md5_array($where), 3600, function () use ($seo_tpl_m, $where) {
				return (array)$seo_tpl_m->bra_where($where)->first();
			});
//			$sel_tpl = (array)$seo_tpl_m->bra_where($where)->first();
			if (!$sel_tpl) {
				$insert = $where;
				$insert['title'] = $this->page_title;
				$insert['id'] = $seo_tpl_m->insertGetId($insert);
				$sel_tpl = $insert;
			}
		}
		$seo = Cache::remember("seo" . BraArray::md5_array($where), 3600, function () use ($seo_m, $where) {
			return (array)$seo_m->bra_where($where)->first();
		});
		if (!$seo) {
			if ($sel_tpl) {
				$insert = $sel_tpl;
				unset($insert['id']);
				$insert['title'] = $this->page_title;
				$seo_res = $seo_m->item_add($insert);
				$seo = $sel_tpl;
			} else {
				$insert = $where;
				$insert['title'] = $this->page_title;
				$seo_res = $seo_m->item_add($insert);
				$seo = $seo_res['data'];
			}
		}

		return $seo;
	}
}
