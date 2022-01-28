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
namespace Bra\bra\pages;

use Bra\core\models\UserMenu;
use Bra\core\objects\BraArray;
use Bra\core\objects\BraFS;
use Bra\core\objects\BraModel;
use Bra\core\objects\BraPage;
use Bra\core\pages\BraController;
use Bra\core\utils\BraAdminItemPage;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BraCloudPage extends BraController {

    use BraAdminItemPage;

    public function __construct (BraPage $bra_page) {
        $this->bra_page = $bra_page;
        $this->initialize();
    }

    public function initialize () {
        global $_W, $_GPC;

		//if the key is match
		$licence = config('licence');
		if(request()->getHttpHost()  != $licence['domain']){
			echo json_encode(bra_res(500, '对不起 , 请开启https!'));
			die();
		}

        $bra_safe = config('bra_safe') ?? [];
        //if api is enabled
        if ($bra_safe['is_api'] !== true) {
			$bra_safe = config('bra_safe');
			$bra_safe = is_array($bra_safe) ? $bra_safe : [];
			$bra_safe['is_api'] = false;
            BraFS::write_config('bra_safe', $bra_safe);
            echo json_encode(bra_res(500, '对不起 , API尚未开启!'));
            die();
        }
        if ($_GPC['api_key'] != $licence['product_licence_code']) {
            echo json_encode(bra_res(500006 , '' , '' , $_GPC));
            die();
        }
        //is in white list
        if ($bra_safe['white_list']) {
			$ip = request()->ip();
			if(!in_array($ip , $bra_safe['white_list'])){
				echo json_encode(bra_res(500, '对不起 ,IP 不在白名单列表内!'));
				die();
			}
        }
        define('IN_DEV', true);
    }

    public function bra_bra_cloud_cloud_api ($query) {
        $query = $query['query'];
        $page_name = $query['page_name'];
        $this->page_data = $this->$page_name($query);
    }

    /**
     * @return mixed
     */
    public function models_idx ($query) {
        $config['hide'] = ['setting'];
        $config['show_old_data'] = false;
        $res = $this->t__bra_table_idx('models');
        if ($res['code'] == 1) {
            $res['code'] = 0;
        }

        return $res;
    }

    /**
     * normal edit
     * @param $query
     * @return mixed
     */
    public function item_edit ($query) {
        global $_W, $_GPC;
        if (is_bra_access(0, 'post')) {
            $_GPC['data'] = $query['data'];

            return $this->t__edit_iframe($query['item_id'], $query['model_id']);
        } else {
            $model = D($query['model_id']);
            $detail = (array)$model->find($query['item_id']);
            $assign['field_list'] = $model->get_admin_publish_fields($detail);

            return bra_res(1, 'get', '', $assign);
        }
    }

    public function user_menu_idx () {
        global $_W, $_GPC;
        $bra_m = D('user_menu');
        $assign['filter_info'] = $ret = $bra_m->gen_ajax_filter_forms($_W['menu']['id']);
        //$admin_menuList = $this->get_module_admin_menus($module, 1);
        $ret['where']['is_admin'] = 0;
        if (isset($ret['where']['module'])) {
            $ret['where']['module'] = ['IN', ['bra', $ret['where']['module']]];
        }
        $assign['tree_data'] = $tree_data = $bra_m->load_tree(0, $ret['where'], 'menu_name', 'id');
        $hide = $show = [];
        $assign['field_list'] = $field_list = $bra_m->get_admin_column_fields($hide, $show);

        return bra_res(1, '', '', $assign);
    }

    /* connect */
    public function admin_menu_idx ($query) {
        global $_W, $_GPC;
        $bra_m = D('user_menu');
        $assign['filter_info'] = $ret = $bra_m->gen_ajax_filter_forms([]);
        $ret['where']['is_admin'] = 1;
        if (isset($ret['where']['module'])) {
            $ret['where']['module'] = ['IN', ['bra', $ret['where']['module']]];
        }
        $assign['tree_data'] = $bra_m->load_tree(0, $ret['where'], 'menu_name', 'id');
        $hide = $show = [];
        $assign['field_list'] = $field_list = $bra_m->get_admin_column_fields($hide, $show);

        return bra_res(1, '', '', $assign);
    }

    /**
     * menu edit
     * @param $query
     * @return mixed
     */
    public function menu_edit ($query) {
        global $_W, $_GPC;
        if (is_bra_access(0, 'post')) {
            $update = $query['data'];
            $menu = (array)D('user_menu')->find($query['item_id']);
            if ($menu) {
                D('user_menu')->item_edit($update, $menu['id']);
            }
            foreach ($update as $k => $v) {
                if (!isset($menu[$k]) || $menu[$k] == $v) {
                    unset($update[$k]);
                }
            }
            //
            $config_data = config('bra_set');
            $config_data['diy_menus'][$query['item_id']] = $update;
            BraFS::write_config('bra_set', $config_data);

            return bra_res(1, '成功 !', '', $update);
        } else {
            $model = D($query['model_id']);
            $detail = (array)$model->find($query['item_id']);
            $config_data = config('bra_set');
            $diy_menu = $config_data['diy_menus'][$query['item_id']];
            if (is_array($diy_menu)) {
                $detail = array_merge($detail, $diy_menu);
            }
            $assign['field_list'] = $model->get_admin_publish_fields($detail);
            $assign['bra_scripts'] = $_W['bra_scripts'];

            return bra_res(1, 'get', '', $assign);
        }
    }

    public function menu_toggle ($query) {
        global $_W, $_GPC;
        $config_data = config('bra_set');
        $hidden_menus = $config_data['menus'] ?? [];
        if (is_numeric($query['menu_id']) && $query['menu_id'] > 0) {
            if (in_array($query['menu_id'], $hidden_menus)) {
                $new_arr = array_flip($hidden_menus);
                $key = $new_arr[$query['menu_id']];
                unset($hidden_menus[$key]);
                $msg = '显示菜单成功';
            } else {
                $hidden_menus[] = $query['menu_id'];
                $msg = '隐藏菜单成功';
            }
            $config_data['menus'] = $hidden_menus;
            BraFS::write_config('bra_set', $config_data);
        } else {
            $msg = '无效操作';
        }

        return bra_res(1, $msg, '', $hidden_menus);
    }

    /**
     *
     * batch menu setting
     * @return Json
     * @throws Exception
     */
    public function sub_menu_idx ($query) {
        global $_W, $_GPC;
        $parent_id = (int)$query['parent_id'];
        $assign['parent'] = $parent_info = UserMenu::find($parent_id);
        if (is_bra_access(0, 'post')) {
            if (!$parent_info) {
                return bra_res(500, "父菜单不存在", '', $query);
            }
            $data = $query['data'];
            $new = $query['new'];
            /**
             * save the old menu
             */
            if (!empty($data)) {
                foreach ($data as $kk => $vall) {
                    D('user_menu')->item_edit($vall, $vall['id']);
                }
            }
            /*  add the new menu and  */
            if (!empty($new)) {
                foreach ($new as $k => $val) {
                    if ($parent_info['root_id'] != 0) {
                        $new[$k]['root_id'] = $parent_info['root_id'];
                    }
                    $new[$k]['params'] = htmlspecialchars_decode($new[$k]['params']);
                    $new[$k]['is_admin'] = $parent_info['is_admin'];
                    $new[$k]['parent_id'] = $parent_id;
                    UserMenu::insert($new[$k]);
                }
            }

            return bra_res(1, "成功");
        } else {
            $assign['menuList'] = D('user_menu')->bra_where(['parent_id' => $parent_id])->orderBy('listorder', 'asc')->get();

            return bra_res(1, 'get', '', $assign);
        }
    }

    /**
     * delete a menui
     */
    public function menu_del ($query) {
        $id = (int)$query['item_id'];
        $menu = UserMenu::find($id);
        //找到子菜单
        $where = ['parent_id' => $id];
        $test = UserMenu::where($where)->first();
        if ($test) {
            return bra_res(2, "“操作失败 , 请先删除子菜单”");
        } else {
            $ctrl = Str::studly($menu['ctrl']);
            $class = "\\Bra\\{$menu['app']}\\pages\\{$ctrl}";
            if (method_exists($class, $menu['act'])) {
                return bra_res(500, '请先删除代码 , 才能删除菜单');
            }
            if (!$test && $menu->destroy($id)) {
                return bra_res(1, '操作完成');
            } else {
                return bra_res(2, '操作失败');
            }
        }
    }

    public function item_add ($query) {
        global $_W, $_GPC;
        if (is_bra_access(0, 'post')) {
            $_GPC['data'] = $query['data'] ?? [];

            return $this->t__add_iframe($query['model_id'], [], ['check_token' => false]);
        } else {
            $model = D($query['model_id']);
            $assign['field_list'] = $model->get_admin_publish_fields([]);
            $assign['bra_scripts'] = $_W['bra_scripts'];

            return bra_res(1, 'get', '', $assign);
        }
    }

    public function modules_idx ($query) {
        global $_W, $_GPC;
        if (is_bra_access(0, 'post')) {
            $res = $this->t__bra_table_idx('modules');
            $res['code'] = 0;

            return $res;
        }
    }

    public function models_label ($query) {
        global $_W;
        $_W['mapping']['model_id'] = $ext['model_id'] = $query['model_id'];
		if (is_bra_access(0, 'post')) {
            $config['show_old_data'] = false;

            return $this->t__idx('models_label', [], '', 15, $config);
        } else {
            return $this->t__idx_page('models_label', $ext);
        }
    }

    public function field_group_set ($query) {
        global $_W, $_GPC;
        $model_id = $query['model_id'];
        $labels = D('models_label')->bra_where(['model_id' => $model_id])->get();
        $model = D($model_id);
        $setting = $model->_TM['setting'];
        $table_name = $model->_TM['table_name'];
        //load bra_set
        $config_data = config('bra_set');
        if (is_bra_access(0, 'post')) {
            $label_id = $query['label_id'];
            $fields = $setting['fields'];
            $__todo_fields = $query['data'] ?? [];
            foreach ($fields as &$field) {
                if (!empty($field['field_group']) && $field['field_group'] == $label_id) {
                    $field['field_group'] = "";
                }
            }
            foreach ($__todo_fields as $k => $field_key) {
                if (isset($fields[$field_key])) {
                    $fields[$field_key]['field_group'] = $label_id;
                    $fields[$field_key]['listorder'] = 10000 - ($k + 1) * 100;
                }
            }
            BraArray::sort_by_val_with_key($fields, 'listorder');
            if ($model->_TM['id'] >= 50000) {
                $setting['fields'] = $fields;
                $model->_TM->setting = $setting;
                $res = $model->_TM->save();
            } else {
                $config_data['models'][$table_name]['Bra_Fields'] = $fields;
                BraFS::write_config('bra_set', $config_data);
            }
            new BraModel($model_id, null, true);

            return bra_res(1, '保存成功' . $res, '', $fields);
        } else {
            $assign['page_title'] = $model->_TM['title'] . '表单分组设置';
            $assign['labels'] = $labels;
            $assign['model'] = $model;
            $assign['bar_text'] = "拖拖拽拽来排序,跨组存多的那组,跨组次次都要存!";

            return $assign;
        }
    }

    public function models_field_idx ($query) {
        $model_id = $query['model_id'];
        $model = D((int)$model_id);
        $updated = false;
        $assign['model_info'] = $model->_TM;
        $setting = $model->_TM['setting'];
        //all fields
        $table_fields_lists = DB::connection()->getSchemaBuilder()->getColumnListing($model->_TM['table_name']);
        $fields = $bra_fields = $setting['fields'];
        $table_name = $model->_TM['table_name'];
        //load bra_set
        $config_data = config('bra_set');
        $diy_fields = $config_data['models'][$table_name]['Bra_Fields'] ?? [];
        //BUG FIX - Extra Fields remove 2017-07-06 Arthur
        if (is_array($fields)) {
            foreach ($fields as $field_name => $field) {
                if (!$model->field_exits($field_name)) {
                    unset($diy_fields[$field_name]);
                    unset($fields[$field_name]);
                    $updated = true;
                    $config_data['models'][$table_name]['Bra_Fields'] = $diy_fields;
                }
                //merge to get real field data
                if ($diy_fields[$field_name]) {
                    $bra_fields[$field_name] = array_merge($field, $diy_fields[$field_name]);
                }
                if (!isset($field['listorder'])) {
                    $fields[$field_name]['listorder'] = 100;
                }
            }
        }
        foreach ($diy_fields as $k => $field) {
            if (empty($bra_fields[$k])) {
                $bra_fields[$k] = $field;
            }
        }
        //删除数据库字段没有的字段
        unset($setting['fields']['list_order']);
        $fields = $fields ?? [];
        BraArray::sort_by_val_with_key($fields, 'listorder');
        if ($updated) {
            $setting['fields'] = $fields;
            $model->_TM->setting = $setting;
            $model->_TM->save();
            BraFS::write_config('bra_set', $config_data);
        }
        $fields = $model->_TM->setting['fields'];
        $assign['table_fields_lists'] = $table_fields_lists;
        $assign['fields'] = $bra_fields;
        $assign['page_title'] = $model->_TM->title;
        $assign['bar_text'] = $model->_TM->title;

        return bra_res(1, 'ok', '', $assign);
    }

    public function model_field_edit ($query) {
        $model_id = $query['model_id'];
        $field_name = $query['field_name'];
        $where = [];
        $where['id'] = $model_id;
        $model = new BraModel($model_id, null, true);
        $assign['model_info'] = $model->_TM;
        $setting = $model->_TM['setting'];
        $fields = $bra_fields = $setting['fields'];
        $field_detail = $fields[$field_name] ?? [];
        if (is_bra_access(0, 'post')) {
            $_data = $query['data'];
            $module = module_exist($model->_TM['module_id']);
            $config_data = config('bra_set');
            //db save
            $setting['fields'][$field_name] = $_data;
            $model->_TM->setting = $setting;
            $model->_TM->save();
            //ext app
            if ($model->_TM['id'] >= 50000 || $module['ext_app'] == 1) { //diy or ext
                unset($config_data['models'][$model->_TM['table_name']]['Bra_Fields'][$field_name]);//rm set
            } else {
                //diff data keep the org
                foreach ($field_detail as $k => $val) {
                    if ($_data[$k] == $val) {
                        unset($_data[$k]); //rm same data as official
                    }
                }
                //save config
                $config_data['models'][$model->_TM['table_name']]['Bra_Fields'][$field_name] = $_data;
                if (empty($config_data['models'][$model->_TM['table_name']]['Bra_Fields'])) {
                    unset($config_data['models'][$model->_TM['table_name']]['Bra_Fields']);
                }
                if (empty($config_data['models'][$model->_TM['table_name']])) {
                    unset($config_data['models'][$model->_TM['table_name']]);
                }
            }
            BraFS::write_config('bra_set', $config_data);
            new BraModel($model_id, null, true);

            return bra_res(1, "ok");
        } else {
            //field detail
            $fields = $model->get_all_fields(true);
            $assign['detail'] = $fields[$field_name] ?? [];
            $assign['model_id'] = $model_id;
            $assign['field_name'] = $field_name;
            //
            $assign['role_id_opts'] = D('users_admin')->load_options('role_id');

            return bra_res(1, 'ok', '', $assign);
        }
    }

    public function get_model_fields () {
        global $_W, $_GPC;
        if (!$_GPC['mid']) {
            return json(bra_res(404, '404'));
        }
        $model = D($_GPC['mid']);
        $model_info = $model->model_info;
        $setting = $model_info['setting'];
        if (!$model) {
            return json(bra_res(404, '404 2'));
        }
        //all fields
        $table_fields_lists = $model->getTableFields([]);
        $fields = $model_info['setting']['fields'];
        //system fields
        $strip_fields = ['id'];
        //BUG FIX - Extra Fields remove 2017-07-06 Arthur
        if (is_array($fields)) {
            foreach ($fields as $field_name => $field) {
                if (!in_array($field_name, $table_fields_lists)) {
                    unset($fields[$field_name]);
                }
                if (!$field['listorder']) {
                    $fields[$field_name]['listorder'] = 100;
                }
            }
        }
        //删除数据库字段没有的字段
        unset($setting['fields']['list_order']);
        MhcmsArray::Ordenar_Array($fields, 'listorder', true);
        $setting['fields'] = $fields;
        $model_info->setting = $setting;
        $res = $model_info->save();
        $fields = $model_info['setting']['fields'];
        //
        foreach ($table_fields_lists as $k => $v) {
            if (in_array($v, $strip_fields)) {
                unset($table_fields_lists[$k]);
            }
        }
        $data['table_fields_lists'] = $table_fields_lists;
        $data['model'] = $model;
        $data['model_info'] = $model_info;
        $data['fields'] = $fields;

        return json(bra_res(1, 'ok', $data));
    }

    /* modules */
    public function get_model_field () {
        global $_W, $_GPC;
        $field_name = $_GPC['field_name'];
        $model = D($_GPC['mid']);
        $model_info = $model->model_info;
        $field_detail = isset($model_info['setting']['fields'][$field_name]) ? $model_info['setting']['fields'][$field_name] : [];
        $data['model'] = $model_info;
        $data['detail'] = $field_detail;
        $data['model_id'] = $model->model_info['id'];
        $data['field_name'] = $field_name;

        return json(bra_res(1, 'ok', $data));
    }

    public function get_modules () {
        $data = D('modules')->select();

        return json(bra_res(1, 'ok', $data));
    }

    /**
     * open dev mode
     * @return mixed
     */
    public function open_dev () {
        $models = D('models')->where('update_with_data', '=', 1)->select()->get();
        $tables = [];
        foreach ($models as $model) {
            $model = (array)$model;
            $tables[$model['table_name']] = $this->check_dev_table($model['table_name'], true);
        }

        return $this->is_dev_ready();
    }

    /**
     * dev check for a table
     * @param $table_name
     * @param bool $update
     * @return mixed
     */
    public function check_dev_table ($table_name, $update = false) {
        $m_db_config = Db::connection()->getConfig();
        $db_name = $m_db_config['database'];
        $prefix = $m_db_config['prefix'];
        $table_name = $prefix . $table_name;
        $sql = "SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA ='$db_name' AND FIND_IN_SET (TABLE_NAME ,'$table_name')";
        $res = DB::select($sql);
        $increment = $res[0]->AUTO_INCREMENT;
        if ($update && $increment < 50000) {
            $u_sql = "alter table $table_name auto_increment= 50000";
            Db::statement($u_sql);
        } else {
            return $increment;
        }
    }

    /**
     *  is site ready for dev
     */
    public function is_dev_ready () {
        $models = D('models')->where('update_with_data', '=', 1)->select()->get();
        $tables = [];
        foreach ($models as $model) {
            $model = (array)$model;
            $tables[$model['table_name']] = $this->check_dev_table($model['table_name']);
            if ($tables[$model['table_name']] < 50000) {
                return bra_res(500, '您的站点没有开启开发模式!');
            }
        }

        return bra_res(1, '您的站点成功开启开发模式!');
    }

    /**
     * refresh_model
     */
    public function refresh_model () {
        $bra_m = D('models');
        $tables = DB::select('SHOW TABLES');
        $data['tables'] = $tables = array_map('current', $tables);
        $config = Db::connection()->getConfig();
        $prefix = $config['prefix'];
        Db::beginTransaction();
        $i = 0;
        $exists = [];
        foreach ($tables as $table) {
            if (strpos($table, $prefix) === false) {
                continue;
            }
            $i++;
            $insert = [];
            $_model_name = str_replace($prefix, '', $table);
            $test = $bra_m->bra_where(['table_name' => $_model_name])->first();
            if (!$test) {
                $insert['table_name'] = $_model_name;
                $insert['title'] = $_model_name;
                try {
                    $id = $bra_m->insert($insert);
                } catch (Exception $e) {
                    Db::rollback();

                    return ['code' => 500, 'msg' => $e->getMessage()];
                }
            } else {
                $data['exists'][] = $table;
            }
        }
        Db::commit();

        return bra_res(1, 'ok' . $i, "", $data);
    }

    /**
     * add_module
     */
    public function add_module () {
        global $_W, $_GPC;
        if ($this->isPost()) {
            $model = D("modules");
            $module_data = [];
            $module_data['module_sign'] = $module_data['module'] = $module_data['module_name'] = $_GPC['module'];
            $module_data['ext_app'] = 1;
            $module_data['is_seo'] = 1;
            $module_data['log_access'] = 1;
            $module_data['is_app'] = 1;
            $module_data['status'] = 1;
            $this->gen_navs($module_data['module_sign'], $module_data['module_name']);
            if (!module_exist($module_data['module_sign'])) {
                $data = $model->model_info->add_content($module_data);

                return json($data);
            } else {
                return json(bra_res(2, '模块已经存在'));
            }
        }
    }

    public function gen_navs ($module_sign, $module_name) {
        $main_menu = [
            'user_menu_name' => "$module_name",
            'user_menu_module' => $module_sign,
            'user_menu_controller' => '#',
            'user_menu_action' => '#',
            'user_menu_mini' => '',
            'user_menu_display' => '1',
            'user_menu_parentid' => '222',
            'user_menu_params' => '0',
            'user_menu_listorder' => '1000',
            'user_menu_icon' => 'icon angle right',
            'site_id' => '0',
            'is_admin' => '1',
            'root_id' => '0',
            'module' => "$module_sign",
            'alias' => '0',
            'debug' => '0',
            'class' => '',
            'content' => ''
        ];
        $main_parent_id = $this->create_nav($main_menu);
        if ($main_parent_id) {
            //create main menu
            $nav_menu = [
                'user_menu_name' => '导航管理',
                'user_menu_module' => 'core',
                'user_menu_controller' => 'admin_nav',
                'user_menu_action' => 'index',
                'user_menu_mini' => '',
                'user_menu_display' => '1',
                'user_menu_parentid' => "$main_parent_id",
                'user_menu_params' => "module=$module_sign",
                'user_menu_listorder' => '1000',
                'user_menu_icon' => 'icon angle right',
                'site_id' => '0',
                'is_admin' => '1',
                'root_id' => '0',
                'module' => "$module_sign",
                'alias' => '1028',
                'debug' => '0',
                'class' => '',
                'content' => ''
            ];
            $nav_res = $this->create_nav($nav_menu);
            //setting menu
            $setting_menu = [
                'user_menu_name' => '模块配置',
                'user_menu_module' => 'core',
                'user_menu_controller' => 'modules',
                'user_menu_action' => 'module_setting',
                'user_menu_mini' => '',
                'user_menu_display' => '1',
                'user_menu_parentid' => "$main_parent_id",
                'user_menu_params' => "module=$module_sign",
                'user_menu_listorder' => '1000',
                'user_menu_icon' => 'icon angle right',
                'site_id' => '0',
                'is_admin' => '1',
                'root_id' => '0',
                'module' => "$module_sign",
                'alias' => '1028',
                'debug' => '0',
                'class' => '',
                'content' => ''
            ];
            $this->create_nav($setting_menu);
            //options menu
            $options_menu = [
                'user_menu_name' => '选项管理',
                'user_menu_module' => 'core',
                'user_menu_controller' => 'admin_options',
                'user_menu_action' => 'show_index',
                'user_menu_mini' => '',
                'user_menu_display' => '1',
                'user_menu_parentid' => "$main_parent_id",
                'user_menu_params' => "module=$module_sign",
                'user_menu_listorder' => '1000',
                'user_menu_icon' => 'icon angle right',
                'site_id' => '0',
                'is_admin' => '1',
                'root_id' => '0',
                'module' => "$module_sign",
                'alias' => '1028',
                'debug' => '0',
                'class' => '',
                'content' => ''
            ];
            $this->create_nav($options_menu);
        }
    }

    public function create_nav ($menu_data) {
        $model = conjunto_modelo('user_menu');
        $where = ['user_menu_module' => $menu_data['user_menu_module'],
            'user_menu_controller' => $menu_data['user_menu_controller'],
            'user_menu_action' => $menu_data['user_menu_action'],
            'user_menu_params' => $menu_data['user_menu_params'],
        ];
        $detail = $model->where($where)->find();
        if (!$detail) {
            return $model->insert($menu_data, false, true);
        } else {
            return $detail['id'];
        }
    }

}
