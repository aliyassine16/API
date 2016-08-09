
<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
date_default_timezone_set('UTC');


define("DB_USER", "root");
define("DB_PASSWORD", "root");
define("DB_HOST", "127.0.0.1");
define("DB_PORT", 3306);
define("DB_NAME", "api_local");



define('MYSQL_BOTH',MYSQLI_BOTH);
define('MYSQL_NUM',MYSQLI_NUM);
define('MYSQL_ASSOC',MYSQLI_ASSOC);


define("SALT", "67A6b06e28X68f4a");

define("HTTPS", true); //Set to TRUE in production
define("HTTP", false); //Set to TRUE in production



define("SITE_TITLE", "API");

define("SITE_NAME", "API");

define("DOMAIN_NAME", "http://api.local"); //Make sure there is NO slash at the end

define("TIMEZONE", "Europe/London"); 

define("ROOT", realpath($_SERVER['DOCUMENT_ROOT'])."/");

define("EMAIL_ADDRESS","aliyassine16@gmail.com"); // All e-mails from system will go from this address

define("WEB_ROOT", "/var/www/api.local/");



?>
