<?php
// Configurações do banco de dados
$host = 'localhost';       // ou IP do servidor MySQL
$usuario = 'root';  // substitua pelo seu usuário
$senha = '';      // substitua pela sua senha
$banco = 'contrata';  // substitua pelo nome do seu banco
// Criar a conexão
$conn = new mysqli($host, $usuario, $senha, $banco);
$conn->set_charset('utf8mb4');
// Verificar se houve erro na conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}
?>
