<?php
// Configurações do banco de dados
$host = 'contrata.mysql.dbaas.com.br';       // ou IP do servidor MySQL
$usuario = 'contrata';  // substitua pelo seu usuário
$senha = 'Senh@projeto20';      // substitua pela sua senha
$banco = 'contrata';  // substitua pelo nome do seu banco
// Criar a conexão
$conn = new mysqli($host, $usuario, $senha, $banco);
$conn->set_charset('utf8mb4');
// Verificar se houve erro na conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}
?>
