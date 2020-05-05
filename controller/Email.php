<?php

class Email {

  protected $ci;

  public function __construct($ci) {
    $this->ci = $ci;
  }

  public function sendMail($from, $to, $subject, $message, $cc=null, $bcc=null, $file=[], $create_id=null) 
  {

    // require_once '../vendor/phpmailer/phpmailer/PHPMailerAutoload.php';
    $mail = $this->ci->phpMailer;
    
    $mail->IsSMTP();
    $mail->Host     = 'gw.parkingcloud.co.kr'; /*SMTP host*/
    $mail->SMTPAuth = true;
    $mail->Debugoutput = 'html';
    $mail->Username = 'iparking@parkingcloud.co.kr';
    $mail->Password = 'qhdksdjatn1!';
    // $mail->SMTPDebug = 2;
    $mail->CharSet = "UTF-8";
    $mail->Encoding = "base64";
    // $mail->SMTPSecure = 'tls';
    $mail->Port = 25;
    $mail->SMTPOptions = array(
                          'ssl' => array(
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true
                          )
                        ); /*Skip SSL Errors(if any),generally not needed*/
    
    $mail->SetFrom('iparking@parkingcloud.co.kr', '파킹클라우드');/*Email content */
    //$mail->AddReplyTo("ywkim@parkingcloud.co.kr","KimYoungWoon");
    $mail->Subject    = $subject;
    //$mail->AltBody    = "한글테스트";
    if($file) {
      foreach($file as $row) {
        if($row['type'] == 'application/pdf') {
          $mail->AddStringAttachment($row['file'], $row['fileName'], 'base64', $row['type']);
        } else {
          $mail->AddStringAttachment($row['file'], $row['fileName']);
        }
      }
    }
    $mail->isHTML(true);
    $mail->MsgHTML($message);
    $mail->AddAddress($to);
    if(!$mail->Send()) {/*Send Email*/
        $send_code = 400;
        $errorMessage = $mail->ErrorInfo;
    } else {
        $send_code = 200;
        $errorMessage = null;       
    }  

    return [$send_code, $errorMessage];

  }
}