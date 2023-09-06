<?php

namespace bng\Controllers;

use bng\Controllers\BaseController;
use bng\Models\Agents;
use DateTime;

class Agent extends BaseController
{

   #=================================================
   public function my_clients()
   {

      if (!check_session() || $_SESSION['user']->profile != 'agent') {
         header('Location: index.php');
      }

      // get all agent clients
      $idAgent = $_SESSION['user']->id;
      $model = new Agents();
      $results = $model->get_agent_clients($idAgent);

      $data['user'] = $_SESSION['user'];
      $data['clients'] = $results['data'];


      // load views with clients and user data
      $this->view('layouts/html_header');
      $this->view('navbar', $data);
      $this->view('agent_clients', $data);
      $this->view('footer');
      $this->view('layouts/html_footer');
   }

   #=================================================
   public function new_client_frm()
   {
      if (!check_session() || $_SESSION['user']->profile != 'agent') {
         header('Location: index.php');
      }

      $data['user'] = $_SESSION['user'];

      // send to the view to include the flatpickr lib
      $data['flatpickr'] = true;

      // check validation errors
      if (!empty($_SESSION['validationErrors'])) {
         $data['validationErrors']  = $_SESSION['validationErrors'];
         unset($_SESSION['validationErrors']);
      }

      // check if there is a server error 
      if (!empty($_SESSION['serverError'])) {
         $data['serverError'] = $_SESSION['serverError'];
         unset($_SESSION['serverError']);
      }

      // load views
      $this->view('layouts/html_header', $data);
      $this->view('navbar', $data);
      $this->view('insert_client_frm', $data);
      $this->view('footer');
      $this->view('layouts/html_footer');
   }

   #=================================================
   public function new_client_submit()
   {
      // validation 
      if (!check_session() || $_SESSION['user']->profile != 'agent' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
         header('Location: index.php');
      }
      // form validation
      $validationErrors = [];

      // text name
      if (empty($_POST['text_name'])) {
         $validationErrors[] = 'Nome de preenchimento obrigatório.';
      } else {
         if (strlen($_POST['text_name']) < 3 || strlen($_POST['text_name']) > 50) {
            $validationErrors[] = 'O nome deve ter entre 3 e 50 caracteres.';
         }
      }

      // gender 
      if (empty($_POST['radio_gender'])) {
         $validationErrors[] = 'O género é de preenchimento obrigatório.';
      }

      // text_birthdate
      if (empty($_POST['text_birthdate'])) {
         $validationErrors[] = 'A data de nascimento é de preenchimento obrigatório.';
      } else {
         // check if birthday is valid and is older than today
         $birthdate = \DateTime::createFromFormat('d-m-Y', $_POST['text_birthdate']);
         if (!$birthdate) {
            $validationErrors[] = 'A data de nascimento não está no formato correto.';
         } else {
            $todayDate = new \DateTime();
            if ($birthdate >= $todayDate) {
               $validationErrors[] = 'A data de nascimento tem de ser anterior ao dia atual.';
            }
         }
      }

      // email 
      if (empty($_POST['text_email'])) {
         $validationErrors[] = 'O email é de preenchimento obrigatório.';
      } else {
         if (!filter_var($_POST['text_email'], FILTER_VALIDATE_EMAIL)) {
            $validationErrors[] = 'Email não é valido.';
         }
      }

      // phone 
      if (empty($_POST['text_phone'])) {
         $validationErrors[] = 'O telefone é de preenchimento obrigatórios.';
      } else {
         if (!preg_match("/^9{1}\d{8}$/", $_POST['text_phone'])) {
            $validationErrors[] = 'O telefone deve começar por 9 e ter 9 algarismos.';
         }
      }

      // check if there are validation errors
      // return to the form if array nt empty
      if (!empty($validationErrors)) {
         $_SESSION['validationErrors'] = $validationErrors;
         $this->new_client_frm();
         return;
      }

      $model = new Agents();
      $results = $model->check_if_client_exists($_POST);

      if ($results['status']) {
         // A person with same name was found in DB
         $_SESSION['serverError'] = 'Já existe um cliente com o mesmo nome.';
         $this->new_client_frm();
         return;
      }

      // insert new client in the database
      $model->add_new_client_to_database($_POST);

      // logger
      logger(get_active_user_name() . " - Adicionou o cliente, " . $_POST['text_name'] . " | " . $_POST['text_email']);

      $this->my_clients();
   }

