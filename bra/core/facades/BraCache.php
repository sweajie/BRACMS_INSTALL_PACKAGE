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
namespace Bra\core\facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed get_cache(string $key,  $tags = 'bra_data')
 * @method static mixed set_cache(string $key, mixed $default , $ttl = 3600)
 * @method static mixed del_cache(string $name,  $tags = 'bra_data')
 * @method static mixed clear_cache($tags = 'bra_data')
 */
class BraCache extends Facade {

    protected static function getFacadeAccessor () {
        return 'bra_cache';
    }


}
