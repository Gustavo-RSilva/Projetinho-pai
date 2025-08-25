<?php
session_start();
include_once("../db/conexao.php");

// Preservar a âncora após submit de formulários
if (isset($_GET['anchor'])) {
    $anchor = $_GET['anchor'];
    echo "<script>document.addEventListener('DOMContentLoaded', function() { mostrarSecao('{$anchor}-section'); });</script>";
}
if (!isset($_SESSION['id_empresa'])) {
    header("Location: login.php");
    exit();
}
$id_empresa = (int) $_SESSION['id_empresa'];

// ------- Dashboard: counts -------
// Vagas (todas / ativas / encerradas)
$counts = ["vagas" => 0, "candidaturas" => 0, "contratados" => 0, "vagas_ativas" => 0, "vagas_encerradas" => 0];

// Total de vagas
$stmt = $conn->prepare("SELECT COUNT(*) total FROM vagas WHERE id_empresa = ?");
$stmt->bind_param("i", $id_empresa);
$stmt->execute();
$counts["vagas"] = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);

// Vagas ativas (ativa = 1)
$stmt = $conn->prepare("SELECT COUNT(*) total FROM vagas WHERE id_empresa = ? AND ativa = 1");
$stmt->bind_param("i", $id_empresa);
$stmt->execute();
$counts["vagas_ativas"] = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);

// Vagas encerradas (ativa = 0)
$stmt = $conn->prepare("SELECT COUNT(*) total FROM vagas WHERE id_empresa = ? AND ativa = 0");
$stmt->bind_param("i", $id_empresa);
$stmt->execute();
$counts["vagas_encerradas"] = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);

// Total de candidaturas
$stmt = $conn->prepare("SELECT COUNT(*) total FROM candidaturas c INNER JOIN vagas v ON v.id_vaga=c.id_vaga WHERE v.id_empresa=?");
$stmt->bind_param("i", $id_empresa);
$stmt->execute();
$counts["candidaturas"] = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);

// Candidatos contratados (status = 'Aprovado' no seu BD)
$stmt = $conn->prepare("SELECT COUNT(*) total FROM candidaturas c INNER JOIN vagas v ON v.id_vaga=c.id_vaga WHERE v.id_empresa=? AND c.status='Aprovado'");
$stmt->bind_param("i", $id_empresa);
$stmt->execute();
$counts["contratados"] = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);

// ------- Empresa info -------
// Incluindo o campo logo_url na consulta
$stmt = $conn->prepare("SELECT id_empresa, nome, cnpj, email, telefone, endereco, descricao, url_logo FROM empresas WHERE id_empresa = ?");
$stmt->bind_param("i", $id_empresa);
$stmt->execute();
$empresa = $stmt->get_result()->fetch_assoc();

// ------- Últimas vagas -------
$stmt = $conn->prepare("SELECT id_vaga, titulo, tipo_contrato as tipo, ativa as status, data_publicacao FROM vagas WHERE id_empresa=? ORDER BY data_publicacao DESC LIMIT 5");
$stmt->bind_param("i", $id_empresa);
$stmt->execute();
$ultimas_vagas = $stmt->get_result();

// ------- Vagas (lista com filtro GET) -------
$pesq = trim($_GET['pesquisa'] ?? '');
$st   = trim($_GET['status'] ?? '');
$query_v = "SELECT id_vaga, titulo, tipo_contrato as tipo, ativa as status, data_publicacao FROM vagas WHERE id_empresa = ?";
$params = [$id_empresa];
$types = "i";
if ($pesq !== '') {
    $query_v .= " AND titulo LIKE ?";
    $params[] = "%$pesq%";
    $types .= "s";
}
if ($st   !== '') {
    if ($st === 'Ativa') {
        $query_v .= " AND ativa = 1";
    } else {
        $query_v .= " AND ativa = 0";
    }
}
$query_v .= " ORDER BY data_publicacao DESC";
$stmt = $conn->prepare($query_v);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$todas_vagas = $stmt->get_result();

