<?php
session_start();
include_once("../db/conexao.php");

// Verificar se o usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../Login.php");
    exit();
}
// Verifica login
$usuarioLogado = isset($_SESSION['id_usuario']) && !empty($_SESSION['email']);
$usuario = [];

if ($usuarioLogado) {
    $id_usuario = $_SESSION['id_usuario'];
    $query = "SELECT * FROM usuarios WHERE id_usuario = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $usuario = $resultado->fetch_assoc();
}
// Verificar se foi passado o ID da vaga
if (!isset($_GET['id_vaga'])) {
    header("Location: Pagina-vagas.php");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];
$id_vaga = intval($_GET['id_vaga']);

// Buscar informações da vaga
$sql_vaga = "SELECT v.*, e.nome as empresa_nome 
             FROM vagas v 
             JOIN empresas e ON v.id_empresa = e.id_empresa 
             WHERE v.id_vaga = ?";
$stmt_vaga = $conn->prepare($sql_vaga);
$stmt_vaga->bind_param("i", $id_vaga);
$stmt_vaga->execute();
$vaga = $stmt_vaga->get_result()->fetch_assoc();

if (!$vaga) {
    header("Location: Pagina-vagas.php");
    exit();
}

// Buscar currículos do usuário
$sql_curriculos = "SELECT * FROM Curriculo WHERE id_usuario = ?";
$stmt_curriculos = $conn->prepare($sql_curriculos);
$stmt_curriculos->bind_param("i", $id_usuario);
$stmt_curriculos->execute();
$curriculos = $stmt_curriculos->get_result();

// Verificar se já existe candidatura para esta vaga
$sql_candidatura = "SELECT * FROM candidaturas 
                   WHERE id_vaga = ? AND id_usuario = ?";
$stmt_candidatura = $conn->prepare($sql_candidatura);
$stmt_candidatura->bind_param("ii", $id_vaga, $id_usuario);
$stmt_candidatura->execute();
$candidatura_existente = $stmt_candidatura->get_result()->fetch_assoc();

