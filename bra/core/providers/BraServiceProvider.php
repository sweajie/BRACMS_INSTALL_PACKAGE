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
namespace Bra\core\providers;

use Bra\core\objects\BraApiPage;
use Bra\core\objects\BraCache;
use Bra\core\objects\BraPage;
use Illuminate\Support\ServiceProvider;

class BraServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('bra_cache', function()
        {
            return new BraCache();
        });

        $this->app->singleton(BraApiPage::class, function()
        {
            return new BraApiPage(request()->path());
        });

        $this->app->singleton(BraPage::class, function()
        {
            return new BraPage(request()->path());
        });

    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
