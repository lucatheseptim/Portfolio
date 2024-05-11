<?php





require_once 'include/connector.php';



class ConfirmInvioMail extends InvioMail
{

    public function __construct($subject)
    {
        parent::__construct($subject);
    }

    public function send()
    {
        //echo("send");
        //print_r($this->mail);
        $this->mail->IsHTML(true);
        if (!$this->mail->Send()) {
            error_log("Mailer Error: " . $this->mail->ErrorInfo);
            return 0;
        } else {
            error_log("Message " . $this->mail->Subject . " sent!");
            return 1;
        }

    }
}



function sendMail($subject, $body, $email, $files = NULL)
{
    $mail = new ConfirmInvioMail($subject);
    $mail->setHost("fast.smtpok.com");
    $mail->setUserPassword("s9091_2", "EuifU?07dZ");
    $mail->setPort(25);
    $mail->setSMTPSecure("none");
    $mail->setBody($body);
    $mail->A($email);
    if ($files) {
        if (is_array($files)) {
            foreach ($files as $file) {
                $mail->allegato($file);
            }
        } else {
            //$mail->allegato($file);
        }
    }
    $mail->CCN(array("psalemi@feniciaspa.it"));
    return $mail->send();
}


echo sendMail("Quantità minima raggiunta","quantita minima raggiunta per il pezzo",array("psalemi@feniciaspa.it"));

?>


