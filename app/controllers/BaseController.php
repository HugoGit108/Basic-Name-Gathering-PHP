<?php

namespace bng\Controllers;

abstract class BaseController {
   
   public function view ($view, $data = []) {
      
      // check if the data passes is an array
      if (!is_array($data)) {
         die('Data is not an array: ' . var_dump($data));
      }

      // transform data array into variables
      extract($data);

      // includes the file if exists 
      if (file_exists("../app/views/$view.php")) {
         require_once("../app/views/$view.php");
      } else {
         die("<h1 class='fw-bold'><strong>The view: ($view) does not exists.<strong></h1>");
      }

   }
}