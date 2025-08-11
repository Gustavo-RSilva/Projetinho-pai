<?php
session_start();
require_once('../db/conexao.php');

if (!isset($_SESSION['id_usuario'])) {
    header('Location: Login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: pag-candidaturas.php');
    exit;
}

$candidatura_id = $_GET['id'];
$usuario_id = $_SESSION['id_usuario'];

// Busca os detalhes da candidatura
$sql = "SELECT c.*, v.*, e.nome as empresa_nome, e.descricao as empresa_descricao, 
               e.url_logo as empresa_logo, e.website as empresa_website,
               cr.pdf_caminho as curriculo_caminho
        FROM candidaturas c
        JOIN vagas v ON c.id_vaga = v.id_vaga
        JOIN empresas e ON v.id_empresa = e.id_empresa
        JOIN Curriculo cr ON c.id_curriculo = cr.id_curriculo
        WHERE c.id_candidatura = ? AND c.id_usuario = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $candidatura_id, $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$candidatura = $result->fetch_assoc();

if (!$candidatura) {
    header('Location: pag-candidaturas.php');
    exit;
}

// Formata a data
$data_candidatura = date('d/m/Y \à\s H:i', strtotime($candidatura['data_candidatura']));
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Detalhes da Candidatura - Contrata</title>
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Google Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
    <link rel="stylesheet" href="css/style.css">
    <style>
        .company-logo-detail {
            width: 100px;
            height: 100px;
            object-fit: contain;
            border-radius: 8px;
        }
        .status-badge {
            font-size: 1rem;
            padding: 0.5em 0.8em;
        }
        .job-detail-card {
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .section-title {
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <!-- Seu navbar existente aqui -->

    <div class="container my-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card job-detail-card mb-4">
                    <div class="card-body">
                        <!-- Cabeçalho com informações da vaga -->
                        <div class="d-flex align-items-start mb-4">
                            <?php if (!empty($candidatura['empresa_logo'])): ?>
                                <img src="<?= htmlspecialchars($candidatura['empresa_logo']) ?>" 
                                     alt="<?= htmlspecialchars($candidatura['empresa_nome']) ?>" 
                                     class="company-logo-detail me-4">
                            <?php endif; ?>
                            <div>
                                <h3><?= htmlspecialchars($candidatura['titulo']) ?></h3>
                                <h5 class="text-muted"><?= htmlspecialchars($candidatura['empresa_nome']) ?></h5>
                                <span class="badge status-badge 
                                    <?= $candidatura['status'] == 'Aprovado' ? 'bg-success' : 
                                       ($candidatura['status'] == 'Rejeitado' ? 'bg-danger' : 
                                       ($candidatura['status'] == 'Cancelado' ? 'bg-secondary' : 'bg-warning text-dark')) ?>">
                                    <?= htmlspecialchars($candidatura['status']) ?>
                                </span>
                                <p class="mt-2 mb-0">
                                    <strong>Data da Candidatura:</strong> <?= $data_candidatura ?>
                                </p>
                            </div>
                        </div>

                        <!-- Detalhes da vaga -->
                        <div class="mb-5">
                            <h4 class="section-title">Detalhes da Vaga</h4>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p><strong>Tipo de Contrato:</strong> <?= htmlspecialchars($candidatura['tipo_contrato']) ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Localização:</strong> 
                                        <?= htmlspecialchars($candidatura['remoto'] ? 'Remoto' : $candidatura['localizacao']) ?>
                                    </p>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p><strong>Faixa Salarial:</strong> 
                                        <?= !empty($candidatura['faixa_salarial']) ? htmlspecialchars($candidatura['faixa_salarial']) : 'A combinar' ?>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Data de Expiração:</strong> 
                                        <?= date('d/m/Y', strtotime($candidatura['data_expiracao'])) ?>
                                    </p>
                                </div>
                            </div>
                            
                            <h5 class="mt-4">Descrição da Vaga</h5>
                            <div class="bg-light p-3 rounded">
                                <?= nl2br(htmlspecialchars($candidatura['descricao'])) ?>
                            </div>
                        </div>

                        <!-- Sobre a empresa -->
                        <div class="mb-5">
                            <h4 class="section-title">Sobre a Empresa</h4>
                            <div class="bg-light p-3 rounded">
                                <?= nl2br(htmlspecialchars($candidatura['empresa_descricao'])) ?>
                                <?php if (!empty($candidatura['empresa_website'])): ?>
                                    <p class="mt-2 mb-0">
                                        <a href="<?= htmlspecialchars($candidatura['empresa_website']) ?>" target="_blank">
                                            Visitar website
                                        </a>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Currículo enviado -->
                        <div class="mb-4">
                            <h4 class="section-title">Currículo Enviado</h4>
                            <div class="d-flex align-items-center">
                                <span class="material-icons me-2">description</span>
                                <a href="<?= htmlspecialchars($candidatura['curriculo_caminho']) ?>" target="_blank">
                                    Visualizar currículo enviado
                                </a>
                            </div>
                        </div>

                        <!-- Observações -->
                        <?php if (!empty($candidatura['observacoes'])): ?>
                            <div class="mb-4">
                                <h4 class="section-title">Observações</h4>
                                <div class="alert alert-info">
                                    <?= nl2br(htmlspecialchars($candidatura['observacoes'])) ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Ações -->
                        <div class="d-flex justify-content-between mt-4">
                            <a href="pag-candidaturas.php" class="btn btn-secondary">
                                <span class="material-icons align-middle">arrow_back</span> Voltar
                            </a>
                            <?php if ($candidatura['status'] == 'Em análise'): ?>
                                <a href="cancelar-candidatura.php?id=<?= $candidatura['id_candidatura'] ?>" 
                                   class="btn btn-danger"
                                   onclick="return confirm('Tem certeza que deseja cancelar esta candidatura?')">
                                    <span class="material-icons align-middle">close</span> Cancelar Candidatura
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Seu footer existente aqui -->

    <!-- Bootstrap 5 JS Bundle (Popper + Bootstrap JS) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$stmt->close();
?>