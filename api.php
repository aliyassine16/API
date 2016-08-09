<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');
ini_set('memory_limit','-1');
set_time_limit(0);



header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Access-Control-Allow-Origin: http://localhost");
header("Pragma: no-cache");
header('Content-Type: application/json');

require_once "./libs/db.class.php";
require_once "./libs/api.routing.class.php";
require_once "./libs/session.class.php";

$session = new session;
$session->start();


//credentials check 

if(isset($_POST) && isset($_POST["ClientId"])){
	$client_id=$_POST["ClientId"];
	$api_key=$_POST["hapikey"];
}
else{
	$client_id="admin";
	$api_key=md5("password");
}


$session->login($client_id,$api_key);

if (!$session->check()){
	echo json_encode ( array("success" => 'false', "message" => "login failed","data"=>$_POST));
	exit();
}

$api = new Api;

echo json_encode($api->execute());

$session->logout();


?>