// ------- Candidatos (lista com filtro GET) -------
$pc_nom = trim($_GET['cand_nome'] ?? '');
$pc_sta = trim($_GET['cand_status'] ?? '');
$pc_vag = (int)($_GET['cand_vaga'] ?? 0);
$query_c = "SELECT c.id_candidatura, u.nome_completo, v.titulo AS vaga_titulo, c.data_candidatura, c.status
            FROM candidaturas c
            INNER JOIN usuarios u ON u.id_usuario = c.id_usuario
            INNER JOIN vagas v ON v.id_vaga = c.id_vaga
            WHERE v.id_empresa = ?";
$pc_params = [$id_empresa];
$pc_types = 'i';
if ($pc_nom !== '') {
    $query_c .= " AND u.nome_completo LIKE ?";
    $pc_params[] = "%$pc_nom%";
    $pc_types .= 's';
}
if ($pc_sta !== '') {
    $query_c .= " AND c.status = ?";
    $pc_params[] = $pc_sta;
    $pc_types .= 's';
}
if ($pc_vag > 0) {
    $query_c .= " AND v.id_vaga = ?";
    $pc_params[] = $pc_vag;
    $pc_types .= 'i';
}
$query_c .= " ORDER BY c.data_candidatura DESC";
$stmt = $conn->prepare($query_c);
$stmt->bind_param($pc_types, ...$pc_params);
$stmt->execute();
$candidatos = $stmt->get_result();

// Para popular select de vagas no filtro de candidatos
$stmt = $conn->prepare("SELECT id_vaga, titulo FROM vagas WHERE id_empresa=? ORDER BY titulo ASC");
$stmt->bind_param("i", $id_empresa);
$stmt->execute();
$vagas_select = $stmt->get_result();

// ... restante do código mantido igual até a parte de relatórios

// ------- Relatórios -------
$r_ini = $_GET['r_ini'] ?? '';
$r_fim = $_GET['r_fim'] ?? '';
$r_status = $_GET['r_status'] ?? '';
$qr = "SELECT DATE(c.data_candidatura) dia, COUNT(*) total
       FROM candidaturas c
       INNER JOIN vagas v ON v.id_vaga=c.id_vaga
       WHERE v.id_empresa=?";
