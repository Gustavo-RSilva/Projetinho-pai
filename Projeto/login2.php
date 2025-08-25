<?php
// Configurações
$client_id = "642053605341-651u695o9r1jur0tj65kdc2dvgdvs4pk.apps.googleusercontent.com";
$redirect_uri = "http://localhost/TIAM24/Projetinho-pai/Projeto/google_callback.php";
$scope = "openid email profile"; // Dados que você quer acessar

// URL de autorização
$auth_url = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
    "client_id" => $client_id,
    "redirect_uri" => $redirect_uri,
    "response_type" => "code",
    "scope" => $scope,
    "access_type" => "offline",
    "prompt" => "consent"
]);

// Redireciona o usuário para o Google
header("Location: " . $auth_url);
exit;
