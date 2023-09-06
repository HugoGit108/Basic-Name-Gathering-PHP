<?php  

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

#=================================================
function check_session() {
   // check if user has an active session
   return isset($_SESSION['user']);
}

#=================================================
function printData($data,$die = true) { 
   echo '<pre>';
   if(is_object($data) || is_array($data)){
      print_r($data);
   } else {
      echo $data;
   }
   if($die){
      die('<br>Fim do dump<br>');
   }
}

#=================================================
function logger ($message = '', $level = 'info') {
   
   $log = new Logger('app_logs');
   $log->pushHandler(new StreamHandler(LOGS_PATH));

   // add log msg
   switch ($level) {
      case 'info':
         $log->info($message);
         break;
      case 'notice':
         $log->notice($message);
         break;
      case 'warning':
         $log->warning($message);
         break;
      case 'error':
         $log->error($message);
         break;
      case 'critical':
         $log->critical($message);
         break;
      case 'alert':
         $log->alert($message);
         break;
      case 'emergency':
         $log->emergency($message);
         break;
      default:
         $log->info($message);
         break;
   }
   
}

#=================================================
function aes_encrypt ($value) {
   // encrypt
   return bin2hex(openssl_encrypt($value, 'aes-256-cbc', OPENSSL_KEY, OPENSSL_RAW_DATA, OPENSSL_IV));
}

#=================================================
function aes_decrypt ($value) {
   // decrypt
   if (strlen($value) % 2 != 0) {
      return false;
   } 
   
   return openssl_decrypt(hex2bin($value), 'aes-256-cbc', OPENSSL_KEY, OPENSSL_RAW_DATA, OPENSSL_IV);
   
}

#=================================================
function get_active_user_name () {
   return $_SESSION['user']->name;
}

#=================================================
function validate_post () {
   if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      header('Location: index.php');
   }
}



