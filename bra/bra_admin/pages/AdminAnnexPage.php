<?php

namespace Bra\bra_admin\pages;

use Bra\core\annex\AnnexEngine;
use Bra\core\pages\BraAdminController;
use Bra\core\utils\BraAdminItemPage;
use Illuminate\Support\Facades\DB;

class AdminAnnexPage extends BraAdminController {

    use BraAdminItemPage;

    public function bra_admin_admin_annex_annex_set_idx () {
        A('_hide_filter', 1);
        $test = (array)D('annex_config')->bra_where(['default' => 1])->first();
        A('bar_text', '当前默认存储:<strong>' . $test['provider_id'] . '</strong>');

        return $this->page_data = $this->t__bra_table_idx("annex_provider");
    }

    public function bra_admin_admin_annex_config ($query) {
        global $_W, $_GPC;
        $id = (int)$query['id'];
        $where = [];
        $where['provider_id'] = $id;
        $where['site_id'] = $_W['site']['id'];
        $bra_m = D('annex_config');
        $storge_config = (array)$bra_m->bra_where($where)->first();
        $annex_provider = (array)D('annex_provider')->find($id);
        if (is_bra_access(0, 'post')) {
            $data = $_GPC[$annex_provider['sign']];
            if ($storge_config) {
                $update_data = [];
                $update_data['config'] = json_encode($data);
                $bra_m->bra_where($where)->update($update_data);
            } else {
                $insert_data = [];
                $insert_data['config'] = json_encode($_GPC[$annex_provider['sign']]);
                $insert_data['site_id'] = $_W['site']['id'];
                $insert_data['provider_id'] = $id;
                D('annex_config')->insert($insert_data);
            }

            return $this->page_data = bra_res(1, "操作成功！");
        } else {
            if ($storge_config) {
                $storge_config['config'] = json_decode($storge_config['config'], 1);
            }
            A($storge_config);
            A('provider', $annex_provider);

            return $this->page_data = A_T('bra_admin.admin_annex.config_' . $annex_provider['sign']);
        }
    }

    public function bra_admin_admin_annex_test ($query) {
        global $_W, $_GPC;
        $id = (int)$query['id'];
        $storge = (array)D('annex_provider')->find($id);
        $where = [];
        $where['provider_id'] = $id;
        $where['site_id'] = $_W['site']['id'];
        $storage_config = (array)D('annex_config')->bra_where($where)->first();
        /** @var AnnexEngine $storge_engine */
        if ($storage_config) {
            $class_name = "\\Bra\\core\\annex\\" . $storge['sign'];
            $storge_engine = new $class_name(json_decode($storage_config['config'], 1));

            return $this->page_data = $storge_engine->test();
        } else {
            return $this->page_data = bra_res(2, '请先点击左侧配置 ， 配置好再来测试！');
        }
    }

    public function bra_admin_admin_annex_set_default ($query) {
        $id = (int)$query['id'];
        $bra_m = D('annex_config');
        Db::beginTransaction();
        $bra_m->with_site()->bra_where(['id' => ['>', 0]])->update(['default' => 0]);
        $where_update['provider_id'] = $id;
        $res = $bra_m->bra_where($where_update)->update(['default' => 1],);
        if ($res) {
            Db::commit();

            return $this->page_data = bra_res(1, '操作成功!');
        } else {
            Db::rollback();

            return $this->page_data = bra_res(403, '请先配置当前存储!');
        }
    }

    public function bra_admin_admin_annex_annex_idx () {
        return $this->page_data = $this->t__bra_table_idx("annex");
    }
}
