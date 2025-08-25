<?php
require_once 'vendor/autoload.php';

$client = new Google_Client();  
$client->setClientId('642053605341-651u695o9r1jur0tj65kdc2dvgdvs4pk.apps.googleusercontent.com');
$client->setClientSecret('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'  );
// A url de redirecionamento deve ser a URL do seu script de callback, seria o caminho da pasta.
$client->setRedirectUri('http://localhost/TIAM24/Projetinho-pai/Projeto/google_callback.php');
$client->addScope("email");
$client->addScope("profile");