   #=================================================
   public function edit_client_submit()
   {
      // validation 
      //  [id_client] => 
      //  [text_name] => dsanuonudsa
      //  [radio_gender] => m
      //  [text_birthdate] => 20-03-2023
      //  [text_email] => djnasnjkd@asnd.com
      //  [text_phone] => 977858456
      //  [text_interests] => asdsadsadasdsadasdsa

      if (!check_session() || $_SESSION['user']->profile != 'agent' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
         header('Location: index.php');
      }

      $validationErrors = [];

      // name validation
      if (empty($_POST['text_name'])) {
         $validationErrors[] = 'O nome é de preenchimento obrigatório.';
      } else {
         if (strlen($_POST['text_name'] < 3 || strlen($_POST['text_name']) > 50)) {
            $validationErrors[] = 'O username deve conter entre 3 e 50 caracteres.';
         }
      }

      // gender validation
      if (empty($_POST['radio_gender'])) {
         $validationErrors[] = 'O género é de preenchimento obrigatório.';
      }

      // birthdate validation
      if (empty($_POST['text_birthdate'])) {
         $validationErrors[] = 'A data de nascimento é de preenchimento obrigatório.';
      } else {
         // check if birthdate is valid and is older than today 
         $birthdate = \DateTime::createFromFormat('d-m-Y', $_POST['text_birthdate']);

         if (!$birthdate) {
            $validationErrors[] = 'A data de nascimento não está no formato correto.';
         } else {
            $today = new DateTime();
            if ($birthdate >= $today) {
               $validationErrors[] = 'A data de nascimento tem que ser anterior ao dia atual.';
            }
         }
      }

      // email
      if (empty($_POST['text_email'])) {
         $validationErrors[] = 'O email é de preenchimento obrigatório.';
      } else {
         if (!filter_var($_POST['text_email'], FILTER_VALIDATE_EMAIL)) {
            $validationErrors[] = 'O email tem de ser válido.';
         }
      }

      // phone 
      if (empty($_POST['text_phone'])) {
         $validationErrors[] = 'O telefone é de preenchimento obrigatório.';
      } else {
         if (!preg_match("/^9{1}\d{8}$/", $_POST['text_phone'])) {
            $validationErrors[] = 'O telefone deve começar por 9 e ter 9 caracteres.';
         }
      }

      // check if id is present in post and its valid 
      if (empty($_POST['id_client'])) {
         header("Location: index.php");
      }

      // decrypts the client id
      $idCliente = aes_decrypt($_POST['id_client']);

      // check if the decrypted value is true
      if (!$idCliente) {
         header('Location: index.php');
      }

      // check if there are validation errors 
      if (!empty($validationErrors)) {
         $_SESSION['validationErrors'] = $validationErrors;
         $this->edit_client((aes_encrypt($idCliente)));
         return;
      }

      // check if there is another client with the same name 
      $model = new Agents();
      $results = $model->check_other_client_with_same_name($idCliente, $_POST['text_name']);

      // check if there is
      if ($results['status']) {
         $_SESSION['serverError'] = 'Já existe outro cliente com o mesmo nome.';
         $this->edit_client(aes_encrypt($idCliente));
         return;
      }

      // updates the agent client in the database
      $model->update_client_data($idCliente, $_POST);

      // logger 
      logger(get_active_user_name() . '- atualizou os dados do ID cliente: ' . $idCliente);

      $this->my_clients();
   }

   #=================================================
   public function edit_client($id)
   {
      // validation 
      if (!check_session() || $_SESSION['user']->profile != 'agent') {
         header('Location: index.php');
      }

      // check if the id is valid 
      $idClient = aes_decrypt($id);

      if (!$idClient) {
         header('Location: index.php');
      }

      $model = new Agents();
      $results = $model->get_client_data($idClient);

      // check if client data exists 
      if (!$results['status']) {
         header('Location: index.php');
      }

      $data['client'] = $results['data'];
      $data['client']->birthdate = date('d-m-Y', strtotime($data['client']->birthdate));

      // display the edit client form 
      $data['user'] = $_SESSION['user'];

      if (!empty($_SESSION['validationErrors'])) {
         $data['validationErrors'] = $_SESSION['validationErrors'];
         unset($_SESSION['validationErrors']);
      }

      if (!empty($_SESSION['serverError'])) {
         $data['serverError'] = $_SESSION['serverError'];
         unset($_SESSION['serverError']);
      }

      // include the flatpickr lib  
      $data['flatpickr'] = true;
      $this->view('layouts/html_header', $data);
      $this->view('navbar', $data);
      $this->view('edit_client_frm', $data);
      $this->view('footer');
      $this->view('layouts/html_footer');
   }

