<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

function EmailPDFAttachment($myemail_to, $myemail_from, $fileattached_pdf, $myemail_subject, $myemail_message, $name_of_file, $do_not_attach, $CCto) {
    // email stuff (change data below)
    $to = $myemail_to;
    $from = $myemail_from;
    $subject = $myemail_subject;
    $message = $myemail_message;

    // a random hash will be necessary to send mixed content
    $separator = md5(time());

    // carriage return type (we use a PHP end of line constant)
    $eol = PHP_EOL;

    // attachment name
    $attachment = chunk_split(base64_encode($fileattached_pdf));

    // main header
    $headers = 'From: ' . $from . $eol;
    if ($CCto != '') {
        $headers = 'Cc: ' . $CCto . $eol;
    }
    $headers .= 'MIME-Version: 1.0' . $eol;
    $headers .= 'Content-Type: multipart/alternative; boundary="' . $separator . '"';

    // no more headers after this, we start the body! //

    $body = '--' . $separator . $eol;
    $body .= 'Content-Transfer-Encoding: 7bit' . $eol . $eol;
    $body .= 'This is a MIME encoded message.' . $eol;

    // message
    $body .= '--' . $separator . $eol;
    $body .= 'Content-Type: text/html; charset="iso-8859-1"' . $eol;
    $body .= 'Content-Transfer-Encoding: 8bit' . $eol . $eol;
    $body .= $message . $eol;

    if ($do_not_attach == 1) {
        // do not attach!
    } else {
        // attachment
        $body .= '--' . $separator . $eol;
        $body .= 'Content-Type: application/octet-stream; name="' . $name_of_file . '"' . $eol;
        $body .= 'Content-Transfer-Encoding: base64' . $eol;
        $body .= 'Content-Disposition: attachment' . $eol . $eol;
        $body .= $attachment . $eol;
        $body .= '--' . $separator . '--';
    }
    // send message
    /*
    print_r3("to",$to);
    print_r3("subject",$subject);
    print_r3("headers",$headers);
    print_r3("body",$body);
    */
    error_log(var_export([$to,$subject,$headers],true));
    $ok = @mail($to, $subject, $body, $headers);

    return $ok;
}// EmailPDFAttachment


// Split an address string as used by the app into [email, name].
// Accepts "a@b.com", "Name <a@b.com>" and "a@b.com (Name)".
function EmailSplitAddress($address) {
    $address = trim($address);
    if (preg_match('/^(.*)<([^>]+)>$/', $address, $m)) {
        return [trim($m[2]), trim($m[1], " \"'")];
    }
    if (preg_match('/^(\S+)\s*\((.*)\)$/', $address, $m)) {
        return [trim($m[1]), trim($m[2])];
    }
    return [$address, ''];
}// EmailSplitAddress

// Same arguments and return value as EmailPDFAttachment(), but sends via PHPMailer
// using the MAIL_* settings in config_main.php.
// $myemail_to and $CCto may contain several addresses separated by commas or semicolons.
function EmailPDFAttachmentPHPMailer($myemail_to, $myemail_from, $fileattached_pdf, $myemail_subject, $myemail_message, $name_of_file, $do_not_attach, $CCto) {
    $mail = new PHPMailer(true); // true = throw exceptions on errors

    try {
        // transport
        $transport = defined('MAIL_TRANSPORT') ? MAIL_TRANSPORT : 'mail';
        if ($transport == 'smtp') {
            $mail->isSMTP();
            $mail->Host = MAIL_SMTP_HOST;
            $mail->Port = MAIL_SMTP_PORT;
            $mail->Timeout = MAIL_SMTP_TIMEOUT;
            $mail->SMTPAuth = MAIL_SMTP_AUTH;
            if (MAIL_SMTP_AUTH) {
                $mail->Username = MAIL_SMTP_USER;
                $mail->Password = MAIL_SMTP_PASS;
            }
            if (MAIL_SMTP_SECURE == 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif (MAIL_SMTP_SECURE == 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = '';
                $mail->SMTPAutoTLS = false;
            }
            $mail->SMTPDebug = MAIL_SMTP_DEBUG;
            $mail->Debugoutput = 'error_log';
        } elseif ($transport == 'sendmail') {
            $mail->isSendmail();
        } else {
            $mail->isMail();
        }
        $mail->CharSet = PHPMailer::CHARSET_UTF8;

        // sender: if a fixed From address is configured use it and make the app's sender the Reply-To
        list($from_email, $from_name) = EmailSplitAddress($myemail_from);
        $fixed_from = defined('MAIL_FROM_ADDRESS') ? MAIL_FROM_ADDRESS : '';
        if ($fixed_from != '') {
            $fixed_name = $from_name != '' ? $from_name : (defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : '');
            $mail->setFrom($fixed_from, $fixed_name);
            if ($from_email != '' && strcasecmp($from_email, $fixed_from) != 0) {
                $mail->addReplyTo($from_email, $from_name);
            }
        } else {
            $mail->setFrom($from_email, $from_name);
        }

        // recipients
        foreach (preg_split('/[,;]/', $myemail_to, -1, PREG_SPLIT_NO_EMPTY) as $address) {
            list($email, $name) = EmailSplitAddress($address);
            $mail->addAddress($email, $name);
        }
        foreach (preg_split('/[,;]/', (string) $CCto, -1, PREG_SPLIT_NO_EMPTY) as $address) {
            list($email, $name) = EmailSplitAddress($address);
            $mail->addCC($email, $name);
        }

        // message
        $mail->isHTML(true);
        $mail->Subject = $myemail_subject;
        $mail->Body = $myemail_message;
        $mail->AltBody = html_entity_decode(strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $myemail_message)), ENT_QUOTES, 'UTF-8');

        // attachment (the PDF is passed in as a string, not a file)
        if ($do_not_attach != 1) {
            $mail->addStringAttachment($fileattached_pdf, $name_of_file, PHPMailer::ENCODING_BASE64, 'application/pdf');
        }

        $mail->send();
        //echo("Sent to : $myemail_to<BR>");
        return true;
    } catch (PHPMailerException $e) {
        $msg ='EmailPDFAttachmentPHPMailer failed sending "' . $myemail_subject . '" to ' . $myemail_to . ': ' . $mail->ErrorInfo ;
        $msg .= " Exception: " . $e->getMessage() ;
        echo($msg);
        error_log($msg);
        //error_log('EmailPDFAttachmentPHPMailer failed sending "' . $myemail_subject . '" to ' . $myemail_to . ': ' . $mail->ErrorInfo);
        return false;
    }
}// EmailPDFAttachmentPHPMailer
