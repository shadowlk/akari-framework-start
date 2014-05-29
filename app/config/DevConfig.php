<?php
Class DevConfig extends BaseConfig{
	public $appName = "Dev Title";
	public $appBaseURL = "http://localhost/";

	public $KDAPI = Array(
        "appkey" => "",
        "secret" => "",
	    "uc" => "http://localhost/uc/",
	    "appid" => ""
	);
	
	public $database = [
		'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=kdbbs',
        'username' => 'root',
        'password' => '',
        'options' => [PDO::MYSQL_ATTR_INIT_COMMAND => 'set names "utf8"']
	];
}