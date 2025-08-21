<?php
session_start();
include_once("../db/conexao.php");

// =========================
// Verifica se a empresa está logada
// =========================
if (!isset($_SESSION['id_empresa'])) {
    header("Location: login_empresa.php");
    exit();
}

$id_empresa = $_SESSION['id_empresa'];

/* ============================
   BUSCAR DADOS DA EMPRESA
============================ */
$sqlEmpresa = "SELECT * FROM empresas WHERE id_empresa = ?";
$stmt = $conn->prepare($sqlEmpresa);
if (!$stmt) {
    die("Erro na query de empresa: " . $conn->error);
}
$stmt->bind_param("i", $id_empresa);
$stmt->execute();
$resultEmpresa = $stmt->get_result();
$empresa = $resultEmpresa->fetch_assoc();

/* ============================
   ESTATÍSTICAS
============================ */
// Total de vagas ativas
$sqlVagasAtivas = "SELECT COUNT(*) AS total 
                   FROM vagas 
                   WHERE id_empresa = ? AND ativa = 1";
$stmt = $conn->prepare($sqlVagasAtivas);
if (!$stmt) {
    die("Erro na query vagas ativas: " . $conn->error);
}
$stmt->bind_param("i", $id_empresa);
$stmt->execute();
$totalVagas = $stmt->get_result()->fetch_assoc()['total'];

// Total de candidaturas
$sqlCandidaturas = "SELECT COUNT(*) AS total 
                    FROM candidaturas c
                    INNER JOIN vagas v ON c.id_vaga = v.id_vaga
                    WHERE v.id_empresa = ?";
$stmt = $conn->prepare($sqlCandidaturas);
if (!$stmt) {
    die("Erro na query candidaturas: " . $conn->error);
}
$stmt->bind_param("i", $id_empresa);
$stmt->execute();
$totalCandidaturas = $stmt->get_result()->fetch_assoc()['total'];

// Total de contratados
$sqlContratados = "SELECT COUNT(*) AS total 
                   FROM candidaturas c
                   INNER JOIN vagas v ON c.id_vaga = v.id_vaga
                   WHERE v.id_empresa = ? AND c.status = 'Aprovado'";
$stmt = $conn->prepare($sqlContratados);
if (!$stmt) {
    die("Erro na query contratados: " . $conn->error);
}
$stmt->bind_param("i", $id_empresa);
$stmt->execute();
$totalContratados = $stmt->get_result()->fetch_assoc()['total'];

/* ============================
   ÚLTIMAS VAGAS
============================ */
$sqlUltimasVagas = "SELECT 
                        v.id_vaga, 
                        v.titulo, 
                        v.tipo_contrato, 
                        v.data_publicacao,
                        (SELECT COUNT(*) 
                           FROM candidaturas c 
                          WHERE c.id_vaga = v.id_vaga) AS total_candidatos, 
                        CASE 
                            WHEN v.ativa = 1 THEN 'Ativa' 
                            ELSE 'Encerrada' 
                        END AS status
                    FROM vagas v
                    WHERE v.id_empresa = ?
                    ORDER BY v.data_publicacao DESC
                    LIMIT 3";
