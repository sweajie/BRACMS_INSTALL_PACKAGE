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
namespace Bra\core\annex;

abstract class AnnexEngine
{

    public $auth, $config, $mhcms_room_id;
    public $user;

    public abstract function test();

    public abstract function form($field);

    public abstract function upload($annex, $local_path = '');

    public function get_prefix()
    {
        return $this->config['url'];
    }

}
