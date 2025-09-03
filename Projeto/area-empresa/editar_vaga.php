<?php
session_start();
include_once("../db/conexao.php");

if (!isset($_SESSION['id_empresa'])) { 
    header("Location: login.php"); 
    exit(); 
}

$id_empresa = (int) $_SESSION['id_empresa'];
$id = (int)($_GET['id'] ?? 0);

// Verifica se a vaga pertence à empresa
$stmt = $conn->prepare("SELECT * FROM vagas WHERE id_vaga=? AND id_empresa=?");
$stmt->bind_param('ii', $id, $id_empresa); 
$stmt->execute(); 
$vaga = $stmt->get_result()->fetch_assoc();
if (!$vaga) { die('Vaga não encontrada.'); }

// Buscar área vinculada
$stmtA = $conn->prepare("SELECT id_area FROM vagas_areas WHERE id_vaga=? LIMIT 1");
$stmtA->bind_param('i', $id);
$stmtA->execute();
$resArea = $stmtA->get_result()->fetch_assoc();
$id_area_vinculada = $resArea['id_area'] ?? 0;

// Buscar todas as áreas
$areas = [];
$result = $conn->query("SELECT id_area, nome FROM areas_profissionais ORDER BY nome ASC");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $areas[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $tipo_contrato = trim($_POST['tipo_contrato'] ?? '');
    $localizacao = trim($_POST['localizacao'] ?? '');
    $faixa_salarial = trim($_POST['faixa_salarial'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $remoto = isset($_POST['remoto']) ? 1 : 0;
    $ativa = isset($_POST['ativa']) ? 1 : 0;
    $id_area = (int) ($_POST['id_area'] ?? 0);
    $data_expiracao = !empty($_POST['data_expiracao']) ? $_POST['data_expiracao'] : null;

    if ($titulo !== '' && $tipo_contrato !== '') {
        // Atualizar vaga
        $stmt = $conn->prepare("UPDATE vagas 
            SET titulo=?, descricao=?, tipo_contrato=?, localizacao=?, faixa_salarial=?, remoto=?, ativa=?, data_expiracao=? 
            WHERE id_vaga=? AND id_empresa=?");
        $stmt->bind_param(
            'sssssiiisi', 
            $titulo, 
            $descricao, 
            $tipo_contrato, 
            $localizacao, 
            $faixa_salarial, 
            $remoto, 
            $ativa, 
            $data_expiracao, 
            $id, 
            $id_empresa
        );
        $stmt->execute();

        // Atualizar vínculo da área
        if ($id_area > 0) {
            // Remove vínculos antigos
            $conn->query("DELETE FROM vagas_areas WHERE id_vaga=$id");
            // Insere novo vínculo
            $stmt2 = $conn->prepare("INSERT INTO vagas_areas (id_vaga, id_area) VALUES (?,?)");
            $stmt2->bind_param('ii', $id, $id_area);
            $stmt2->execute();
        }

        header('Location: index.php'); 
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Editar Vaga</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-3">
  <div class="container">
    <h3 class="mb-3">Editar Vaga</h3>
    <form method="POST" class="card p-3">

      <div class="mb-3">
        <label class="form-label">Título*</label>
        <input name="titulo" class="form-control" value="<?= htmlspecialchars($vaga['titulo']) ?>" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Descrição</label>
        <textarea name="descricao" class="form-control" rows="3"><?= htmlspecialchars($vaga['descricao']) ?></textarea>
      </div>

      <div class="mb-3">
        <label class="form-label">Tipo de Contrato*</label>
        <select name="tipo_contrato" class="form-select" required>
          <option value="CLT" <?= $vaga['tipo_contrato']==='CLT'?'selected':'' ?>>CLT</option>
          <option value="PJ" <?= $vaga['tipo_contrato']==='PJ'?'selected':'' ?>>PJ</option>
          <option value="Estágio" <?= $vaga['tipo_contrato']==='Estágio'?'selected':'' ?>>Estágio</option>
          <option value="Temporário" <?= $vaga['tipo_contrato']==='Temporário'?'selected':'' ?>>Temporário</option>
          <option value="Freelance" <?= $vaga['tipo_contrato']==='Freelance'?'selected':'' ?>>Freelance</option>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Área*</label>
        <select name="id_area" class="form-select" required>
          <option value="">Selecione uma área</option>
          <?php foreach ($areas as $a): ?>
            <option value="<?= $a['id_area'] ?>" <?= $id_area_vinculada == $a['id_area'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($a['nome']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Localização</label>
        <input name="localizacao" class="form-control" value="<?= htmlspecialchars($vaga['localizacao']) ?>">
      </div>

      <div class="mb-3">
        <label class="form-label">Faixa Salarial</label>
        <input name="faixa_salarial" class="form-control" value="<?= htmlspecialchars($vaga['faixa_salarial']) ?>">
      </div>

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

      <div class="mb-3">
        <label class="form-label">Data de Expiração</label>
        <input type="date" name="data_expiracao" class="form-control" 
               value="<?= $vaga['data_expiracao'] ? htmlspecialchars($vaga['data_expiracao']) : '' ?>">
        <small class="text-muted">Se não preencher, a vaga não terá expiração.</small>
      </div>

      <div class="d-flex gap-2">
        <button class="btn btn-success" type="submit">Salvar</button>
        <a class="btn btn-secondary" href="index.php" role="button">Cancelar</a>
      </div>
    </form>
  </div>
</body>
</html>
