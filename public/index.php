<?php

global $_W, $_GPC;

use Bra\core\objects\BraCMS_W;

define('BRACMS_START_TIME', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
#composer load time , if it takes too long , make something going on down there
$composer_time = microtime(true) - BRACMS_START_TIME;



const DS = DIRECTORY_SEPARATOR;
const PUBLIC_ROOT = __DIR__ . DS;

$_W = new BraCMS_W();
$_W->bra_scripts = [];
$_W->_time['composer'] = $composer_time;

$_W->run();
