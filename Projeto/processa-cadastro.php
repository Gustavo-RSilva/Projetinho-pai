<?php
session_start();

include_once("db/conexao.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = mysqli_real_escape_string($conn, trim($_POST['nome_completo']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $senha = $_POST['senha'];
    $confirma_senha = $_POST['confirma_senha'];

    if ($senha !== $confirma_senha) {
        die("As senhas não coincidem.");
    }

    // Hash seguro da senha
    $hash = password_hash($senha, PASSWORD_DEFAULT);

    // Verifica se o e-mail já existe
    $verifica = mysqli_query($conn, "SELECT id_usuario FROM usuarios WHERE email = '$email'");
    if (mysqli_num_rows($verifica) > 0) {
        die("E-mail já cadastrado.");
    }

    // Insere o usuário
    $sql = "INSERT INTO usuarios (nome_completo, email, senha) VALUES ('$nome', '$email', '$hash')";
    if (mysqli_query($conn, $sql)) {
        session_start();
        $_SESSION['id_usuario'] = mysqli_insert_id($conn);
        header("Location: upload-foto.php");
        exit;
    } else {
        die("Erro: " . mysqli_error($conn));
    }
}
