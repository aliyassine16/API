<?php
class timezone
{
	function __construct(){
		require_once(__DIR__.'/config.php');
		$this->sql = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	}

	private function check($timezone){
		if (in_array($timezone, timezone_identifiers_list())){
			return true;
		} else {
			return false;
		}
	}

	public function get_user_timezone($user_id = NULL){
		if ($user_id == NULL){
			$user_id = $_SESSION['user_id'];
		} else {
			$user_id = $this->sql->real_escape_string($user_id);
		}
		$data = $this->sql->query("SELECT `setting_value` FROM `user_settings` WHERE `setting` = 'timezone' AND `user_id` = '$user_id'");
		if ($data->num_rows == 0){
			return TIMEZONE;
		} elseif ($data->fetch_object()->setting_value == NULL OR $data->fetch_object()->setting_value == ''){
			return TIMEZONE;
		} elseif ($this->check($data->fetch_object()->setting_value)){
			return $data->fetch_object()->setting_value;
		} else {
			return TIMEZONE;
		}
	}

	public function from_utc($datetime, $timezone = NULL){
		if ($timezone == NULL){
			$timezone = $this->get_user_timezone();
		} elseif (!$this->check($timezone)){
			return false;
		}
		date_default_timezone_set($timezone);
		return date('Y-m-d H:i:s',strtotime("$datetime UTC"));
	}

	public function get_local_time($timezone = NULL){
		if ($timezone == NULL){
			$timezone = $this->get_user_timezone();
		} elseif (!$this->check($timezone)){
			return false;
		}
		date_default_timezone_set($timezone);
		return date('Y-m-d H:i:s', time());
	}

	public function get_utc_time(){
		date_default_timezone_set('UTC');
		return date('Y-m-d H:i:s', time());
	}

	public function to_utc ($datetime, $timezone = NULL){
		if ($timezone == NULL){
			$timezone = $this->get_user_timezone();
		} elseif (!$this->check($timezone)){
			return false;
		}
		date_default_timezone_set("UTC");
		return date('Y-m-d H:i:s',strtotime("$datetime $timezone"));
	}

}