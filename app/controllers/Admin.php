<?php

namespace bng\Controllers;

use bng\Controllers\BaseController;
use bng\Models\AdminModel;
use bng\System\SendEmail;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf;

class Admin extends BaseController
{
    #=================================================
    public function all_clients()
    {
        // check session and if profile is admin
        if (!check_session() || $_SESSION['user']->profile !== 'admin') {
            header('Location: index.php');
        }

        // get all clients from all agents 
        $model = new AdminModel();
        $results = $model->get_all_clients();

        $data['user'] = $_SESSION['user'];
        $data['clients'] = $results->results;

        // load views with clients and user data
        $this->view('layouts/html_header');
        $this->view('navbar', $data);
        $this->view('global_clients', $data);
        $this->view('footer');
        $this->view('layouts/html_footer');
    }

    #=================================================
    public function export_clients_xlsx()
    {
        if (!check_session() || $_SESSION['user']->profile !== 'admin') {
            header('Location: index.php');
        }

        $model = new AdminModel();
        $results = $model->get_all_clients();
        $results = $results->results;

        // get all agent clients 
        $data[] = ['name', 'gender', 'birthdate', 'email', 'phone', 'interests', 'created_at', 'Agente'];

        // place all clients in the $data collection
        foreach ($results as $client) {
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
    public function stats()
    {
        if (!check_session() || $_SESSION['user']->profile !== 'admin') {
            header('Location: index.php');
        }

        // get totals from agent's clients 
        $model = new AdminModel();
        $data['agents'] = $model->get_all_clients_stats();

        $data['user'] = $_SESSION['user'];

        // prepare data to chart js 
        if (count($data['agents']) !== 0) {
            $labelsTmp = [];
            $totalsTmp = [];
            foreach ($data['agents'] as $agent) {
                $labelsTmp[] = $agent->agente;
                $totalsTmp[] = $agent->total_clientes;
            }
            $data['chartLabels'] = '["' . implode('","', $labelsTmp) . '"]';
            $data['chartTotals'] = '["' . implode('","', $totalsTmp) . '"]';
            $data['chartJs'] = true;
        }

        // set global stats 
        $data['globalStats'] = $model->get_global_stats();
        
        // load views
        $this->view('layouts/html_header', $data);
        $this->view('navbar', $data);
        $this->view('stats', $data);
        $this->view('footer');
        $this->view('layouts/html_footer');
    }

    // =======================================================
    public function create_pdf_report()
    {
        // check if session has a user with admin profile
        if (!check_session() || $_SESSION['user']->profile != 'admin') {
            header('Location: index.php');
        }

        // logger
        logger(get_active_user_name() . " - visualizou o PDF com o report estatístico.");

        // get totals from agent's clients and global stats
        $model = new AdminModel();
        $agents = $model->get_all_clients_stats();
        $globalStats = $model->get_global_stats();

        // generate PDF file
        $pdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P'
        ]);

        // set starting coordinates
        $x = 50;    // horizontal
        $y = 50;    // vertical
        $html = "";

        // logo and app name
        $html .= '<div style="position: absolute; left: ' . $x . 'px; top: ' . $y . 'px;">';
        $html .= '<img src="assets/images/logo_32.png">';
        $html .= '</div>';
        $html .= '<h2 style="position: absolute; left: ' . ($x + 50) . 'px; top: ' . ($y - 10) . 'px;">' . APP_NAME . '</h2>';

        // separator
        $y += 50;
        $html .= '<div style="position: absolute; left: ' . $x . 'px; top: ' . $y . 'px; width: 700px; height: 1px; background-color: rgb(200,200,200);"></div>';

        // report title
        $y += 20;
        $html .= '<h3 style="position: absolute; left: ' . $x . 'px; top: ' . $y . 'px; width: 700px; text-align: center;">REPORT DE DADOS DE ' . date('d-m-Y') . '</h4>';

        // -----------------------------------------------------------
        // table agents and totals
        $y += 50;

        $html .= '
            <div style="position: absolute; left: ' . ($x + 90) . 'px; top: ' . $y . 'px; width: 500px;">
                <table style="border: 1px solid black; border-collapse: collapse; width: 100%;">
                    <thead>
                        <tr>
                            <th style="width: 60%; border: 1px solid black; text-align: left;">Agente</th>
                            <th style="width: 40%; border: 1px solid black;">N.º de Clientes</th>
                        </tr>
                    </thead>
                    <tbody>';
        foreach ($agents as $agent) {
            $html .=
                '<tr style="border: 1px solid black;">
                    <td style="border: 1px solid black;">' . $agent->agente . '</td>
                    <td style="text-align: center;">' . $agent->total_clientes . '</td>
                </tr>';
            $y += 25;
        }

        $html .= '
            </tbody>
            </table>
            </div>';

        // -----------------------------------------------------------
        // table globals
        $y += 50;

        $html .= '
            <div style="position: absolute; left: ' . ($x + 90) . 'px; top: ' . $y . 'px; width: 500px;">
                <table style="border: 1px solid black; border-collapse: collapse; width: 100%;">
                    <thead>
                        <tr>
                            <th style="width: 60%; border: 1px solid black; text-align: left;">Item</th>
                            <th style="width: 40%; border: 1px solid black;">Valor</th>
                        </tr>
                    </thead>
                    <tbody>';

        $html .= '<tr><td>Total agentes:</td><td style="text-align: right;">' . $globalStats['totalAgents']->value . '</td></tr>';
        $html .= '<tr><td>Total clientes:</td><td style="text-align: right;">' . $globalStats['totalClients']->value . '</td></tr>';
        $html .= '<tr><td>Total clientes removidos:</td><td style="text-align: right;">' . $globalStats['totalInactiveClients']->value . '</td></tr>';
        $html .= '<tr><td>Média de clientes por agente:</td><td style="text-align: right;">' . sprintf("%.2f", $globalStats['averageClientsPerAgent']->value) . '</td></tr>';

        if (empty($globalStats['youngerClient']->value)) {
            $html .= '<tr><td>Idade do cliente mais novo:</td><td style="text-align: right;">-</td></tr>';
        } else {
            $html .= '<tr><td>Idade do cliente mais novo:</td><td style="text-align: right;">' . $globalStats['youngerClient']->value . ' anos.</td></tr>';
        }
        if (empty($globalStats['oldestClient']->value)) {
            $html .= '<tr><td>Idade do cliente mais velho:</td><td style="text-align: right;">-</td></tr>';
        } else {
            $html .= '<tr><td>Idade do cliente mais velho:</td><td style="text-align: right;">' . $globalStats['oldestClient']->value . ' anos.</td></tr>';
        }

        $html .= '<tr><td>Percentagem de homens:</td><td style="text-align: right;">' . $globalStats['percentageMales']->value . ' %</td></tr>';
        $html .= '<tr><td>Percentagem de mulheres:</td><td style="text-align: right;">' . $globalStats['percentageFemales']->value . ' %</td></tr>';

        $html .= '
                    </tbody>
                </table>
            </div>';

        // -----------------------------------------------------------

        $pdf->WriteHTML($html);
        $pdfName = 'Relatório' /* time('d-m-Y')*/;
        $pdf->Output($pdfName, 'I');
    }

