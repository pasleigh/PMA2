<?php
function EmailBodyString($myemail_to, $myemail_from, $myemail_subject, $myemail_message)
{
   // email stuff (change data below)
   $to = $myemail_to; 
   $from = $myemail_from; 
   //echo "From: $from <BR>";
   //echo "MyEmailFrom: $my_email_from <BR>";
   $subject =  $myemail_subject; 
   $message = $myemail_message;
   
   // a random hash will be necessary to send mixed content
   $separator = md5(time());
   
   // carriage return type (we use a PHP end of line constant)
   $eol = PHP_EOL;
   
   // main header
   $headers  = "From: ".$from.$eol;
   $headers .= "MIME-Version: 1.0".$eol; 
   $headers .= "Content-Type: multipart/alternative; boundary=\"".$separator."\"";
   
   // no more headers after this, we start the body! //
   
   $body = "--".$separator.$eol;
   $body .= "Content-Transfer-Encoding: 7bit".$eol.$eol;
   $body .= "This is a MIME encoded message.".$eol;
   
   // message
   $body .= "--".$separator.$eol;
   $body .= "Content-Type: text/html; charset=\"iso-8859-1\"".$eol;
   $body .= "Content-Transfer-Encoding: 8bit".$eol.$eol;
   $body .= $message.$eol;
   
   // send message
   $ok = @mail($to, $subject, $body, $headers);

   return $ok;

}// EmailBodyString

?>