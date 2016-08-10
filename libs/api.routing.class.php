<?php
require_once(__DIR__.'/db.class.php');
class Api{
	
	private $server;
	private $path_slugs;
	
	function __construct(){
		$this->server = explode("?", $_SERVER['REQUEST_URI']);
		$this->path_slugs = explode("/", $this->server[0]);
	}
	
	private function unset_slug($key){
		if (isset($this->path_slugs[$key])){
			$slug = $this->path_slugs[$key];
			unset($this->path_slugs[$key]);
			return $slug;
		} else {
			return false;
		}
	}
	
	function execute(){
		global $db;

		$this->unset_slug(0);
		$this->unset_slug(1);
		$api_class = $this->unset_slug(2);
		$api_function = $this->unset_slug(3);
		
		$class_location = __DIR__.'/'.$api_class.".class.php";
		//Make sure we can see the api class file
		if(!file_exists($class_location)) return array("success"=>false,"message"=>"API files are missing!");
		require_once $class_location;
		
		if(!class_exists(ucfirst($api_class), false)) return array("success"=>false,"message"=>"API class not found!");
		$api = new $api_class;
		
		if(!method_exists($api, $api_function)) return array("success"=>false,"message"=>"API action not found!");
		
		require_once $_SERVER["DOCUMENT_ROOT"] . '/libs/session.class.php';
		$session = new session();
		$allowed = $session->allowed($api_class, $api_function);

		//Log stuff here
		$nolog = array("getToasts","count","countAlerts", "canView", "password_change");
		if (!in_array($api_function, $nolog)){
			$data = '';
			$dataset = $_REQUEST;
			unset($dataset['PHPSESSID']);
			if (!empty($dataset)){$data = $db->secure(json_encode($dataset));}
			$ip = $_SERVER['REMOTE_ADDR'];
			$success = 1;
			$user_id = $_SESSION['user_id'];
			if (!$allowed['success']) {$success = 0;}
			$db->insert("INSERT INTO `log` (`user_id`,`module`,`action`,`data`,`ip`,`success`) VALUES ('$user_id','$api_class','$api_function','$data',INET_ATON('$ip'),'$success')");
		}
		//End Log stuff here


		if (!$allowed['success']){ return $allowed;}
		$ret= call_user_func_array(array($api, $api_function), $this->path_slugs);
		return $ret;

	}
	
	
	
	
	function draw(){
		?>	
		get:
		<pre>
			<?=var_export($_GET, true)?>
		</pre>
		
		post:
		<pre>
			<?=var_export($_REQUEST, true)?>
		</pre>
		
		querystring:
		<pre>
			<?=var_export($this->server, true)?>
		</pre>
		
		url:
		<pre>
			<?=var_export($this->path_slugs, true)?>
		</pre>
		<?php
	}
	
}

?>
