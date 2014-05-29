<?php
!defined("AKARI_PATH") && exit;

Logging::_logInfo("Task Test Running..");

$params = Router::getInstance()->parseTaskParam($params);
if($params['action'] == "start"){
	Logging::_logInfo("starting...");
	sleep(10);
	Logging::_logInfo("doing...");
	sleep(8);
}else{
	Logging::_logErr("not found command $params[action]");
}