    // =======================================================
    public function agents_management()
    {

        // check if session has a user with admin profile
        if (!check_session() || $_SESSION['user']->profile != 'admin') {
            header('Location: index.php');
        }

        // get agents 
        $model = new AdminModel();
        $results = $model->get_agents_for_management();
        $data['agents'] = $results->results;

        $data['user'] = $_SESSION['user'];

        $this->view('layouts/html_header', $data);
        $this->view('navbar', $data);
        $this->view('agents_management', $data);
        $this->view('footer');
        $this->view('layouts/html_footer');
    }

    // =======================================================
    public function new_agent_frm()
    {

        // check if session has a user with admin profile
        if (!check_session() || $_SESSION['user']->profile != 'admin') {
            header('Location: index.php');
        }

        $data['user'] = $_SESSION['user'];

        // checks for validation errors
        if (isset($_SESSION['validationError'])) {
            $data['validationError'] = $_SESSION['validationError'];
            unset($_SESSION['validationError']);
        }

        // checks for server errors
        if (isset($_SESSION['serverError'])) {
            $data['serverError'] = $_SESSION['serverError'];
            unset($_SESSION['serverError']);
        }

        $this->view('layouts/html_header', $data);
        $this->view('navbar', $data);
        $this->view('agents_add_new_frm', $data);
        $this->view('footer');
        $this->view('layouts/html_footer');
    }

