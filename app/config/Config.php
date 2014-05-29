<?php
Class Config extends BaseConfig{
	public $appName = "Test";

	public $upyun = Array(
		"username" => "",
		"password" => "",
		"bucket" => ""
	);

	public $KDAPI = Array(
		"appkey" => "",
	    "secret" => "",
	    "uc" => "http://uc.kdays.cn/",
	    "appid" => ""
	);

	public $database = Array(
		'dsn' => 'mysql:host=127.0.01;port=3306;dbname=kdbbs',
        'username' => 'root',
        'password' => '',
        'options' => Array(
        	PDO::MYSQL_ATTR_INIT_COMMAND => 'set names "utf8"'
        )
	);
}