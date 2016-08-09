<?php
/**
 * Author: Koji
 * Description: Handles all functions for session management
 * Functions:
 *  login($user, $password) - logs user into system
 *  kill($user_id) - remotely kills a user session
 *  check() - checks whether a session is still valid
 *  logout() - logs user out of system
 *  permit($item) - checks whether user can access item/feature
 *  start() - starts and maintains a user session
 */
error_reporting(E_ALL);
ini_set('display_errors', 'On');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.class.php';


global $db;

class session
{
  function __construct(){
    $this->sql = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

  }

  private function isSafeIP($user_id){
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_id = $this->sql->real_escape_string($user_id);
    if ($this->sql->query("SELECT `ip` FROM `user_safe_ips` WHERE `user_id` = '$user_id' AND `ip` = INET_ATON('$ip')")->num_rows > 0){
      return true;
    } else {
      return false;
    }
  }

  private function twofactor_create($user_id){
    $user_id = $this->sql->real_escape_string($user_id);
    $ip = $_SERVER['REMOTE_ADDR'];
        $sms_code = substr(bin2hex(openssl_random_pseudo_bytes(8)), 0, 6); //This gets sent to user
        $db_code = hash('sha512',$sms_code . $ip); //This gets stored in DB
        $this->sql->query("UPDATE `user` SET `tfa_token`='$db_code', `tfa_expiry` = ADDTIME(NOW(), '00:30:00') WHERE `user_id` = '$user_id'");
        //Good time to send off $sms_code here via [method]
	    // send email
      }

      private function twofactor_auth($user_id, $sms_code){
        $user_id = $this->sql->real_escape_string($user_id);
        $sms_code = $this->sql->real_escape_string($sms_code);
        $ip = $_SERVER['REMOTE_ADDR'];
        $db_code = hash('sha512',$sms_code . $ip);
        $check = $this->sql->query("SELECT `user_id` FROM `user` WHERE `tfa_token` = '$db_code' AND `tfa_expiry` > NOW() AND `user_id` = '$user_id'");
        if ($check->num_rows > 0){
          $this->sql->query("REPLACE INTO `user_safe_ips` (`user_id`,`ip`) VALUES ('$user_id',INET_ATON('$ip'))");
          return true;
        } else {
          return false;
        }
      }

