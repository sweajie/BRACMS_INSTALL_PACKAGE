<?php

use App\Actions\BraCms\InstallIndex;
use App\Actions\BraCms\UpdateIndex;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

if (!file_exists(config_path('bracms_install.lock'))) {
	Route::prefix('install')->group(function () {
		Route::any('/', function (InstallIndex $page) {
			return $page->page_data;
		})->where('any', '.*');
		Route::any('/{any}', function (InstallIndex $page) {
			return $page->page_data;
		})->where('any', '.*');
	});
	Route::any('/{any}', function (InstallIndex $page) {
		return $page->page_data;
	})->where('any', '.*');
	Route::any('/', function (InstallIndex $page) {
		return $page->page_data;
	})->where('any', '.*');
} else {
	$modules = Illuminate\Support\Facades\Cache::remember("modules", 3600, function () {
		return DB::table("modules")->get()->toArray();
	});
	foreach ($modules as $module) {
		if ($module->module_sign != "bra_admin") {
			Route::prefix($module->module_sign)->group(function () use ($module) {
				Route::any('/', function (Bra\core\objects\BraPage $page) {
//						throw new \Bra\core\utils\BraException('test');
					return $page->page_data;
				})->where('any', '.*');
				Route::any('/{any}', function (Bra\core\objects\BraPage $page) {
					return $page->page_data;
				})->where('any', '.*');
			});
		}
	}
	Route::prefix('bra_admin')->group(function () {
		Route::any('/passport/{any}', function (Bra\core\objects\BraPage $page) {
			return $page->page_data;
		})->where('any', '.*');
		Route::middleware(['admin_auth', 'auth_session'])->any('/', function (Bra\core\objects\BraPage $page) {
			return $page->page_data;
		})->where('any', '.*');
		Route::middleware(['admin_auth', 'auth_session'])->any('/{any}', function (Bra\core\objects\BraPage $page) {
			return $page->page_data;
		})->where('any', '.*');
	});

	Route::prefix('update')->group(function () {
		Route::middleware(['admin_auth', 'auth_session'])->any('/', function (UpdateIndex $page) {
			return $page->page_data;
		})->where('any', '.*');
		Route::middleware(['admin_auth', 'auth_session'])->any('/{any}', function (UpdateIndex $page) {
			return $page->page_data;
		})->where('any', '.*');
	});
}
