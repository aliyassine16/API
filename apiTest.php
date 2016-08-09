<?php
/**
* 
*/
class apiTest 
{
	
	function __construct()
	{
		
	}


	private $client_id="admin";
	
	private $apiDomain="http://api.local/";

	private $hapikey = "5f4dcc3b5aa765d61d8327deb882cf99";

	private $error_message = "";

	private function getClientName()
	{
		/***********************************************************************************************************************************/
        /////////////////////////////////////////////// get the access token
		$authenticationArray = array(
			'AuthenticationToken:'=>$this->hapikey,
			'ClientId:'=>$this->client_id
			);

		$post_json = json_encode($authenticationArray);

		$endpoint_token = $this->apiDomain."api/client/GetAccessToken";
		echo $endpoint_token;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, true);

		curl_setopt($ch, CURLOPT_URL, $endpoint_token);
		$headers = array(
			'Content-Type: application/json'
			);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_json);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);
		$json_response = json_decode($response);
        //print_r($json_response);

		if (isset($json_response)) {
			if (isset($json_response->success) && $json_response->success== "false") {
				$this->error_message = $json_response->message;
				return false;
			}
			else{
				
				$accessToken=$json_response->data[0]["accessToken"];
				/**************************************************************************************************************************/
                /////////////////////////////////////////////// get the client name
                //echo $accessToken;
                // now we have the access token
				if(isset($accessToken)) {



					$authenticationArray = array(
						'AccessToken' => $accessToken
						'AuthenticationToken:'=>$this->hapikey,
						'ClientId:'=>$this->client_id
						);

					$post_json = json_encode($authenticationArray);


					$endpoint_token = $this->apiDomain . "api/client/getClientName";
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_POST, true);

					curl_setopt($ch, CURLOPT_URL, $endpoint_token);
					$headers = array(
						'Content-Type: application/json'						
						);
					curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $post_json);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					$response = curl_exec($ch);
					curl_close($ch);

					$json_response = json_decode($response);

					if (isset($json_response)) {

						var_dump($json_response)
					}
				}
			}
		}

	}
}
?>