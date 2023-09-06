<?php

namespace bng\Controllers;

use bng\Controllers\BaseController;
use bng\Models\Agents;
use bng\System\SendEmail;


class Main extends BaseController
{
   #=================================================
   public function index()
   {

      // check if the user has a login session
      if (!check_session()) {
         $this->login_frm();
         return;
      }

      $data['user'] = $_SESSION['user'];
      // load homepage
      $this->view('layouts/html_header');
      $this->view('navbar', $data);
      $this->view('homepage', $data);
      $this->view('footer');
      $this->view('layouts/html_footer');
   }

   #=================================================
   # Login 
   #=================================================
   public function login_frm()
   {

      //check if there is already a user in the session
      if (check_session()) {
         $this->index();
         return;
      }

      // checks for validation errors after (login_submit)
      $data = [];
      if (!empty($_SESSION['validationErrors'])) {
         $data['validationErrors'] = $_SESSION['validationErrors'];
         unset($_SESSION['validationErrors']);
      }

      // check if there is an invalid login
      if (!empty($_SESSION['serverError'])) {
         $data['serverError'] = $_SESSION['serverError'];
         unset($_SESSION['serverError']);
      }

      // display login form
      $this->view('layouts/html_header');
      $this->view('login_frm', $data);
      $this->view('layouts/html_footer');
   }

   #=================================================
   public function login_submit()
   {
      //check if there is already a user in the session
      if (check_session()) {
         $this->index();
         return;
      }

      // check if there was a post request
      if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
         $this->index();
         return;
      }

      // form validation
      $validationErrors = [];
      if (empty($_POST['text_username'] || empty($_POST['text_password']))) {
         $validationErrors[] = 'Username e password são obrigatórios';
      }

      // get form data
      $username = $_POST['text_username'];
      $password = $_POST['text_password'];

      // check if the username is a valid email
      if (!filter_var($username, FILTER_VALIDATE_EMAIL)) {
         $validationErrors[] = 'O username tem que ser um email  válido';
      }

      // check if the username is between 5 and 50 chars
      if (strlen($username) < 5 || strlen($username) > 50) {
         $validationErrors[] = 'O username deve ter entre 5 e 50 caracteres.';
      }

      // check if password is valid
      if (strlen($password) < 6 || strlen($password) > 20) {
         $validationErrors[] = 'A password deve ter entre 6 e 20 caracteres.';
      }

      // check if there are validation errors
      if (!empty($validationErrors)) {
         $_SESSION['validationErrors'] = $validationErrors;
         $this->login_frm();
         return;
      }


      $model = new Agents();
      $results = $model->check_login($username, $password);

      // check if the login is valid
      if (!$results['status']) {
         // logger
         logger("$username - Login inválido", 'erro');

         $_SESSION['serverError'] = 'Login Inválido.';
         $this->login_frm();
         return;
      }

      // logger
      logger("$username - Login com sucesso.");

      // load user information to the session
      $results = $model->get_user_data($username);

      // add user to the session (id, username, profile)
      $_SESSION['user'] = $results['data'];

      // updates user last login
      $results = $model->set_user_last_login($_SESSION['user']->id);

