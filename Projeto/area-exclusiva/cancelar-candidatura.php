<?php
session_start();
require_once('../db/conexao.php');

if (!isset($_SESSION['id_usuario'])) {
    header('Location: Login.php');
    exit;
}

if (isset($_GET['id'])) {
    $candidatura_id = $_GET['id'];
    $usuario_id = $_SESSION['id_usuario'];
    
    // Atualiza o status da candidatura para "Cancelado"
    $sql = "UPDATE candidaturas 
            SET status = 'Cancelado', 
                data_atualizacao = CURRENT_TIMESTAMP,
                observacoes = CONCAT(IFNULL(observacoes, ''), '\nCandidatura cancelada pelo candidato em ', NOW())
            WHERE id_candidatura = ? AND id_usuario = ? AND status = 'Em análise'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $candidatura_id, $usuario_id);
    
    if ($stmt->execute()) {
        $_SESSION['mensagem'] = 'Candidatura cancelada com sucesso!';
        $_SESSION['tipo_mensagem'] = 'success';
    } else {
        $_SESSION['mensagem'] = 'Erro ao cancelar candidatura ou candidatura não está mais em análise.';
        $_SESSION['tipo_mensagem'] = 'danger';
    }
    $stmt->close();
}

header('Location: pag-candidaturas.php');
exit;
?>