$rp = [$id_empresa];
$rt = 'i';
if ($r_ini !== '') {
    $qr .= " AND DATE(c.data_candidatura) >= ?";
    $rp[] = $r_ini;
    $rt .= 's';
}
if ($r_fim !== '') {
    $qr .= " AND DATE(c.data_candidatura) <= ?";
    $rp[] = $r_fim;
    $rt .= 's';
}
if ($r_status !== '') {
    $qr .= " AND c.status = ?";
    $rp[] = $r_status;
    $rt .= 's';
}
$qr .= " GROUP BY DATE(c.data_candidatura) ORDER BY dia ASC";
$stmt = $conn->prepare($qr);
$stmt->bind_param($rt, ...$rp);
$stmt->execute();
$rep = $stmt->get_result();
$labels = [];
$data = [];
foreach ($rep as $row) {
    $labels[] = $row['dia'];
    $data[] = (int)$row['total'];
}
// Mostrar mensagens de debug
if (isset($_SESSION['msg'])) {
    echo '<div class="alert alert-' . ($_SESSION['tipo_msg'] ?? 'info') . '">' . $_SESSION['msg'] . '</div>';
    unset($_SESSION['msg']);
    unset($_SESSION['tipo_msg']);
}
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
            <a class="navbar-brand" href="#">
                <img src="../img/Logo design for a job search platform named 'Contrata'. Use a modern, technological style with a bol(1) (1).png"
                    alt="Contrata" height="40" />
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown">
                            <?php if (!empty($empresa['url_logo'])): ?>
                                <img src="<?= htmlspecialchars($empresa['url_logo']) ?>"
                                    alt="Logo da Empresa"
                                    class="rounded-circle me-2"
                                    style="width: 32px; height: 32px; object-fit: cover;">
                            <?php else: ?>
                                <span class="material-icons me-2">business</span>
                            <?php endif; ?>
                            <?= htmlspecialchars($empresa['nome']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#" onclick="mostrarEmpresa(event)"><span class="material-icons">business</span> Minha Empresa</a></li>
                            <li>
                                <hr class="dropdown-divider" />
                            </li>
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
                    </ul>
                </div>
            </div>

            <!-- Main -->
            <div class="col-md-9 main-content">

                <!-- Dashboard -->
                <div id="dashboard-section" class="section active" id="dashboard">
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card stat-card">
                                <div class="stat-number"><?= $counts['vagas'] ?></div>
                                <div class="stat-label">Total de Vagas</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card">
                                <div class="stat-number"><?= $counts['vagas_ativas'] ?></div>
                                <div class="stat-label">Vagas Ativas</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card">
                                <div class="stat-number"><?= $counts['candidaturas'] ?></div>
                                <div class="stat-label">Candidaturas</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card">
                                <div class="stat-number"><?= $counts['contratados'] ?></div>
                                <div class="stat-label">Contratados</div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Últimas Vagas Publicadas</h5>
                            <a href="adicionar_vaga.php" class="btn btn-sm btn-primary"><span class="material-icons">add</span> Nova Vaga</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Cargo</th>
                                            <th>Tipo</th>
                                            <th>Candidatos</th>
                                            <th>Status</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($vaga = $ultimas_vagas->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($vaga['titulo']); ?></td>
                                                <td><?= htmlspecialchars($vaga['tipo']); ?></td>
                                                <td>
                                                    <?php $q = $conn->query("SELECT COUNT(*) total FROM candidaturas WHERE id_vaga=" . (int)$vaga['id_vaga']);
                                                    echo (int)($q->fetch_assoc()['total'] ?? 0); ?>
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
                <div id="vagas-section" class="section" style="display:none;" id="vagas">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Gerenciamento de Vagas</h5>
                            <a href="adicionar_vaga.php" class="btn btn-sm btn-primary"><span class="material-icons">add</span> Nova Vaga</a>
                        </div>
                        <div class="card-body">
                            <form method="GET" class="row g-2 mb-3">
                                <input type="hidden" name="anchor" value="vagas">
                                <div class="col-md-4"><input type="text" name="pesquisa" value="<?= htmlspecialchars($pesq) ?>" class="form-control" placeholder="Pesquisar por título"></div>
                                <div class="col-md-3">
                                    <select name="status" class="form-select">
                                        <option value="">Todos os Status</option>
                                        <option value="Ativa" <?= $st === 'Ativa' ? 'selected' : '' ?>>Ativa</option>
                                        <option value="Encerrada" <?= $st === 'Encerrada' ? 'selected' : '' ?>>Encerrada</option>
                                    </select>
                                </div>
                                <div class="col-md-2"><button type="submit" class="btn btn-secondary w-100">Filtrar</button></div>
                                <div class="col-md-2">
                                    <a href="index.php#vagas" class="btn btn-light w-100">Limpar</a>
                                </div>
                            </form>

                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Cargo</th>
                                            <th>Publicação</th>
                                            <th>Candidatos</th>
                                            <th>Status</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($vaga = $todas_vagas->fetch_assoc()): $idv = (int)$vaga['id_vaga']; ?>
                                            <tr>
                                                <td><?= htmlspecialchars($vaga['titulo']); ?></td>
                                                <td><?= $vaga['data_publicacao'] ? date('d/m/Y', strtotime($vaga['data_publicacao'])) : '-' ?></td>
                                                <td><?php $q = $conn->query("SELECT COUNT(*) total FROM candidaturas WHERE id_vaga=$idv");
                                                    echo (int)($q->fetch_assoc()['total'] ?? 0); ?></td>
                                                <td><span class="badge bg-<?= $vaga['status'] === 'Ativa' ? 'success' : 'secondary' ?>"><?php echo htmlspecialchars($vaga['status']); ?></span></td>
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
                <div id="candidatos-section" class="section" style="display:none;" id="candidatos">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Candidatos</h5>
                        </div>
                        <div class="card-body">
                            <form method="GET" class="row g-2 mb-3">
                                <input type="hidden" name="anchor" value="candidatos">
                                <div class="col-md-4"><input type="text" name="cand_nome" value="<?= htmlspecialchars($pc_nom) ?>" class="form-control" placeholder="Nome do candidato"></div>
                                <div class="col-md-3">
                                    <select name="cand_vaga" class="form-select">
                                        <option value="0">Todas as vagas</option>
                                        <?php foreach ($vagas_select as $vs): ?>
                                            <option value="<?= (int)$vs['id_vaga'] ?>" <?= $pc_vag == (int)$vs['id_vaga'] ? 'selected' : '' ?>><?= htmlspecialchars($vs['titulo']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select name="cand_status" class="form-select">
                                        <option value="">Todos os status</option>
                                        <option value="Em análise" <?= $pc_sta === 'Em análise' ? 'selected' : '' ?>>Em análise</option>
                                        <option value="Aprovado" <?= $pc_sta === 'Aprovado' ? 'selected' : '' ?>>Aprovado</option>
                                        <option value="Rejeitado" <?= $pc_sta === 'Rejeitado' ? 'selected' : '' ?>>Rejeitado</option>
                                        <option value="Cancelado" <?= $pc_sta === 'Cancelado' ? 'selected' : '' ?>>Cancelado</option>
                                    </select>
                                </div>
                                <div class="col-md-2"><button class="btn btn-secondary w-100" type="submit">Filtrar</button></div>
                            </form>

                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nome</th>
                                            <th>Vaga</th>
                                            <th>Data</th>
                                            <th>Status</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($cand = $candidatos->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($cand['nome_completo']); ?></td>
                                                <td><?= htmlspecialchars($cand['vaga_titulo']); ?></td>
                                                <td><?= $cand['data_candidatura'] ? date('d/m/Y', strtotime($cand['data_candidatura'])) : '-' ?></td>
                                                <td><?= htmlspecialchars($cand['status']); ?></td>
                                                <td class="d-flex gap-2">
                                                    <form method="POST" action="atualizar_status_candidatura.php" onsubmit="return confirm('Confirmar ação?');">
                                                        <input type="hidden" name="id_candidatura" value="<?= (int)$cand['id_candidatura'] ?>" />
                                                        <select name="novo_status" class="form-select form-select-sm d-inline-block w-auto">
                                                            <option value="Em análise">Em análise</option>
                                                            <option value="Aprovado">Aprovado</option>
                                                            <option value="Rejeitado">Rejeitado</option>
                                                            <option value="Cancelado">Cancelado</option>
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
                <div id="empresa-section" class="section" style="display:none;" id="empresa">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Informações da Empresa</h5>
                        </div>
                        <div class="card-body">
                            <form id="form-empresa" action="atualizar_empresa.php" method="POST" enctype="multipart/form-data">
                                <!-- Campo para upload de imagem -->
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Logo da Empresa</label>
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <img id="preview-logo"
                                                src="<?= !empty($empresa['url_logo']) ? htmlspecialchars($empresa['url_logo']) : '' ?>"
                                                alt="Logo da Empresa"
                                                class="img-thumbnail"
                                                style="max-width: 150px; max-height: 150px; <?= empty($empresa['url_logo']) ? 'display:none;' : '' ?>">
                                        </div>
                                        <div class="flex-grow-1">
                                            <input type="file" name="logo" id="input-logo" class="form-control" accept="image/*">
                                            <div class="form-text">Selecione uma imagem para alterar o logo da empresa.</div>
                                        </div>
                                    </div>
                                </div>

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
                <div id="relatorios-section" class="section" style="display:none;" id="relatorios">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Relatórios</h5>
                        </div>
                        <div class="card-body">
                            <form method="GET" class="row g-2 mb-4">
                                <input type="hidden" name="anchor" value="relatorios">
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
                                        <?php $rs = $_GET['r_status'] ?? '';
                                        foreach (["Pendente", "Aprovado", "Reprovado", "Contratado"] as $opt): ?>
                                            <option value="<?= $opt ?>" <?= $rs === $opt ? 'selected' : '' ?>><?= $opt ?></option>
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
                            $rp = [$id_empresa];
                            $rt = 'i';
                            if ($r_ini !== '') {
                                $qr .= " AND DATE(c.data_candidatura) >= ?";
                                $rp[] = $r_ini;
                                $rt .= 's';
                            }
                            if ($r_fim !== '') {
                                $qr .= " AND DATE(c.data_candidatura) <= ?";
                                $rp[] = $r_fim;
                                $rt .= 's';
                            }
                            if ($r_status !== '') {
                                $qr .= " AND c.status = ?";
                                $rp[] = $r_status;
                                $rt .= 's';
                            }
                            $qr .= " GROUP BY DATE(c.data_candidatura) ORDER BY dia ASC";
                            $stmt = $conn->prepare($qr);
                            $stmt->bind_param($rt, ...$rp);
                            $stmt->execute();
                            $rep = $stmt->get_result();
                            $labels = [];
                            $data = [];
                            foreach ($rep as $row) {
                                $labels[] = $row['dia'];
                                $data[] = (int)$row['total'];
                            }
                            ?>
                            <div class="mb-3">
                                <canvas id="chartCandidaturas"></canvas>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Data</th>
                                            <th>Total de Candidaturas</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_map(null, $labels, $data) as $pair): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($pair[0]) ?></td>
                                                <td><?= (int)$pair[1] ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="text-center py-3 bg-dark text-white">
        <p>&copy; 2025 Contrata</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        function mostrarSecao(id) {
            // Esconder todas as seções
            document.querySelectorAll('.section').forEach(s => s.style.display = 'none');

            // Mostrar a seção desejada
            document.getElementById(id).style.display = 'block';

            // Remover a classe active de todos os itens do menu
            document.querySelectorAll('.sidebar .nav-link').forEach(link => {
                link.classList.remove('active');
            });

            // Adicionar a classe active ao item do menu correspondente
            const menuItems = {
                'dashboard-section': '[onclick*="mostrarDashboard"]',
                'vagas-section': '[onclick*="mostrarVagas"]',
                'candidatos-section': '[onclick*="mostrarCandidatos"]',
                'empresa-section': '[onclick*="mostrarEmpresa"]',
                'relatorios-section': '[onclick*="mostrarRelatorios"]'
            };

            if (menuItems[id]) {
                const activeLink = document.querySelector(`.sidebar .nav-link${menuItems[id]}`);
                if (activeLink) {
                    activeLink.classList.add('active');
                }
            }

            // Salvar a seção atual no sessionStorage
            sessionStorage.setItem('secaoAtiva', id);

            // Atualizar a URL com a âncora correspondente
            const anchorMap = {
                'dashboard-section': 'dashboard',
                'vagas-section': 'vagas',
                'candidatos-section': 'candidatos',
                'empresa-section': 'empresa',
                'relatorios-section': 'relatorios'
            };

            if (anchorMap[id]) {
                history.replaceState(null, null, `#${anchorMap[id]}`);
            }
        }

        function mostrarDashboard(e) {
            if (e) e.preventDefault();
            mostrarSecao('dashboard-section');
        }

        function mostrarVagas(e) {
            if (e) e.preventDefault();
            mostrarSecao('vagas-section');
        }

        function mostrarCandidatos(e) {
            if (e) e.preventDefault();
            mostrarSecao('candidatos-section');
        }

        function mostrarEmpresa(e) {
            if (e) e.preventDefault();
            mostrarSecao('empresa-section');
        }

        function mostrarRelatorios(e) {
            if (e) e.preventDefault();
            mostrarSecao('relatorios-section');
        }

        // Chart.js (Relatórios)
        const ctx = document.getElementById('chartCandidaturas');
        if (ctx) {
            const labels = <?= json_encode($labels ?? []) ?>;
            const data = <?= json_encode($data   ?? []) ?>;
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        label: 'Candidaturas por dia',
                        data,
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // Ao carregar a página, restaurar a seção anterior ou usar a âncora da URL
        document.addEventListener('DOMContentLoaded', function() {
            // Primeiro verificar se há uma âncora na URL
            const hash = window.location.hash;
            if (hash) {
                const sectionMap = {
                    '#dashboard': 'dashboard-section',
                    '#vagas': 'vagas-section',
                    '#candidatos': 'candidatos-section',
                    '#empresa': 'empresa-section',
                    '#relatorios': 'relatorios-section'
                };

                if (sectionMap[hash]) {
                    mostrarSecao(sectionMap[hash]);
                    return;
                }
            }

            // Se não houver âncora, verificar se há uma seção salva no sessionStorage
            const secaoSalva = sessionStorage.getItem('secaoAtiva');
            if (secaoSalva) {
                mostrarSecao(secaoSalva);
            } else {
                // Por padrão, mostrar o dashboard
                mostrarSecao('dashboard-section');
            }
        });

        // Preview da logo
        document.getElementById('input-logo').addEventListener('change', function(event) {
            const file = event.target.files[0];
            const preview = document.getElementById('preview-logo');

            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                preview.src = "";
                preview.style.display = "none";
            }
        });
    </script>
</body>

</html>