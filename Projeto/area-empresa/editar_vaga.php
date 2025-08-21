<?php
session_start();
include_once("../db/conexao.php");
if (!isset($_SESSION['id_empresa'])) { header("Location: login.php"); exit(); }
$id_empresa = (int) $_SESSION['id_empresa'];
$id = (int)($_GET['id'] ?? 0);

// Verifica se a vaga pertence à empresa
$stmt = $conn->prepare("SELECT * FROM vagas WHERE id_vaga=? AND id_empresa=?");
$stmt->bind_param('ii', $id, $id_empresa); $stmt->execute(); $vaga = $stmt->get_result()->fetch_assoc();
if (!$vaga) { die('Vaga não encontrada.'); }

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $titulo = trim($_POST['titulo'] ?? '');
  $tipo_contrato = trim($_POST['tipo_contrato'] ?? '');
  $localizacao = trim($_POST['localizacao'] ?? '');
  $faixa_salarial = trim($_POST['faixa_salarial'] ?? '');
  $descricao = trim($_POST['descricao'] ?? '');
  $remoto = isset($_POST['remoto']) ? 1 : 0;
  $ativa = isset($_POST['ativa']) ? 1 : 0;
  
  if ($titulo !== '' && $tipo_contrato !== '') {
    $stmt = $conn->prepare("UPDATE vagas SET titulo=?, descricao=?, tipo_contrato=?, localizacao=?, faixa_salarial=?, remoto=?, ativa=? WHERE id_vaga=? AND id_empresa=?");
    $stmt->bind_param('sssssiiii', $titulo, $descricao, $tipo_contrato, $localizacao, $faixa_salarial, $remoto, $ativa, $id, $id_empresa);
    $stmt->execute();
    header('Location: index.php'); exit();
  }
}
?>
<!DOCTYPE html><html lang="pt-BR"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Editar Vaga</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-3">
  <div class="container">
    <h3 class="mb-3">Editar Vaga</h3>
    <form method="POST" class="card p-3">
      <div class="mb-3"><label class="form-label">Título*</label><input name="titulo" class="form-control" value="<?= htmlspecialchars($vaga['titulo']) ?>" required></div>
      <div class="mb-3"><label class="form-label">Descrição</label><textarea name="descricao" class="form-control" rows="3"><?= htmlspecialchars($vaga['descricao']) ?></textarea></div>
      <div class="mb-3"><label class="form-label">Tipo de Contrato*</label>
        <select name="tipo_contrato" class="form-select" required>
          <option value="CLT" <?= $vaga['tipo_contrato']==='CLT'?'selected':'' ?>>CLT</option>
          <option value="PJ" <?= $vaga['tipo_contrato']==='PJ'?'selected':'' ?>>PJ</option>
          <option value="Estágio" <?= $vaga['tipo_contrato']==='Estágio'?'selected':'' ?>>Estágio</option>
          <option value="Temporário" <?= $vaga['tipo_contrato']==='Temporário'?'selected':'' ?>>Temporário</option>
          <option value="Freelance" <?= $vaga['tipo_contrato']==='Freelance'?'selected':'' ?>>Freelance</option>
        </select>
      </div>
      <div class="mb-3"><label class="form-label">Localização</label><input name="localizacao" class="form-control" value="<?= htmlspecialchars($vaga['localizacao']) ?>"></div>
      <div class="mb-3"><label class="form-label">Faixa Salarial</label><input name="faixa_salarial" class="form-control" value="<?= htmlspecialchars($vaga['faixa_salarial']) ?>"></div>
      <div class="mb-3">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="remoto" value="1" id="remoto" <?= $vaga['remoto']?'checked':'' ?>>
          <label class="form-check-label" for="remoto">Trabalho Remoto</label>
        </div>
      </div>
      <div class="mb-3">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="ativa" value="1" id="ativa" <?= $vaga['ativa']?'checked':'' ?>>
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