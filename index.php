<?php
//error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED);

include("core/akari.php");

if(!CLI_MODE){
	akari::getInstance()->initApp(__DIR__)->run();
}else{
	if(count($argv) < 2){
	    echo("Akari Framework\n");
	    echo("CLI模式时，执行至少需要指定task的名称.  (no task command)\n");
	    echo("\nusage: php -f index.php taskName parameter\n\n");
	    exit();
	}
	akari::getInstance()->initApp(__DIR__)->run($argv[1], false, $argv[2]);
}