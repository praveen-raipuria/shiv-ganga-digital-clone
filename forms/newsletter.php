<?php
/**
 * Newsletter subscription handler
 * - Validates email
 * - Prevents header injection
 * - Responds with 'OK' on success for frontend compatibility
 */

function nl_safe_email($email) {
  $sanitized = filter_var((string)($email ?? ''), FILTER_SANITIZE_EMAIL);
  return filter_var($sanitized, FILTER_VALIDATE_EMAIL) ? $sanitized : '';
}

function nl_has_header_injection($value) {
  return (bool)preg_match('/[\r\n]|content-type:|bcc:|cc:/i', (string)$value);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo 'Method Not Allowed';
  exit;
}

$email = nl_safe_email($_POST['email'] ?? '');
if ($email === '' || nl_has_header_injection($email)) {
  http_response_code(400);
  echo 'Invalid email';
  exit;
}

// Optionally send an email to site owner; skipped if mail() is unavailable
$to = 'contact@example.com';
$subject = 'New newsletter subscription';
$body = "Email: {$email}\n";
$headers = [
  'MIME-Version: 1.0',
  'Content-Type: text/plain; charset=UTF-8',
  'X-Requested-With: XMLHttpRequest',
  'From: no-reply@' . ($_SERVER['SERVER_NAME'] ?? 'localhost')
];

if (function_exists('mail')) {
  @mail($to, $subject, $body, implode("\r\n", $headers));
}

echo 'OK';