// Processar envio do formulário
$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id_curriculo'])) {
        $id_curriculo = intval($_POST['id_curriculo']);

        // Verificar se o currículo pertence ao usuário
        $sql_verificar = "SELECT * FROM Curriculo 
                         WHERE id_curriculo = ? AND id_usuario = ?";
        $stmt_verificar = $conn->prepare($sql_verificar);
        $stmt_verificar->bind_param("ii", $id_curriculo, $id_usuario);
        $stmt_verificar->execute();

        if ($stmt_verificar->get_result()->num_rows > 0) {
            // Inserir candidatura
            $sql_inserir = "INSERT INTO candidaturas 
                           (id_vaga, id_usuario, id_curriculo, status, observacoes) 
                           VALUES (?, ?, ?, 'Em análise', ?)";
            $stmt_inserir = $conn->prepare($sql_inserir);

            $observacoes = isset($_POST['observacoes']) ? $_POST['observacoes'] : '';
            $stmt_inserir->bind_param("iiis", $id_vaga, $id_usuario, $id_curriculo, $observacoes);

            if ($stmt_inserir->execute()) {
                $mensagem = "Candidatura enviada com sucesso!";
                header("Location: pag-candidaturas.php?sucesso=1");
                exit();
            } else {
                $erro = "Erro ao enviar candidatura. Tente novamente.";
            }
        } else {
            $erro = "Currículo inválido.";
        }
    } else {
        $erro = "Selecione um currículo para enviar.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidatar-se à Vaga</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .card {
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }

        .company-logo {
            width: 80px;
            height: 80px;
            object-fit: contain;
            border-radius: 8px;
        }

        .btn-submit {
            background-color: var(--brand-color);
            border: none;
            padding: 6px 15px;
            font-weight: 600;
            color: white;
        }

        .btn-submit:hover {
            background-color: #092c46;
            color: white;
        }

        .azul {
            background-color: #144d78;
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-md" role="navigation" aria-label="Menu principal">
        <div class="navbar-container">
            <a href="index.php" class="navbar-brand">
                <img style="width: 90px;" src="../img/Logo design for a job search platform named 'Contrata'. Use a modern, technological style with a bol.png" alt="Contrata">
            </a>

            <!-- Links Desktop -->
            <div class="nav-always-visible d-none d-lg-flex" aria-hidden="true" aria-label="Links de navegação principal">
                <a href="Pagina-vagas.php" class="nav-link">
                    <span class="material-icons">list_alt</span>
                    Vagas Ativas
                </a>
                <a href="pag-cargos.php" class="nav-link">
                    <span class="material-icons">next_week</span>
                    Cargos/Salários
                </a>
            </div>
            <button class="btn user-status" type="button" aria-label="Abrir menu de navegação"
                data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav"
                aria-expanded="false" aria-live="polite" aria-atomic="true" aria-label="Usuário logado">


                <!-- Css da foto de perfil do usuario-->
                <style>
                    .material-icon-avatar {
                        display: inline-flex;
                        align-items: center;
                        justify-content: center;
                        width: 24px;
                        /* tamanho padrão dos material-icons */
                        height: 24px;
                        border-radius: 50%;
                        overflow: hidden;
                        vertical-align: middle;
                        background-color: transparent;
                        /* igual ao fundo do ícone */
                        transition: background-color 0.2s ease;
                        cursor: pointer;
                    }

                    .material-icon-avatar img {
                        width: 100%;
                        height: 100%;
                        object-fit: cover;
                    }

                    .material-icon-avatar:hover {
                        background-color: rgba(0, 0, 0, 0.1);
                        /* efeito de hover igual aos ícones */
                        cursor: pointer;
                    }
                </style>
                <!-- Exibe foto de perfil se o usuário estiver logado -->
                <?php if ($usuarioLogado): ?>
                    <span class="material-icons material-icon-avatar">
                        <?php
                        $foto = $usuario['foto_perfil'] ?? 'img/foto-perfil/default.png';

                        if (preg_match('/^https?:\/\//', $foto)) {
                            $foto_url = $foto;
                        } else {
                            $foto_url = '../' . $foto;  // Caminho relativo ajustado
                        }

                        ?>
                        <img src="<?php echo htmlspecialchars($foto_url); ?>" alt="Foto de perfil" class="foto-perfil">
                    </span>
                    Olá, <?php echo htmlspecialchars($_SESSION['nome_completo']); ?>
                <?php else: ?>
                    <span class="material-icons" aria-hidden="true">account_circle</span>
                    Visitante
                <?php endif; ?>
            </button>
        </div>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto align-items-center">

                <!-- Links Mobile -->
                <li class="nav-item d-lg-none">
                    <a href="Pagina-vagas.php" class="nav-link">
                        <span class="material-icons">list_alt</span>
                        Vagas Ativas
                    </a>
                </li>
                <li class="nav-item d-lg-none">
                    <a href="pag-cargos.php" class="nav-link">
                        <span class="material-icons">next_week</span>
                        Cargos/Salários
                    </a>
                </li>

                <!-- Seus outros links -->
                <li class="nav-item">
                    <a href="pag-minha-conta.php" class="nav-link">
                        <span class="material-icons">account_circle</span>
                        Minha Conta
                    </a>
                </li>
                <li class="nav-item">
                    <a href="curriculos.php" class="nav-link">
                        <span class="material-icons">description</span>
                        Meu Currículo
                    </a>
                </li>
                <li class="nav-item">
                    <a href="pag-candidaturas.php" class="nav-link">
                        <span class="material-icons">work_outline</span>
                        Minhas Candidaturas
                    </a>
                </li>
            </ul>

            <div class="auth-buttons">
                <?php if ($usuarioLogado): ?>
                    <a href="logout2.php">
                        <button type="button" class="btn btn-entrar" tabindex="0">Sair</button>
                    </a>
                <?php else: ?>
                    <a href="../Login.php">
                        <button type="button" class="btn btn-entrar" tabindex="0">Entrar</button>
                    </a>
                    <a href="../Crie-conta.php">
                        <button type="button" class="btn btn-cadastrar" tabindex="0">Cadastrar</button>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header azul text-white">
                        <h4 class="mb-0">Candidatar-se à Vaga</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($candidatura_existente): ?>
                            <div class="alert alert-info">
                                <h5>Você já se candidatou a esta vaga</h5>
                                <p>Status: <?php echo $candidatura_existente['status']; ?></p>
                                <p>Data da candidatura: <?php echo date('d/m/Y H:i', strtotime($candidatura_existente['data_candidatura'])); ?></p>
                                <a href="pag-candidaturas.php" class="btn btn-primary">Ver Minhas Candidaturas</a>
                                <a href="Pagina-vagas.php" class="btn btn-secondary">Voltar para Vagas</a>
                            </div>
                        <?php else: ?>
                            <h5>Vaga: <?php echo htmlspecialchars($vaga['titulo']); ?></h5>
                            <p><strong>Empresa:</strong> <?php echo htmlspecialchars($vaga['empresa_nome']); ?></p>
                            <p><strong>Localização:</strong> <?php echo htmlspecialchars($vaga['localizacao']); ?></p>
                            <p><strong>Tipo de contrato:</strong> <?php echo htmlspecialchars($vaga['tipo_contrato']); ?></p>

                            <hr>

                            <?php if ($erro): ?>
                                <div class="alert alert-danger"><?php echo $erro; ?></div>
                            <?php endif; ?>

                            <form method="POST">
                                <div class="mb-3">
                                    <label for="id_curriculo" class="form-label">Selecione seu currículo:</label>
                                    <?php if ($curriculos->num_rows > 0): ?>
                                        <select class="form-select" id="id_curriculo" name="id_curriculo" required>
                                            <option value="">Selecione um currículo</option>
                                            <?php while ($curriculo = $curriculos->fetch_assoc()): ?>
                                                <option value="<?php echo $curriculo['id_curriculo']; ?>">
                                                    <?php echo htmlspecialchars($curriculo['pdf_nome']); ?>
                                                    (Enviado em: <?php echo date('d/m/Y', strtotime($curriculo['data_envio'])); ?>)
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    <?php else: ?>
                                        <div class="alert alert-warning">
                                            Você não possui currículos cadastrados.
                                            <?php
                                            // Monta o destino de retorno (formulário da vaga com o id)
                                            $next_for_curriculos = 'formulario-curriculo.php?id_vaga=' . intval($id_vaga);
                                            ?>
                                            <a href="curriculos.php?next=<?php echo urlencode($next_for_curriculos); ?>" class="alert-link">
                                                Cadastre um currículo
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-3">
                                    <label for="observacoes" class="form-label">Mensagem para o recrutador (opcional):</label>
                                    <textarea class="form-control" id="observacoes" name="observacoes" rows="3"
                                        placeholder="Por que você se interessa por esta vaga?"></textarea>
                                </div>

                                <?php if ($curriculos->num_rows > 0): ?>
                                    <button type="submit" class="btn btn-submit">Enviar Candidatura</button>
                                <?php endif; ?>
                                <a href="Pagina-vagas.php" class="btn btn-secondary">Cancelar</a>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="site-footer">
        <div class="footer-container">
            <div class="footer-section">
                <h4 class="footer-title">Sobre Nós</h4>
                <p class="footer-text">Conectamos talentos às melhores oportunidades. Nosso compromisso é facilitar o acesso ao mercado de trabalho com simplicidade e eficiência.</p>
            </div>

            <div class="footer-section rightlinks">
                <h4 class="footer-title">Links Rápidos</h4>
                <ul class="footer-links">
                    <li><a href="index.php">Início</a></li>
                    <li><a href="Pagina-vagas.php">Vagas</a></li>
                    <li><a href="Meu-curriculo.php">Cadastrar Currículo</a></li>
                    <li><a href="contato.php">Contato</a></li>
                </ul>
            </div>

            <div class="footer-section rightredes">
                <h4 class="footer-title">Redes Sociais</h4>
                <div class="social-buttons">
                    <a href="#" class="social-btn facebook" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-btn instagram" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-btn linkedin" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" class="social-btn whatsapp" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; 2025 JobSearch. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>