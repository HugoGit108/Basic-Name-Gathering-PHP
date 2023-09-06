<?php

namespace bng\Models;

use bng\Models\BaseModel;

class Agents extends BaseModel
{

   #=================================================
   public function check_login($username, $password)
   {

      // check if login is valid
      $params = [
         ':username' => $username
      ];

      // check if the user is in the DB and is not deleted
      $this->db_connect();
      $results = $this->query(
         "SELECT id , passwrd FROM agents " .
            "WHERE AES_ENCRYPT(:username, '" . MYSQL_AES_KEY . "') = name " .
            "AND deleted_at IS NULL",
         $params
      );


      // return false if there is no user
      if ($results->affected_rows == 0) {
         return [
            'status' => false
         ];
      }

      // there is a username in database equal to the input
      // validate the password
      if (!password_verify($password, $results->results[0]->passwrd)) {
         return [
            'status' => false
         ];
      }

      // Login ok!
      return [
         'status' => true
      ];
   }

   #=================================================
   public function get_user_data($username)
   {

      // get user data to insert in the session
      $params = [
         ':username' => $username
      ];
      $this->db_connect();

      $results = $this->query(
         "SELECT " .
            "id, " .
            "AES_DECRYPT(name, '" . MYSQL_AES_KEY . "') name, " .
            "profile " .
            "FROM agents " .
            "WHERE AES_ENCRYPT(:username, '" . MYSQL_AES_KEY . "') = name ",
         $params
      );
      return [
         'status' => 'success',
         'data' => $results->results[0]
      ];
   }

   #=================================================
   public function set_user_last_login($id)
   {

      // update user last login
      $params = [
         ':id' => $id
      ];
      $this->db_connect();

      $results = $this->non_query(
         "
         UPDATE agents SET
         last_login = NOW()
         WHERE id = :id
         ",
         $params
      );
      return $results;
   }

   #=================================================
   public function get_agent_clients($agentId)
   {

      // get all agent clients based on the agent id

      $params = [
         ':agentId' => $agentId
      ];
      $this->db_connect();
      $results = $this->query(
         "SELECT id, AES_DECRYPT(name, '" . MYSQL_AES_KEY . "') name, " .
            "gender, birthdate, AES_DECRYPT(email, '" . MYSQL_AES_KEY . "') email, " .
            "AES_DECRYPT(phone, '" . MYSQL_AES_KEY . "') phone, " .
            "interests, created_at , updated_at " .
            "FROM persons WHERE id_agent = :agentId " .
            "AND deleted_at IS NULL",
         $params
      );

      return [
         'status' => 'success',
         'data' => $results->results
      ];
   }

   #=================================================
   public function check_if_client_exists($postData)
   {

      // check if there are already a client with same name 
      $params = [
         ':idAgent' => $_SESSION['user']->id,
         ':clientName' => $postData['text_name']
      ];

      $this->db_connect();
      $results = $this->query(
         "SELECT id FROM persons " .
            "WHERE AES_ENCRYPT(:clientName, '" . MYSQL_AES_KEY .  "') = name " .
            "AND id_agent = :idAgent",
         $params
      );

      if ($results->affected_rows == 0) {
         return [
            'status' => false
         ];
      } else {
         return [
            'status' => true
         ];
      }
   }

