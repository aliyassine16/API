<?php

require_once(__DIR__ . '/base_class.php');

class client extends base_class
{

    private $accessToken = "1234-5678-1010-5050";

    function getAccessToken()
    {
        // 				http://api.local/api/client/getAccessToken/

        $data = array("accessToken" => $this->accessToken);

        return $this->success($data);
    }

    private function isValidAccessToken($token)
    {
        return ($token == $this->accessToken);
    }

    public function getClientName()
    {

        $data = json_decode(file_get_contents('php://input'));


        if (isset($data) && isset($data->AccessToken) && $this->isValidAccessToken($data->AccessToken)) {
            $data = array("clientName" => "Cambridge Analytica");
            return $this->success($data);
        } else {
            $data = "Invalid Access Token";
            return $this->success($data);

        }


    }
}

?>