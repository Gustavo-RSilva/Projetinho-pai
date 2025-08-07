<?php
session_start();
$erro = "";

include_once("../db/conexao.php");

if (!isset($_SESSION['id_usuario'])) {
    header("Location: Login.html");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

$sql_usuario = "SELECT nome_completo, email, telefone, endereco_cidade, endereco_estado FROM usuarios WHERE id_usuario = ?";
$stmt_usuario = $conn->prepare($sql_usuario);

if ($stmt_usuario === false) {
    die('Erro na preparação da consulta: ' . $conn->error);
}

$stmt_usuario->bind_param("i", $id_usuario);
$stmt_usuario->execute();
$result_usuario = $stmt_usuario->get_result();

if ($result_usuario->num_rows > 0) {
    $usuario = $result_usuario->fetch_assoc();
    $nome = $usuario['nome_completo'];
    $email = $usuario['email'];
    $telefone = $usuario['telefone'];
    $cidade = $usuario['endereco_cidade'];
    $estado = $usuario['endereco_estado'];
} else {
    $erro = "Usuário não encontrado.";
}

$sql_candidaturas = "SELECT v.titulo, e.nome AS empresa, v.localizacao AS cidade, 
                    SUBSTRING_INDEX(v.localizacao, ', ', -1) AS estado, c.data_candidatura 
                    FROM candidaturas c
                    JOIN vagas v ON c.id_vaga = v.id_vaga
                    JOIN empresas e ON v.id_empresa = e.id_empresa
                    WHERE c.id_usuario = ?";
$stmt_candidaturas = $conn->prepare($sql_candidaturas);

if ($stmt_candidaturas === false) {
    die('Erro na preparação da consulta: ' . $conn->error);
}

$stmt_candidaturas->bind_param("i", $id_usuario);
$stmt_candidaturas->execute();
$res_candidaturas = $stmt_candidaturas->get_result();

// Armazenar resultados em array para usar posteriormente
$candidaturas = [];
while ($row = $res_candidaturas->fetch_assoc()) {
    $candidaturas[] = $row;
}

$stmt_usuario->close();
$stmt_candidaturas->close();
$conn->close();
?>


<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Conta | JobSearch</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --brand-color: #0b7285;
            --brand-hover: #0d90b8;
            --text-dark: #2d3748;
            --text-light: #f8fafb;
            --bg-light: #ffffff;
            --nav-bg: #ffffff;
        }

        /* Estilos consistentes com seu site */
        body {
            font-family: "Poppins", sans-serif;
            background-color: #f8fafb;
            color: var(--text-dark);
        }

        /* Navbar Styles */
        .navbar {
            background-color: var(--nav-bg);
            box-shadow: 0 2px 8px rgba(11, 114, 133, 0.15);
            padding: 0.5rem 1rem;
            position: sticky;
            top: 0;
            z-index: 1030;
        }

        .navbar-container {
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            width: 100%;
            gap: 12px;
        }

        /* Botão Voltar */
        .nav-back-button {
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--brand-color);
            font-weight: 600;
            text-decoration: none;
            margin-right: auto;
            padding: 0.5rem 0;
        }

        .nav-back-button:hover {
            color: var(--brand-hover);
        }

        /* Logo */
        .navbar-brand {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
        }

        .navbar-brand img {
            width: 90px;
        }

        /* Botão Hamburguer */
        .custom-toggle {
            background: none;
            border: none;
            color: var(--brand-color);
            font-size: 28px;
            margin-left: auto;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
        }

        .custom-toggle:hover {
            background-color: rgba(11, 114, 133, 0.1);
        }

        /* Menu Colapsável */
        .navbar-collapse {
            display: none;
            position: absolute;
            top: 100%;
            right: 1rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 280px;
            z-index: 1000;
            padding: 1rem;
        }

        .navbar-collapse.show {
            display: block;
        }

        /* Conteúdo da Página */
        .account-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        /* Card de perfil */
        .profile-card {
            background: var(--bg-light);
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            background-color: #e2e8f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }

        .profile-avatar .material-icons {
            font-size: 60px;
            color: var(--brand-color);
        }

        /* Cards de resumo */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        /* Abas de navegação */
        .account-tabs .nav-link {
            color: var(--text-dark);
            font-weight: 600;
            border: none;
            padding: 1rem 1.5rem;
            position: relative;
        }

        .account-tabs .nav-link.active {
            color: var(--brand-color);
            background: transparent;
        }

        .account-tabs .nav-link.active::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 50%;
            height: 3px;
            background: var(--brand-color);
            border-radius: 3px;
        }

        /* Lista de candidaturas */
        .application-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 1rem;
            transition: transform 0.2s;
        }

        .application-card:hover {
            transform: translateY(-2px);
        }

        .status-badge {
            font-size: 0.8rem;
            padding: 0.35rem 0.75rem;
        }

        /* Botões */
        .btn-primary {
            background-color: var(--brand-color);
            border: none;
        }

        .btn-primary:hover {
            background-color: var(--brand-hover);
        }

        .btn-cadastrar {
            background: transparent;
            border: 2px solid var(--brand-color);
            color: var(--brand-color);
        }

        .btn-cadastrar:hover {
            background: var(--brand-color);
            color: white;
        }

        /* Foco acessível */
        button:focus,
        a:focus,
        input:focus {
            outline: 3px solid var(--brand-hover);
            outline-offset: 2px;
        }

        @media (max-width: 768px) {
            .account-tabs .nav-link {
                padding: 0.75rem;
                font-size: 0.9rem;
            }

            .navbar-brand {
                position: static;
                transform: none;
                order: 1;
                margin: 0 auto;
            }

            .nav-back-button {
                order: 0;
            }

            .custom-toggle {
                order: 2;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-md" role="navigation" aria-label="Menu principal">
        <div class="navbar-container">
            <a href="index.php" class="nav-back-button">
                <span class="material-icons">arrow_back</span>
                Voltar
            </a>
            <a href="#" class="navbar-brand">
                <img src="../img/Logo design for a job search platform named 'Contrata'. Use a modern, technological style with a bol(1) (1).png" alt="JobSearch">
            </a>
        </div>
    </nav>

    <main class="account-container" id="main-content" tabindex="-1">
        <h1 class="mb-4" style="color: var(--brand-color);">Minha Conta</h1>

        <?php if (!empty($erro)): ?>
            <div class="alert alert-danger"><?php echo $erro; ?></div>
        <?php endif; ?>

        <section class="profile-card text-center">
            <div class="profile-avatar">
                <span class="material-icons">account_circle</span>
            </div>
            <h2><?php echo htmlspecialchars($nome ?? ''); ?></h2>
            <p class="text-muted"><?php echo htmlspecialchars($email ?? ''); ?></p>
            <button class="btn btn-cadastrar">
                <span class="material-icons">edit</span> Editar Perfil
            </button>
        </section>

        <!-- Cards Resumo -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <span class="material-icons mb-2" style="color: var(--brand-color); font-size: 2.5rem;">work_outline</span>
                        <h3><?php echo count($candidaturas); ?></h3>
                        <p class="mb-3">Candidaturas Ativas</p>
                        <a href="#candidaturas" class="btn btn-primary">Ver Todas</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <span class="material-icons mb-2" style="color: var(--brand-color); font-size: 2.5rem;">description</span>
                        <h3>1</h3>
                        <p class="mb-3">Currículo Cadastrado</p>
                        <button class="btn btn-primary">Gerenciar</button>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <span class="material-icons mb-2" style="color: var(--brand-color); font-size: 2.5rem;">notifications</span>
                        <h3>5</h3>
                        <p class="mb-3">Alertas Ativos</p>
                        <button class="btn btn-primary">Configurar</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Abas -->
        <ul class="nav account-tabs mb-4" id="accountTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="dados-tab" data-bs-toggle="tab" data-bs-target="#dados" type="button">Dados Pessoais</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="candidaturas-tab" data-bs-toggle="tab" data-bs-target="#candidaturas" type="button">Minhas Candidaturas</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="seguranca-tab" data-bs-toggle="tab" data-bs-target="#seguranca" type="button">Segurança</button>
            </li>
        </ul>

        <!-- Conteúdo Abas -->
        <div class="tab-content">
            <!-- Aba Dados -->
            <div class="tab-pane fade show active" id="dados">
                <form method="POST" action="atualizar-dados.php">
                    <h2 class="mb-4">Informações Pessoais</h2>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="nome" class="form-label">Nome Completo</label>
                            <input type="text" class="form-control" id="nome" name="nome" value="<?= htmlspecialchars($nome ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">E-mail</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="telefone" class="form-label">Telefone</label>
                            <input type="tel" class="form-control" id="telefone" name="telefone" value="<?= htmlspecialchars($telefone ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="localizacao" class="form-label">Localização</label>
                            <input type="text" class="form-control" id="localizacao" name="localizacao" value="<?= htmlspecialchars(($cidade ?? '') . ', ' . ($estado ?? '')) ?>">
                        </div>
                        <div class="col-12 mt-3">
                            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Aba Candidaturas -->
            <div class="tab-pane fade" id="candidaturas">
                <h2 class="mb-4">Minhas Candidaturas</h2>
                <?php if (!empty($candidaturas)): ?>
                    <?php foreach ($candidaturas as $cand): ?>
                        <div class="card mb-3 application-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="card-title mb-1"><?= htmlspecialchars($cand['titulo']) ?></h5>
                                        <p class="card-text mb-1">
                                            <?= htmlspecialchars($cand['empresa']) ?> – <?= htmlspecialchars($cand['cidade']) ?>, <?= htmlspecialchars($cand['estado']) ?>
                                        </p>
                                        <p class="text-muted mb-0">
                                            Candidatado em: <?= date("d/m/Y", strtotime($cand['data_candidatura'])) ?>
                                        </p>
                                    </div>
                                    <span class="badge bg-success status-badge">Ativa</span>
                                </div>
                                <div class="mt-3 d-flex justify-content-between">
                                    <a href="#" class="btn btn-sm btn-outline-primary">Ver Detalhes</a>
                                    <button class="btn btn-sm btn-outline-danger">Cancelar</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info" role="alert">
                        Você ainda não se candidatou a nenhuma vaga.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Aba Segurança -->
            <div class="tab-pane fade" id="seguranca">
                <h2 class="mb-4">Configurações de Segurança</h2>
                <form method="POST" action="atualizar-senha.php">
                    <div class="mb-3">
                        <label for="senha_atual" class="form-label">Senha Atual</label>
                        <input type="password" class="form-control" id="senha_atual" name="senha_atual" required>
                    </div>
                    <div class="mb-3">
                        <label for="nova_senha" class="form-label">Nova Senha</label>
                        <input type="password" class="form-control" id="nova_senha" name="nova_senha" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirmar_senha" class="form-label">Confirmar Nova Senha</label>
                        <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Alterar Senha</button>
                </form>
            </div>
        </div>
    </main>

    <!-- Scripts Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>