   #=================================================
   public function add_new_client_to_database($postData)
   {

      // [text_name] => ~djsanjk
      // [radio_gender] => m
      // [text_birthdate] => 24-08-2023
      // [text_email] => hbndsa@dsan.com
      // [text_phone] => 977858585
      // [text_interests] => teste 
      $birthDate = new \DateTime($postData['text_birthdate']);
      $params = [
         ':text_name' => $postData['text_name'],
         ':radio_gender' => $postData['radio_gender'],
         ':text_birthdate' => $birthDate->format('Y-m-d H:i:s'),
         ':text_email' => $postData['text_email'],
         ':text_phone' => $postData['text_phone'],
         ':text_interests' => $postData['text_interests'],
         ':id_agent' => $_SESSION['user']->id
      ];

      $this->db_connect();
      $results = $this->non_query(
         "INSERT INTO persons VALUES(
         0,
         AES_ENCRYPT(:text_name, '" . MYSQL_AES_KEY . "'),
         :radio_gender,
         :text_birthdate,
         AES_ENCRYPT(:text_email, '" . MYSQL_AES_KEY . "'),
         AES_ENCRYPT(:text_phone, '" . MYSQL_AES_KEY . "'),
         :text_interests,
         :id_agent,
         NOW(),
         NOW(),
         NULL
         )",
         $params
      );
   }

   #=================================================
   public function get_client_data($idClient)
   {
      // will receive and ecrypted id 
      $params = [
         ':idClient' => $idClient
      ];

      $this->db_connect();
      $results = $this->query(
         "
         SELECT 
         id,
         AES_DECRYPT(name, '" . MYSQL_AES_KEY . "') name, 
         gender,
         birthdate,
         AES_DECRYPT(email, '" . MYSQL_AES_KEY . "') email, 
         AES_DECRYPT(phone, '" . MYSQL_AES_KEY . "') phone, 
         interests 
         FROM persons 
         WHERE id = :idClient
      ",
         $params
      );

      if ($results->affected_rows == 0) {
         return [
            'status' => false
         ];
      }

      return [
         'status' => true,
         'data' => $results->results[0]
      ];
   }

   #=================================================
   public function check_other_client_with_same_name($clientId, $clientName)
   {

      $params = [
         ':id' => $clientId,
         ':name' => $clientName,
         ':idAgent' => $_SESSION['user']->id
      ];

      $this->db_connect();
      $results = $this->query(
         "SELECT id FROM persons
         WHERE id <> :id
         and id_agent = :idAgent
         AND AES_ENCRYPT(:name, '" . MYSQL_AES_KEY . "') = name 
         ",
         $params
      );

      if ($results->affected_rows == 0) {
         return ['status' => false];
      }

      return ['status' => true];
   }

   #=================================================
   public function update_client_data($clientId, $postData)
   {

      // set valid datetime to send to database 
      $postData['text_birthdate'] = date('Y-m-d', strtotime($postData['text_birthdate']));
      $params = [
         ':name' => $postData['text_name'],
         ':gender' => $postData['radio_gender'],
         ':text_birthdate' => $postData['text_birthdate'],
         ':text_email' => $postData['text_email'],
         ':text_phone' => $postData['text_phone'],
         ':text_interests' => $postData['text_interests'],
         ':idAgent' => $_SESSION['user']->id,
         ':idClient' => $clientId,
      ];

      $this->db_connect();
      $results = $this->non_query(
         "
         UPDATE persons SET 
            name = AES_ENCRYPT (:name, '" . MYSQL_AES_KEY . "' ),
            gender = :gender,
            birthdate = :text_birthdate,
            email = AES_ENCRYPT (:text_email, '" . MYSQL_AES_KEY . "'),
            phone = AES_ENCRYPT (:text_phone, '" . MYSQL_AES_KEY . "' ),
            interests = :text_interests,
            id_agent = :idAgent,
            updated_at = NOW()
            WHERE id = :idClient
         ",
         $params
      );

      if ($results->affected_rows == 0) {
         return ['status' => false];
      }
      return ['status' => true];
   }

   #=================================================
   public function delete_client($id)
   {

      // will delete the client on the database (hard delete)
      $params = [
         ':id' => $id
      ];

      $this->db_connect();
      $this->non_query("DELETE FROM persons WHERE id = :id", $params);
   }

   #=================================================
   public function check_current_password($currentPassword)
   {

      // check if current password of a user is equal to the given 
      $params = [
         ':id' => $_SESSION['user']->id,
      ];

      $this->db_connect();
      $results = $this->query(
         "
         SELECT passwrd FROM agents WHERE id = :id
         ",
         $params
      );

      if (password_verify($currentPassword, $results->results[0]->passwrd)) {
         return ['status' => true];
      }

      return ['status' => false];
   }

   #=================================================
   public function update_agent_password($newPassword)
   {
      $params = [
         'passwrd' => password_hash($newPassword, PASSWORD_DEFAULT),
         'id' => $_SESSION['user']->id
      ];

      $this->db_connect();
      $results = $this->non_query(
         "
         UPDATE agents SET passwrd = :passwrd,
         updated_at = NOW()
         WHERE id = :id
         ",
         $params
      );

      return $results;
   }

   #=================================================
   public function check_new_agent_purl($purl)
   {
      // check if there is already a agent with this purl
      $params = [
         ':purl' => $purl
      ];

      $this->db_connect();

      $results = $this->query("
         SELECT id FROM agents WHERE purl = :purl
      ", $params);

      if ($results->affected_rows == 0) {
         return [
            'status' => false
         ];
      } else {
         return [
            'status' => true,
            'id' => $results->results[0]->id
         ];
      }
   }

   #=================================================
   public function set_agent_password($id, $newPassword)
   {
      // updates the current user
      $params = [
         ':id' => $id,
         'passwrd' => password_hash($newPassword, PASSWORD_DEFAULT),
      ];

      $this->db_connect();
      $this->non_query("
      UPDATE agents SET
         passwrd = :passwrd,
         purl = NULL,
         updated_at = NOW()
         WHERE id = :id
      ", $params);
   }

   #=================================================
   public function set_code_for_recover_password($username)
   {
      $params = [
         ':name' => $username
      ];

      $this->db_connect();
      $results = $this->query("
      SELECT id FROM agents
      WHERE AES_ENCRYPT(:name, '" . MYSQL_AES_KEY . "') = name
      AND passwrd IS NOT NULL
      AND deleted_at IS NULL
      ", $params);

      if ($results->affected_rows == 0) {
         return [
            'status' => 'error'
         ];
      }

      // the agent was found
      $code = rand(100000, 999999);
      $id = $results->results[0]->id;

      $params = [
         ':id' => $id,
         ':code' => $code
      ];


      $results = $this->non_query("
         UPDATE agents SET 
         code = :code
         WHERE id = :id
      ", $params);

      return [
         'status' => 'success',
         'id' => $id,
         'code' => $code
      ];
   }

   #=================================================
   public function check_if_reset_code_is_correct($id, $code)
   {
      // check if the reset code is equal to the existent on the database

      $params = [
         ':id' => $id,
         ':code' => $code
      ];

      $this->db_connect();
      $results = $this->query("
      SELECT id FROM agents 
      WHERE id = :id and code = :code
      ", $params);

      if ($results->affected_rows == 0) {
         return [
            'status' => false
         ];
      } else {
         return [
            'status' => true
         ];
      }
   }

   public function change_agent_password($id ,$newPassword)
   {
      $params = [
         ':id' => $id,
         ':password' => password_hash($newPassword, PASSWORD_DEFAULT)
      ];
      $this->db_connect();
      $this->non_query("
      UPDATE agents SET 
         passwrd = :password
         WHERE id = :id
      ",$params);
   }
}
