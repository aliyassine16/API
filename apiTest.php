<?php

/**
 *
 */
class apiTest
{

    private $client_id = "admin";
    private $apiDomain = "http://api.local/";
    private $hapikey = "5f4dcc3b5aa765d61d8327deb882cf99";
    private $error_message = "";

    function __construct()
    {

    }

    public function curl_($json_data){
        //ob_start();
        $out = fopen('php://output', 'w');

        $authenticationArray = array(
            'ApiKey' => $this->hapikey,
            'ClientId' => $this->client_id
        );

        $post_json = json_encode($authenticationArray);

        $endpoint_token = $this->apiDomain . "api/client/GetAccessToken";
        //echo $endpoint_token;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, true);

        curl_setopt($ch, CURLOPT_URL, $endpoint_token);
        $headers = array(
            'Content-Type: application/json'
        );
        //curl_setopt($ch, CURLOPT_VERBOSE, true);
        //curl_setopt($ch, CURLOPT_STDERR, $out);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);



        curl_close($ch);
        $json_response = json_decode($response);
        fclose($out);
        //$debug = ob_get_clean();
        echo "<pre>";
        //echo $debug;
        echo "<pre>";

    }

    public function getClientName()
    {

        //ob_start();
        $out = fopen('php://output', 'w');

        $authenticationArray = array(
            'ApiKey' => $this->hapikey,
            'ClientId' => $this->client_id
        );

        $post_json = json_encode($authenticationArray);

        $endpoint_token = $this->apiDomain . "api/client/GetAccessToken";
        //echo $endpoint_token;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, true);

        curl_setopt($ch, CURLOPT_URL, $endpoint_token);
        $headers = array(
            'Content-Type: application/json'
        );
        //curl_setopt($ch, CURLOPT_VERBOSE, true);
        //curl_setopt($ch, CURLOPT_STDERR, $out);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);



        curl_close($ch);
        $json_response = json_decode($response);
        //var_dump($json_response);


        if (isset($json_response)) {


            $accessToken = $json_response->data->accessToken;

            //echo $accessToken;
            // now we have the access token
            if (isset($accessToken)) {


                $authenticationArray = array(
                    'AccessToken' => $accessToken,
                    'ApiKey' => $this->hapikey,
                    'ClientId' => $this->client_id
                );

                $post_json = json_encode($authenticationArray);


                $endpoint_token = $this->apiDomain . "api/client/getClientName";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_POST, true);

                curl_setopt($ch, CURLOPT_URL, $endpoint_token);

                $headers = array(
                    'Content-Type: application/json'
                );


                //curl_setopt($ch, CURLOPT_VERBOSE, true);
                //curl_setopt($ch, CURLOPT_STDERR, $out);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post_json);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);




                curl_close($ch);

                $json_response = json_decode($response);

                if (isset($json_response)) {

                var_dump($json_response);
                }
            }

        }
        fclose($out);
        //$debug = ob_get_clean();
        echo "<pre>";
        //echo $debug;
        echo "<pre>";


    }
}

?>