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
namespace Bra\core\objects;

use App\Models\User;
use ArrayAccess;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BraCMS_W  implements ArrayAccess {

	public User $user;
	public array $site = [];
	public array $mapping = [];
	public string $current_url;
	public array $bra_scripts = [];
	public array $bra_templates = [];

	private Application $app;

	#debug area
	public bool $debug = false;
	public array $sqls = [];
	public array $_time = []; # monitor time loading


	public function __construct () {
		global $_GPC;
		$this->app = require_once PUBLIC_ROOT . '../bootstrap/app.php';

		define('SYS_ROOT', base_path() . DS);
		define('CONFIG_PATH',SYS_ROOT . 'config' . DS);
		define('BRA_ROOT',SYS_ROOT . 'bra' . DS);


	}

	public function run () {
		$kernel = $this->app->make(Kernel::class);
		$response = tap($kernel->handle(
			$request = Request::capture()
		))->send();
		$kernel->terminate($request, $response);
	}

	public function offsetExists ($offset) {

		return isset($this->$offset);
	}

	public function offsetGet ($offset) {

		if(isset($this->$offset)){
			return $this->$offset;
		}

	}

	public function offsetSet ($offset, $value) {
		$this->$offset = $value;
	}

	public function offsetUnset ($offset) {
		$this->$offset = null;
	}

	public function debug_sql () {
		DB::listen(function ($query) {
			$res['sql'] = $query->sql;
			$res['bindings'] = $query->bindings;
			$res['time'] = $query->time;
			$this->sqls[] = $res;
		});
	}
}
