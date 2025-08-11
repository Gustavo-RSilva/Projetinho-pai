<?php
require_once 'google-config.php';

$auth_url = $client->createAuthUrl();
print_r($auth_url);
header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
exit;