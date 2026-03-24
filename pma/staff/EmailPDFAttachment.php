<?php
function EmailPDFAttachment($myemail_to, $myemail_from, $fileattached_pdf, $myemail_subject, $myemail_message, $name_of_file, $do_not_attach, $CCto)
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
   
   // attachment name
   $attachment = chunk_split(base64_encode($fileattached_pdf));


   // main header
   $headers  = "From: ".$from.$eol;
   if($CCto != "" ) {
       $headers = "Cc: " . $CCto . $eol;
   }
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
   
   if($do_not_attach == 1){
     // do not attach!
   }else{
     // attachment
     $body .= "--".$separator.$eol;
     $body .= "Content-Type: application/octet-stream; name=\"".$name_of_file."\"".$eol; 
     $body .= "Content-Transfer-Encoding: base64".$eol;
     $body .= "Content-Disposition: attachment".$eol.$eol;
     //echo ("To:$to<BR>subject:$subject<BR> header=$headers <BR> body=$body <br />");
     $body .= $attachment.$eol;
     $body .= "--".$separator."--";
   }
   // send message
    /*
    print_r3("to",$to);
    print_r3("subject",$subject);
    print_r3("headers",$headers);
    print_r3("body",$body);
    */
   $ok = @mail($to, $subject, $body, $headers);

   return $ok;

}// EmailPDFAttachment

?>