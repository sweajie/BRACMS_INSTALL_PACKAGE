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

use Bra\core\utils\BraDis;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\ParameterBag;
use Illuminate\Support\Facades\Response;

class BraApiPage extends BraPage {
    public $page_info; // seo data
    public $page_data; // page data
    public $params = [];
    public $action_sign;
    private $module_sign;
    private $module;
    private $path_params = [];
    private $pager_sign; // bra_pager

    public function __construct($path, $page_title = '暂无描述') {


		if(Request::method() == "OPTIONS"){
			Log::write('error' , "received");
			Log::write('error' , Request::method());
			header('Access-Control-Allow-Origin: *');
			header('Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE');
			header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Key, Authorization');
			header('Access-Control-Allow-Credentials: true');
			$response = Response::make("", 200);
			$response->send();
			app()->terminate();

			exit();
		}

        global $_W, $_GPC;
        $_GPC = Request::all();// process gpc
        if(empty($_GPC)){
            $this->parameters = $this->decode();
            Request::merge($this->parameters['inputs']);
            $_W['_bra_uploaded_files'] = $this->parameters['files'];
            $_GPC = Request::all();// process gpc
        }
        if(($_GPC['refer'] ?? false ) && is_numeric($_GPC['refer'])){
            $_W['refer'] = intval($_GPC['refer']);
        }
        $_W['current_url'] = url()->full();

        BraAccess::identify_site($_GPC['site_id']);//init site
        $_W['bra_client'] = $_GPC['bra_client'];
        self::config_user();//init user
        if (is_string($_GPC['query'])) {
            $_GPC['query'] = json_decode($_GPC['query'], 1);
        }
        // parse url
        $this->parse_m_c_a_p($path);
        //init module
        BraModule::module_init($_GPC['query']['module']);
        $this->params = array_merge($_GPC, $this->path_params);
        if (!app()->request->ajax()) {
            list($_W['device'], $_W['theme']) = BraTheme::get_module_device_theme($this->module_sign);
        }
        if (!preg_match('/^[\x{4e00}-\x{9fa5}_0-9a-z]{1,50}$/iu', $this->action_sign)) {
            end_resp(bra_res('bra_10003', '非法页面@' . $this->action_sign));
        }
        if ($this->action_sign) {
            $this->base_data();
            $page_data = is_array($this->page_data) ? $this->page_data : [];
            $this->mapping = array_merge($_W['mapping'] ?? [], $this->params, $page_data);
        }
    }

    public static function config_user() {
        global $_W, $_GPC;
        $user =Auth::guard('api')->user();
		if($user){
			$_W->user = $user;
		}
        $_W['uuid'] = $_GPC['bra_uuid'];
    }

    private function parse_m_c_a_p($result) {
        global $_W, $_GPC;
        $page_name = $_GPC['query']['page_name'] ?? '';
        //page sign
        if ($page_name) {
            $this->module_sign = $_GPC['query']['module'];
            define("ROUTE_M", $this->module_sign);
            $this->module = module_exist($this->module_sign);
            if (strpos($page_name, '@') !== false) {
                $p_d = explode('@', $page_name);
                $this->action_sign = $p_d[1];
                $this->pager_sign = $p_d[0];
                define("ROUTE_C", $this->pager_sign);
                if (!preg_match("/[a-z_A-Z0-9]+/", ROUTE_C)) {
                    abort(403, '无效页面访问!');
                }
                define("ROUTE_A", $this->action_sign);
                if (strpos(ROUTE_A, '__') !== false) {
                    abort(403, '无效访问!');
                }
                if (!preg_match("/[a-z_A-Z0-9]+/", ROUTE_A)) {
                    abort(rand(520, 530), '无效访问程序!' . ROUTE_A);
                }
            } else {
                abort(403, '无效的访问页面1 ' . $result  . json_encode($_GPC) . json_encode($_REQUEST));
            }
        } else {
            abort(403, ' 无效的访问页面2' . json_encode($this->parameters)  );
        }
    }

