# ===============================
# index.php (Portal Empresarial)
# ===============================
<?php
session_start();
include_once("../db/conexao.php");

if (!isset($_SESSION['id_empresa'])) { header("Location: login.php"); exit(); }
$id_empresa = (int) $_SESSION['id_empresa'];

// ------- Dashboard: counts -------
// Vagas (todas / ativas / encerradas)
$counts = ["vagas"=>0, "candidaturas"=>0, "contratados"=>0, "vagas_ativas"=>0, "vagas_encerradas"=>0];

$stmt = $conn->prepare("SELECT COUNT(*) total FROM vagas WHERE id_empresa = ?");
$stmt->bind_param("i", $id_empresa); $stmt->execute(); $counts["vagas"] = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);

$stmt = $conn->prepare("SELECT COUNT(*) total FROM vagas WHERE id_empresa = ? AND status = 'Ativa'");
$stmt->bind_param("i", $id_empresa); $stmt->execute(); $counts["vagas_ativas"] = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);

$stmt = $conn->prepare("SELECT COUNT(*) total FROM vagas WHERE id_empresa = ? AND status = 'Encerrada'");
$stmt->bind_param("i", $id_empresa); $stmt->execute(); $counts["vagas_encerradas"] = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);

$stmt = $conn->prepare("SELECT COUNT(*) total FROM candidaturas c INNER JOIN vagas v ON v.id_vaga=c.id_vaga WHERE v.id_empresa=?");
$stmt->bind_param("i", $id_empresa); $stmt->execute(); $counts["candidaturas"] = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);

$stmt = $conn->prepare("SELECT COUNT(*) total FROM candidaturas c INNER JOIN vagas v ON v.id_vaga=c.id_vaga WHERE v.id_empresa=? AND c.status='Contratado'");
$stmt->bind_param("i", $id_empresa); $stmt->execute(); $counts["contratados"] = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);

// ------- Empresa info -------
$stmt = $conn->prepare("SELECT id_empresa, nome, cnpj, email, telefone, endereco, descricao FROM empresas WHERE id_empresa = ?");
$stmt->bind_param("i", $id_empresa); $stmt->execute(); $empresa = $stmt->get_result()->fetch_assoc();

// ------- Últimas vagas -------
$stmt = $conn->prepare("SELECT id_vaga, titulo, tipo, status, data_publicacao FROM vagas WHERE id_empresa=? ORDER BY data_publicacao DESC LIMIT 5");
$stmt->bind_param("i", $id_empresa); $stmt->execute(); $ultimas_vagas = $stmt->get_result();

// ------- Vagas (lista com filtro GET) -------
$pesq = trim($_GET['pesquisa'] ?? '');
$st   = trim($_GET['status'] ?? '');
$query_v = "SELECT id_vaga, titulo, tipo, status, data_publicacao FROM vagas WHERE id_empresa = ?";
$params = [$id_empresa]; $types = "i";
if ($pesq !== '') { $query_v .= " AND titulo LIKE ?"; $params[] = "%$pesq%"; $types .= "s"; }
if ($st   !== '') { $query_v .= " AND status = ?";   $params[] = $st;        $types .= "s"; }
$query_v .= " ORDER BY data_publicacao DESC";
$stmt = $conn->prepare($query_v);
$stmt->bind_param($types, ...$params); $stmt->execute(); $todas_vagas = $stmt->get_result();

// ------- Candidatos (lista com filtro GET) -------
$pc_nom = trim($_GET['cand_nome'] ?? '');
$pc_sta = trim($_GET['cand_status'] ?? '');
$pc_vag = (int)($_GET['cand_vaga'] ?? 0);
$query_c = "SELECT c.id_candidatura, u.nome_completo, v.titulo AS vaga_titulo, c.data_candidatura, c.status
            FROM candidaturas c
            INNER JOIN usuarios u ON u.id_usuario = c.id_usuario
            INNER JOIN vagas v ON v.id_vaga = c.id_vaga
            WHERE v.id_empresa = ?";
