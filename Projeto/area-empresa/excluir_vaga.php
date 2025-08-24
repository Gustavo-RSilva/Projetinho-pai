<?php
session_start();
include_once("../db/conexao.php");

if (!isset($_SESSION['id_empresa'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $id_vaga = (int)$_GET['id'];
    $id_empresa = (int)$_SESSION['id_empresa'];
    
    // Verificar se a vaga pertence à empresa antes de excluir
    $stmt = $conn->prepare("SELECT id_vaga FROM vagas WHERE id_vaga = ? AND id_empresa = ?");
    $stmt->bind_param("ii", $id_vaga, $id_empresa);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Primeiro, excluir todas as candidaturas associadas a esta vaga
        $stmt_candidaturas = $conn->prepare("DELETE FROM candidaturas WHERE id_vaga = ?");
        $stmt_candidaturas->bind_param("i", $id_vaga);
        $stmt_candidaturas->execute();
        $stmt_candidaturas->close();
        
        // Agora excluir a vaga
        $stmt_delete = $conn->prepare("DELETE FROM vagas WHERE id_vaga = ?");
        $stmt_delete->bind_param("i", $id_vaga);
        
        if ($stmt_delete->execute()) {
            $_SESSION['msg'] = "Vaga excluída com sucesso!";
            $_SESSION['tipo_msg'] = "success";
        } else {
            $_SESSION['msg'] = "Erro ao excluir vaga: " . $conn->error;
            $_SESSION['tipo_msg'] = "danger";
        }
        $stmt_delete->close();
    } else {
        $_SESSION['msg'] = "Vaga não encontrada ou você não tem permissão para excluí-la.";
        $_SESSION['tipo_msg'] = "danger";
    }
    
    $stmt->close();
}

header("Location: index.php");
exit();
?>