      // go to main page
      $this->index();
   }

   #=================================================
   public function logout()
   {

      // disable direct access to logout
      if (!check_session()) {
         $this->index();
         return;
      }

      // logger
      logger($_SESSION['user']->name . ' - fez logout.');

      // clear user session
      unset($_SESSION['user']);

      // return to index method
      $this->index();
   }

   #=================================================
   public function change_password_frm()
   {

      if (!check_session()) {
         $this->index();
         return;
      }

      $data['user'] = $_SESSION['user'];

      // validation errors~
      if (!empty($_SESSION['validationErrors'])) {
         $data['validationErrors'] = $_SESSION['validationErrors'];
         unset($_SESSION['validationErrors']);
      }

      // look for server errors
      if (!empty($_SESSION['serverErrors'])) {
         $data['serverErrors'] = $_SESSION['serverErrors'];
         unset($_SESSION['serverErrors']);
      }

      $this->view('layouts/html_header');
      $this->view('navbar', $data);
      $this->view('profile_change_password_frm', $data);
      $this->view('footer');
      $this->view('layouts/html_footer');
   }

   #=================================================
   public function change_password_submit()
   {

      if (!check_session()) {
         $this->index();
         return;
      }

      validate_post();

      // validation var
      $validationErrors = [];

      // text_current_password
      // text_new_password
      // text_repeat_new_password

      // check current password 
      if (empty($_POST['text_current_password'])) {
         $validationErrors[] = 'Password atual é de preenchimento obrigatório';
         $_SESSION['validationErrors'] = $validationErrors;
         $this->change_password_frm();
         return;
      }

      // text_new_password
      if (empty($_POST['text_new_password'])) {
         $validationErrors[] = 'A nova password é de preenchimento obrigatório';
         $_SESSION['validationErrors'] = $validationErrors;
         $this->change_password_frm();
         return;
      }

      // text_repeat_new_password
      if (empty($_POST['text_repeat_new_password'])) {
         $validationErrors[] = 'A repetição da password é de preenchimento obrigatório';
         $_SESSION['validationErrors'] = $validationErrors;
         $this->change_password_frm();
         return;
      }

      // get the input values 
      $currentPassword = $_POST['text_current_password'];
      $newPassword = $_POST['text_new_password'];
      $repeatNewPassword = $_POST['text_repeat_new_password'];

      // check if password has more than 6 and less than 12 chars
      if (strlen($currentPassword) < 6 || strlen($currentPassword) > 12) {
         $validationErrors[] = 'A password atual deve conter de 6 a 12 caracteres.';
         $_SESSION['validationErrors'] = $validationErrors;
         $this->change_password_frm();
         return;
      }

      if (strlen($newPassword) < 6 || strlen($newPassword) > 12) {
         $validationErrors[] = 'A nova password deve conter entre 6 a 12 caracteres.';
         $_SESSION['validationErrors'] = $validationErrors;
         $this->change_password_frm();
         return;
      }

      if (strlen($repeatNewPassword) < 6 || strlen($repeatNewPassword) > 12) {
         $validationErrors[] = 'A repetição da nova password deve ter entre 6 e 12 caracteres';
         $_SESSION['validationErrors'] = $validationErrors;
         $this->change_password_frm();
         return;
      }

      // check if the password have at least one upperkey, one lowerkey, and one digit 

      if (!preg_match("/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])/", $currentPassword)) {
         $validationErrors[] = 'A password atual tem de conter pelo menos, 1 letra maiuscula, 1 minuscula e um numero';
         $_SESSION['validationErrors'] = $validationErrors;
         $this->change_password_frm();
         return;
      }
      
      if (!preg_match("/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])/", $newPassword)) {
         $validationErrors[] = 'A nova password tem de conter pelo menos, 1 letra maiuscula, 1 minuscula e um numero';
         $_SESSION['validationErrors'] = $validationErrors;
         $this->change_password_frm();
         return;
      }

      if (!preg_match("/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])/", $repeatNewPassword)) {
         $validationErrors[] = 'A repetição da nova password tem de conter pelo menos, 1 letra maiuscula, 1 minuscula e um numero';
         $_SESSION['validationErrors'] = $validationErrors;
         $this->change_password_frm();
         return;
      }

      if ($newPassword !== $repeatNewPassword) {
         $validationErrors[] = 'As duas password não coincidem.';
         $_SESSION['validationErrors'] = $validationErrors;
         $this->change_password_frm();
         return;
      }

      // check if the inputed password is equal to the database
      $model = new Agents();
      $results = $model->check_current_password($currentPassword);

      if (!$results['status']) {
         // here we check that the passwords dont match 
         $serverError[] = 'Palavra-passe incorreta.';
         $_SESSION['serverError'] = $validationErrors;
         $this->change_password_frm();
         return;
      }
      // form is ok, lets update the password in the database 
      $model->update_agent_password($newPassword);

      //logger 
      logger(get_active_user_name() . ' - Atualizou a sua palavra passe');

      // show success view
      $data['user'] = $_SESSION['user'];
      $this->view('layouts/html_header');
      $this->view('navbar', $data);
      $this->view('profile_change_password_success', $data);
      $this->view('footer');
      $this->view('layouts/html_footer');
   }

   #=================================================
   public function define_password($purl = '')
   {
      // die($purl);
      if (check_session()) {
         $this->index();
         return;
      }

      // checl if the purl is valid 
      if (empty($purl) || strlen($purl) !== 20) {
         die('Erro nas credenciais de access');
      }

      // check if there is another agent with the same purl
      $model = new Agents();
      $results = $model->check_new_agent_purl($purl);
      if (!$results['status']) {
         die('Erro nas credenciais de acesso');
      }

      if (!empty($_SESSION['validationError'])) {
         $data['validationError'] = $_SESSION['validationError'];
         unset($_SESSION['validationError']);
      }

      // set data to views
      $data['purl'] = $purl;
      $data['id'] = $results['id'];

      $this->view('layouts/html_header');
      $this->view('new_agent_define_password', $data);
      $this->view('layouts/html_footer');
   }

   #=================================================
   public function define_password_submit()
   {
      if (check_session()) {
         $this->index();
         return;
      }

      // check if a post was made
      validate_post();

      // form validation
      if (empty($_POST['purl']) || empty($_POST['id']) || strlen($_POST['purl']) !== 20) {
         $this->index();
         return;
      }

      // get hidden fields
      $id = aes_decrypt($_POST['id']);
      $purl = $_POST['purl'];

      // check if the decryption of id passed
      if (!$id) {
         $this->index();
         return;
      }


      // here the user is able to pass to the actual form 
      // check if the inputs are empty
      if (empty($_POST['text_new_password'])) {
         $_SESSION['validationError'] = 'O campo da password é de preenchimento obrigatório.';
         $this->define_password($purl);
         return;
      }

      if (empty($_POST['text_repeat_new_password'])) {
         $_SESSION['validationError'] = 'O campo da repetição da password é de preenchimento obrigatório.';
         $this->define_password($purl);
         return;
      }

      $password = $_POST['text_new_password'];
      $passwordRepeat = $_POST['text_repeat_new_password'];

      // check lenghts
      if (strlen($password) < 6 || strlen($password) > 12) {
         $_SESSION['validationError'] = 'O campo da password deve ter entre 6 e 12 caracteres.';
         $this->define_password($purl);
         return;
      }

      if (strlen($passwordRepeat) < 6 || strlen($passwordRepeat) > 12) {
         $_SESSION['validationError'] = 'O campo da repetição de password deve ter entre 6 e 12 caracteres.';
         $this->define_password($purl);
         return;
      }

      // check if the password have atleast one uppercase char
      // one lowercase char and one digit 
      if (!preg_match("/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])/", $password)) {
         $_SESSION['validationError'] = 'O campo da password deve ter pelo menos 1 caractére maiusculos, 1 maiusculo e 1 algarismo.';
         $this->define_password($purl);
         return;
      }

      if (!preg_match("/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])/", $passwordRepeat)) {
         $_SESSION['validationError'] = 'O campo da repetição de password deve ter pelo menos 1 caractére maiusculos, 1 maiusculo e 1 algarismo.';
         $this->define_password($purl);
         return;
      }

      if ($password !== $passwordRepeat) {
         $_SESSION['validationError'] = 'As password não coincidem.';
         $this->define_password();
         return;
      }


      // update the password in the database
      $model = new Agents();
      $model->set_agent_password($id, $password);

      // logger 
      logger('Foi definido uma password para o agente ID: ' . $id . " | purl: $purl");

      // display the success view 
      $this->view('layouts/html_header');
      $this->view('reset_password_define_password_success');
      $this->view('layouts/html_footer');
   }

   #=================================================
   public function reset_password()
   {
      if (check_session()) {
         $this->index();
         return;
      }

      $data = [];

      // checks validation errors
      if(isset($_SESSION['validationError'])) {
         $data['validationError'] = $_SESSION['validationError'];
         unset($_SESSION['validationError']);
      }

      // check for server errors 
      if (isset($_SESSION['serverError'])) {
         $data['serverError'] = $_SESSION['serverError'];
         unset($_SESSION['serverError']);
      }
   

      $this->view('layouts/html_header');
      $this->view('reset_password_frm',$data);
      $this->view('layouts/html_footer', $data);
   }

   #=================================================
   public function reset_password_submit()
   {
      if(check_session()) {
         $this->index();
         return;
      }

      // check if it was post
      validate_post();

      // form validation
      if(empty($_POST['text_username'])) {
         $_SESSION['validationError'] = 'O username é de preenchimento obrigatório';
         $this->reset_password();
         return;
      }

      if(!filter_var($_POST['text_username'], FILTER_VALIDATE_EMAIL)) {
         $_SESSION['validationError'] = 'O username deve ser um email válido';
         $this->reset_password();
         return;
      }

      $username = $_POST['text_username'];

      // set a code to recover password,send the email
      $model = new Agents();
      $results = $model->set_code_for_recover_password($username);

      if ($results['status'] == 'error') {
         // logger 
         logger('Aconteceu um erro na criação do codigo de recuperação de password. User: ' . $username,'error');
         $_SESSION['validationError'] = 'Aconteceu um erro inesperado. Por favor tente novamente.';
         $this->reset_password();
         return;
      }

      $id = $results['id'];
      $code = $results['code'];

      $eol="\r\n"; 

      // code is stored. Send email with the code 
      $email = new SendEmail();
      $results = $email->send_email(APP_NAME . '- Código para recuperar a password' , 'código_recuperar_password' ,['to'=> $username,'code' => $results['code']]);

      if ($results['status'] == 'error') {
         // logger 
         logger('Aconteceu um erro com o envia do email de recuperação de password. User:' . $username);

         $_SESSION['validationError'] = 'Aconteceu um erro inesperado. Por favor tente novamente.';
         $this->reset_password();
         return;
      }

      logger('Email com código de verificação enviado com sucesso User: ' . $username . ' | Code: ' . $code);

      // the email was sent 
      $this->insert_code(aes_encrypt($id));
   }

   #=================================================
   public function insert_code ($id = '') {  
      if (check_session()) {
         $this->index();
         return;
      }

      // check if id is valid 
      if(empty($id)) {
         $this->index();
         return;
      }

      $id = aes_decrypt($id);
      if (!$id) {
         $this->index();
         return;
      }

      $data['id'] = $id;

      // check for validation Errors
      if (isset($_SESSION['validationError'])) {
         $data['validationError'] = $_SESSION['validationError'];
         unset($_SESSION['validationError']);
      }

      if(isset($_SESSION['serverError'])) {
         $data['serverError'] = $_SESSION['serverError'];
         unset($_SESSION['serverError']);
      }

      // display the views 
      $this->view('layouts/html_header');
      $this->view('reset_password_insert_code',$data);
      $this->view('layouts/html_footer');
   }

   #=================================================
   public function insert_code_submit($id) {
      // will reveive the code from the reset_password_insert_code view

      if(check_session()) {
         $this->index();
         return;
      }

      // check id 
      if (empty($id)) {
         $this->index();
         return;
      }

      $id = aes_decrypt($id);
      if(!$id) {
         $this->index();
         return;
      }


      // validate if its a post
      validate_post();

      // form validation 
      if (empty($_POST['text_code'])) {
         $_SESSION['validationError'];
      }

      $code = $_POST['text_code'];

      if(!preg_match("/^\d{6}$/",$code)) {
         $_SESSION['validationError'] = 'O código deve ser constituido por 6 apenas algarismos.';
         $this->index(aes_encrypt($id));
         return;
      }

      // check of the code is correct 
      $model = new Agents();
      $results = $model->check_if_reset_code_is_correct($id , $code);

      if(!$results) {
         $_SESSION['serverError'] = 'Código incorreto.';
         $this->index(aes_encrypt($id));
         return;
      }
      
      $this->reset_define_password(aes_encrypt($id));
   }

   #=================================================
   public function reset_define_password($id = '')
   {
      // check to see if the user is logged in already
      if(check_session()) {
         $this->index();
         return;
      }

      // check if the id is valid 
      if (empty($id)) {
         $this->index();
         return;
      }
      
      $id = aes_decrypt($id);
      if(!$id) {
         $this->index();
         return;
      }

      $data['id'] = $id;

      if(isset($_SESSION['validationError'])) {
         $data['validationError'] = $_SESSION['validationError'];
         unset($_SESSION['validationError']);
      }

      if(isset($_SESSION['serverError'])) {
         $data['serverError'] = $_SESSION['serverError'];
         unset($_SESSION['serverError']);
      }

      // display the form to define a new password 
      $this->view('layouts/html_header');
      $this->view('reset_password_define_password_frm',$data);
      $this->view('layouts/html_footer');
   }

   public function reset_define_password_submit($id = '')
   {
      // check to see if the user is logged in already
      if(check_session()) {
         $this->index();
         return;
      }
      
      // check if the id is valid 
      if (empty($id)) {
         $this->index();
         return;
      }
      
      $id = aes_decrypt($id);
      if(!$id) {
         $this->index();
         return;
      }

      // check if there is a post request 
      validate_post();

      // form validation
      if(empty($_POST['text_new_password'])) {
         $_SESSION['validationError'] = 'O campo da password é de preenchimento obrigatório';
         $this->reset_define_password(aes_encrypt($id));
         return;
      }

      if(empty($_POST['text_repeat_new_password'])) {
         $_SESSION['validationError'] = 'O campo da nova password é de preenchimento obrigatório';
         $this->reset_define_password(aes_encrypt($id));
         return;
      }

      // get the input values
      $newPassword = $_POST['text_new_password'];
      $repeatNewPassword = $_POST['text_repeat_new_password'];

      // validate the sizes of the input
      if(strlen($newPassword) < 6 || strlen($newPassword) > 12) {
         $_SESSION['validationError'] = 'O campo da password deve conter apenas entre 6 e 12 caractéres';
         $this->reset_define_password(aes_encrypt($id));
         return;
      }

      if(strlen($repeatNewPassword) < 6 || strlen($repeatNewPassword) > 12) {
         $_SESSION['validationError'] = 'O campo da repetição da password deve conter apenas entre 6 e 12 caractéres';
         $this->reset_define_password(aes_encrypt($id));
         return;
      }

      // check if password have at least 1 uppercase,1 lowercase and 1 digit
      if (!preg_match("/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])/", $newPassword)) {
         $validationErrors[] = 'A nova password tem de conter pelo menos, 1 letra maiuscula, 1 minuscula e um numero';
         $_SESSION['validationErrors'] = $validationErrors;
         $this->reset_define_password(aes_encrypt($id));
         return;
      }

      if (!preg_match("/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])/", $repeatNewPassword)) {
         $validationErrors[] = 'A repetição da nova password tem de conter pelo menos, 1 letra maiuscula, 1 minuscula e um numero';
         $_SESSION['validationErrors'] = $validationErrors;
         $this->reset_define_password(aes_encrypt($id));
         return;
      }

      // check if both of the passwords are equal 
      if ($newPassword != $repeatNewPassword) {
         $_SESSION['validationError'] = 'As passwords não coincidem';
         $this->reset_define_password(aes_decrypt($id));
         return;
      }

      // updates the agent password in the database 
      $model = new Agents();
      $model->change_agent_password($id ,$newPassword);

      logger("A palavra passe do utilizador com id:[$id] foi alterado com sucesso");

      // display the successs page 
      $this->view('layouts/html_header');
      $this->view('profile_change_password_success');
      $this->view('layouts/html_footer');

   }
}