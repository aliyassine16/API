<?php

require_once(__DIR__ . '/base_class.php');

class client extends base_class
{

    private $accessToken = "1234-5678";

    function getAccessToken()
    {
        // 				http://api.local/api/client/getAccessToken/

        $data = array("accessToken" => $this->accessToken);

        return $this->success($data);
    }

    function getClientName()
    {

        $data = json_decode(file_get_contents('php://input'));


        if (isset($data) && isset($data->AccessToken) && ($data->AccessToken==$this->accessToken) ) {
            $data = array("clientName" => "Cambridge Analytica");
            return $this->success($data);
        } else {
            $data = "Invalid Access Token";
            return $this->success($data);

        }


    }
}

?>