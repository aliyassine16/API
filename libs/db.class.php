<?php

require_once(__DIR__.'/config.php');
require_once(__DIR__.'/timezone.class.php');


global $conn;

class Db{
	
	private $logging = true;
	private $timezone = null;
	private $in_transaction=false;
	
	
	/* Setup connection variable */
	function __construct(){
		global $conn;
		$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
		$conn->set_charset("utf8");
		$this->connection = $conn;
		$this->timezone = new timezone;
		
	}

	function __destruct(){
		if ($this->in_transaction){
			$this->connection->rollback();
			error_log("transaction left hanging");
		}
	}
	
	# returns [["name"=>"my name", ...], ...]
	# or an empty array for no results
	# or false for an error
	function get($sql, $timeout=null){

		$list = [];
		$rs= $this->connection->query($sql);
		if($rs === false) {
			if($this->logging) error_log('Wrong SQL: ' . $sql . ' Error: ' . $this->connection->error.' -- '.E_USER_ERROR);
			return false;
		} else if($rs->num_rows > 0){
			$rs->data_seek(0);
			while($row = $rs->fetch_assoc()){
				$list[] = $row;
				//var_dump($list);
			}
			$rs->free_result();
		}


		return $list;
	}
	
	function get_object($sql){
		$object = array();
		$rs= $this->connection->query($sql);
		if($rs === false) {
			if($this->logging) echo ('Wrong SQL: ' . $sql . ' Error: ' . $this->connection->error.' -- '.E_USER_ERROR);
			return false;
		} else if($rs->num_rows == 0) {
			return false;
		} else{
			$object = $rs->fetch_object();
			$rs->free_result();
		}
		return $object;
	}
	
	#returns ["name"=>"my_name", ...]
	# or an empty array for no results
	# or false for an error
	function get_one($sql, $timeout=null){


		$object=null;
		$rs = $this->connection->query($sql);
		if($rs === false) {
			if($this->logging) echo ('Wrong SQL: ' . $sql . ' Error: ' . $this->connection->error.' -- '.E_USER_ERROR);
			return false;
		} else if($rs->num_rows == 0) {
			$object=[];
		} else{
			$rs->data_seek(0);
			while($row = $rs->fetch_assoc()){
				$rs->free_result();
				$object = $row;
				break;
			}
		}



		return $object;
	}

	#returns an array of single column values from a query.
	function get_list($sql, $column, $timeout=null){
		$result_set = $this->connection->query($sql);
		if ($result_set === false){
			if ($this->logging){
				echo ('Wrong SQL: ' . $sql . ' Error: ' . $this->connection->error.' -- '.E_USER_ERROR);
			}
			$ret = new SplFixedArray(0);
			return $ret;
		} else if ($result_set->num_rows == 0) {
			$ret = new SplFixedArray(0);
			return $ret;
		} else {
			$list = new SplFixedArray($result_set->num_rows);
			$result_set->data_seek(0);
			$index=0;
			while($row = $result_set->fetch_assoc()){
				if (array_key_exists($column, $row)){
					$list[$index]=$row[$column];
					$index += 1;
				}
			}
			$result_set->free_result();
			$list->setSize($index);
			return $list;
		}
	}
	
	# returns an int, or 0 for no rows or an error
	function item_count($sql){
		$list = array();
		$rs= $this->connection->query($sql);
		if($rs === false) {
			if($this->logging) echo ('Wrong SQL: ' . $sql . ' Error: ' . $this->connection->error.' -- '.E_USER_ERROR);
			return 0;
		} else {
			$count=$rs->num_rows;
			$rs->free_result();
			return $count;
		}
	}
	
	# returns true or false for success or fail
	function update($sql, $affected=false){
		if($this->connection->query($sql) === false) {
			if($this->logging) echo ('Wrong SQL: ' . $sql . ' Error: ' . $this->connection->error.' -- '.E_USER_ERROR);
			return false;
		} else {
			if ($affected){
				return array("affected" => $this->connection->affected_rows);
			} else {
				return true;
			}
		}
	}
	
	# alias for update
	function delete($sql, $affected=false){
		return $this->update($sql, $affected);
	}
	
	# returns the new row id if a single row is inserted
	# or [most_recent_insert_id, num_rows] if affected is true
	function insert($sql, $affected=false){
		if($this->connection->query($sql) === false) {
			if($this->logging)
				error_log('Wrong SQL: ' . $sql . ' Error: ' . $this->connection->error.' -- '.E_USER_ERROR);
			return false;
		} else {
			if($affected) return array($this->connection->insert_id, $this->connection->affected_rows);
			else return $this->connection->insert_id;
		}
	}
	
	function secure($var){
		if (!is_array($var)){
			$string = str_replace(array('`', '’'), "'", $var);
			return $this->connection->real_escape_string($string);
		} else {
			return $this->secure_array($var);
		}
	}
	
	function secure_array($array){
		foreach($array as $key=>$value) {
			if(is_array($value)) { $this->secure($value); }
			else { $array[$key] = $this->secure($value); }
		}
		return $array;
	}
	
	#$var is the name of a request variable... i.e $_POST['cat'] would become post('cat')
	function post($var){
		if (isset($_POST[$var]) && !empty($_POST[$var])){
			return $this->secure($_POST[$var]);
		} else if (isset($_GET[$var]) && !empty($_GET[$var])){
			return $this->secure($_GET[$var]);
		} else {
			return false;
		}
	}

	function start_transaction(){
		if (!$this->in_transaction){
			$this->connection->autocommit(false);
			$this->connection->begin_transaction();
		}

		$this->in_transaction=true;
	}

	function commit_transaction(){
		if ($this->in_transaction){
			$this->connection->commit();
		}
		
		$this->in_transaction=false;
	}

	function rollback_transaction($message=null){
		if ($this->in_transaction){
			$this->connection->rollback();

			if ($message != null){
				error_log($message);
			}
		}
		
		$this->in_transaction=false;
	}

	//Time zone stuff
	public function time_from_utc($datetime, $timezone = NULL){
		return $this->timezone->from_utc($datetime, $timezone = NULL);
	}

	public function time_to_utc($datetime, $timezone = NULL){
		return $this->timezone->to_utc($datetime, $timezone = NULL);
	}

	public function utc_time(){
		return $this->timezone->get_utc_time();
	}
	public function local_time(){
		return $this->timezone->get_local_time($timezone = NULL);
	}

	public function array_to_condition($model, $config=array('separator' => ' and ')) {
		$model = array_filter($model);
		if( !isset($config['separator']) )
			$config['separator'] = ' and ';

		$lastIndex = count($model) - 1;
		$conditions = " ";
		foreach( $model as $prop => $value ) {
			$prop = !empty($config['table_name']) ? "`{$config['table_name']}`.`{$prop}`" : "`{$prop}`";

			if( is_array($value) ) {
				$value = "'" . implode("', '", $value) . "'";
				$conditions .= "{$prop} IN ({$value})";
			}
			else if( strtolower($value) === 'null' ) {
				$conditions .= "{$prop} IS NULL ";
			}
			else {
				$conditions .= "{$prop}='{$this->secure($value)}' ";
			}

			$conditions .= $lastIndex-- != 0 ? " {$config['separator']} " : '';
		}
		return $conditions;
	}

	public function exists($val) {
		return isset($val) || !empty($value) || $val !== 'undefined' || $val !== 'null';
	}

}

global $db;
$db = new Db();
?>
