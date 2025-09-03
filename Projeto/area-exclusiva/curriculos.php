<?php
session_start();
include_once("../db/conexao.php");

if (!isset($_SESSION['id_usuario'])) {
    header("Location: Login.html");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

// Consulta currículos do usuário
$sql_curriculos = "SELECT id_curriculo, pdf_nome, pdf_caminho, data_envio FROM Curriculo WHERE id_usuario = ?";
$stmt_curriculos = $conn->prepare($sql_curriculos);
$stmt_curriculos->bind_param("i", $id_usuario);
$stmt_curriculos->execute();
$result_curriculos = $stmt_curriculos->get_result();

// Consulta formações
$sql_formacoes = "SELECT f.* FROM formacoes f 
                 JOIN Curriculo c ON f.id_curriculo = c.id_curriculo 
                 WHERE c.id_usuario = ?";
$stmt_formacoes = $conn->prepare($sql_formacoes);
$stmt_formacoes->bind_param("i", $id_usuario);
$stmt_formacoes->execute();
$result_formacoes = $stmt_formacoes->get_result();

// Consulta experiências
$sql_experiencias = "SELECT e.* FROM experiencias e 
                    JOIN Curriculo c ON e.id_curriculo = c.id_curriculo 
                    WHERE c.id_usuario = ?";
$stmt_experiencias = $conn->prepare($sql_experiencias);
$stmt_experiencias->bind_param("i", $id_usuario);
$stmt_experiencias->execute();
$result_experiencias = $stmt_experiencias->get_result();

// Consulta habilidades
$sql_habilidades = "SELECT h.* FROM habilidades h 
                   JOIN Curriculo c ON h.id_curriculo = c.id_curriculo 
                   WHERE c.id_usuario = ?";
$stmt_habilidades = $conn->prepare($sql_habilidades);
$stmt_habilidades->bind_param("i", $id_usuario);
$stmt_habilidades->execute();
$result_habilidades = $stmt_habilidades->get_result();

// Processar upload de novo currículo
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["novo_curriculo"])) {
    $target_dir = "../arquivos/curriculos/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $target_file = $target_dir . basename($_FILES["novo_curriculo"]["name"]);
    $uploadOk = 1;
    $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Verificar se é um PDF
    if ($fileType != "pdf") {
        $erro = "Apenas arquivos PDF são permitidos.";
        $uploadOk = 0;
    }

    // Verificar tamanho do arquivo (5MB máximo)
    if ($_FILES["novo_curriculo"]["size"] > 5000000) {
        $erro = "O arquivo é muito grande. Tamanho máximo: 5MB.";
        $uploadOk = 0;
    }

    if ($uploadOk == 1) {
        if (move_uploaded_file($_FILES["novo_curriculo"]["tmp_name"], $target_file)) {
            $pdf_nome = basename($_FILES["novo_curriculo"]["name"]);
            $pdf_tipo = $_FILES["novo_curriculo"]["type"];
            $pdf_caminho = $target_file;

            $sql_insert = "INSERT INTO Curriculo (id_usuario, pdf_nome, pdf_tipo, pdf_caminho) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql_insert);
            $stmt->bind_param("isss", $id_usuario, $pdf_nome, $pdf_tipo, $pdf_caminho);

            if ($stmt->execute()) {
                $sucesso = "Currículo enviado com sucesso!";
                header("Refresh:0");
            } else {
                $erro = "Erro ao salvar no banco de dados: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $erro = "Ocorreu um erro ao enviar o arquivo.";
        }
    }
}

// Processar exclusão de currículo
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["excluir_curriculo"])) {
    $id_curriculo = $_POST["id_curriculo"];

    // Primeiro, excluir registros relacionados
    $sql_delete_relacionados = "DELETE f, e, h FROM Curriculo c
                              LEFT JOIN formacoes f ON c.id_curriculo = f.id_curriculo
                              LEFT JOIN experiencias e ON c.id_curriculo = e.id_curriculo
                              LEFT JOIN habilidades h ON c.id_curriculo = h.id_curriculo
                              WHERE c.id_curriculo = ? AND c.id_usuario = ?";

    $stmt = $conn->prepare($sql_delete_relacionados);
    $stmt->bind_param("ii", $id_curriculo, $id_usuario);
    $stmt->execute();
    $stmt->close();

    // Agora excluir o currículo
    $sql_delete_curriculo = "DELETE FROM Curriculo WHERE id_curriculo = ? AND id_usuario = ?";
    $stmt = $conn->prepare($sql_delete_curriculo);
    $stmt->bind_param("ii", $id_curriculo, $id_usuario);

    if ($stmt->execute()) {
        $sucesso = "Currículo excluído com sucesso!";
        header("Refresh:0");
    } else {
        $erro = "Erro ao excluir currículo: " . $stmt->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Currículos</title>
    <link rel="icon" href="../img/icon/icone-pag.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="../css/pag-minha-conta.css" rel="stylesheet">
    <style>
        .curriculo-card {
            transition: transform 0.2s;
            margin-bottom: 20px;
        }

        .curriculo-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .pdf-icon {
            font-size: 3rem;
            color: #e74c3c;
        }

        .section-title {
            border-bottom: 2px solid #3498db;
            padding-bottom: 5px;
            margin-top: 30px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-md" role="navigation" aria-label="Menu principal">
        <div class="navbar-container">
            <button type="button" class="btn nav-back-button" onclick="history.back()" aria-label="Voltar para página anterior">
                <span class="material-icons" aria-hidden="true">arrow_back</span>
                Voltar
            </button>
            <a href="#" class="navbar-brand">
                <img src="../img/Logo design for a job search platform named 'Contrata'. Use a modern, technological style with a bol.png" alt="JobSearch">
            </a>
        </div>
    </nav>

    <main class="account-container">
        <h1 class="mb-4" style="color: var(--brand-color);">Meus Currículos</h1>

        <?php if (!empty($erro)): ?>
            <div class="alert alert-danger"><?php echo $erro; ?></div>
        <?php endif; ?>

        <?php if (!empty($sucesso)): ?>
            <div class="alert alert-success"><?php echo $sucesso; ?></div>
        <?php endif; ?>

        <!-- Upload de novo currículo -->
        <div class="card mb-4">
            <div class="card-body">
                <h3 class="card-title">Adicionar Novo Currículo</h3>
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="novo_curriculo" class="form-label">Selecione um arquivo PDF</label>
                        <input class="form-control" type="file" id="novo_curriculo" name="novo_curriculo" accept=".pdf" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Enviar Currículo</button>
                </form>
            </div>
        </div>

        <!-- Botão para editar currículo online -->
        <div class="card mb-4">
            <div class="card-body">
                <h3 class="card-title">Editar Currículo Online</h3>
                <p>Preencha seu currículo diretamente em nosso formulário online.</p>
                <a href="Meu-curriculo.php" class="btn btn-primary">
                    <i class="fas fa-edit me-2"></i>Editar Currículo Online
                </a>
            </div>
        </div>

        <!-- Lista de currículos -->
        <h2 class="section-title">Meus Currículos Cadastrados</h2>

        <?php if ($result_curriculos->num_rows > 0): ?>
            <div class="row">
                <?php while ($curriculo = $result_curriculos->fetch_assoc()): ?>
                    <div class="col-md-6">
                        <div class="card curriculo-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="far fa-file-pdf pdf-icon me-3"></i>
                                    <div>
                                        <h5 class="card-title mb-1"><?php echo htmlspecialchars($curriculo['pdf_nome']); ?></h5>
                                        <p class="text-muted mb-0">Enviado em: <?php echo date("d/m/Y H:i", strtotime($curriculo['data_envio'])); ?></p>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="<?php echo htmlspecialchars($curriculo['pdf_caminho']); ?>" target="_blank" class="btn btn-outline-primary">
                                        <span class="material-icons">visibility</span> Visualizar
                                    </a>

                                    <form method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este currículo? Todas as informações associadas também serão removidas.');">
                                        <input type="hidden" name="id_curriculo" value="<?php echo $curriculo['id_curriculo']; ?>">
                                        <button type="submit" name="excluir_curriculo" class="btn btn-outline-danger">
                                            <span class="material-icons">delete</span> Excluir
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Seções de informações do currículo -->
            <h2 class="section-title">Informações do Currículo</h2>

            <!-- Formações -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3>Formações Acadêmicas</h3>
                </div>
                <div class="card-body">
                    <?php if ($result_formacoes->num_rows > 0): ?>
                        <ul class="list-group">
                            <?php while ($formacao = $result_formacoes->fetch_assoc()): ?>
                                <li class="list-group-item">
                                    <h5><?php echo htmlspecialchars($formacao['curso']); ?></h5>
                                    <p class="mb-1"><?php echo htmlspecialchars($formacao['instituicao']); ?></p>
                                    <p class="mb-1">
                                        <?php echo htmlspecialchars($formacao['nivel_formacao']); ?> |
                                        <?php echo date("m/Y", strtotime($formacao['data_inicio'])); ?> -
                                        <?php echo $formacao['cursando'] ? 'Atualmente' : date("m/Y", strtotime($formacao['data_conclusao'])); ?>
                                    </p>
                                    <?php if (!empty($formacao['descricao'])): ?>
                                        <p class="mt-2"><?php echo nl2br(htmlspecialchars($formacao['descricao'])); ?></p>
                                    <?php endif; ?>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted">Nenhuma formação acadêmica cadastrada.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Experiências -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3>Experiências Profissionais</h3>
                </div>
                <div class="card-body">
                    <?php if ($result_experiencias->num_rows > 0): ?>
                        <ul class="list-group">
                            <?php while ($experiencia = $result_experiencias->fetch_assoc()): ?>
                                <li class="list-group-item">
                                    <h5><?php echo htmlspecialchars($experiencia['cargo']); ?></h5>
                                    <p class="mb-1"><?php echo htmlspecialchars($experiencia['empresa']); ?></p>
                                    <p class="mb-1">
                                        <?php echo date("m/Y", strtotime($experiencia['data_inicio'])); ?> -
                                        <?php echo $experiencia['trabalho_atual'] ? 'Atualmente' : date("m/Y", strtotime($experiencia['data_fim'])); ?>
                                    </p>
                                    <?php if (!empty($experiencia['responsabilidades'])): ?>
                                        <div class="mt-2">
                                            <h6>Responsabilidades:</h6>
                                            <p><?php echo nl2br(htmlspecialchars($experiencia['responsabilidades'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted">Nenhuma experiência profissional cadastrada.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Habilidades -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3>Habilidades</h3>
                </div>
                <div class="card-body">
                    <?php if ($result_habilidades->num_rows > 0): ?>
                        <div class="d-flex flex-wrap gap-2">
                            <?php while ($habilidade = $result_habilidades->fetch_assoc()): ?>
                                <span class="badge bg-primary p-2">
                                    <?php echo htmlspecialchars($habilidade['nome_habilidade']); ?>
                                    <small class="ms-1">(<?php echo htmlspecialchars($habilidade['nivel_habilidade']); ?>)</small>
                                </span>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Nenhuma habilidade cadastrada.</p>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>
            <div class="alert alert-info">
                Você ainda não possui currículos cadastrados. Envie seu primeiro currículo usando o formulário acima.
            </div>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>

</html>