<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
		'api/bra_service/bra_page' ,
        'bra/bra_cloud/*' ,
        'bra/bra_service/bra_page' ,
        'wechat/bra_wechat/pay_callback' ,
        'wechat/bra_wechat/api' ,
        'wechat/platform_service/notify' ,
        'wechat/message_service/callback' ,
        'update/*' ,
        'bra/annex/*'
    ];
}
