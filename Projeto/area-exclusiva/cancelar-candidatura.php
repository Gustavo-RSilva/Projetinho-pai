<?php
session_start();
require_once("../db/conexao.php");

// Verifica se o usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../Login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_candidatura'])) {
    $id_candidatura = intval($_POST['id_candidatura']);
    $id_usuario = $_SESSION['id_usuario'];

    // Atualiza a candidatura para "Cancelado" (somente do usuário logado)
    $sql = "UPDATE candidaturas 
            SET status = 'Cancelado' 
            WHERE id_candidatura = ? AND id_usuario = ? AND status = 'Em análise'";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_candidatura, $id_usuario);

    if ($stmt->execute()) {
        $_SESSION['msg'] = "<div class='alert alert-success text-center'>✅ Candidatura cancelada com sucesso!</div>";
    } else {
        $_SESSION['msg'] = "<div class='alert alert-danger text-center'>❌ Erro ao cancelar a candidatura. Tente novamente.</div>";
    }

    $stmt->close();
} else {
    $_SESSION['msg'] = "<div class='alert alert-warning text-center'>⚠ Nenhuma candidatura selecionada.</div>";
}

$conn->close();

// Redireciona de volta para a página de candidaturas
header("Location: pag-candidaturas.php");
exit();