    // =======================================================
    public function new_agent_submit()
    {

        // check if session has a user with admin profile
        if (!check_session() || $_SESSION['user']->profile != 'admin') {
            header('Location: index.php');
        }

        // check if there is a post
        validate_post();

        // form validation
        $validationError = null;

        // check if there is a valid email
        // and is not empty
        if (empty($_POST['text_name']) || !filter_var($_POST['text_name'], FILTER_VALIDATE_EMAIL)) {
            $validationError = 'O nome do agente deve ser um email válido.';
        }

        // check if profile is valid
        $validProfiles = ['admin', 'agent'];
        if (empty($_POST['select_profile']) || !in_array($_POST['select_profile'], $validProfiles)) {
            $validationError = 'O perfil selecionado não é valido.';
        }

        if (!empty($validationError)) {
            $_SESSION['validationError'] = $validationError;
            $this->new_agent_frm();
            return;
        }

        // check if there is an agents with the same username
        $model = new AdminModel();
        $results = $model->check_if_user_exists($_POST['text_name']);

        // if ($results) {
        //     // there is an agent with the same name
        //     $_SESSION['serverError'] = 'Já existe um agente com o mesmo nome.';
        //     $this->new_agent_frm();
        //     return;
        // }

        // add new agent do database
        $results = $model->add_new_agent($_POST);

        // logger
        if ($results['status'] == 'error') {
            logger(get_active_user_name() . ' - Aconteceu um erro na criação de um novo registo de agente');
            header('Location: index.php');
        }

        // send email to purl
        $url = explode('?', $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
        $url = $url[0] . '?ct=main&mt=define_password&purl=' . $results['purl'];

        $email = new SendEmail();

        // prepare data to send to the email class
        $data = [
            'to' => $_POST['text_name'],
            'link' => $url
        ];

        // send email 
        $results = $email->send_email(APP_NAME . ' Conclusão do registo do agente', 'email_body_new_agent', $data);
        if ($results['status'] == 'error') {
            // logger
            logger(get_active_user_name() . " - Não foi possivel enviar o email para a conclusão do registo: " . $_POST['text_name']);
            die($results['message']);
        }

        // logger success
        logger(get_active_user_name() . ' - O email de registo foi enviado com sucesso: ' . $_POST['text_name']);

        $data['user'] = $_SESSION['user'];
        $data['email'] = $_POST['text_name'];

        $this->view('layouts/html_header', $data);
        $this->view('navbar', $data);
        $this->view('agents_email_sent', $data);
        $this->view('footer');
        $this->view('layouts/html_footer');
    }

    // =======================================================
    public function edit_agent($id)
    {
        if (!check_session() || $_SESSION['user']->profile != 'admin') {
            header('Location: index.php');
        }

        // check if id is empty
        if (empty($id)) {
            header('Location: index.php');
        }

        $id = aes_decrypt($id);

        // check if id was decrypted
        if (!$id) {
            header('Location: index.php');
        }

        // get agents data 
        $model = new AdminModel();
        $results = $model->get_agent_data($id);

        // server error
        if (isset($_SESSION['serverError'])) {
            $data['serverError'] = $_SESSION['serverError'];
            unset($_SESSION['serverError']);
        }

        // validation errors 
        if (isset($_SESSION['validationError'])) {
            $data['validationError'] = $_SESSION['validationError'];
            unset($_SESSION['validationError']);
        }

        $data['user'] = $_SESSION['user'];
        $data['agent'] = $results->results[0];

        // display views
        $this->view('layouts/html_header', $data);
        $this->view('navbar', $data);
        $this->view('agents_edit_frm', $data);
        $this->view('footer');
        $this->view('layouts/html_footer');
    }

    // =======================================================
    public function edit_agent_submit()
    {
        if (!check_session() || $_SESSION['user']->profile != 'admin') {
            header('Location: index.php');
        }

        // check if post was maded 
        validate_post();

        if (empty($_POST['id'])) {
            header('Location: index.php');
        }

        $id = aes_decrypt($_POST['id']);
        if (!$id) {
            header('Location: index.php');
        }

        // form validation
        $validationError = null;

        if (empty($_POST['text_name']) || !filter_var($_POST['text_name'], FILTER_VALIDATE_EMAIL)) {
            $validationError = 'O nome do agente deve ser um email válido.';
        }

        $validProfiles = ['admin', 'agent'];
        if (empty($_POST['select_profile']) || !in_array($_POST['select_profile'], $validProfiles)) {
            $validationError = 'O perfil selecionado é inválido';
        }

        if (!empty($validationError)) {
            $_SESSION['validationError'] = $validationError;
            $this->edit_agent(aes_encrypt($id));
            return;
        }

        // check if there is already another agent with the same name 
        $model = new AdminModel();
        $results = $model->check_if_user_exists_with_same_name($id, $_POST['text_name']);

        if ($results) {
            // there is another agent with the same name
            $_SESSION['serverError'] = 'Já existe um agente com o mesmo nome';
            $this->edit_agent(aes_encrypt($id));
            return;
        }

        // now we can edit the agent in the database
        $results = $model->edit_agent($id, $_POST);

        if ($results->status == 'error') {
            // logger e
            logger(get_active_user_name(), " - Aconteceu um erro ao tentar editar os dados do agente com id - " . $id, 'error');
            header("Location: index.php");
        } else {
            // logger
            logger(get_active_user_name(), " - editado com sucesso os dados do agente com id- " . $id);
        }

        // go back to admin page 
        $this->agents_management();
    }

    // =======================================================
    public function edit_delete($id = '')
    {
        // check session
        if (!check_session() || $_SESSION['user']->profile != 'admin') {
            header('Location: index.php');
        }

        // check if id is valid
        $id = aes_decrypt($id);
        if (!$id) {
            header('Location: index.php');
        }

        // get agent data
        $model = new AdminModel();
        $results = $model->get_agent_data_and_clients($id);

        // display page of confirmation
        $data['user'] = $_SESSION['user'];
        $data['agent'] = $results->results[0];

        // display views
        $this->view('layouts/html_header', $data);
        $this->view('navbar', $data);
        $this->view('agents_delete_confirmation', $data);
        $this->view('footer');
        $this->view('layouts/html_footer');
    }

    // =======================================================
    public function delete_agent_confirm($id = '')
    {
        // check session
        if (!check_session() || $_SESSION['user']->profile != 'admin') {
            header('Location: index.php');
        }

        // check id 
        $id = aes_decrypt($id);
        if (!$id) {
            header('Location: index.php');
        }

        $model = new AdminModel();
        $results = $model->deleted_agent($id);

        if ($results->status == 'success') {
            // logger
            logger(get_active_user_name(), ' - Eliminado com sucesso o agente com ID: ' . $id);
        } else {
            logger(get_active_user_name(), ' - Aconteceu um erro ao eliminar o agente com ID: ' . $id, 'error');
        }

        // get back to the previous page
        $this->agents_management();
    }

    // =======================================================
    public function edit_recover($id = '')
    {
        // check session
        if (!check_session() || $_SESSION['user']->profile != 'admin') {
            header('Location: index.php');
        }

        // check id 
        $id = aes_decrypt($id);
        if (!$id) {
            header('Location: index.php');
        }

        // get agent data
        $model = new AdminModel();
        $results = $model->get_agent_data_and_clients($id);

        // display confirm page 
        $data['user'] = $_SESSION['user'];
        $data['agent'] = $results->results[0];

        // display the edit agent form 
        $this->view('layouts/html_header', $data);
        $this->view('navbar', $data);
        $this->view('agents_recover_confirmation', $data);
        $this->view('footer');
        $this->view('layouts/html_footer');
    }

    // =======================================================
    public function recover_agent_confirm($id = '')
    {

        // check session
        if (!check_session() || $_SESSION['user']->profile != 'admin') {
            header('Location: index.php');
        }

        // check id 
        $id = aes_decrypt($id);
        if (!$id) {
            header('Location: index.php');
        }

        // get agent data
        $model = new AdminModel();
        $results = $model->recover_agent($id);

        if ($results->status == 'success') {
            // logger 
            logger(get_active_user_name(), ' - Recuperção com sucesso do id: ' . $id);
        } else {
            // logger 
            logger(get_active_user_name(), ' - Erro na tentativa de recuperação do id: ' . $id, 'error');
        }

        $this->agents_management();
    }

    // =======================================================
    public function export_agents_xslx()
    {
        if (!check_session() || $_SESSION['user']->profile !== 'admin') {
            header('Location: index.php');
        }

        $model = new AdminModel();
        $results = $model->get_agent_data_and_total_clients();
        $results = $results->results;

        // get all agent clients 
        $data[] = ['name', 'profile', 'active', 'last_login', 'created_at', 'updated_at', 'deleted_at','total_active_clients','total_inactive_clients'];

        // place all clients in the $data collection
        foreach ($results as $client) {
            // remove id from array 
            unset($client->id);

            // transform the client obj in array 
            $data[] = (array)$client;
        }

        // store into the xlsx 
        $username = explode('@', $_SESSION['user']->name);
        $filename = "dump_agents" . $username[0] . '_' . time() . '.xlsx';
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
}
