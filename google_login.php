<?php
require_once 'config.php';

// Generate state token for CSRF protection
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['google_oauth_state'] = bin2hex(random_bytes(16));

$params = [
    'client_id'     => GOOGLE_CLIENT_ID,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope'         => 'email profile',
    'access_type'   => 'online',
    'state'         => $_SESSION['google_oauth_state'],
    'prompt'        => 'select_account'
];

$loginUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);

// Redirect user to Google
header('Location: ' . $loginUrl);
exit;
