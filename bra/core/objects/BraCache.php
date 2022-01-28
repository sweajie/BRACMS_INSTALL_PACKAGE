<?php

namespace Bra\core\objects;

use Illuminate\Cache\TaggableStore;
use Illuminate\Support\Facades\Cache;

class BraCache {

    private $support_tag = false;

    public function __construct () {
        if (Cache::getStore() instanceof TaggableStore) {
            // We have a taggable cache.
            $this->support_tag = true;
        }

    }

    public function set_cache ($name, $value, $tag = 'bra_data', $expire = null) {
        if (!$expire) {
            $expire = $expire ?? 3600;
        }

        if ($this->support_tag) {
            Cache::tags([$tag])->put($name, $value, $expire);
        } else {
            Cache::put($name, $value, $expire);
        }
    }

    public function get_cache ($name, $tag = 'bra_data') {

        if ($this->support_tag) {
         return   Cache::tags([$tag])->get($name);
        } else {
         return   Cache::get($name);
        }

    }


    public function del_cache ($name , $tag = 'bra_data') {

        if ($this->support_tag) {
            Cache::tags($tag)->forget($name);
        } else {
            Cache::forget($name);
        }
    }

    public function clear_cache ($tag = 'bra_data') {
        if ($this->support_tag) {
            Cache::tags($tag)->flush();
        } else {
            Cache::flush();
        }
    }
}