   #=================================================
   public function delete_client($id)
   {
      // validation 
      if (!check_session() || $_SESSION['user']->profile != 'agent') {
         header('Location: index.php');
      }

      $idClient = aes_decrypt($id);
      if (!$idClient) {
         // check if id is correct
         header('Location: index.php');
      }

      // load model to get clients data 
      $model = new Agents();
      $results = $model->get_client_data($idClient);

      // check if database query results is
      // returning data
      if (empty($results['data'])) {
         header("Location: index.php");
      }

      // prepare data to view
      $data['user'] = $_SESSION['user'];
      $data['client'] = $results['data'];

      // load views 
      $this->view('layouts/html_header');
      $this->view('navbar', $data);
      $this->view('delete_client_confirmation', $data);
      $this->view('footer');
      $this->view('layouts/html_footer');
   }

   #=================================================
   public function delete_client_confirm($id)
   {
      // validation 
      if (!check_session() || $_SESSION['user']->profile != 'agent') {
         header('Location: index.php');
      }

      // check of id is valid 
      $idClient = aes_decrypt($id);
      if (!$idClient) {
         header("Location: index.php");
      }

      // loads the model to delete client 
      $model = new Agents();
      $model->delete_client($idClient);

      // logger 
      logger(get_active_user_name() . ' - Eliminado o cliente com o id: ' .  $idClient);

      // return to page 
      $this->my_clients();
   }

   #=================================================
   public function upload_file_frm()
   {
      if (!check_session() || $_SESSION['user']->profile !== 'agent') {
         header('Location: index.php');
      }

      // display the view 
      $data['user'] = $_SESSION['user'];

      if (!empty($_SESSION['serverError'])) {
         $data['serverError'] = $_SESSION['serverError'];
         unset($_SESSION['serverError']);
      }

      if (!empty($_SESSION['report'])) {
         $data['report'] = $_SESSION['report'];
         unset($_SESSION['report']);
      }

      // load views 
      $this->view('layouts/html_header');
      $this->view('navbar', $data);
      $this->view('upload_file_with_clients_frm', $data);
      $this->view('footer');
      $this->view('layouts/html_footer');
   }

   #=================================================
   public function upload_file_submit()
   {

      if (!check_session() || $_SESSION['user']->profile !== 'agent') {
         header('Location: index.php');
      }

      // check if there is a post
      validate_post();

      // check if there is uploaded files 
      if (empty($_FILES) || empty($_FILES['clients_file']['name'])) {
         $_SESSION['serverError'] = 'Por favor faça upload de um ficheiro CSV ou XLSX.';
         $this->upload_file_frm();
         return;
      }

      // check if the file extension is valid 
      $validExtension = [
         'xlsx',
         'csv'
      ];
      $tmp = explode('.', $_FILES['clients_file']['name']);
      $extension = end($tmp);

      // check if the extension is compatible 
      if (!in_array($extension, $validExtension)) {
         // logger 
         logger(get_active_user_name() . ' - tentou carregar um ficheiro->' . $_FILES['clients_file']['name'], 'error');
         $_SESSION['serverError'] = 'O ficheiro deve ser do tipo CSV ou XLSX.';
         $this->upload_file_frm();
         return;
      }

      // check if file is larger than 2 MB
      if ($_FILES['clients_file']['size'] > 200000) {
         // logger 
         logger(get_active_user_name() . ' - tentou carregar um ficheiro grande de mais->' . $_FILES['clients_file']['name'], 'error');
         $_SESSION['serverError'] = 'O ficheiro deve ter no maximo 2MB';
         $this->upload_file_frm();
         return;
      }

      // move file to final destination 
      $filePath = __DIR__ . '/../../public/uploads/import_' . time() . '.' . $extension;
      if (move_uploaded_file($_FILES['clients_file']['tmp_name'], $filePath)) {

         // call a function to validate if the header is valid
         $result = $this->has_valid_header($filePath);

         if ($result) {
            // header is ok, now we gonna load the file data to DB
            $results = $this->load_file_to_database($filePath);
         } else {
            // logger 
            logger(get_active_user_name() . ' - Tentou carregar um ficheiro com o header inválido->' . $_FILES['clients_file']['name'], 'error');
            $_SESSION['serverError'] = 'O ficheiro não tem um header no Formato correto.';
            $this->upload_file_frm();
            return;
         }
      } else {
         //logger 
         logger(get_active_user_name() . ' - Aconteceu um erro inesperado na submissão do ficheiro->' . $_FILES['clients_file']['name'], 'error');
         // Upload error ... 
         $_SESSION['serverError'] = 'Aconteceu um erro inesperado e o upload do ficheiro não foi possivel.';
         $this->upload_file_frm();
         return;
      }
   }

