<?php

class base_class {
    function error($message=null){
        if (isset($message) && $message!=null){
            return ["success" => false, "message" => $message];
        } else {
            return ["success" => false];
        }
    }

    function success($data=null){
        if (isset($data) && $data!=null){
            return ["success" => true, "data" => $data];
        } else {
            return ["success" => true];
        }
    }

    function forbidden($message = null){
        if (isset($message) && message!=null){
            return ["success" => false, "forbidden" => true, "message" => $message];
        } else {
            return ["success" => false, "forbidden" => true];
        }
    }

    function is_not_blank_str($string){
        return ($string != null) && (!empty($string)) && (is_string($string));
    }

    function is_valid_date_string($format, $string){
        $d = DateTime::createFromFormat($format, $string);
        return $d && $d->format($format) == $string;
    }
}

?>