      private function session_permissions(){
       $_SESSION['permissions'] = array();
       $role_id = $_SESSION['role_id'];
       

    	//Get APP level permissions from DB
       $app_permissions = $this->sql->query("SELECT `module`, `permission` FROM `role_permissions` WHERE `role_id` = '$role_id'")->fetch_all(MYSQL_ASSOC);
       $_SESSION['permissions']['app']['role_id'] = $_SESSION['role_id'];
       foreach ($app_permissions as $X){
    		//Commit APP level permissions to SESSION
        $_SESSION['permissions']['app'][$X['module']][] = $X['permission'];
    		//Get API level permissions from main permission file
        if (isset($perms[$X['module']][$X['permission']])){
          foreach ($perms[$X['module']][$X['permission']] as $keyY=>$valY){
            if (file_exists($_SERVER["DOCUMENT_ROOT"] . '/libs/permissions/'.$keyY.'.permissions.php')){
             require_once $_SERVER["DOCUMENT_ROOT"] . '/libs/permissions/'.$keyY.'.permissions.php';
             if (isset($permissions[$keyY])){
              foreach ($permissions[$keyY] as $keyZ=>$valZ){
               if ($valZ == $valY){
                $_SESSION['permissions']['api'][$keyY][] = $keyZ;
              }
            }
          }
        }
      }
    }
  }

    	//Commit GLOBAL API level permissions to SESSION
  require_once $_SERVER["DOCUMENT_ROOT"] . '/libs/permissions/global.permissions.php';

  foreach ($permissions as $key=>$val){
    foreach ($val as $per){
     $_SESSION['permissions']['api'][$key][]=$per;
   }
 }

 return true;
}

public function getLocalTime($datetime = NULL){
  $user_id = $_SESSION['user_id'];
  if ($data = $this->sql->query("SELECT `timezone` from `user_settings` WHERE `user_id` = '$user_id'")){
   if ($data->num_rows > 0){
    $timezone = $data->fetch_object()->timezone;
  } else {
    $timezone = TIMEZONE;
  }
} else {
 $timezone = TIMEZONE;
}
if ($datetime == NULL){
 $datetime = '';
}
return false;
}

public function validate_user($username, $password) {
  global $db;
  $username = $db->secure($username);
  $result = $db->get_one("
   SELECT *
   FROM `user`
   WHERE `user`.`user` = '$username' AND `user`.`status` = '1'
   ");
  if ($result) {
   $user = $result;

   $passwordSecure = $db->secure($password);
     //$is_valid_password = password_verify($passwordSecure, $user['password']);
   //$is_valid_password=(md5($passwordSecure)== $user['password']);
   $is_valid_password=(($passwordSecure)== $user['password']);


   if( $is_valid_password ) {
    $output = array('success' => true, 'data' => $user);
  }
  else {
    $output = array('success' => false, 'code' => 0,'message' => 'Invalid user name and/or password.');
  }
}
else {
  $output = array('success' => false, 'code' => 0,'message' => 'Invalid user name and/or password.');
}
return $output;
}

	//Check if role has been suspended
public function validate_role($user) {
  global $db;
  $role_data = $db->get_one("SELECT `status` from `role` WHERE `role_id` = '" . $user['role_id'] . "' AND `status` = '1'");
  if ($role_data){
   $output = array(
    'success' => false, 
    'code' => 2,
    'message' => 'Two factor authentication is required from your location. Your user group has been suspended. Please contact your system administrator for assistance.'
    );
 }
 else {
   $output = array( 'success' => true );
 }
 return $output;
}

public function validate_two_factor($user) {
  $twf_check = $this->sql->query("SELECT `role` FROM `role` WHERE `role_id` = '" . $user['role_id'] . "' AND `twofactor` = 1");
  if( $twf_check->num_rows == 1 && !$this->isSafeIP($user['user_id']) ){
    if( !isset($_REQUEST['twofactor']) ) {
				//Trigger two factor authentication initiation
      $this->twofactor_create($user['user_id']);
      $output = array( 'success' => false, 'message' => 'Two factor authentication is required from your location. A code has been sent to your mobile phone.');
    } 
    else {
      $sms_code = $this->sql->real_escape_string($_REQUEST['twofactor']);
      if (!$this->twofactor_auth($user['user_id'], $sms_code)) {
        $output = array( 'success' => false, 'message' => 'Incorrect authentication code.');
      }
      else {
       $output = array( 'success' => true );
     }
   }
 }
 else {
   $output = array( 'success' => true, 'message' => 'two factor authentication not required.' );
 }
 return $output;
}

protected function create_session($user) {
  $session_salt = bin2hex(openssl_random_pseudo_bytes(4));
  $ip = $_SERVER['REMOTE_ADDR'];
        // saltyy
        $user_browser = "";//$this->sql->real_escape_string($ip . ': ' . $_SERVER['HTTP_USER_AGENT']);
        $user_data = hash("sha512", $user_browser . $user['user_id'] . $session_salt . SALT);
        $this->sql->query("REPLACE INTO `user_sessions` (`user_id`, `user`,`client`,`data`) VALUES ('".$user['user_id']."', '".$user['user']."', '$user_browser', '$user_data')");

        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user'] = $user['user'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['salt'] = $session_salt;

        $this->session_permissions();
        $this->sql->query("UPDATE `user` SET `last_logged` = NOW() WHERE `user_id` = '" . $user['user_id'] . "'");

        $output = array( 'success' => true, 'code'=>1);
        return $output;
      }

      public function login_new( $username, $password ) {
        $is_valid_user = $this->validate_user($username, $password);
        if( $is_valid_user['success'] ) {
         $user = $is_valid_user['data'];
         $two_factor_valid = $this->validate_two_factor($user);
         if( $two_factor_valid['success'] ) {
          $user_session = $this->create_session($user);
          if( $user_session ) {
           $output = array( 'success' => true );
         }
         else {
           $output = array( 'success' => false, 'message' => $user_session['message'] );
         }
       }
       else {
        $output = array( 'success' => false, 'message' => $two_factor_valid['message'] );
      }
    }
    else {
     $output = array( 'success' => false, 'message' => $is_valid_user['message'] );
   }

   return $output;
 }

	/* MAY - This function is too monolithic and I can't re-use it with the app.aggregateiq.com.
	 * I've broken it up into the login_new and a few other fucntion above.
	 * After confirming the completeness with Koji, I will write test cases for them and switch it over.
	 */
  public function login($user, $password){
    $session_salt = bin2hex(openssl_random_pseudo_bytes(4));
    $ip = $_SERVER['REMOTE_ADDR'];
    $user = $this->sql->real_escape_string($user);
    $password = $this->sql->real_escape_string($password);

    if ($wait_time = $this->brute_force($user)){return array('success' => false, 'code'=>0,'data'=>"You need to wait $wait_time seconds before you next log in attempt.");}
    $data = $this->sql->query("SELECT * FROM `user` WHERE `user` = '$user' AND `status` = '1'");
    if ($data->num_rows == '0'){
      return array('success' => false, 'code'=>0,'data'=>'Invalid user name and/or password.');
    } else {
      $secure = $data->fetch_object();
      $user_id = $secure->user_id;
            //if (password_verify($password, $secure->password)) {
      //if (md5($password)== $secure->password) {
      if (($password)== $secure->password) {
            //$pass_hash=hash('sha512', $password );
            //if ($pass_hash=== $secure->password) {
                //Check if Two-factor auth is required by the role
        $twf_check = $this->sql->query("SELECT `role` FROM `role` WHERE `role_id` = '".$secure->role_id."' AND `twofactor` = 1");
        if ($twf_check->num_rows == 1){
          if (!$this->isSafeIP($user_id) && !isset($_REQUEST['twofactor'])){
                        //Trigger two factor authentication initiation
            $this->twofactor_create($user_id);
            return array( 'success' => true, 'code'=>2,'data'=>'Two factor authentication is required from your location. A code has been sent to your mobile phone.');
          } elseif (!$this->isSafeIP($user_id) && isset($_REQUEST['twofactor'])){
            $sms_code = $this->sql->real_escape_string($_REQUEST['twofactor']);
            if (!$this->twofactor_auth($user_id, $sms_code)){
              return array( 'success' => false, 'code'=>2,'data'=>'Incorrect authentication code.');
            }
          }
        }
        $this->sql->query("DELETE FROM `user_login_attempts` WHERE `user` = '$user'");
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user'] = $secure->user;
        $_SESSION['role_id'] = $secure->role_id;
        $_SESSION['salt'] = $session_salt;

                //Get Dashboard type
        $_SESSION['dashboard'] = $this->sql->query("SELECT `dashboard` FROM `role` WHERE `role_id` = '".$secure->role_id."'")->fetch_object()->dashboard;

                //Check if role has been suspended
        $role_data = $this->sql->query("SELECT `status` from `role` WHERE `role_id` = '".$_SESSION['role_id']."' AND `status` = '1'");
        if ($role_data->num_rows == '0'){
          return array( 'success' => false, 'code'=>2,'data'=>'Two factor authentication is required from your location. Your user group has been suspended. Please contact your system administrator for assistance.');
        }
                // saltyy
                $user_browser = "";//$this->sql->real_escape_string($ip . ': ' . $_SERVER['HTTP_USER_AGENT']);
                $user_data = hash("sha512", $user_browser . $_SESSION['user_id'] . $session_salt . SALT);
                $this->sql->query("REPLACE INTO `user_sessions` (`user_id`, `user`,`client`,`data`) VALUES ('".$_SESSION['user_id']."', '".$_SESSION['user']."', '$user_browser', '$user_data')");

                $this->session_permissions();
                $this->sql->query("UPDATE `user` SET `last_logged` = NOW() WHERE `user_id` = '$user_id'");
                return array( 'success' => true, 'code'=>1);
              } 
              else {
                return array( 'success' => false, 'code'=>0,'data'=>'Invalid user name and/or password.');
              }
            }
          }


          private function brute_force($user){
            $data = $this->sql->query("SELECT now() AS `current`, `time`, `attempts` FROM `user_login_attempts` WHERE `user` = '$user' LIMIT 1");
            if ($data->num_rows > 0){
              $dataset = $data->fetch_object();
              $attempts = $dataset->attempts;
              if ($attempts==1 || $attempts==2){$wait=0;}
              if ($attempts==3){$wait=30;}
              if ($attempts==4){$wait=60;}
              if ($attempts==5){$wait=300;}
              if ($attempts==6){$wait=600;}
              if ($attempts>6){$wait=1800;}

              $next_time = strtotime($dataset->time) + $wait;
              $current_time = strtotime($dataset->current);
              $wait_time = $next_time - $current_time;
              if ($wait_time > 0){
                return $wait_time;
              } else {
                $this->sql->query("UPDATE `user_login_attempts` SET `attempts` = `attempts` + 1 WHERE `user` = '$user'");
              }
              
            } else {
              $this->sql->query("INSERT INTO `user_login_attempts` (`user`) VALUES ('$user')");
            }
          }

          public function kill($user_id){
            $user_id = $this->sql->real_escape_string($user_id);
            if ($this->sql->query("DELETE FROM `user_sessions` WHERE `user_id` = '$user_id'")){
              return true;
            } else {
              return false;
            }
          }

    //JS API proxy for kill()
          public function kill_user(){
            if (isset($_POST['user_id']) && !empty($_POST['user_id'])){
              $user_id = $_POST['user_id'];
            } else {
              return ['success'=>false, 'message'=>'User ID required.'];
            }
            if ($this->kill($user_id)){
              return ['success'=>true]; 
            } else {
              return ['success'=>false];
            }
          }

          public function check(){
            if (isset($_SESSION['user_id']) && isset($_SESSION['role_id'])) {
              $user_id = $_SESSION['user_id'];
            // saltyy
            $user_browser = "";//$this->sql->real_escape_string($_SERVER['REMOTE_ADDR'] . ': ' . $_SERVER['HTTP_USER_AGENT']);
            $user_data = hash("sha512", $user_browser . $_SESSION['user_id'] . $_SESSION['salt'] . SALT);
            $chekquery="SELECT `user` FROM `user` WHERE `status` = '0' AND `user_id` = '$user_id'";
            if ($this->sql->query($chekquery)->num_rows == '1'){
                //echo $chekquery;
              $this->kill($user_id);
              return false;
            } elseif ($this->sql->query("SELECT `user` FROM `user_sessions` WHERE `client` = '$user_browser' AND `data` = '$user_data'")->num_rows == '0'){
              return false;
            } else {
              return true;
            }
          } else {
            return false;
          }
        }

        public function logout(){
          $params = session_get_cookie_params();
          setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
          if (isset($_SESSION['user_id'])){
            $this->sql->query("DELETE FROM `user_sessions` WHERE `user_id` = '".$_SESSION['user_id']."'");
          }
          $_SESSION = array();
          session_destroy();

        }

        public function destroy(){
          $this->logout();
        }

        public function start(){
          if (ini_set('session.use_only_cookies', 1) === FALSE){
           $msg = "We could not initiate a secure connection at this time. Please try again later or contact a system administrator for assistance.";
           header("Location: /error.php?msg=$msg");
           exit();
         }
         $cookieParams = session_get_cookie_params();
         session_set_cookie_params($cookieParams["lifetime"], $cookieParams["path"], $cookieParams["domain"], HTTPS, TRUE);
         session_start();
         session_regenerate_id();
       }

       public function allowed($class, $function){
		//This function interacts with API permissions only
        if ($_SESSION['role_id'] == 1){
         return ['success'=>TRUE];
       }

       if ($_SESSION['role_id'] == 2 && $class !== 'Admin' && $function !== 'Admin' && $class !== 'admin' && $function !== 'admin'){
        return ['success'=>TRUE];
      }

      if (isset($_SESSION['permissions']['api'][$class])){
       if (in_array($function, $_SESSION['permissions']['api'][$class])){
        return ['success'=>TRUE];
      } else {
        return ['success'=>FALSE,'FORBIDDEN'=>TRUE, 'message'=>"Access Denied."];
      }
    } else {
     return ['success'=>FALSE,'FORBIDDEN'=>TRUE, 'message'=>"Access Denied."];
   }
 }

 public function allowedTablet($class, $function){
		//This function interacts with API permissions only
  if ($_SESSION['role_id'] == 1 || $_SESSION['role_id'] == 3){
   return ['success'=>TRUE];
 }

 if ($_SESSION['role_id'] == 2 && $class !== 'Admin' && $function !== 'Admin' && $class !== 'admin' && $function !== 'admin'){
  return ['success'=>TRUE];
}

if (isset($_SESSION['permissions']['api'][$class])){
 if (in_array($function, $_SESSION['permissions']['api'][$class])){
  return ['success'=>TRUE];
} else {
  return ['success'=>FALSE,'FORBIDDEN'=>TRUE, 'message'=>"Access Denied."];
}
} else {
 return ['success'=>FALSE,'FORBIDDEN'=>TRUE, 'message'=>"Access Denied."];
}
}

public function AppPermissions(){
  if (isset($_SESSION['permissions']['app'])){
    return ['success'=>TRUE,'data'=>$_SESSION['permissions']['app']];
  } else {
    return ['success'=>TRUE,'data'=>[]];
  } 
}

public function canView(){
    	//This is for APP (angular JS / view) permissions only
    	//Check /permissions/permissions.php for what to set in view.. i.e   has-permission="Asset_Manager:canView"
  if ($_SESSION['role_id'] == 1){
    return ['success'=>TRUE];
  }
  if (isset($_POST['module']) && !empty($_POST['module']) && $_POST['module'] !== 'undefined'){
    $module = $_POST['module'];
  } else {
    return ['success'=>FALSE,'message'=>"No module provided."];
  }
  if (isset($_POST['permit']) && !empty($_POST['permit']) && $_POST['permit'] !== 'undefined'){
    $permit = $_POST['permit'];
  } else {
    $permit = '';
  }

  if ($_SESSION['role_id'] == 2 && $class !== 'Admin' && $function !== 'Admin' && $class !== 'admin' && $function !== 'admin'){
    return ['success'=>TRUE];
  }

  if ($permit !== '' && $permit !== 'undefined'){
    if (isset($_SESSION['permissions']['app'][$module]) && in_array($permit, $_SESSION['permissions']['app'][$module])){
      return ['success'=>TRUE, 'data'=>[$module => [$permit => true]]];
    } else {
      return ['success'=>FALSE];
    }
  } else {
    if (isset($_SESSION['permissions']['app'][$module])){
      return ['success'=>TRUE, 'data'=>[$module => true]];
    } else {
      return ['success'=>FALSE];
    }
  }   
}

public function _canDo($module, $permit=''){
        //This is for APP (angular JS / view) permissions only
        //Check /permissions/permissions.php for what to set in view.. i.e   has-permission="Asset_Manager:canView"
  if ($_SESSION['role_id'] == 1){
    return true;
  }
  if ($_SESSION['role_id'] == 2 && $class !== 'Admin' && $function !== 'Admin' && $class !== 'admin' && $function !== 'admin'){
    return true;
  }

  if ($permit && $permit !== ''){
    if (isset($_SESSION['permissions']['app'][$module]) && in_array($permit, $_SESSION['permissions']['app'][$module])){
      return true;
    } else {
      return false;
    }
  } else {
    if (isset($_SESSION['permissions']['app'][$module])){
      return true;
    } else {
      return false;
    }
  }   
}

public function impersonate(){
  if (isset($_REQUEST['role_id']) && !empty($_REQUEST['role_id'])){
    $role_id = $this->sql->real_escape_string($_REQUEST['role_id']);
  } elseif (isset($_REQUEST['role']) && !empty($_REQUEST['role'])){
    $role = $this->sql->real_escape_string($_REQUEST['role']);
    if (!$role_id = $this->sql->query("SELECT `role_id` FROM `role` WHERE `role` = '$role'")->fetch_assoc()['role_id']){
      return ['success'=>FALSE,'message'=>"Invalid Role name."];
    }
  } else {
    return ['success'=>FALSE,'message'=>"No role ID provided."];
  }

  $real_role = $this->sql->query("SELECT `role_id` FROM `user` WHERE `role_id` = '$role_id'")->fetch_assoc()['role_id'];
  if ($real_role !== '1' && ($role_id == '1' || $role_id == '0')){
    return ['success'=>false, 'message'=>'Cannot impersonate Administrative roles.'];
  }
  if ($this->sql->query("SELECT `role_id` FROM `role` WHERE `role_id` = '$role_id'")->fetch_assoc()){
    $_SESSION['role_id'] = $role_id;
    $this->session_permissions();
            //Get Dashboard type
    $_SESSION['dashboard'] = $this->sql->query("SELECT `dashboard` FROM `role` WHERE `role_id` = '$role_id'")->fetch_object()->dashboard;
    return ['success'=>true];
  } else {
    return ['success'=>false, 'message'=>'Invalid role ID.'];
  }
}
}
