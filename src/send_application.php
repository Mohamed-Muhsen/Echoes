<?php
$to = 'hiring@echoes.travel'; 
$fromEmail = 'hiring@echoes.travel'; 
$fromName  = 'New Job Application';        
$maxSizeMB = 5;
$allowedExts  = ['pdf','doc','docx'];
$allowedMime  = [
  'application/pdf',
  'application/msword',
  'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
];
// -----------------------------------------------

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo 'Method Not Allowed';
  exit;
}

if (!empty($_POST['website'])) {
  http_response_code(400);
  echo 'Bad Request';
  exit;
}

$first = trim($_POST['first_name'] ?? '');
$last  = trim($_POST['last_name']  ?? '');
$email = trim($_POST['email']      ?? '');
$phone = trim($_POST['phone']      ?? '');
$about = trim($_POST['about']      ?? '');

$errors = [];

if ($first === '') $errors[] = 'First name is required';
if ($last  === '') $errors[] = 'Last name is required';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
if ($phone === '') $errors[] = 'Phone is required';
if ($about === '') $errors[] = 'About is required';

if (!isset($_FILES['cv']) || $_FILES['cv']['error'] !== UPLOAD_ERR_OK) {
  $errors[] = 'CV file is required';
} else {
  $cv = $_FILES['cv'];

  $maxBytes = $maxSizeMB * 1024 * 1024;
  if ($cv['size'] <= 0 || $cv['size'] > $maxBytes) {
    $errors[] = 'CV must be less than ' . $maxSizeMB . 'MB';
  }

  $ext = strtolower(pathinfo($cv['name'], PATHINFO_EXTENSION));
  if (!in_array($ext, $allowedExts, true)) {
    $errors[] = 'Allowed CV types: pdf, doc, docx';
  }

  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime  = $finfo->file($cv['tmp_name']);
  if (!in_array($mime, $allowedMime, true)) {
    $errors[] = 'Invalid CV file type';
  }
}

if ($errors) {
  http_response_code(422);
  echo '<h3>There were errors:</h3><ul><li>' . implode('</li><li>', array_map('htmlspecialchars',$errors)) . '</li></ul>';
  echo '<p><a href="javascript:history.back()">Go back</a></p>';
  exit;
}

$fullName = $first . ' ' . $last;
$subject  = 'New Job Application – Senior Front-End: ' . $fullName;

$textBody = "New application details:\r\n\r\n"
          . "Name:  {$fullName}\r\n"
          . "Email: {$email}\r\n"
          . "Phone: {$phone}\r\n"
          . "--------\r\n"
          . "About:\r\n{$about}\r\n";

$cvContent = file_get_contents($_FILES['cv']['tmp_name']);
$cvBase64  = chunk_split(base64_encode($cvContent));
$safeName  = preg_replace('/[^A-Za-z0-9._-]/', '_', $_FILES['cv']['name']);

$boundary = '=_Boundary_' . md5(uniqid((string)mt_rand(), true));

$headers  = "From: {$fromName} <{$fromEmail}>\r\n";
$headers .= "Reply-To: {$email}\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";

$body  = "--{$boundary}\r\n";
$body .= "Content-Type: text/plain; charset=UTF-8\r\n";
$body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
$body .= $textBody . "\r\n";

$body .= "--{$boundary}\r\n";
$body .= "Content-Type: {$mime}; name=\"{$safeName}\"\r\n";
$body .= "Content-Transfer-Encoding: base64\r\n";
$body .= "Content-Disposition: attachment; filename=\"{$safeName}\"\r\n\r\n";
$body .= $cvBase64 . "\r\n";
$body .= "--{$boundary}--";

$envelopeParam = "-f{$fromEmail}";

$sent = @mail($to, $subject, $body, $headers, $envelopeParam);

if ($sent) {
  echo '<h2>Thank you!</h2><p>Your application has been sent successfully.</p>';
  echo '<p><a href="javascript:history.back()">Back</a></p>';
} else {
  http_response_code(500);
  echo '<h2>Sorry</h2><p>We could not send your application at the moment. Please try again later.</p>';
  echo '<p><a href="javascript:history.back()">Back</a></p>';
}