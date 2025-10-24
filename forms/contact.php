<?php
/**
 * Contact form handler
 * - Uses BootstrapMade PHP Email Form library if present
 * - Falls back to a safe native mail() implementation otherwise
 */

// Replace contact@example.com with your real receiving email address
$receiving_email_address = 'contact@example.com';

$php_email_form_path = '../assets/vendor/php-email-form/php-email-form.php';
$use_library = false;
if (file_exists($php_email_form_path)) {
  include $php_email_form_path;
  $use_library = class_exists('PHP_Email_Form');
}

// Common input helpers
function cf_safe_input($value) {
  $value = trim((string)($value ?? ''));
  // Prevent header injection via CRLF
  return str_replace(["\r", "\n"], ' ', $value);
}

function cf_safe_email($email) {
  $sanitized = filter_var((string)($email ?? ''), FILTER_SANITIZE_EMAIL);
  return filter_var($sanitized, FILTER_VALIDATE_EMAIL) ? $sanitized : '';
}

function cf_has_header_injection($value) {
  return (bool)preg_match('/[\r\n]|content-type:|bcc:|cc:/i', (string)$value);
}

$name    = cf_safe_input($_POST['name']    ?? '');
$email   = cf_safe_email($_POST['email']   ?? '');
$subject = cf_safe_input($_POST['subject'] ?? 'Website contact form');
$message = trim((string)($_POST['message'] ?? ''));

// Basic validation
if ($email === '' || $message === '' || cf_has_header_injection($name) || cf_has_header_injection($subject)) {
  http_response_code(400);
  echo 'Invalid input';
  exit;
}

if ($use_library) {
  // Use the library when available
  $contact = new PHP_Email_Form();
  $contact->ajax = true;
  $contact->to = $receiving_email_address;
  $contact->from_name = $name;
  $contact->from_email = $email;
  $contact->subject = $subject;

  // Uncomment below code if you want to use SMTP to send emails. You need to enter your correct SMTP credentials
  /*
  $contact->smtp = array(
    'host' => 'example.com',
    'username' => 'example',
    'password' => 'pass',
    'port' => '587'
  );
  */

  $contact->add_message($name, 'From');
  $contact->add_message($email, 'Email');
  $contact->add_message($message, 'Message', 10);

  echo $contact->send();
  exit;
}

// Fallback: safe native mail()
$headers = [];
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-Type: text/plain; charset=UTF-8';
$headers[] = 'X-Requested-With: XMLHttpRequest';
if ($email) {
  $headers[] = 'Reply-To: ' . $email;
}
$from = $email ? ($name !== '' ? ("$name <{$email}>") : $email) : ('no-reply@' . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
$headers[] = 'From: ' . $from;

$body  = "From: {$name}\n";
$body .= "Email: {$email}\n\n";
$body .= "Message:\n{$message}\n";

$sent = @mail($receiving_email_address, $subject, $body, implode("\r\n", $headers));
if ($sent) {
  echo 'OK';
} else {
  http_response_code(500);
  echo 'Failed to send email';
}
?>
