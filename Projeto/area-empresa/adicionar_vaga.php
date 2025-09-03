<?php
session_start();
include_once("../db/conexao.php");

if (!isset($_SESSION['id_empresa'])) {
    header("Location: login.php");
    exit();
}

$id_empresa = (int) $_SESSION['id_empresa'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $tipo_contrato = trim($_POST['tipo_contrato'] ?? '');
    $localizacao = trim($_POST['localizacao'] ?? '');
    $faixa_salarial = trim($_POST['faixa_salarial'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $remoto = isset($_POST['remoto']) ? 1 : 0;
    $ativa = isset($_POST['ativa']) ? 1 : 0;
    $id_area = (int) ($_POST['id_area'] ?? 0);

    // Data de expiração (pode ser NULL)
    $data_expiracao = !empty($_POST['data_expiracao']) ? $_POST['data_expiracao'] : null;

    if ($titulo !== '' && $tipo_contrato !== '' && $id_area > 0) {
        // Inserir vaga
        $stmt = $conn->prepare("INSERT INTO vagas 
          (id_empresa, titulo, descricao, tipo_contrato, localizacao, faixa_salarial, remoto, ativa, data_publicacao, data_expiracao) 
          VALUES (?,?,?,?,?,?,?,?, NOW(), ?)");

        $stmt->bind_param(
            'isssssiss',
            $id_empresa,
            $titulo,
            $descricao,
            $tipo_contrato,
            $localizacao,
            $faixa_salarial,
            $remoto,
            $ativa,
            $data_expiracao
        );
        $stmt->execute();

        // Pegar ID da vaga criada
        $id_vaga = $stmt->insert_id;

        // Vincular área
        $stmt2 = $conn->prepare("INSERT INTO vagas_areas (id_vaga, id_area) VALUES (?,?)");
        $stmt2->bind_param('ii', $id_vaga, $id_area);
        $stmt2->execute();

        header('Location: index.php');
        exit();
    }
}

// Buscar áreas do banco
$areas = [];
$result = $conn->query("SELECT id_area, nome FROM areas_profissionais ORDER BY nome ASC");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $areas[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nova Vaga</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-3">
  <div class="container">
    <h3 class="mb-3">Nova Vaga</h3>
    <form method="POST" class="card p-3">
      <div class="mb-3">
        <label class="form-label">Título*</label>
        <input name="titulo" class="form-control" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Descrição</label>
        <textarea name="descricao" class="form-control" rows="3"></textarea>
      </div>

      <div class="mb-3">
        <label class="form-label">Tipo de Contrato*</label>
        <select name="tipo_contrato" class="form-select" required>
          <option value="">Selecione</option>
          <option value="CLT">CLT</option>
          <option value="PJ">PJ</option>
          <option value="Estágio">Estágio</option>
          <option value="Temporário">Temporário</option>
          <option value="Freelance">Freelance</option>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Área*</label>
        <select name="id_area" class="form-select" required>
          <option value="">Selecione uma área</option>
          <?php foreach ($areas as $a): ?>
            <option value="<?= $a['id_area'] ?>"><?= htmlspecialchars($a['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Localização</label>
        <input name="localizacao" class="form-control">
      </div>

      <div class="mb-3">
        <label class="form-label">Faixa Salarial</label>
        <input name="faixa_salarial" class="form-control" placeholder="Ex: R$ 3.000 - R$ 4.000">
      </div>

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

      <div class="mb-3">
        <label class="form-label">Data de Expiração</label>
        <input type="date" name="data_expiracao" class="form-control">
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