$stmt = $conn->prepare($sqlUltimasVagas);
if (!$stmt) {
    die("Erro na query últimas vagas: " . $conn->error);
}
$stmt->bind_param("i", $id_empresa);
$stmt->execute();
$ultimasVagas = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Empresarial | Contrata</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --brand-color: #0b7285;
            --brand-hover: #0d90b8;
            --bg-light: #ffffff;
            --footer-bg: #2c3e50;
            --footer-text: #ecf0f1;
            --footer-hover: #3498db;
            --footer-border: #34495e;
        }

        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: "Poppins", sans-serif;
            background-color: #f8fafb;
        }

        /* Layout principal usando flexbox para ocupar 100% da altura */
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .navbar-brand img {
            width: 90px;
        }

        /* Container principal expandido */
        .dashboard-container {
            flex: 1;
            width: 100%;
            max-width: none;
            margin: 0;
            padding: 0;
        }

        /* Ajuste do grid principal */
        .dashboard-container>.row {
            margin: 0;
            height: 100%;
        }

        /* Sidebar com altura total */
        .sidebar-container {
            padding: 0;
            background-color: white;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.08);
            height: 100%;
        }

        .sidebar {
            height: 100%;
            padding: 20px;
            overflow-y: auto;
        }

        /* Conteúdo principal expandido */
        .main-content {
            padding: 20px;
            height: 100%;
            overflow-y: auto;
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
            border: none;
        }

        .card-header {
            background-color: var(--brand-color);
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }

        .btn-brand {
            background-color: var(--brand-color);
            color: white;
        }

        .btn-brand:hover {
            background-color: var(--brand-hover);
            color: white;
        }

        .stat-card {
            text-align: center;
            padding: 20px;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--brand-color);
        }

        .stat-label {
            color: #6c757d;
        }

        .vaga-status {
            font-weight: bold;
        }

        .status-ativa {
            color: #28a745;
        }

        .status-pausada {
            color: #ffc107;
        }

        .status-encerrada {
            color: #dc3545;
        }

        .sidebar .nav-link {
            color: #495057;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 5px;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: rgba(11, 114, 133, 0.1);
            color: var(--brand-color);
        }

        .sidebar .nav-link .material-icons {
            vertical-align: middle;
            margin-right: 10px;
        }

        .modal-vaga {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            border-radius: 10px;
            width: 80%;
            max-width: 700px;
        }

        .close-modal {
            float: right;
            cursor: pointer;
            font-size: 1.5rem;
        }

        /* Estilo para seções ocultas */
        .section {
            display: none;
        }

        .section.active {
            display: block;
        }

        /* Estilo para formulário de configurações */
        .config-section {
            margin-bottom: 30px;
        }

        .config-section h4 {
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        /* FOOTER COMPACTO */
        footer {
            background-color: var(--footer-bg);
            color: var(--footer-text);
            margin-top: auto;
            padding: 0;
            font-size: 0.9rem;
        }

        .footer-main {
            padding: 30px 0 15px;
        }

        .footer-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
            position: relative;
            padding-bottom: 8px;
        }

        .footer-title::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 30px;
            height: 2px;
            background-color: var(--brand-color);
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin-bottom: 8px;
        }

        .footer-links a {
            color: var(--footer-text);
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }

        .footer-links a:hover {
            color: var(--footer-hover);
            padding-left: 3px;
        }

        .footer-links a i {
            margin-right: 6px;
            font-size: 12px;
        }

        .contact-info {
            margin-bottom: 10px;
            display: flex;
            align-items: flex-start;
        }

        .contact-info i {
            margin-right: 8px;
            margin-top: 3px;
            color: var(--brand-color);
            font-size: 14px;
        }

        .contact-info div {
            font-size: 0.85rem;
        }

        .social-links {
            display: flex;
            margin-top: 15px;
        }

        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--footer-text);
            margin-right: 8px;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .social-links a:hover {
            background-color: var(--brand-color);
            transform: translateY(-2px);
        }

        .newsletter-form {
            display: flex;
            margin-top: 12px;
        }

        .newsletter-input {
            flex-grow: 1;
            padding: 8px 12px;
            border: none;
            border-radius: 4px 0 0 4px;
            outline: none;
            font-size: 0.85rem;
        }

        .newsletter-btn {
            background-color: var(--brand-color);
            color: white;
            border: none;
            padding: 0 12px;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .newsletter-btn:hover {
            background-color: var(--brand-hover);
        }

        .footer-bottom {
            background-color: rgba(0, 0, 0, 0.2);
            padding: 12px 0;
            border-top: 1px solid var(--footer-border);
            font-size: 0.8rem;
        }

        .payment-methods {
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }

        .payment-methods span {
            margin-right: 5px;
            font-size: 0.8rem;
        }

        .payment-methods i {
            font-size: 20px;
            margin-left: 8px;
            color: #ddd;
        }

        .footer-about p {
            font-size: 0.85rem;
            line-height: 1.4;
        }

        .back-to-top {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--brand-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .back-to-top.show {
            opacity: 1;
            visibility: visible;
        }

        .back-to-top:hover {
            background-color: var(--brand-hover);
            transform: translateY(-3px);
        }

        /* Responsividade do footer */
        @media (max-width: 768px) {
            .footer-main {
                text-align: center;
                padding: 20px 0 10px;
            }

            .footer-title::after {
                left: 50%;
                transform: translateX(-50%);
            }

            .social-links {
                justify-content: center;
            }

            .payment-methods {
                justify-content: center;
                margin-top: 10px;
            }

            .contact-info {
                justify-content: center;
            }

            .footer-main .row>div {
                margin-bottom: 20px;
            }
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <img src="../img/logo-empresa.png" alt="Contrata">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <span class="material-icons">account_circle</span> <?= htmlspecialchars($empresa['nome']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#" onclick="mostrarConfiguracoes(event)"><span class="material-icons">settings</span> Configurações</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="../logout.php"><span class="material-icons">logout</span> Sair</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Dashboard -->
    <div class="dashboard-container">
        <div class="row g-0 h-100">
            <!-- Sidebar -->
            <div class="col-md-3 sidebar-container">
                <div class="sidebar">
                    <h5 class="mb-4">Menu</h5>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="#" onclick="mostrarDashboard(event)">
                                <span class="material-icons">dashboard</span> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" onclick="mostrarVagas(event)">
                                <span class="material-icons">work</span> Vagas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" onclick="mostrarCandidatos(event)">
                                <span class="material-icons">people</span> Candidatos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" onclick="mostrarEmpresa(event)">
                                <span class="material-icons">business</span> Empresa
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" onclick="mostrarRelatorios(event)">
                                <span class="material-icons">analytics</span> Relatórios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" onclick="mostrarConfiguracoes(event)">
                                <span class="material-icons">settings</span> Configurações
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 main-content">
                <!-- Seção Dashboard -->
                <div id="dashboard-section" class="section active">
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card stat-card">
                                <div class="stat-number" id="vagas-ativas"><?= $totalVagas ?></div>
                                <div class="stat-label">Vagas Ativas</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card stat-card">
                                <div class="stat-number" id="total-candidaturas"><?= $totalCandidaturas ?></div>
                                <div class="stat-label">Candidaturas</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card stat-card">
                                <div class="stat-number" id="total-contratados"><?= $totalContratados ?></div>
                                <div class="stat-label">Contratados</div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Últimas Vagas Publicadas</h5>
                            <button class="btn btn-sm btn-light" onclick="mostrarVagas()">Ver Todas</button>
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
                                        <?php while ($vaga = $ultimasVagas->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($vaga['titulo']) ?></td>
                                                <td><?= htmlspecialchars($vaga['tipo_contrato']) ?></td>
                                                <td><?= $vaga['total_candidatos'] ?></td>
                                                <td><span class="vaga-status <?= $vaga['status'] == 'Ativa' ? 'status-ativa' : 'status-encerrada' ?>"><?= $vaga['status'] ?></span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="verDetalhesVaga(<?= $vaga['id_vaga'] ?>)">Ver</button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Seção Vagas -->
                <div id="vagas-section" class="section">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Gerenciamento de Vagas</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-4">
                                <button class="btn btn-brand" onclick="mostrarFormularioVaga()">
                                    <span class="material-icons">add</span> Nova Vaga
                                </button>
                                <div class="w-50">
                                    <div class="input-group">
                                        <input type="text" class="form-control" placeholder="Buscar vagas...">
                                        <button class="btn btn-outline-secondary" type="button">Buscar</button>
                                    </div>
                                </div>
                            </div>

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
                                        <?php
                                        // Reset o ponteiro do resultado
                                        $todasVagas->data_seek(0);
                                        while ($vaga = $todasVagas->fetch_assoc()):
                                            $dataFormatada = date('d/m/Y', strtotime($vaga['data_publicacao']));
                                        ?>
                                            <tr>
                                                <td><?= htmlspecialchars($vaga['titulo']) ?></td>
                                                <td><?= $dataFormatada ?></td>
                                                <td><?= $vaga['total_candidatos'] ?></td>
                                                <td><span class="vaga-status <?= $vaga['status'] == 'Ativa' ? 'status-ativa' : 'status-encerrada' ?>"><?= $vaga['status'] ?></span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="verDetalhesVaga(<?= $vaga['id_vaga'] ?>)">Ver</button>
                                                    <button class="btn btn-sm btn-outline-success" onclick="editarVaga(<?= $vaga['id_vaga'] ?>)">Editar</button>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="confirmarExclusao(<?= $vaga['id_vaga'] ?>)">Excluir</button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Seção Candidatos -->
                <div id="candidatos-section" class="section">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Candidatos</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <div class="input-group w-50">
                                    <input type="text" class="form-control" placeholder="Buscar candidatos...">
                                    <button class="btn btn-outline-secondary" type="button">Buscar</button>
                                </div>
                            </div>

                            <ul class="nav nav-tabs mb-4" id="candidatosTab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="todos-tab" data-bs-toggle="tab" data-bs-target="#todos" type="button" role="tab">Todos</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="analise-tab" data-bs-toggle="tab" data-bs-target="#analise" type="button" role="tab">Em Análise</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="aprovados-tab" data-bs-toggle="tab" data-bs-target="#aprovados" type="button" role="tab">Aprovados</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="rejeitados-tab" data-bs-toggle="tab" data-bs-target="#rejeitados" type="button" role="tab">Rejeitados</button>
                                </li>
                            </ul>

                            <div class="tab-content" id="candidatosTabContent">
                                <div class="tab-pane fade show active" id="todos" role="tabpanel">
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
                                                <?php
                                                // Reset o ponteiro do resultado
                                                $candidatos->data_seek(0);
                                                while ($candidato = $candidatos->fetch_assoc()):
                                                    $badgeClass = '';
                                                    if ($candidato['status'] == 'Aprovado') $badgeClass = 'bg-success';
                                                    elseif ($candidato['status'] == 'Em Análise') $badgeClass = 'bg-warning';
                                                    else $badgeClass = 'bg-danger';
                                                ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($candidato['nome']) ?></td>
                                                        <td><?= htmlspecialchars($candidato['vaga']) ?></td>
                                                        <td><?= $candidato['data_candidatura'] ?></td>
                                                        <td><span class="badge <?= $badgeClass ?>"><?= $candidato['status'] ?></span></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-primary" onclick="verCandidato(<?= $candidato['id_candidatura'] ?>)">Ver</button>
                                                            <?php if ($candidato['status'] == 'Em Análise'): ?>
                                                                <button class="btn btn-sm btn-outline-success" onclick="aprovarCandidato(<?= $candidato['id_candidatura'] ?>)">Aprovar</button>
                                                                <button class="btn btn-sm btn-outline-danger" onclick="rejeitarCandidato(<?= $candidato['id_candidatura'] ?>)">Rejeitar</button>
                                                            <?php elseif ($candidato['status'] == 'Aprovado'): ?>
                                                                <button class="btn btn-sm btn-outline-secondary" onclick="marcarEntrevista(<?= $candidato['id_candidatura'] ?>)">Entrevista</button>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="analise" role="tabpanel">
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
                                                <?php
                                                // Reset o ponteiro do resultado
                                                $candidatos->data_seek(0);
                                                while ($candidato = $candidatos->fetch_assoc()):
                                                    if ($candidato['status'] != 'Em Análise') continue;
                                                ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($candidato['nome']) ?></td>
                                                        <td><?= htmlspecialchars($candidato['vaga']) ?></td>
                                                        <td><?= $candidato['data_candidatura'] ?></td>
                                                        <td><span class="badge bg-warning"><?= $candidato['status'] ?></span></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-primary" onclick="verCandidato(<?= $candidato['id_candidatura'] ?>)">Ver</button>
                                                            <button class="btn btn-sm btn-outline-success" onclick="aprovarCandidato(<?= $candidato['id_candidatura'] ?>)">Aprovar</button>
                                                            <button class="btn btn-sm btn-outline-danger" onclick="rejeitarCandidato(<?= $candidato['id_candidatura'] ?>)">Rejeitar</button>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="aprovados" role="tabpanel">
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
                                                <?php
                                                // Reset o ponteiro do resultado
                                                $candidatos->data_seek(0);
                                                while ($candidato = $candidatos->fetch_assoc()):
                                                    if ($candidato['status'] != 'Aprovado') continue;
                                                ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($candidato['nome']) ?></td>
                                                        <td><?= htmlspecialchars($candidato['vaga']) ?></td>
                                                        <td><?= $candidato['data_candidatura'] ?></td>
                                                        <td><span class="badge bg-success"><?= $candidato['status'] ?></span></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-primary" onclick="verCandidato(<?= $candidato['id_candidatura'] ?>)">Ver</button>
                                                            <button class="btn btn-sm btn-outline-secondary" onclick="marcarEntrevista(<?= $candidato['id_candidatura'] ?>)">Entrevista</button>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="rejeitados" role="tabpanel">
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
                                                <?php
                                                // Reset o ponteiro do resultado
                                                $candidatos->data_seek(0);
                                                while ($candidato = $candidatos->fetch_assoc()):
                                                    if ($candidato['status'] != 'Rejeitado') continue;
                                                ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($candidato['nome']) ?></td>
                                                        <td><?= htmlspecialchars($candidato['vaga']) ?></td>
                                                        <td><?= $candidato['data_candidatura'] ?></td>
                                                        <td><span class="badge bg-danger"><?= $candidato['status'] ?></span></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-primary" onclick="verCandidato(<?= $candidato['id_candidatura'] ?>)">Ver</button>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Seção Empresa -->
                <div id="empresa-section" class="section">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Informações da Empresa</h5>
                        </div>
                        <div class="card-body">
                            <form id="form-empresa" action="atualizar_empresa.php" method="POST">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="empresa-nome" class="form-label">Nome da Empresa*</label>
                                        <input type="text" class="form-control" id="empresa-nome" name="nome" value="<?= htmlspecialchars($empresa['nome']) ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="empresa-cnpj" class="form-label">CNPJ*</label>
                                        <input type="text" class="form-control" id="empresa-cnpj" name="cnpj" value="<?= htmlspecialchars($empresa['cnpj']) ?>" required>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="empresa-email" class="form-label">E-mail*</label>
                                        <input type="email" class="form-control" id="empresa-email" name="email" value="<?= htmlspecialchars($empresa['email']) ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="empresa-telefone" class="form-label">Telefone*</label>
                                        <input type="tel" class="form-control" id="empresa-telefone" name="telefone" value="<?= htmlspecialchars($empresa['telefone']) ?>" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="empresa-descricao" class="form-label">Descrição da Empresa*</label>
                                    <textarea class="form-control" id="empresa-descricao" name="descricao" rows="4" required><?= htmlspecialchars($empresa['descricao']) ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="empresa-endereco" class="form-label">Endereço</label>
                                    <textarea class="form-control" id="empresa-endereco" name="endereco" rows="2"><?= htmlspecialchars($empresa['endereco']) ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="empresa-site" class="form-label">Site</label>
                                    <input type="url" class="form-control" id="empresa-site" name="website" value="<?= htmlspecialchars($empresa['website']) ?>">
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="button" class="btn btn-outline-secondary" onclick="cancelarEdicaoEmpresa()">Cancelar</button>
                                    <button type="submit" class="btn btn-brand">Salvar Alterações</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Seção Relatórios -->
                <div id="relatorios-section" class="section">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Relatórios</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title">Candidaturas por Vaga</h5>
                                            <div id="grafico-candidaturas" style="height: 200px; background-color: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                                                [Gráfico de Candidaturas]
                                            </div>
                                            <button class="btn btn-sm btn-outline-primary mt-3 w-100" onclick="gerarRelatorio('candidaturas')">Gerar Relatório</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title">Status de Candidatos</h5>
                                            <div id="grafico-status" style="height: 200px; background-color: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                                                [Gráfico de Status]
                                            </div>
                                            <button class="btn btn-sm btn-outline-primary mt-3 w-100" onclick="gerarRelatorio('status')">Gerar Relatório</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title">Contratações</h5>
                                            <div id="grafico-contratacoes" style="height: 200px; background-color: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                                                [Gráfico de Contratações]
                                            </div>
                                            <button class="btn btn-sm btn-outline-primary mt-3 w-100" onclick="gerarRelatorio('contratacoes')">Gerar Relatório</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Relatório Personalizado</h5>
                                </div>
                                <div class="card-body">
                                    <form id="form-relatorio" action="gerar_relatorio.php" method="POST">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="relatorio-tipo" class="form-label">Tipo de Relatório*</label>
                                                <select class="form-select" id="relatorio-tipo" name="tipo" required>
                                                    <option value="">Selecione...</option>
                                                    <option value="candidaturas">Candidaturas</option>
                                                    <option value="vagas">Vagas Publicadas</option>
                                                    <option value="contratacoes">Contratações</option>
                                                    <option value="desempenho">Desempenho de Vagas</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="relatorio-periodo" class="form-label">Período*</label>
                                                <select class="form-select" id="relatorio-periodo" name="periodo" required>
                                                    <option value="">Selecione...</option>
                                                    <option value="7">Últimos 7 dias</option>
                                                    <option value="30">Últimos 30 dias</option>
                                                    <option value="90">Últimos 3 meses</option>
                                                    <option value="custom">Personalizado</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row mb-3" id="custom-periodo" style="display: none;">
                                            <div class="col-md-6">
                                                <label for="relatorio-data-inicio" class="form-label">Data Início</label>
                                                <input type="date" class="form-control" id="relatorio-data-inicio" name="data_inicio">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="relatorio-data-fim" class="form-label">Data Fim</label>
                                                <input type="date" class="form-control" id="relatorio-data-fim" name="data_fim">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="relatorio-formato" class="form-label">Formato*</label>
                                            <select class="form-select" id="relatorio-formato" name="formato" required>
                                                <option value="">Selecione...</option>
                                                <option value="pdf">PDF</option>
                                                <option value="excel">Excel</option>
                                                <option value="csv">CSV</option>
                                            </select>
                                        </div>

                                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                            <button type="button" class="btn btn-outline-secondary" onclick="limparFormularioRelatorio()">Limpar</button>
                                            <button type="submit" class="btn btn-brand">Gerar Relatório</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Seção Configurações -->
                <div id="configuracoes-section" class="section">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Configurações da Conta</h5>
                        </div>
                        <div class="card-body">
                            <div class="config-section">
                                <h4>Informações do Usuário</h4>
                                <form id="form-usuario" action="atualizar_usuario.php" method="POST">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="usuario-nome" class="form-label">Nome Completo*</label>
                                            <input type="text" class="form-control" id="usuario-nome" name="nome" value="<?= htmlspecialchars($usuario['nome']) ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="usuario-email" class="form-label">E-mail*</label>
                                            <input type="email" class="form-control" id="usuario-email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" required>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="usuario-cargo" class="form-label>">Cargo*</label>
                                            <input type="text" class="form-control" id="usuario-cargo" name="cargo" value="<?= htmlspecialchars($usuario['cargo']) ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="usuario-telefone" class="form-label">Telefone</label>
                                            <input type="tel" class="form-control" id="usuario-telefone" name="telefone" value="<?= htmlspecialchars($usuario['telefone']) ?>">
                                        </div>
                                    </div>

                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <button type="button" class="btn btn-outline-secondary" onclick="cancelarEdicaoUsuario()">Cancelar</button>
                                        <button type="submit" class="btn btn-brand">Salvar Alterações</button>
                                    </div>
                                </form>
                            </div>

                            <div class="config-section">
                                <h4>Alterar Senha</h4>
                                <form id="form-senha" action="alterar_senha.php" method="POST">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="senha-atual" class="form-label">Senha Atual*</label>
                                            <input type="password" class="form-control" id="senha-atual" name="senha_atual" required>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="nova-senha" class="form-label">Nova Senha*</label>
                                            <input type="password" class="form-control" id="nova-senha" name="nova_senha" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="confirmar-senha" class="form-label">Confirmar Nova Senha*</label>
                                            <input type="password" class="form-control" id="confirmar-senha" name="confirmar_senha" required>
                                        </div>
                                    </div>

                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <button type="button" class="btn btn-outline-secondary" onclick="limparFormularioSenha()">Cancelar</button>
                                        <button type="submit" class="btn btn-brand">Alterar Senha</button>
                                    </div>
                                </form>
                            </div>

                            <div class="config-section">
                                <h4>Preferências</h4>
                                <form id="form-preferencias" action="salvar_preferencias.php" method="POST">
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="notificacoes-email" name="notificacoes_email" <?= $usuario['notificacoes_email'] ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="notificacoes-email">Receber notificações por e-mail</label>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="notificacoes-app" name="notificacoes_app" <?= $usuario['notificacoes_app'] ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="notificacoes-app">Receber notificações no aplicativo</label>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="idioma" class="form-label">Idioma</label>
                                        <select class="form-select" id="idioma" name="idioma">
                                            <option value="pt" <?= $usuario['idioma'] == 'pt' ? 'selected' : '' ?>>Português</option>
                                            <option value="en" <?= $usuario['idioma'] == 'en' ? 'selected' : '' ?>>Inglês</option>
                                            <option value="es" <?= $usuario['idioma'] == 'es' ? 'selected' : '' ?>>Espanhol</option>
                                        </select>
                                    </div>

                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <button type="button" class="btn btn-outline-secondary" onclick="resetarPreferencias()">Redefinir</button>
                                        <button type="submit" class="btn btn-brand">Salvar Preferências</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Formulário de Vaga (oculto inicialmente) -->
                <div id="form-vaga-section" class="section" style="display: none;">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Publicar Nova Vaga</h5>
                            <button class="btn btn-sm btn-outline-secondary" onclick="ocultarFormularioVaga()">Cancelar</button>
                        </div>
                        <div class="card-body">
                            <form id="form-vaga" action="publicar_vaga.php" method="POST">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="cargo" class="form-label">Cargo*</label>
                                        <input type="text" class="form-control" id="cargo" name="titulo" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="tipo" class="form-label">Tipo de Contratação*</label>
                                        <select class="form-select" id="tipo" name="tipo_contrato" required>
                                            <option value="">Selecione</option>
                                            <option value="CLT">CLT</option>
                                            <option value="PJ">PJ</option>
                                            <option value="Freelancer">Freelancer</option>
                                            <option value="Estágio">Estágio</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="salario" class="form-label">Faixa Salarial</label>
                                        <input type="text" class="form-control" id="salario" name="faixa_salarial" placeholder="Ex: R$ 3.000,00 - R$ 5.000,00">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="local" class="form-label">Localização*</label>
                                        <input type="text" class="form-control" id="local" name="localizacao" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="descricao" class="form-label">Descrição da Vaga*</label>
                                    <textarea class="form-control" id="descricao" name="descricao" rows="4" required></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="requisitos" class="form-label">Requisitos*</label>
                                    <textarea class="form-control" id="requisitos" name="requisitos" rows="4" required></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="beneficios" class="form-label">Benefícios</label>
                                    <textarea class="form-control" id="beneficios" name="beneficios" rows="2"></textarea>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-brand">Publicar Vaga</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Detalhes da Vaga -->
    <div id="modal-vaga" class="modal-vaga">
        <div class="modal-content">
            <span class="close-modal" onclick="fecharModal()">&times;</span>
            <h3 id="modal-titulo">Detalhes da Vaga</h3>
            <div id="modal-conteudo">
                <!-- Conteúdo será preenchido por JavaScript -->
            </div>
        </div>
    </div>

    <!-- Modal de Candidato -->
    <div id="modal-candidato" class="modal-vaga">
        <div class="modal-content">
            <span class="close-modal" onclick="fecharModalCandidato()">&times;</span>
            <h3 id="modal-candidato-titulo">Detalhes do Candidato</h3>
            <div id="modal-candidato-conteudo">
                <!-- Conteúdo será preenchido por JavaScript -->
            </div>
        </div>
    </div>

    <!-- Footer Compacto -->
    <footer>
        <div class="container-fluid footer-main">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4 mb-md-0">
                    <div class="footer-about">
                        <img src="../img/logo-empresa.png" alt="Contrata" width="100" class="mb-2">
                        <p class="mb-2">Soluções completas de recrutamento e seleção para empresas.</p>
                        <div class="social-links">
                            <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                            <a href="#" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                            <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-2 col-md-6 mb-4 mb-md-0">
                    <h5 class="footer-title">Links</h5>
                    <ul class="footer-links">
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Início</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Serviços</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Planos</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> FAQ</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                    <h5 class="footer-title">Contato</h5>
                    <div class="contact-info">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>Av. Paulista, 1000 - São Paulo/SP</div>
                    </div>
                    <div class="contact-info">
                        <i class="fas fa-phone"></i>
                        <div>(11) 3456-7890</div>
                    </div>
                    <div class="contact-info">
                        <i class="fas fa-envelope"></i>
                        <div>contato@contrata.com.br</div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                    <h5 class="footer-title">Newsletter</h5>
                    <p class="mb-2">Receba nossas atualizações</p>
                    <form class="newsletter-form">
                        <input type="email" class="newsletter-input" placeholder="Seu e-mail" required>
                        <button type="submit" class="newsletter-btn"><i class="fas fa-paper-plane"></i></button>
                    </form>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <p class="mb-0">&copy; 2025 Contrata. Todos os direitos reservados.</p>
                    </div>
                    <div class="col-md-6">
                        <div class="payment-methods">
                            <i class="fab fa-cc-visa" title="Visa"></i>
                            <i class="fab fa-cc-mastercard" title="Mastercard"></i>
                            <i class="fab fa-cc-amex" title="American Express"></i>
                            <i class="fab fa-cc-paypal" title="PayPal"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Botão Voltar ao Topo -->
    <a href="#" class="back-to-top" id="backToTop">
        <i class="fas fa-arrow-up"></i>
    </a>

    <!-- Bootstrap JS -->
    <script>
        function mostrarDashboard(e) {
            e.preventDefault();
            mostrarSecao("dashboard-section");
        }

        function mostrarVagas(e) {
            e.preventDefault();
            mostrarSecao("vagas-section");
        }

        function mostrarCandidatos(e) {
            e.preventDefault();
            mostrarSecao("candidatos-section");
        }

        function mostrarEmpresa(e) {
            e.preventDefault();
            mostrarSecao("empresa-section");
        }

        function mostrarRelatorios(e) {
            e.preventDefault();
            mostrarSecao("relatorios-section");
        }

        function mostrarConfiguracoes(e) {
            e.preventDefault();
            mostrarSecao("config-section");
        }

        function mostrarSecao(id) {
            document.querySelectorAll(".section").forEach(sec => {
                sec.classList.remove("active");
            });
            document.getElementById(id).classList.add("active");
        }
    </script>

</body>

</html>