<?php
session_start();
require_once 'db/conexao.php';
header("Content-Type: application/json");      
// Verifica o token no Google
$clientId = '642053605341-651u695o9r1jur0tj65kdc2dvgdvs4pk.apps.googleusercontent.com'; // Substitua pelo seu CLIENT_ID

$token = $_POST['credential'] ?? '';
if (!$token) {
    echo json_encode(['success' => false, 'error' => 'Token não enviado.']);
    exit;
}

// Valida token no Google
$googleApiUrl = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $token;
$response = file_get_contents($googleApiUrl);
$data = json_decode($response, true);

// Verifica se o token é válido
if (isset($data['aud']) && $data['aud'] === $clientId) {
    // Dados do usuário
    $nome   = $data['name'] ?? '';
    $email  = $data['email'] ?? '';
    $foto   = $data['picture'] ?? '';

    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'error' => 'Erro de conexão: ' . $conn->connect_error]);
        exit;
    }

    try {
        // Insert com ON DUPLICATE KEY (atualiza caso já exista o email)
        $stmt = $conn->prepare("
            INSERT INTO usuarios (nome_completo, email, foto_perfil) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
                nome_completo = VALUES(nome_completo), 
                foto_perfil = VALUES(foto_perfil)
        ");
        $stmt->bind_param("sss", $nome, $email, $foto);
        $stmt->execute();

        if ($stmt->error) {
            throw new Exception("Erro ao inserir/atualizar usuário: " . $stmt->error);
        }

        // Pega ID do usuário
        $stmt = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $linha = $result->fetch_object();
            $_SESSION["id_usuario"]   = $linha->id_usuario;
            $_SESSION["nome_completo"] = $nome;
            $_SESSION["email"]        = $email;
            $_SESSION["foto_perfil"]  = $foto;
        }

        // Redireciona para área protegida
        header("Location: ./area-protec.php");
        exit;

    } catch (Exception $e) {
        echo "Erro durante o login com o Google: " . $e->getMessage();
    }

} else {
    echo "Token inválido ou não corresponde ao Client ID.";
}



// Verifica se o token é válido e emitido para seu Client ID
// if (isset($data['aud']) && $data['aud'] === $clientId) {
//     /*
//     echo json_encode([
//         'success' => true,
//         'name' => $data['name'],
//         'email' => $data['email'],
//         'picture' => $data['picture']
//     ]);
//     */
//     echo json_encode([
//         'success' => true,
//         'id' => $codigo,
//         'name' => $data['name'],
//         'email' => $data['email'],
//         'picture' => $data['picture']
//     ]);
// } else {
//     echo json_encode(['success' => false, 'error' => 'Token inválido.']);
// }
