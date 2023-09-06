<?php 


namespace bng\System;
use bng\Controllers\Main;
use Exception;

class Router
{
   public static function dispatch () {
      // main route values
      $httpVerb = $_SERVER['REQUEST_METHOD'];
      $controller = 'main';
      
      $method = 'index';

      // check uri params
      if(isset($_GET['ct'])){
         $controller = $_GET['ct'];
      }

      if(isset($_GET['mt'])){
         $method = $_GET['mt'];
      }

      // method params 
      $params = $_GET;

      // remove controller and method on params
      if(key_exists('ct',$params)){
         unset($params['ct']);
      }

      if(key_exists('mt',$params)){
         unset($params['mt']);
      }

      // tries to instanciate the controller and execute the method 
      try {
         $class = "bng\Controllers\\$controller";
         $controller = new $class();
         $controller->$method(...$params);
      } catch (\Throwable $err) {
         die($err->getMessage());
      }
   }
}