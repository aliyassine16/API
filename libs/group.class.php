<?php

require_once(__DIR__.'/base_class.php');

class group extends base_class
{

	function test(){
		// 				http://api.local/api/group/test/11/12	

		

		$data= array('story' => 1 ,"_GET"=>json_encode($_SESSION));

		return $this->success($data);
	}
}

?>