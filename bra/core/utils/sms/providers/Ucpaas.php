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
namespace Bra\core\utils\sms\providers;

use Bra\core\utils\sms\providers\Ucpaas\UcpaasApi;

class Ucpaas extends Providers
{
    private $config;

    /**
     * Alidayu constructor.
     * @param $sms_site_config
     */
    public function __construct($config)
    {
        $this->config = $config;
    }


    function send($target, $params, $tpl_id)
    {
        $params = join(',', $params);
        $options['accountsid'] = $this->config['sid'];
        $options['token'] = $this->config['token'];

        $api = new UcpaasApi($options);
        $resp_sms = $api->SendSms($this->config['app_id'], $tpl_id, $params, $target, '');
        $resp_sms = json_decode($resp_sms, 1);

        if ($resp_sms['msg'] == "OK") {
            $resp['code'] = 1;
            $resp['msg'] = $resp_sms['msg'];
            $resp['data'] = $resp_sms;
        } else {
            $resp['code'] = 0;
            $resp['msg'] = "短信发送失败" . $resp_sms['data']['msg'];
            $resp['data'] = $resp_sms;
        }
        return $resp;
    }
}
