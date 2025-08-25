<?php
session_start();
// Recebe os dados JSON
$input = json_decode(file_get_contents('php://input'), true);
$token = $input['credential'] ?? '';

// Verifica o token no Google
$clientId = '642053605341-651u695o9r1jur0tj65kdc2dvgdvs4pk.apps.googleusercontent.com'; // Substitua pelo seu CLIENT_ID

if (!$token) {
    echo json_encode(['success' => false, 'error' => 'Token não enviado.']);
    exit;
}

// Faz requisição para o endpoint de validação do Google
$googleApiUrl = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $token;
$response = file_get_contents($googleApiUrl);
$data = json_decode($response, true);

// Verifica se o token é válido e emitido para seu Client ID
if (isset($data['aud']) && $data['aud'] === $clientId) {
    /*
    echo json_encode([
        'success' => true,
        'name' => $data['name'],
        'email' => $data['email'],
        'picture' => $data['picture']
    ]);
    */
    echo json_encode([
        'success' => true,
        'id' => $codigo,
        'name' => $data['name'],
        'email' => $data['email'],
        'picture' => $data['picture']
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Token inválido.']);
}
