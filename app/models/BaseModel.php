<?php 

namespace bng\Models;

use bng\System\Database;

abstract class BaseModel
{
   public $db;

   #=================================================
   public function db_connect () {
      $sqlConfig = [
         'host' => MYSQL_HOST,
         'database' => MYSQL_DATABASE,
         'username' => MYSQL_USERNAME,
         'password' => MYSQL_PASSWORD
      ];
      $this->db = new Database($sqlConfig);
   }

   #=================================================
   public function query ($sql = "", $params = []) {
      return $this->db->execute_query($sql,$params);
   }

   #=================================================
   public function non_query ($sql = "", $params = []) {
      return $this->db->execute_query($sql, $params);
   }
}