    private function base_data() {
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
            end_resp(bra_res(500, '您好2 , 页面不存在' . $pager_name), null, 403);
        } else {
            //menu
            $container = Container::getInstance();
            $bra_page = new $pager_name($this);

            if (!$this->page_data) {
                BraMenu::init_menu($this->params['menu_id'] ?? 0);
                $container->call([$bra_page, $this->action_sign], [
                    'query' => $this->params['query']
                ]);
                $this->page_data = $bra_page->page_data;
                if(($this->params['query']['refer'] ?? false ) && $this->params['query']['refer'] && $_W['user']['id'] != $this->params['query']['refer'] ){

                    if($_W['user']['parent_id'] == $this->params['query']['refer']){
//                        //make notice
//                        $params = [];
//
//                        $referer = request()->headers->get('referer');
//                        if(is_array($this->page_data['loupans'])){
////                            "#/pages_house/loupan/loupan_detail?id="
//                            $url =  $referer . $this->page_info['share_path_h5_sap'] . $this->page_data['detail']['id'] ;
//                        }else{
//
//                            if(isset($this->page_data['detail']['chanquan'])){
//                                $url = $referer . '#/pages_house/esf/esf_detail?id=' . $this->page_data['detail']['id'] ;
//                            }else{
//                                $url = $referer . '#/pages_house/rent/rent_detail?id=' . $this->page_data['detail']['id'] ;
//                            }
//                        }
//                        $params['header'] = $_W['user']['nickname'] . "点击了您的分享";
//                        $params['footer'] = $this->page_data['detail']['title'] ?? '首页分享';
//
//                        $params['url'] = $url;
//                        $params['time'] = date('Y-m-d H:i:s');
//                        $bn = new BraNotice(1, $params);
//                        $bn->send($this->params['query']['refer']);
                        if($_W['user'] && $_W['user']['parent_id'] == 0 && $_W['user']['dis_level_id'] == 0){
                            $_W['user']->parent_id = $this->params['query']['refer'];
                            $_W['user']->save();
                        }
                    }else{
                        if(!$_W['user']['parent_id'] && !$_W['user']['dis_level_id']){
                            BraDis::make_down_line($_W['user'] , $this->params['query']['refer']);
                        }
                    }

                }
            }

            @$log['res'] = json_encode(is_array($bra_page->page_data) ? $bra_page->page_data : []);
            $log['size'] = strlen(json_encode($bra_page->page_data));
            $log['note'] = microtime(true) - BRACMS_START_TIME;
            Db::table('log')->insert($log);



			if($_W['bra_client'] == 3){
				$mini = D('wechat')->bra_one($_GPC['app_id']);
				$this->page_data['is_checking'] = (bool) $mini['is_checking'];
			}
            A($this->page_data);
            //$this->res_type = $this->bra_page->res_type;
        }
    }

    private function init_page() {
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
            $sel_tpl = $seo_tpl_m->bra_where($where)->first();
            if (!$sel_tpl) {
                $insert = $where;
                $insert['title'] = $this->page_title;
                $insert['id'] = $seo_tpl_m->insertGetId($insert);
            }
            $this->page_info = BraView::parse_param_obj($sel_tpl, $this->mapping);

            return [];
        } else {
            $sel_tpl = $seo_tpl_m->bra_where($where)->first();
            $seo = $seo_m->with_site()->bra_where($where)->first();
            if (!$seo) {
                if ($sel_tpl) {
                    $insert = $sel_tpl;
                    unset($insert['id']);
                    $insert['title'] = $this->page_title;
                    $seo_res = $seo_m->item_add($insert);

                    return $sel_tpl;
                } else {
                    $insert = $where;
                    $insert['title'] = $this->page_title;
                    $seo_res = $seo_m->item_add($insert);

                    return $seo_res['data'];
                }
            } else {
                foreach ($seo as $k => $v) {
                    if (empty($v)) {
                        $seo[$k] = isset($sel_tpl[$k]) ? $sel_tpl[$k] : "";
                    }
                }
                unset($seo['old_data']);
                if (empty($seo['seo_title'])) {
                    $seo['seo_title'] = $this->page_title;
                }
                $this->page_info = BraView::parse_param_arr($seo, $this->mapping);
                //load share icon
                if ($_W['site']['config']['logo']) {
                    $this->page_info['share_icon'] = $_W['site']['config']['logo'];
                }

                return $this->page_info;
            }
        }
    }


    public function decode()
    {
        $this->files = [];
        $this->data = [];
        // Fetch content and determine boundary
        $rawData = file_get_contents('php://input');
        $boundary = substr($rawData, 0, strpos($rawData, "\r\n"));
		$parts = [];
		if($boundary){
			$parts = array_slice(explode($boundary, $rawData), 1);
		}
        // Fetch and process each part

        foreach ($parts as $part) {
            // If this is the last part, break
            if ($part == "--\r\n") {
                break;
            }

            // Separate content from headers
            $part = ltrim($part, "\r\n");
            list($rawHeaders, $content) = explode("\r\n\r\n", $part, 2);
            $content = substr($content, 0, strlen($content) - 2);

            // Parse the headers list
            $rawHeaders = explode("\r\n", $rawHeaders);
            $headers = [];

            foreach ($rawHeaders as $header) {
                list($name, $value) = explode(':', $header);
                $headers[strtolower($name)] = ltrim($value, ' ');
            }

            // Parse the Content-Disposition to get the field name, etc.
            if (isset($headers['content-disposition'])) {
                preg_match('/^form-data; *name="([^"]+)"(; *filename="([^"]+)")?/', $headers['content-disposition'], $matches);

                $fieldName = $matches[1];
                $fileName = (isset($matches[3]) ? $matches[3] : null);
                parse_str($fieldName, $fieldNameAsArray);

                // If we have a file, save it. Otherwise, save the data.
                if ($fileName !== null) {
                    $localFileName = tempnam(sys_get_temp_dir(), 'sfy');

                    file_put_contents($localFileName, $content);

                    $fileData = [
                        'name' => $fileName,
                        'type' => $headers['content-type'],
                        'tmp_name' => $localFileName,
                        'error' => 0,
                        'size' => filesize($localFileName),
                    ];

                    $this->parseFieldName($this->files, array_keys($fieldNameAsArray)[0], array_values($fieldNameAsArray)[0], $fileData);

                    // register a shutdown function to cleanup the temporary file
                    register_shutdown_function(function () use($localFileName) {
                        unlink($localFileName);
                    });
                } else {
                    $this->parseFieldName($this->data, array_keys($fieldNameAsArray)[0], array_values($fieldNameAsArray)[0], $content);
                }
            }
        }

        $fields = new ParameterBag($this->data);

        return [
            "inputs" => $fields->all(),
            "files" => $this->files
        ];
    }

    public function parseFieldName(&$var, $fieldBaseName, $fieldNameAsArray, $content)
    {
        if (empty($fieldNameAsArray)) {
            $var[$fieldBaseName] = $content;
            return;
        }

        foreach ($fieldNameAsArray as $key => $value) {
            if (gettype($value) === 'string') {
                //  TODO: deal with nested arrays
                $var[$fieldBaseName][$key] = $content;
            }
        }
    }
}
