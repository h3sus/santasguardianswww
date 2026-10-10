<?php
declare(strict_types=1);

/**
 * Contact form handler for santasguardians.com
 *
 * The form on index.html posts here. On success the visitor lands on
 * thanks.html; on a problem they get a short page with the error and a way
 * back. Nothing is stored on the server, the message is only emailed.
 *
 * SETTINGS: edit the five values below. $FROM must be a real mailbox on this
 * domain, otherwise the hosting company's mail server may refuse to send.
 * Right now both $TO and $FROM are info@, so the site emails itself and the
 * visitor's address is only used as Reply-To.
 */

$TO         = 'info@santasguardians.com';
$FROM       = 'info@santasguardians.com';
$FROM_NAME  = "Santa's Guardians website";
$SUBJECT    = 'New message from santasguardians.com';
$THANKS_URL = 'thanks.html';

// Set to true if you want the sender's IP address in the email. Useful when
// fighting spam, but it is personal data, so it is off by default.
$INCLUDE_IP = false;

/** Strip anything that could inject extra email headers. */
function header_safe(string $value): string
{
    return trim(str_replace(["\r", "\n", "\t", "%0a", "%0d"], ' ', $value));
}

/** Show a short error page in the site's style and stop. */
function show_error(string $heading, string $detail, int $status): void
{
    http_response_code($status);
    header('Content-Type: text/html; charset=UTF-8');
    $heading = htmlspecialchars($heading, ENT_QUOTES, 'UTF-8');
    $detail  = htmlspecialchars($detail, ENT_QUOTES, 'UTF-8');
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message not sent | Santa's Guardians</title>
    <meta name="robots" content="noindex">
    <link rel="icon" type="image/png" href="images/website_favicon.png">
    <link rel="stylesheet" href="styles.css?v=20260916">
</head>
<body>
    <section class="form-result">
        <div class="container form-result-inner">
            <img src="images/site-logo.png" alt="" width="1024" height="1024" class="form-result-logo">
            <h1>$heading</h1>
            <p>$detail</p>
            <p>You can also email us directly at
                <a href="mailto:info@santasguardians.com">info@santasguardians.com</a>.</p>
            <a class="btn btn-book" href="index.html#contact">Back to the form</a>
        </div>
    </section>
</body>
</html>
HTML;
    exit;
}

// Only POST is a real submission. Anything else goes back to the form.
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: index.html#contact', true, 303);
    exit;
}

// Spam trap: the field is hidden, so only bots fill it. Pretend all is well.
if (!empty($_POST['_gotcha'])) {
    header('Location: ' . $THANKS_URL, true, 303);
    exit;
}

$email   = header_safe((string) ($_POST['email'] ?? ''));
$phone   = header_safe((string) ($_POST['phone'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    show_error(
        'We need a working email address',
        'The email address looked wrong, so we could not send your message. Go back and check it.',
        400
    );
}

if ($message === '') {
    show_error(
        'The message was empty',
        'Go back and write us a line about your group, your dates or your question.',
        400
    );
}

if (mb_strlen($message) > 5000) {
    $message = mb_substr($message, 0, 5000) . "\n\n[message truncated at 5000 characters]";
}

$lines = [
    'Email:   ' . $email,
    'Phone:   ' . ($phone !== '' ? $phone : '-'),
    'Sent:    ' . date('Y-m-d H:i:s'),
];

if ($INCLUDE_IP) {
    $lines[] = 'IP:      ' . ($_SERVER['REMOTE_ADDR'] ?? '-');
}

$body = implode("\n", $lines) . "\n\n" . str_repeat('-', 40) . "\n\n" . $message . "\n";

$headers = [
    'From: ' . mb_encode_mimeheader($FROM_NAME, 'UTF-8') . ' <' . $FROM . '>',
    'Reply-To: ' . $email,
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'Content-Transfer-Encoding: 8bit',
];

$subject = mb_encode_mimeheader($SUBJECT, 'UTF-8');

// The -f switch sets the envelope sender, which keeps SPF checks happy.
$sent = @mail($TO, $subject, $body, implode("\r\n", $headers), '-f' . $FROM);

if (!$sent) {
    show_error(
        'Something went wrong at our end',
        'Your message did not go through. Please try again in a moment.',
        500
    );
}

header('Location: ' . $THANKS_URL, true, 303);
exit;