$pc_params = [$id_empresa]; $pc_types = 'i';
if ($pc_nom !== '') { $query_c .= " AND u.nome_completo LIKE ?"; $pc_params[] = "%$pc_nom%"; $pc_types .= 's'; }
if ($pc_sta !== '') { $query_c .= " AND c.status = ?";          $pc_params[] = $pc_sta;     $pc_types .= 's'; }
if ($pc_vag > 0)   { $query_c .= " AND v.id_vaga = ?";          $pc_params[] = $pc_vag;     $pc_types .= 'i'; }
$query_c .= " ORDER BY c.data_candidatura DESC";
$stmt = $conn->prepare($query_c);
$stmt->bind_param($pc_types, ...$pc_params); $stmt->execute(); $candidatos = $stmt->get_result();

// Para popular select de vagas no filtro de candidatos
$stmt = $conn->prepare("SELECT id_vaga, titulo FROM vagas WHERE id_empresa=? ORDER BY titulo ASC");
$stmt->bind_param("i", $id_empresa); $stmt->execute(); $vagas_select = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Portal Empresarial | Contrata</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="../css/pag-index-emp.css" />
</head>
<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container-fluid">
      <a class="navbar-brand" href="#"><img src="img/logo-empresa.png" alt="Contrata" /></a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
              <span class="material-icons">account_circle</span> <?= htmlspecialchars($empresa['nome']); ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="#" onclick="mostrarConfiguracoes(event)"><span class="material-icons">settings</span> Configurações</a></li>
              <li><hr class="dropdown-divider" /></li>
              <li><a class="dropdown-item" href="logout.php"><span class="material-icons">logout</span> Sair</a></li>
            </ul>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="dashboard-container">
    <div class="row g-0 h-100">
      <!-- Sidebar -->
      <div class="col-md-3 sidebar-container">
        <div class="sidebar">
          <h5 class="mb-4">Menu</h5>
          <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link active" href="#" onclick="mostrarDashboard(event)"><span class="material-icons">dashboard</span> Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="#" onclick="mostrarVagas(event)"><span class="material-icons">work</span> Vagas</a></li>
            <li class="nav-item"><a class="nav-link" href="#" onclick="mostrarCandidatos(event)"><span class="material-icons">people</span> Candidatos</a></li>
            <li class="nav-item"><a class="nav-link" href="#" onclick="mostrarEmpresa(event)"><span class="material-icons">business</span> Empresa</a></li>
            <li class="nav-item"><a class="nav-link" href="#" onclick="mostrarRelatorios(event)"><span class="material-icons">analytics</span> Relatórios</a></li>
            <li class="nav-item"><a class="nav-link" href="#" onclick="mostrarConfiguracoes(event)"><span class="material-icons">settings</span> Configurações</a></li>
          </ul>
        </div>
      </div>

      <!-- Main -->
      <div class="col-md-9 main-content">

        <!-- Dashboard -->
        <div id="dashboard-section" class="section active">
          <div class="row mb-4">
            <div class="col-md-3"><div class="card stat-card"><div class="stat-number"><?= $counts['vagas'] ?></div><div class="stat-label">Total de Vagas</div></div></div>
            <div class="col-md-3"><div class="card stat-card"><div class="stat-number"><?= $counts['vagas_ativas'] ?></div><div class="stat-label">Vagas Ativas</div></div></div>
            <div class="col-md-3"><div class="card stat-card"><div class="stat-number"><?= $counts['candidaturas'] ?></div><div class="stat-label">Candidaturas</div></div></div>
            <div class="col-md-3"><div class="card stat-card"><div class="stat-number"><?= $counts['contratados'] ?></div><div class="stat-label">Contratados</div></div></div>
          </div>

          <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h5 class="mb-0">Últimas Vagas Publicadas</h5>
              <a href="adicionar_vaga.php" class="btn btn-sm btn-primary"><span class="material-icons">add</span> Nova Vaga</a>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead><tr><th>Cargo</th><th>Tipo</th><th>Candidatos</th><th>Status</th><th>Ações</th></tr></thead>
                  <tbody>
                    <?php while($vaga = $ultimas_vagas->fetch_assoc()): ?>
                      <tr>
                        <td><?= htmlspecialchars($vaga['titulo']); ?></td>
                        <td><?= htmlspecialchars($vaga['tipo']); ?></td>
                        <td>
                          <?php $q=$conn->query("SELECT COUNT(*) total FROM candidaturas WHERE id_vaga=".(int)$vaga['id_vaga']); echo (int)($q->fetch_assoc()['total'] ?? 0); ?>
                        </td>
                        <td><?= htmlspecialchars($vaga['status']); ?></td>
                        <td>
                          <a class="btn btn-sm btn-warning" href="editar_vaga.php?id=<?= (int)$vaga['id_vaga'] ?>">Editar</a>
                          <a class="btn btn-sm btn-danger" href="excluir_vaga.php?id=<?= (int)$vaga['id_vaga'] ?>" onclick="return confirm('Excluir esta vaga?');">Excluir</a>
                        </td>
                      </tr>
                    <?php endwhile; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Vagas -->
        <div id="vagas-section" class="section" style="display:none;">
          <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h5 class="mb-0">Gerenciamento de Vagas</h5>
              <a href="adicionar_vaga.php" class="btn btn-sm btn-primary"><span class="material-icons">add</span> Nova Vaga</a>
            </div>
            <div class="card-body">
              <form method="GET" class="row g-2 mb-3">
                <div class="col-md-4"><input type="text" name="pesquisa" value="<?= htmlspecialchars($pesq) ?>" class="form-control" placeholder="Pesquisar por título"></div>
                <div class="col-md-3">
                  <select name="status" class="form-select">
                    <option value="">Todos os Status</option>
                    <option value="Ativa"     <?= $st==='Ativa'?'selected':'' ?>>Ativa</option>
                    <option value="Encerrada" <?= $st==='Encerrada'?'selected':'' ?>>Encerrada</option>
                  </select>
                </div>
                <div class="col-md-2"><button type="submit" class="btn btn-secondary w-100">Filtrar</button></div>
                <div class="col-md-2"><a href="index.php#vagas" class="btn btn-light w-100" onclick="location.href='index.php';">Limpar</a></div>
              </form>

              <div class="table-responsive">
                <table class="table table-hover">
                  <thead><tr><th>Cargo</th><th>Publicação</th><th>Candidatos</th><th>Status</th><th>Ações</th></tr></thead>
                  <tbody>
                    <?php while($vaga = $todas_vagas->fetch_assoc()): $idv=(int)$vaga['id_vaga']; ?>
                      <tr>
                        <td><?= htmlspecialchars($vaga['titulo']); ?></td>
                        <td><?= $vaga['data_publicacao'] ? date('d/m/Y', strtotime($vaga['data_publicacao'])) : '-' ?></td>
                        <td><?php $q=$conn->query("SELECT COUNT(*) total FROM candidaturas WHERE id_vaga=$idv"); echo (int)($q->fetch_assoc()['total'] ?? 0); ?></td>
                        <td><span class="badge bg-<?= $vaga['status']==='Ativa'?'success':'secondary' ?>"><?php echo htmlspecialchars($vaga['status']); ?></span></td>
                        <td>
                          <a class="btn btn-sm btn-warning" href="editar_vaga.php?id=<?= $idv ?>">Editar</a>
                          <a class="btn btn-sm btn-danger" href="excluir_vaga.php?id=<?= $idv ?>" onclick="return confirm('Tem certeza que deseja excluir?');">Excluir</a>
                        </td>
                      </tr>
                    <?php endwhile; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Candidatos -->
        <div id="candidatos-section" class="section" style="display:none;">
          <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Candidatos</h5></div>
            <div class="card-body">
              <form method="GET" class="row g-2 mb-3">
                <div class="col-md-4"><input type="text" name="cand_nome" value="<?= htmlspecialchars($pc_nom) ?>" class="form-control" placeholder="Nome do candidato"></div>
                <div class="col-md-3">
                  <select name="cand_vaga" class="form-select">
                    <option value="0">Todas as vagas</option>
                    <?php foreach($vagas_select as $vs): ?>
                      <option value="<?= (int)$vs['id_vaga'] ?>" <?= $pc_vag==(int)$vs['id_vaga']?'selected':'' ?>><?= htmlspecialchars($vs['titulo']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-3">
                  <select name="cand_status" class="form-select">
                    <option value="">Todos os status</option>
                    <option value="Pendente"    <?= $pc_sta==='Pendente'?'selected':'' ?>>Pendente</option>
                    <option value="Aprovado"    <?= $pc_sta==='Aprovado'?'selected':'' ?>>Aprovado</option>
                    <option value="Reprovado"   <?= $pc_sta==='Reprovado'?'selected':'' ?>>Reprovado</option>
                    <option value="Contratado"  <?= $pc_sta==='Contratado'?'selected':'' ?>>Contratado</option>
                  </select>
                </div>
                <div class="col-md-2"><button class="btn btn-secondary w-100" type="submit">Filtrar</button></div>
              </form>

              <div class="table-responsive">
                <table class="table table-hover">
                  <thead><tr><th>Nome</th><th>Vaga</th><th>Data</th><th>Status</th><th>Ações</th></tr></thead>
                  <tbody>
                    <?php while($cand = $candidatos->fetch_assoc()): ?>
                      <tr>
                        <td><?= htmlspecialchars($cand['nome_completo']); ?></td>
                        <td><?= htmlspecialchars($cand['vaga_titulo']); ?></td>
                        <td><?= $cand['data_candidatura'] ? date('d/m/Y', strtotime($cand['data_candidatura'])) : '-' ?></td>
                        <td><?= htmlspecialchars($cand['status']); ?></td>
                        <td class="d-flex gap-2">
                          <form method="POST" action="atualizar_status_candidatura.php" onsubmit="return confirm('Confirmar ação?');">
                            <input type="hidden" name="id_candidatura" value="<?= (int)$cand['id_candidatura'] ?>" />
                            <select name="novo_status" class="form-select form-select-sm d-inline-block w-auto">
                              <option value="Pendente">Pendente</option>
                              <option value="Aprovado">Aprovado</option>
                              <option value="Reprovado">Reprovado</option>
                              <option value="Contratado">Contratado</option>
                            </select>
                            <button class="btn btn-sm btn-primary" type="submit">Atualizar</button>
                          </form>
                        </td>
                      </tr>
                    <?php endwhile; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Empresa -->
        <div id="empresa-section" class="section" style="display:none;">
          <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Informações da Empresa</h5></div>
            <div class="card-body">
              <form id="form-empresa" action="atualizar_empresa.php" method="POST">
                <div class="row mb-3">
                  <div class="col-md-6">
                    <label class="form-label">Nome da Empresa*</label>
                    <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($empresa['nome']) ?>" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">CNPJ*</label>
                    <input type="text" name="cnpj" class="form-control" value="<?= htmlspecialchars($empresa['cnpj']) ?>" required>
                  </div>
                </div>
                <div class="row mb-3">
                  <div class="col-md-6">
                    <label class="form-label">E-mail</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($empresa['email'] ?? '') ?>">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Telefone</label>
                    <input type="text" name="telefone" class="form-control" value="<?= htmlspecialchars($empresa['telefone'] ?? '') ?>">
                  </div>
                </div>
                <div class="mb-3">
                  <label class="form-label">Endereço</label>
                  <input type="text" name="endereco" class="form-control" value="<?= htmlspecialchars($empresa['endereco'] ?? '') ?>">
                </div>
                <div class="mb-3">
                  <label class="form-label">Descrição</label>
                  <textarea name="descricao" class="form-control" rows="3"><?= htmlspecialchars($empresa['descricao'] ?? '') ?></textarea>
                </div>
                <button class="btn btn-success" type="submit">Salvar Alterações</button>
              </form>
            </div>
          </div>
        </div>

        <!-- Relatórios -->
        <div id="relatorios-section" class="section" style="display:none;">
          <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Relatórios</h5></div>
            <div class="card-body">
              <form method="GET" class="row g-2 mb-4">
                <div class="col-md-3">
                  <label class="form-label">Início</label>
                  <input type="date" class="form-control" name="r_ini" value="<?= htmlspecialchars($_GET['r_ini'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Fim</label>
                  <input type="date" class="form-control" name="r_fim" value="<?= htmlspecialchars($_GET['r_fim'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Status Candidatura</label>
                  <select name="r_status" class="form-select">
                    <option value="">Todos</option>
                    <?php $rs = $_GET['r_status'] ?? ''; foreach(["Pendente","Aprovado","Reprovado","Contratado"] as $opt): ?>
                      <option value="<?= $opt ?>" <?= $rs===$opt?'selected':'' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-3 d-flex align-items-end"><button class="btn btn-primary w-100" type="submit">Gerar</button></div>
              </form>
              <?php
                // Query de relatório (candidaturas por dia)
                $r_ini = $_GET['r_ini'] ?? '';
                $r_fim = $_GET['r_fim'] ?? '';
                $r_status = $_GET['r_status'] ?? '';
                $qr = "SELECT DATE(c.data_candidatura) dia, COUNT(*) total
                       FROM candidaturas c
                       INNER JOIN vagas v ON v.id_vaga=c.id_vaga
                       WHERE v.id_empresa=?";
                $rp = [$id_empresa]; $rt = 'i';
                if ($r_ini !== '') { $qr .= " AND DATE(c.data_candidatura) >= ?"; $rp[] = $r_ini; $rt .= 's'; }
                if ($r_fim !== '') { $qr .= " AND DATE(c.data_candidatura) <= ?"; $rp[] = $r_fim; $rt .= 's'; }
                if ($r_status !== '') { $qr .= " AND c.status = ?"; $rp[] = $r_status; $rt .= 's'; }
                $qr .= " GROUP BY DATE(c.data_candidatura) ORDER BY dia ASC";
                $stmt = $conn->prepare($qr); $stmt->bind_param($rt, ...$rp); $stmt->execute(); $rep = $stmt->get_result();
                $labels=[]; $data=[]; foreach($rep as $row){ $labels[]=$row['dia']; $data[]=(int)$row['total']; }
              ?>
              <div class="mb-3">
                <canvas id="chartCandidaturas"></canvas>
              </div>
              <div class="table-responsive">
                <table class="table table-sm">
                  <thead><tr><th>Data</th><th>Total de Candidaturas</th></tr></thead>
                  <tbody>
                    <?php foreach(array_map(null,$labels,$data) as $pair): ?>
                      <tr><td><?= htmlspecialchars($pair[0]) ?></td><td><?= (int)$pair[1] ?></td></tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Configurações -->
        <div id="configuracoes-section" class="section" style="display:none;">
          <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Configurações da Conta</h5></div>
            <div class="card-body">
              <form action="atualizar_config.php" method="POST">
                <div class="row mb-3">
                  <div class="col-md-6">
                    <label class="form-label">Nome *</label>
                    <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($empresa['nome']) ?>" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">E-mail *</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($empresa['email'] ?? '') ?>" required>
                  </div>
                </div>
                <div class="row mb-3">
                  <div class="col-md-6">
                    <label class="form-label">Nova Senha</label>
                    <input type="password" name="senha" class="form-control" placeholder="Deixe em branco para manter">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Confirmar Senha</label>
                    <input type="password" name="senha_confirm" class="form-control" placeholder="Repita a nova senha">
                  </div>
                </div>
                <button class="btn btn-success" type="submit">Salvar</button>
              </form>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

  <footer class="text-center py-3 bg-dark text-white"><p>&copy; 2025 Contrata</p></footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script>
    function mostrarSecao(id){ document.querySelectorAll('.section').forEach(s=>s.style.display='none'); document.getElementById(id).style.display='block'; }
    function mostrarDashboard(e){ if(e) e.preventDefault(); mostrarSecao('dashboard-section'); }
    function mostrarVagas(e){ if(e) e.preventDefault(); mostrarSecao('vagas-section'); }
    function mostrarCandidatos(e){ if(e) e.preventDefault(); mostrarSecao('candidatos-section'); }
    function mostrarEmpresa(e){ if(e) e.preventDefault(); mostrarSecao('empresa-section'); }
    function mostrarRelatorios(e){ if(e) e.preventDefault(); mostrarSecao('relatorios-section'); }
    function mostrarConfiguracoes(e){ if(e) e.preventDefault(); mostrarSecao('configuracoes-section'); }

    // Chart.js (Relatórios)
    const ctx = document.getElementById('chartCandidaturas');
    if (ctx) {
      const labels = <?= json_encode($labels ?? []) ?>;
      const data   = <?= json_encode($data   ?? []) ?>;
      new Chart(ctx, { type: 'line', data: { labels, datasets: [{ label: 'Candidaturas por dia', data }] }, options: { responsive: true, scales: { y: { beginAtZero: true } } } });
    }
  </script>
</body>
</html>


# =================================
# adicionar_vaga.php (create vaga)
# =================================
<?php
session_start();
include_once("../db/conexao.php");
if (!isset($_SESSION['id_empresa'])) { header("Location: login.php"); exit(); }
$id_empresa = (int) $_SESSION['id_empresa'];

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $titulo = trim($_POST['titulo'] ?? '');
  $tipo   = trim($_POST['tipo'] ?? '');
  $status = trim($_POST['status'] ?? 'Ativa');
  if ($titulo !== '' && $tipo !== '') {
    $stmt = $conn->prepare("INSERT INTO vagas (id_empresa, titulo, tipo, status, data_publicacao) VALUES (?,?,?,?, NOW())");
    $stmt->bind_param('isss', $id_empresa, $titulo, $tipo, $status);
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
      <div class="mb-3"><label class="form-label">Tipo*</label><input name="tipo" class="form-control" required></div>
      <div class="mb-3"><label class="form-label">Status</label>
        <select name="status" class="form-select"><option value="Ativa">Ativa</option><option value="Encerrada">Encerrada</option></select>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-success" type="submit">Salvar</button>
        <a class="btn btn-secondary" href="index.php" role="button">Cancelar</a>
      </div>
    </form>
  </div>
</body></html>


# ================================
# editar_vaga.php (update vaga)
# ================================
<?php
session_start();
include_once("../db/conexao.php");
if (!isset($_SESSION['id_empresa'])) { header("Location: login.php"); exit(); }
$id_empresa = (int) $_SESSION['id_empresa'];
$id = (int)($_GET['id'] ?? 0);

// Verifica se a vaga pertence à empresa
$stmt = $conn->prepare("SELECT id_vaga, titulo, tipo, status FROM vagas WHERE id_vaga=? AND id_empresa=?");
$stmt->bind_param('ii', $id, $id_empresa); $stmt->execute(); $vaga = $stmt->get_result()->fetch_assoc();
if (!$vaga) { die('Vaga não encontrada.'); }

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $titulo = trim($_POST['titulo'] ?? '');
  $tipo   = trim($_POST['tipo'] ?? '');
  $status = trim($_POST['status'] ?? 'Ativa');
  if ($titulo !== '' && $tipo !== '') {
    $stmt = $conn->prepare("UPDATE vagas SET titulo=?, tipo=?, status=? WHERE id_vaga=? AND id_empresa=?");
    $stmt->bind_param('sssii', $titulo, $tipo, $status, $id, $id_empresa);
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
      <div class="mb-3"><label class="form-label">Tipo*</label><input name="tipo" class="form-control" value="<?= htmlspecialchars($vaga['tipo']) ?>" required></div>
      <div class="mb-3"><label class="form-label">Status</label>
        <select name="status" class="form-select">
          <option value="Ativa"     <?= $vaga['status']==='Ativa'?'selected':'' ?>>Ativa</option>
          <option value="Encerrada" <?= $vaga['status']==='Encerrada'?'selected':'' ?>>Encerrada</option>
        </select>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-success" type="submit">Salvar</button>
        <a class="btn btn-secondary" href="index.php" role="button">Cancelar</a>
      </div>
    </form>
  </div>
</body></html>


# ================================
# excluir_vaga.php (delete vaga)
# ================================
<?php
session_start();
include_once("../db/conexao.php");
if (!isset($_SESSION['id_empresa'])) { header("Location: login.php"); exit(); }
$id_empresa = (int) $_SESSION['id_empresa'];
$id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("DELETE v FROM vagas v WHERE v.id_vaga=? AND v.id_empresa=?");
$stmt->bind_param('ii', $id, $id_empresa);
$stmt->execute();
header('Location: index.php');
exit();
?>


# ============================================
# atualizar_status_candidatura.php (actions)
# ============================================
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

if (in_array($novo, ['Pendente','Aprovado','Reprovado','Contratado'])) {
  $stmt = $conn->prepare("UPDATE candidaturas SET status=? WHERE id_candidatura=?");
  $stmt->bind_param('si', $novo, $id_cand); $stmt->execute();
}
header('Location: index.php');
exit();
?>


# ======================================
# atualizar_empresa.php (update perfil)
# ======================================
<?php
session_start();
include_once("../db/conexao.php");
if (!isset($_SESSION['id_empresa'])) { header("Location: login.php"); exit(); }
$id_empresa = (int) $_SESSION['id_empresa'];

$nome = trim($_POST['nome'] ?? '');
$cnpj = trim($_POST['cnpj'] ?? '');
$email = trim($_POST['email'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$endereco = trim($_POST['endereco'] ?? '');
$descricao = trim($_POST['descricao'] ?? '');

$stmt = $conn->prepare("UPDATE empresas SET nome=?, cnpj=?, email=?, telefone=?, endereco=?, descricao=? WHERE id_empresa=?");
$stmt->bind_param('ssssssi', $nome, $cnpj, $email, $telefone, $endereco, $descricao, $id_empresa);
$stmt->execute();
header('Location: index.php');
exit();
?>


# =====================================
# atualizar_config.php (conta/segurança)
# =====================================
<?php
session_start();
include_once("../db/conexao.php");
if (!isset($_SESSION['id_empresa'])) { header("Location: login.php"); exit(); }
$id_empresa = (int) $_SESSION['id_empresa'];

$nome  = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$senha = $_POST['senha'] ?? '';
$senha_confirm = $_POST['senha_confirm'] ?? '';

if ($senha !== '' && $senha === $senha_confirm) {
  // supondo que a coluna seja empresas.senha (hash)
  $hash = password_hash($senha, PASSWORD_DEFAULT);
  $stmt = $conn->prepare("UPDATE empresas SET nome=?, email=?, senha=? WHERE id_empresa=?");
  $stmt->bind_param('sssi', $nome, $email, $hash, $id_empresa);
} else {
  $stmt = $conn->prepare("UPDATE empresas SET nome=?, email=? WHERE id_empresa=?");
  $stmt->bind_param('ssi', $nome, $email, $id_empresa);
}
$stmt->execute();
header('Location: index.php');
exit();
?>


# ==============
# logout.php
# ==============
<?php
session_start();
session_unset();
session_destroy();
header('Location: login.php');
exit();
?>
