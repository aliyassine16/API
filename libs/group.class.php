<?php

require_once(__DIR__.'/base_class.php');

class group extends base_class
{

	function test($grup){

		
		// 				http://api.local/api/group/test/11/12	

		

		$data= array('story' => 1 ,"_GET"=>$grup);

		return $this->success($data);
	}
}

?>