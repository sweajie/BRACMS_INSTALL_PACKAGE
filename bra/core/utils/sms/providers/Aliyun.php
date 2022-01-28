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

use Flc\Dysms\Client;
use Flc\Dysms\Request\SendSms;


class Aliyun extends Providers
{

    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    function send($target, $params, $tpl_id, $out_id = '')
    {
        global $_W;
        $config = [
            'accessKeyId' => $this->config['app_key'],
            'accessKeySecret' => $this->config['app_secret'],
        ];
        $client = new Client($config);
        $sendSms = new SendSms;
        $sendSms->setPhoneNumbers($target);
        $sendSms->setSignName($this->config["signature"]);
        $sendSms->setTemplateCode($tpl_id);
        $sendSms->setTemplateParam($params);
        $sendSms->setOutId($out_id);
        $resp_sms = $client->execute($sendSms);

        if ($resp_sms->Message == "OK" && $resp_sms->Code == "OK") {
            $resp['code'] = 1;

            $resp['msg'] = "短信发送成功";
        } else {
            $resp['code'] = 0;
			$resp['msg'] = "短信发送失败 , " . $resp_sms->Message;
            $resp['data'] = $resp_sms;
            $resp['params'] = $params;
        }
        return $resp;

    }
}
