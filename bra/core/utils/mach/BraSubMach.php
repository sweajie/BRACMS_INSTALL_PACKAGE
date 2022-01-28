<?php

namespace Bra\core\utils\mach;

use EasyWeChat\Factory;
use EasyWeChat\MicroMerchant\Application;

class BraSubMach {
    public Application $app;
    public $pay_config;

    public function __construct ($config = []) {
        if (!$config) {
            $done['pay_way_id'] = 4;
            $pay_way = (array)D('pay_way_config')->with_site()->bra_where($done)->first();
            $this->pay_config = json_decode($pay_way['config'], 1);
        } else {
            $this->pay_config = $config;
        }
        if ($this->pay_config['apiclient_cert']) {
            $cert_path = annex_url($this->pay_config['apiclient_cert'], true);
        }
        if ($this->pay_config['apiclient_key']) {
            $key_path = annex_url($this->pay_config['apiclient_key'], true);
        }
        $config = [
            // 必要配置
            'mch_id' => $this->pay_config['mchid'], // 服务商的商户号
            'key' => $this->pay_config['apikey'], // API 密钥
            'apiv3_key' => $this->pay_config['apikey'], // APIv3 密钥
            // API 证书路径(登录商户平台下载 API 证书)
            'cert_path' => $cert_path ?? '', // XXX: 绝对路径！！！！
            'key_path' => $key_path ?? '', // XXX: 绝对路径！！！！
            // 以下两项配置在获取证书接口时可为空，
            //在调用入驻接口前请先调用获取证书接口获取以下两项配置,如果获取过证书可以直接在这里配置，也可参照本文档获取平台证书章节中示例
            // 'serial_no'     => '获取证书接口获取到的平台证书序列号',
            // 'certificate'   => '获取证书接口获取到的证书内容'
            // 以下为可选项
            // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
            'response_type' => 'array',
            'appid' => $this->pay_config['app_id'] // 服务商的公众账号 ID
        ];
        if ($this->pay_config['serial_no']) {
            $config['serial_no'] = $this->pay_config['serial_no'];
        }
        if ($this->pay_config['certificates']) {
            $config['certificate'] = $this->pay_config['certificates'];
        }
        $this->app = Factory::microMerchant($config);
    }

    public function get_cert () {
        // 获取到证书后可以做缓存处理，无需每次重新获取 sodium
        return $response = $this->app->certficates->get();
        // 获取到平台证书后，可以直接使用 setCertificate 方法把证书配置追加到配置项里面去
        // $this->app->setCertificate(string $certificate, string $serialNo);
    }

    public function upload_file ($path) {
        return $response = $this->app->media->upload($path);
    }

    public function submit_apply ($data) {
        //create data base[
        //            'business_code' => '123456', // 业务申请编号
        //            'id_card_copy' => 'media_id', // 身份证人像面照片
        //            // ...
        //            // 参数太多就不一一列出，自行根据 (小微商户专属文档 -> 申请入驻api) 填写
        //        ]
        $data = array('business_code' => $data['id']) + $data;
        $data['id_card_name'] = $data['account_name'];

        unset($data['id']);
        unset($data['status']);
        unset($data['area_id']);
        unset($data['mach_id']);


        return $result = $this->app->submitApplication($data);
    }

    public function check_status ($applyment_id, $business_code) {
        $applymentId = $applyment_id;//商户申请单号(申请入驻接口返回)
        $businessCode = $business_code; // 业务申请编号(business_code)

        return $this->app->getStatus($applymentId, $businessCode);
    }

    public function query_withdraw () {
        return $response = $this->app->withdraw->queryWithdrawalStatus($date, $subMchId = '');
    }

    public function submit_withdraw ($date) {
        return $response = $this->app->withdraw->requestWithdraw($date, $subMchId = '');
    }

    public function subscribe_config ($subAppId, $subscribeAppId) {
        return $response = $this->app->merchantConfig->setFollowConfig(
            $subAppId,
            $subscribeAppId, $receiptAppId = '', $subMchId = '');
    }

    public function pay_dir_add ($jsapiPath, $appId = '', $subMchId = '') {
        return $response = $this->app->merchantConfig->addPath($jsapiPath, $appId = '', $subMchId = '');
    }

    public function bind_appid ($subAppId, $appId, $subMchId) {
        return $response = $this->app->merchantConfig->bindAppId($subAppId, $appId = '', $subMchId = '');
    }

    public function check_config ($subMchId, $appId) {
        return $response = $this->app->merchantConfig->getConfig($subMchId = '', $appId = '');
    }
}
