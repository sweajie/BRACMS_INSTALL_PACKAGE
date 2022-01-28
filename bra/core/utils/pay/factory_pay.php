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
namespace Bra\core\utils\pay;

use Bra\core\utils\BraException;
use EasyWeChat\Payment\Application;

abstract class factory_pay implements interface_pay {

    /**
     * @var $pay Application
     */
    public $pay;
    public $media_id;
    public $media;
    public $media_mid;
    public $callback_url;
    public $sub_mch_id;
    public $pay_config;

    public function __construct ($media_id, $media_mid, $sub_mch_id = '') {
        $this->media_id = $media_id;
        $this->media_mid = $media_mid;
        $this->media = (array)D($media_mid)->find($media_id);
        if ($sub_mch_id) {
            $this->sub_mch_id = $sub_mch_id;
        }
        if (!$this->media) {
            throw new BraException('MEDIA NOT FOUND');
        }
    }

}
