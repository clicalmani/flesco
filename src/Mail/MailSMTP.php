<?php
namespace Clicalmani\Flesco\Mail;

class MailSMTP 
{
    private $WordWrap;
    
    function __construct(private $mail = null)
    {
        $this->mail             = new \PHPMailer\PHPMailer\PHPMailer;
        
        $this->mail->CharSet    = env('MAIL_CHARSET', 'UTF-8');
        $this->mail->Encoding   = env('MAIL_ENCODING', 'base64');
        $this->mail->Host       = env('MAIL_HOST', 'localhost');
        $this->mail->Username   = env('MAIL_USERNAME', 'user');
        $this->mail->Password   = env('MAIL_PASSWORD', '');
        $this->mail->SMTPSecure = env('MAIL_ENCRYPTION', 'ssl');
        $this->mail->Port       = env('MAIL_PORT', '465'); 

        $this->mail->isSMTP(true);

        $this->mail->SMTPAuth   = true;
        $this->WordWrap         = 50;
    }

    function setBody($body)
    {
        $this->mail->Body = $body;
    }

    function setSubject($subject)
    {
        $this->mail->Subject = $subject;
    }

    function setWordWrap($WordWrap)
    {
        $this->mail->WordWrap = $WordWrap;
    }

    function __call($method, $args)
    {
        $mailer_methods = [
            'setFrom',
            'addAddress',
            'addCC',
            'addBC',
            'isHTML',
            'send'
        ];

        if ( in_array($method, $mailer_methods) ) {
            return $this->mail->{$method}(...$args);
        } else throw new \Clicalmani\Flesco\Exceptions\MailException("Unsupported method $method has been called on " . static::class);
    }
}
