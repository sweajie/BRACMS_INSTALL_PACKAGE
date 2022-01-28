<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
//

Illuminate\Support\Facades\Route::options('/bra_service/bra' . '_' . 'page', function () {
	Log::write('error' , "options received");
	Log::write('error' , \Illuminate\Support\Facades\Request::method());
	return 'options is ok';
});

Illuminate\Support\Facades\Route::any('/bra_service/bra' . '_' . 'page', function (Bra\core\objects\BraApiPage $api_page) {
	$els = view()->getShared();
	unset($els['__env']);
	unset($els['app']);
	unset($els['errors']);

	log_events();

	return bra_res(1, '', '', $els);
})->where('any', '.*');
