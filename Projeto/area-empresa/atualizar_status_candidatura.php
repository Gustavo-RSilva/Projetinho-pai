<?php
session_start();
include_once("../db/conexao.php");
if (!isset($_SESSION['id_empresa'])) { header("Location: login.php"); exit(); }
$id_empresa = (int) $_SESSION['id_empresa'];

$id_cand = (int)($_POST['id_candidatura'] ?? 0);
$novo = trim($_POST['novo_status'] ?? '');

// Garante que a candidatura pertence a vaga da empresa
$stmt = $conn->prepare("SELECT c.id_candidatura FROM candidaturas c INNER JOIN vagas v ON v.id_vaga=c.id_vaga WHERE c.id_candidatura=? AND v.id_empresa=?");
$stmt->bind_param('ii', $id_cand, $id_empresa); $stmt->execute(); $ok = $stmt->get_result()->fetch_assoc();
if (!$ok) { die('Operação não permitida.'); }

// Status válidos conforme seu BD: 'Em análise','Aprovado','Rejeitado','Cancelado'
$status_validos = ['Em análise', 'Aprovado', 'Rejeitado', 'Cancelado'];
if (in_array($novo, $status_validos)) {
  $stmt = $conn->prepare("UPDATE candidaturas SET status=?, data_atualizacao=NOW() WHERE id_candidatura=?");
  $stmt->bind_param('si', $novo, $id_cand); $stmt->execute();
}
header('Location: index.php');
exit();
?>