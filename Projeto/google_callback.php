<?php
session_start();

require_once 'google-config.php';
require_once 'db/conexao.php'; // Certifique-se de que o caminho esteja correto

if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

if (isset($_GET['code'])) {
    try {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

        if (isset($token['error'])) {
            throw new Exception("Erro ao obter token do Google: " . $token['error']);
        }

        $client->setAccessToken($token['access_token']);

        $oauth = new Google_Service_Oauth2($client);
        $googleUser = $oauth->userinfo->get();

        $nome = $googleUser->name;
        $email = $googleUser->email;
        $foto = $googleUser->picture;

        // Verifica se usuário existe
        $stmt = $conn->prepare("INSERT INTO usuarios (nome_completo, email, foto_perfil) VALUES (?, ?, ?) 
    ON DUPLICATE KEY UPDATE nome_completo = VALUES(nome_completo), foto_perfil = VALUES(foto_perfil)");
        $stmt->bind_param("sss", $nome, $email, $foto);
        $stmt->execute();
        if ($stmt->error) {
            throw new Exception("Erro ao inserir/atualizar usuário: " . $stmt->error);
        }
        // Corrigido: $_SESSION['nome_completo'] estava usando variável não definida
        $_SESSION['nome_completo'] = $nome;
        $_SESSION['email'] = $email;
        $_SESSION['foto_perfil'] = $foto;

        header("Location: area-protec.php");
        exit;
    } catch (Exception $e) {
        echo "Erro durante o login com o Google: " . $e->getMessage();
    }
} else {
    echo "Código de autorização do Google não recebido.";
}
