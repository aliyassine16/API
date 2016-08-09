<?php

require_once(__DIR__.'/base_class.php');

class client extends base_class
{

	function GetAccessToken(){

		
		// 				http://api.local/api/client/getAccessToken/
		

		$data= array("accessToken"=>"1234-5678");

		return $this->success($data);
	}

	function getClientName(){

		if(isset($_POST) && isset($_POST["accessToken"])){
			$data= array("clientName"=>"Cambridge Analytica");
			return $this->success($data);
		}else{
			$data= "Invalid accessToken";
			return $this->error($data);

		}

		
	}
}

?>