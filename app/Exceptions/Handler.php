<?php

namespace App\Exceptions;

use Bra\core\utils\BraException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Throwable;

class Handler extends ExceptionHandler {
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
		\League\OAuth2\Server\Exception\OAuthServerException::class
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register () {
        $this->reportable(function (Throwable $e) {
            //
            global $_W , $_GPC;
            if ($e->getCode() == 419) {
                // Do whatever you need to do here.
                end_resp(bra_res(419, '由于您太久没有操作当前页面会话已经过期 , 请刷新页面重试!'));
            }


			Log::write("info" , $e->getCode());
			Log::write("info" , $e->getMessage());
        });


    }

	/**

	 * Convert an authentication exception into a response.

	 *

	 * @param  \Illuminate\Http\Request  $request

	 * @param  \Illuminate\Auth\AuthenticationException  $exception

	 * @return \Symfony\Component\HttpFoundation\Response

	 */

	protected function unauthenticated($request, AuthenticationException $exception)
	{
		if(str_contains($request->path() , '/admin_')){
			$route = make_url('bra_admin/passport/login');
			if(!$request->ajax()){
				Cookie::queue('last_auth_url' , make_url('bra_admin/index/index'));
			}
		}else{
			$route = make_url('bra/passport/login');
			Cookie::queue('last_auth_url' , $request->url());
		}
		return $request->expectsJson()
			? response()->json(['message' => $exception->getMessage()], 401)
			: redirect()->guest($exception->redirectTo() ?? $route);
	}
}
