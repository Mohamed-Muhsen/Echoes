<?php
// ------------ SETTINGS ------------
$to        = 'hello@echoes.travel';     // البريد المستلم
$fromEmail = 'hello@echoes.travel';     // يُفضَّل يكون من نفس الدومين
$fromName  = 'Website Contact';          // اسم المرسِل الظاهر
$subjectTag = 'New Contact Message';     // وسم في العنوان
// -----------------------------------

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405); echo 'Method Not Allowed'; exit;
}

// honeypot
if (!empty($_POST['website'])) { http_response_code(400); echo 'Bad Request'; exit; }

// read + sanitize
$full   = trim($_POST['full_name'] ?? '');
$email  = trim($_POST['email'] ?? '');
$subjIn = trim($_POST['subject'] ?? '');
$msg    = trim($_POST['message'] ?? '');

$full   = str_replace(["\r","\n"], ' ', $full);
$subjIn = str_replace(["\r","\n"], ' ', $subjIn);

$errors = [];
if ($full === '') $errors[] = 'Full name is required';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
if ($subjIn === '') $errors[] = 'Subject is required';
if ($msg === '') $errors[] = 'Message is required';
if (strlen($msg) > 5000) $errors[] = 'Message too long';

if ($errors) {
  http_response_code(422);
  echo '<h3>There were errors:</h3><ul><li>'.implode('</li><li>', array_map('htmlspecialchars',$errors)).'</li></ul>';
  exit;
}

// compose
$subject = $subjectTag . ' – ' . $subjIn;
$body = "New contact message:\r\n\r\n"
      . "Name:  {$full}\r\n"
      . "Email: {$email}\r\n"
      . "Subject: {$subjIn}\r\n"
      . "--------\r\n"
      . "Message:\r\n{$msg}\r\n";

// headers
$headers  = "From: {$fromName} <{$fromEmail}>\r\n";
$headers .= "Reply-To: {$email}\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// envelope from
$envelopeParam = "-f{$fromEmail}";

// send
$sent = @mail($to, $subject, $body, $headers, $envelopeParam);

if ($sent) {
  http_response_code(200); echo 'OK';
} else {
  http_response_code(500); echo 'Mail function failed';
}