   #=================================================
   private function load_file_to_database($filePath)
   {
      $data = [];
      $fileInfo = pathinfo($filePath);

      if ($fileInfo['extension'] == 'csv') {
         // create reader obj
         $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
         $reader->setInputEncoding('UTF-8');
         $reader->setDelimiter(';');
         $reader->setEnclosure('');
         $sheet = $reader->load($filePath);
         $data = $sheet->getActiveSheet()->toArray();
      } elseif ($fileInfo['extension'] == 'xlsx') {
         // opens xlsx file to read the header only 
         $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
         $reader->setReadDataOnly(true);
         $spreadSheet = $reader->load($filePath);
         $data = $spreadSheet->getActiveSheet()->toArray();
      }

      $model = new Agents();

      $report = [
         'total' => 0,
         'totalCarregados' => 0,
         'totalNaoCarregados' => 0
      ];
      // destroy the first element of array (header)
      array_shift($data);

      // cicle to insert all the elements on the array
      foreach ($data as $client) {
         // prepare date in case the file is xlsx
         if ($fileInfo['extension'] == 'xlsx') {
            $client[2] = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($client[2]);
            $client[2] = $client[2]->format("Y-m-d H:i:s");
         }

         $report['total']++;
         // check if the client already exists in the database
         $status = $model->check_if_client_exists(['text_name' => $client[0]]);
         if (!$status['status']) {
            $report['totalCarregados']++;

            $postData = [
               'text_name' => $client[0],
               'radio_gender' => $client[1],
               'text_birthdate' => $client[2],
               'text_email' => $client[3],
               'text_phone' => $client[4],
               'text_interests' => $client[5]
            ];
            $model->add_new_client_to_database($postData);
         } else {
            // client already is in the database
            $report['totalNaoCarregados']++;
         }
      }

      // logger 
      logger(get_active_user_name() . ' - Carregamento do ficheiro efetuado->' . $_FILES['clients_file']['name']);
      logger(get_active_user_name() . " - report->" . json_encode($report));

      // set a report session
      $report['filename'] = $_FILES['clients_file']['name'];
      $_SESSION['report'] = $report;

      $this->upload_file_frm();
   }

   #=================================================
   public function export_clients_xlsx()
   {
      if (!check_session() || $_SESSION['user']->profile !== 'agent') {
         header('Location: index.php');
      }

      $model = new Agents();
      $results = $model->get_agent_clients($_SESSION['user']->id);

      // get all agent clients 
      $data[] = ['name', 'gender', 'birthdate', 'email', 'phone', 'interests', 'created_at', 'updated_at'];

      // place all clients in the $data collection
      foreach ($results['data'] as $client) {
         // remove id from array 
         unset($client->id);

         // transform the client obj in array 
         $data[] = (array)$client;
      }

      // store into the xlsx 
      $username = explode('@', $_SESSION['user']->name);
      $filename = "dump_" . $username[0] . '_' . time() . '.xlsx';
      $spreadSheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
      $spreadSheet->removeSheetByIndex(0);
      $workSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadSheet, 'dados');
      $spreadSheet->addSheet($workSheet);
      $workSheet->fromArray($data);

      $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadSheet);
      header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
      header('Content-Disposition: attachment; filename="' . urldecode($filename) . '"');
      $writer->save('php://output');

      // logger 
      logger(get_active_user_name() . ' - Fez download de uma lista de clientes no total de ' . count($data));
   }

   #=================================================
   # PRIVATE METHODS 
   #=================================================
   private function has_valid_header($filePath)
   {
      // validates the file 
      $data = [];
      $fileInfo = pathinfo($filePath);

      if ($fileInfo['extension'] == 'csv') {
         // create reader obj
         $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
         $reader->setInputEncoding('UTF-8');
         $reader->setDelimiter(';');
         $reader->setEnclosure('');
         $sheet = $reader->load($filePath);
         $data = $sheet->getActiveSheet()->toArray()[0];
      } elseif ($fileInfo['extension'] == 'xlsx') {
         // opens xlsx file to read the header only 
         $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
         $reader->setReadDataOnly(true);
         $spreadSheet = $reader->load($filePath);
         $data = $spreadSheet->getActiveSheet()->toArray()[0];
      }
      // validate the header 
      $header = 'name,gender,birthdate,email,phone,interests';
      $headerToCheck = implode(',', $data);
      return $header == $headerToCheck ? true : false;
   }
}
