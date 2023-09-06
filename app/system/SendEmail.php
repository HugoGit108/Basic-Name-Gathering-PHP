<?php 

namespace bng\System;

use ArrayAccess;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class SendEmail {
    
    
    #=================================================
    public function send_email($subject,$body,$data)
    {
        // send an email locally using laragon
        $mail = new PHPMailer();

        try {
            $mail->setFrom(EMAIL_FROM);
            $mail->addAddress($data['to']);

            $mail->isHTML(true);
            $mail->CharSet = "UTF-8";
            $mail->Subject = $subject;
            $mail->Body = $this->$body($data);

            $mail->send();

            return [
                'status' => 'success'
            ];
        } catch (Exception $e){
            // error here
            return [
                'status' => 'error',
                'message' => $mail->ErrorInfo
            ];
        }
    }

    #=================================================
    private function email_body_new_agent ($data) {
        $html = '<p>Para concluir o processo de registo,clique no link abaixo</p>';
        $html .= '<a href="'. $data['link'] . '">' . 'Concluir registo do cliente</a>';
        return $html;
    }

    #=================================================
    private function código_recuperar_password($data) {
        $html = "<p>Para definir a sua password, use o seguinte código:</p>";
        $html .= "<h3>{$data['code']}</h3>";
        return $html;
    }
}