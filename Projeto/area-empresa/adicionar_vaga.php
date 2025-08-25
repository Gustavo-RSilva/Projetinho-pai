<?php
session_start();
include_once("../db/conexao.php");
if (!isset($_SESSION['id_empresa'])) { header("Location: login.php"); exit(); }
$id_empresa = (int) $_SESSION['id_empresa'];

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $titulo = trim($_POST['titulo'] ?? '');
  $tipo_contrato = trim($_POST['tipo_contrato'] ?? '');
  $localizacao = trim($_POST['localizacao'] ?? '');
  $faixa_salarial = trim($_POST['faixa_salarial'] ?? '');
  $descricao = trim($_POST['descricao'] ?? '');
  $remoto = isset($_POST['remoto']) ? 1 : 0;
  $ativa = isset($_POST['ativa']) ? 1 : 0;
  
  if ($titulo !== '' && $tipo_contrato !== '') {
    $stmt = $conn->prepare("INSERT INTO vagas (id_empresa, titulo, descricao, tipo_contrato, localizacao, faixa_salarial, remoto, ativa, data_publicacao) VALUES (?,?,?,?,?,?,?,?, NOW())");
    $stmt->bind_param('isssssii', $id_empresa, $titulo, $descricao, $tipo_contrato, $localizacao, $faixa_salarial, $remoto, $ativa);
    $stmt->execute();
    header('Location: index.php'); exit();
  }
}
?>
<!DOCTYPE html><html lang="pt-BR"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nova Vaga</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-3">
  <div class="container">
    <h3 class="mb-3">Nova Vaga</h3>
    <form method="POST" class="card p-3">
      <div class="mb-3"><label class="form-label">Título*</label><input name="titulo" class="form-control" required></div>
      <div class="mb-3"><label class="form-label">Descrição</label><textarea name="descricao" class="form-control" rows="3"></textarea></div>
      <div class="mb-3"><label class="form-label">Tipo de Contrato*</label>
        <select name="tipo_contrato" class="form-select" required>
          <option value="">Selecione</option>
          <option value="CLT">CLT</option>
          <option value="PJ">PJ</option>
          <option value="Estágio">Estágio</option>
          <option value="Temporário">Temporário</option>
          <option value="Freelance">Freelance</option>
        </select>
      </div>
      <div class="mb-3"><label class="form-label">Localização</label><input name="localizacao" class="form-control"></div>
      <div class="mb-3"><label class="form-label">Faixa Salarial</label><input name="faixa_salarial" class="form-control" placeholder="Ex: R$ 3.000 - R$ 4.000"></div>
      <div class="mb-3">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="remoto" value="1" id="remoto">
          <label class="form-check-label" for="remoto">Trabalho Remoto</label>
        </div>
      </div>
      <div class="mb-3">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="ativa" value="1" id="ativa" checked>
          <label class="form-check-label" for="ativa">Vaga Ativa</label>
        </div>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-success" type="submit">Salvar</button>
        <a class="btn btn-secondary" href="index.php" role="button">Cancelar</a>
      </div>
    </form>
  </div>
</body></html>