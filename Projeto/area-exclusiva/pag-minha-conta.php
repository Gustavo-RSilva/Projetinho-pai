<?php
session_start();
$erro = "";
$sucesso = "";

include_once("../db/conexao.php");

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../Login.php");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

/* ============================
   SALVAR ALTERAÇÕES
============================ */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    /* ===== Atualizar Dados Pessoais ===== */
    if (isset($_POST['acao']) && $_POST['acao'] === 'atualizar_dados') {
        $nome_completo = trim($_POST['nome_completo'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $data_nascimento = trim($_POST['data_nascimento'] ?? null);
        $endereco_rua = trim($_POST['endereco_rua'] ?? '');
        $endereco_numero = trim($_POST['endereco_numero'] ?? '');
        $endereco_complemento = trim($_POST['endereco_complemento'] ?? '');
        $endereco_cidade = trim($_POST['endereco_cidade'] ?? '');
        $endereco_estado = trim($_POST['endereco_estado'] ?? '');
        $endereco_cep = trim($_POST['endereco_cep'] ?? '');
        $resumo_profissional = trim($_POST['resumo_profissional'] ?? '');

        $sql_update = "UPDATE usuarios 
                      SET nome_completo = ?, email = ?, telefone = ?, data_nascimento = ?,
                          endereco_rua = ?, endereco_numero = ?, endereco_complemento = ?,
                          endereco_cidade = ?, endereco_estado = ?, endereco_cep = ?,
                          resumo_profissional = ?
                      WHERE id_usuario = ?";

        $stmt = $conn->prepare($sql_update);
        $stmt->bind_param(
            "sssssssssssi",
            $nome_completo,
            $email,
            $telefone,
            $data_nascimento,
            $endereco_rua,
            $endereco_numero,
            $endereco_complemento,
            $endereco_cidade,
            $endereco_estado,
            $endereco_cep,
            $resumo_profissional,
            $id_usuario
        );

        if ($stmt->execute()) {
            $sucesso = "Dados pessoais atualizados com sucesso!";
        } else {
            $erro = "Erro ao atualizar os dados pessoais: " . $stmt->error;
        }
        $stmt->close();
    }

    /* ===== Alterar Senha ===== */
    if (isset($_POST['acao']) && $_POST['acao'] === 'alterar_senha') {
        if (empty($_POST['senha_atual']) || empty($_POST['nova_senha']) || empty($_POST['confirmar_senha'])) {
            $erro = "Todos os campos são obrigatórios.";
        } else {
            $senha_atual = md5($_POST['senha_atual']);
            $nova_senha = $_POST['nova_senha'];
            $confirmar_senha = $_POST['confirmar_senha'];

            if ($nova_senha !== $confirmar_senha) {
                $erro = "A nova senha e a confirmação não coincidem.";
            } else {
                $sql_check = "SELECT senha FROM usuarios WHERE id_usuario = ?";
                $stmt = $conn->prepare($sql_check);
                $stmt->bind_param("i", $id_usuario);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($result && $result['senha'] === $senha_atual) {
                    $nova_senha_md5 = md5($nova_senha);
                    $sql_update_senha = "UPDATE usuarios SET senha = ? WHERE id_usuario = ?";
                    $stmt = $conn->prepare($sql_update_senha);
                    $stmt->bind_param("si", $nova_senha_md5, $id_usuario);

                    if ($stmt->execute()) {
                        $sucesso = "Senha alterada com sucesso!";
                    } else {
                        $erro = "Erro ao alterar a senha: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $erro = "Senha atual incorreta.";
                }
            }
        }
    }

    /* ===== Atualizar Foto de Perfil ===== */
    if (isset($_POST['acao']) && $_POST['acao'] === 'alterar_foto') {
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
            $pastaBase = realpath(dirname(__FILE__)) . '/../img/foto-perfil/';
            if (!file_exists($pastaBase)) {
                mkdir($pastaBase, 0777, true);
            }

            $pastaUsuario = uniqid();
            $caminhoPastaUsuario = $pastaBase . $pastaUsuario;
            if (!file_exists($caminhoPastaUsuario)) {
                mkdir($caminhoPastaUsuario, 0777, true);
            }

            $extensao = strtolower(pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION));
            $nomeFoto = 'perfil.' . $extensao;
            $caminhoCompleto = $caminhoPastaUsuario . '/' . $nomeFoto;

            $check = getimagesize($_FILES['foto_perfil']['tmp_name']);
            if ($check !== false && in_array($extensao, ['jpg', 'jpeg', 'png', 'gif'])) {
                if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $caminhoCompleto)) {
                    $novaFoto = './img/foto-perfil/' . $pastaUsuario . '/' . $nomeFoto;
                    $sql_update_foto = "UPDATE usuarios SET foto_perfil = ? WHERE id_usuario = ?";
                    $stmt = $conn->prepare($sql_update_foto);
                    $stmt->bind_param("si", $novaFoto, $id_usuario);

                    if ($stmt->execute()) {
                        $sucesso = "Foto de perfil atualizada com sucesso!";
                        $_SESSION['foto_perfil'] = $novaFoto;
                        header("Refresh:0");
                    } else {
                        $erro = "Erro ao atualizar foto no banco de dados.";
                    }
                    $stmt->close();
                } else {
                    $erro = "Erro ao mover o arquivo. Verifique as permissões.";
                }
            } else {
                $erro = "Arquivo inválido. Apenas imagens JPG, JPEG, PNG e GIF são permitidas.";
            }
        } else {
            $erro = "Nenhuma foto foi selecionada ou ocorreu um erro no upload.";
        }
    }

    /* ===== Adicionar Alerta ===== */
    if (isset($_POST['acao']) && $_POST['acao'] === 'adicionar_alerta') {
        $termo_busca = trim($_POST['termo_busca'] ?? '');
        $localizacao = trim($_POST['localizacao'] ?? '');
        $id_area = !empty($_POST['id_area']) ? (int)$_POST['id_area'] : null;
        $frequencia = $_POST['frequencia'] ?? 'Diário';

        $sql_insert_alerta = "INSERT INTO alertas_vagas (id_usuario, termo_busca, localizacao, id_area, ativo, frequencia) 
                              VALUES (?, ?, ?, ?, 1, ?)";
        $stmt = $conn->prepare($sql_insert_alerta);
        $stmt->bind_param("issis", $id_usuario, $termo_busca, $localizacao, $id_area, $frequencia);

        if ($stmt->execute()) {
            $sucesso = "Novo alerta criado com sucesso!";
        } else {
            $erro = "Erro ao criar alerta: " . $stmt->error;
        }
        $stmt->close();
    }

    /* ===== Gerenciar Alerta (ativar, desativar, excluir) ===== */
    if (isset($_POST['acao']) && $_POST['acao'] === 'gerenciar_alerta') {
        $id_alerta = (int) ($_POST['id_alerta'] ?? 0);
        $operacao = $_POST['operacao'] ?? '';

        if ($id_alerta > 0) {
            if ($operacao === 'ativar') {
                $sql = "UPDATE alertas_vagas SET ativo = 1 WHERE id_alerta = ? AND id_usuario = ?";
            } elseif ($operacao === 'desativar') {
                $sql = "UPDATE alertas_vagas SET ativo = 0 WHERE id_alerta = ? AND id_usuario = ?";
            } elseif ($operacao === 'excluir') {
                $sql = "DELETE FROM alertas_vagas WHERE id_alerta = ? AND id_usuario = ?";
            }

            if (!empty($sql)) {
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $id_alerta, $id_usuario);
                if ($stmt->execute()) {
                    $sucesso = "Operação realizada com sucesso!";
                } else {
                    $erro = "Erro: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

/* ============================
   CONSULTA DADOS DO USUÁRIO
============================ */
$sql_usuario = "SELECT nome_completo, email, telefone, data_nascimento,
           endereco_rua, endereco_numero, endereco_complemento,
           endereco_cidade, endereco_estado, endereco_cep,
           resumo_profissional, foto_perfil
    FROM usuarios
    WHERE id_usuario = ?";
$stmt_usuario = $conn->prepare($sql_usuario);
$stmt_usuario->bind_param("i", $id_usuario);
$stmt_usuario->execute();
$result_usuario = $stmt_usuario->get_result();
$usuario = $result_usuario->fetch_assoc();
$stmt_usuario->close();

/* ============================
   CONSULTA CURRÍCULOS
============================ */
$sql_curriculos = "SELECT id_curriculo, pdf_nome, data_envio FROM curriculo WHERE id_usuario = ?";
$stmt_curriculos = $conn->prepare($sql_curriculos);
$stmt_curriculos->bind_param("i", $id_usuario);
$stmt_curriculos->execute();
$res_curriculos = $stmt_curriculos->get_result();
$curriculos = $res_curriculos->fetch_all(MYSQLI_ASSOC);
$total_curriculos = count($curriculos);
$stmt_curriculos->close();

/* ============================
   CONSULTA CANDIDATURAS
============================ */
$sql_candidaturas = "SELECT v.titulo, e.nome AS empresa, v.localizacao AS cidade, 
           SUBSTRING_INDEX(v.localizacao, ', ', -1) AS estado, c.data_candidatura 
    FROM candidaturas c
    JOIN vagas v ON c.id_vaga = v.id_vaga
    JOIN empresas e ON v.id_empresa = e.id_empresa
    WHERE c.id_usuario = ?";
$stmt_candidaturas = $conn->prepare($sql_candidaturas);
$stmt_candidaturas->bind_param("i", $id_usuario);
$stmt_candidaturas->execute();
$res_candidaturas = $stmt_candidaturas->get_result();
$candidaturas = $res_candidaturas->fetch_all(MYSQLI_ASSOC);
$stmt_candidaturas->close();

/* ============================
   CONSULTA ALERTAS
============================ */
$sql_alertas_count = "SELECT COUNT(*) AS total FROM alertas_vagas WHERE id_usuario = ? AND ativo = 1";
$stmt_alertas_count = $conn->prepare($sql_alertas_count);
$stmt_alertas_count->bind_param("i", $id_usuario);
$stmt_alertas_count->execute();
$total_alertas = $stmt_alertas_count->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_alertas_count->close();

$sql_alertas_lista = "SELECT a.id_alerta, a.termo_busca, a.localizacao, a.frequencia, a.ativo, a.data_criacao, ap.nome AS area
    FROM alertas_vagas a
    LEFT JOIN areas_profissionais ap ON a.id_area = ap.id_area
    WHERE a.id_usuario = ?
    ORDER BY a.data_criacao DESC";
$stmt_alertas_lista = $conn->prepare($sql_alertas_lista);
$stmt_alertas_lista->bind_param("i", $id_usuario);
$stmt_alertas_lista->execute();
$res_alertas_lista = $stmt_alertas_lista->get_result();
$alertas = $res_alertas_lista->fetch_all(MYSQLI_ASSOC);
$stmt_alertas_lista->close();

$conn->close();
?>


<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Conta</title>
    <link rel="icon" href="../img/icon/icone-pag.png" />
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="../css/pag-minha-conta.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>

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
                <img src="../img/Logo design for a job search platform named 'Contrata'. Use a modern, technological style with a bol.png" alt="JobSearch">
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
                <?php
                $foto = $usuario['foto_perfil'] ?? 'img/foto-perfil/default.png';

                if (preg_match('/^https?:\/\//', $foto)) {
                    // Se for link externo (Google, etc.)
                    $foto_url = $foto;
                } else {
                    // Caminho relativo da página para a pasta img
                    $foto_url = '../' . $foto;
                }
                ?>
                <img src="<?php echo htmlspecialchars($foto_url); ?>"
                    alt="Foto de Perfil"
                    style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover;">
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
                        <a href="pag-candidaturas.php" class="btn btn-primary">Ver Todas</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <span class="material-icons mb-2" style="color: var(--brand-color); font-size: 2.5rem;">description</span>
                        <h3><?php echo $total_curriculos; ?></h3>
                        <p class="mb-3">Currículo(s) Cadastrado(s)</p>
                        <a href="curriculos.php" class="btn btn-primary">Gerenciar</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <span class="material-icons mb-2" style="color: var(--brand-color); font-size: 2.5rem;">notifications</span>
                        <h3><?php echo $total_alertas; ?></h3>
                        <p class="mb-3">Alertas Ativos</p>
                        <a href="#alertas" id="btnConfigurar" class="btn btn-primary">Configurar</a>
                        </a>
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
                <button class="nav-link" id="alertas-tab" data-bs-toggle="tab" data-bs-target="#alertas" type="button">Meus Alertas</button>
            </li>

            <li class="nav-item">
                <button class="nav-link" id="seguranca-tab" data-bs-toggle="tab" data-bs-target="#seguranca" type="button">Segurança</button>
            </li>
        </ul>

        <!-- Conteúdo Abas -->
        <div class="tab-content">
            <!-- Aba Dados -->
            <!-- Aba Dados -->
            <!-- Aba Dados -->
            <div class="tab-pane fade show active" id="dados">
                <form method="POST" action="" class="form-dados-pessoais">
                    <input type="hidden" name="acao" value="atualizar_dados">

                    <h3 class="mb-4">Informações Básicas</h3>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nome Completo</label>
                            <input type="text" class="form-control" name="nome_completo"
                                value="<?php echo htmlspecialchars($usuario['nome_completo'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email"
                                value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Telefone</label>
                            <input type="text" class="form-control" name="telefone"
                                value="<?php echo htmlspecialchars($usuario['telefone'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Data de Nascimento</label>
                            <input type="date" class="form-control" name="data_nascimento"
                                value="<?php echo htmlspecialchars($usuario['data_nascimento'] ?? ''); ?>">
                        </div>
                    </div>

                    <h3 class="mb-4 mt-4">Endereço</h3>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Rua</label>
                            <input id="endereco_rua" type="text" class="form-control" name="endereco_rua"
                                value="<?php echo htmlspecialchars($usuario['endereco_rua'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Número</label>
                            <input id="endereco_numero" type="text" class="form-control" name="endereco_numero"
                                value="<?php echo htmlspecialchars($usuario['endereco_numero'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Complemento</label>
                            <input id="endereco_complemento" type="text" class="form-control" name="endereco_complemento"
                                value="<?php echo htmlspecialchars($usuario['endereco_complemento'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Cidade</label>
                            <input id="endereco_cidade" type="text" class="form-control" name="endereco_cidade"
                                value="<?php echo htmlspecialchars($usuario['endereco_cidade'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Estado (UF)</label>
                            <input id="endereco_estado" type="text" class="form-control" name="endereco_estado" maxlength="2"
                                value="<?php echo htmlspecialchars($usuario['endereco_estado'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">CEP</label>
                            <input id="endereco_cep" type="text" class="form-control" name="endereco_cep"
                                value="<?php echo htmlspecialchars($usuario['endereco_cep'] ?? ''); ?>"
                                inputmode="numeric" autocomplete="postal-code" placeholder="00000-000">
                            <small id="cepHelp" class="text-muted d-block mt-1"></small>
                        </div>
                    </div>


                    <h3 class="mb-4 mt-4">Informações Profissionais</h3>
                    <div class="mb-3">
                        <label class="form-label">Resumo Profissional</label>
                        <textarea class="form-control" name="resumo_profissional" rows="4"><?php
                                                                                            echo htmlspecialchars($usuario['resumo_profissional'] ?? '');
                                                                                            ?></textarea>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
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

            <!-- Aba Meus Alertas -->
            <div class="tab-pane fade" id="alertas">
                <h2 class="mb-4">Meus Alertas</h2>

                <!-- Formulário Novo Alerta -->
                <form method="POST" class="mb-4">
                    <input type="hidden" name="acao" value="adicionar_alerta">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <input type="text" name="termo_busca" class="form-control" placeholder="Cargo ou palavra-chave" required>
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="localizacao" class="form-control" placeholder="Localização">
                        </div>
                        <div class="col-md-3">
                            <select name="frequencia" class="form-select">
                                <option value="Diário">Diário</option>
                                <option value="Semanal">Semanal</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Adicionar</button>
                        </div>
                    </div>
                </form>

                <!-- Lista de Alertas -->
                <?php if (!empty($alertas)): ?>
                    <?php foreach ($alertas as $a): ?>
                        <div class="card mb-3">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?= htmlspecialchars($a['termo_busca']) ?></strong> – <?= htmlspecialchars($a['localizacao']) ?>
                                    <br><small>Frequência: <?= htmlspecialchars($a['frequencia']) ?></small>
                                    <br><small>Área: <?= htmlspecialchars($a['area'] ?? 'Não definida') ?></small>
                                </div>
                                <div>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="acao" value="gerenciar_alerta">
                                        <input type="hidden" name="id_alerta" value="<?= $a['id_alerta'] ?>">
                                        <input type="hidden" name="operacao" value="<?= $a['ativo'] ? 'desativar' : 'ativar' ?>">
                                        <button type="submit" class="btn btn-sm <?= $a['ativo'] ? 'btn-warning' : 'btn-success' ?>">
                                            <?= $a['ativo'] ? 'Desativar' : 'Ativar' ?>
                                        </button>
                                    </form>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="acao" value="gerenciar_alerta">
                                        <input type="hidden" name="id_alerta" value="<?= $a['id_alerta'] ?>">
                                        <input type="hidden" name="operacao" value="excluir">
                                        <button type="submit" class="btn btn-sm btn-danger">Excluir</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info">Você ainda não possui alertas criados.</div>
                <?php endif; ?>
            </div>
            <style>
                .input-with-icon {
                    position: relative;
                }

                .eye-icon {
                    position: absolute;
                    right: 12px;
                    top: 50%;
                    transform: translateY(-50%);
                    cursor: pointer;
                    width: 22px;
                    height: 22px;
                    user-select: none;
                }
            </style>

            <!-- Aba Segurança -->
            <div class="tab-pane fade" id="seguranca">
                <h2 class="mb-4">Configurações de Segurança</h2>
                <?php if (!empty($sucesso) && isset($_POST['acao']) && $_POST['acao'] === 'alterar_senha'): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="acao" value="alterar_senha">
                    <div class="mb-3">
                        <label for="senha_atual" class="form-label">Senha Atual</label>
                        <div class="input-with-icon">
                            <input type="password" class="form-control password-input" id="senha_atual" name="senha_atual" required>
                            <img src="../img/view.png" class="toggle-password eye-icon" alt="Mostrar senha" title="Mostrar senha">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="nova_senha" class="form-label">Nova Senha</label>
                        <div class="input-with-icon">
                            <input type="password" class="form-control password-input" id="nova_senha" name="nova_senha" required minlength="6">
                            <img src="../img/view.png" class="toggle-password eye-icon" alt="Mostrar senha" title="Mostrar senha">
                        </div>
                        <small class="text-muted">A senha deve ter pelo menos 6 caracteres</small>  
                    </div>


                    <div class="mb-3">
                        <label for="confirmar_senha" class="form-label">Confirmar Nova Senha</label>
                        <div class="input-with-icon">
                            <input type="password" class="form-control password-input" id="confirmar_senha" name="confirmar_senha" required minlength="6">
                            <img src="../img/view.png" class="toggle-password eye-icon" alt="Mostrar senha" title="Mostrar senha">
                        </div>
                        <button type="submit" class="btn btn-primary topo">Alterar Senha</button>
                    </div>   
                </form>
            </div>
            
    </main>
    <!-- Modal Editar Perfil -->
    <div class="modal fade" id="editarPerfilModal" tabindex="-1" aria-labelledby="editarPerfilModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editarPerfilModalLabel">Editar Foto de Perfil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formEditarFoto" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="acao" value="alterar_foto">

                        <div class="text-center mb-4">
                            <div class="profile-avatar mx-auto">
                                <img id="previewFotoPerfil" src="<?php echo htmlspecialchars($foto_url); ?>"
                                    alt="Foto de Perfil"
                                    style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover;">
                            </div>
                            <label for="novaFoto" class="btn btn-cadastrar mt-3">
                                <span class="material-icons">photo_camera</span> Escolher Nova Foto
                            </label>
                            <input type="file" id="novaFoto" name="foto_perfil" accept="image/*" class="d-none">
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
document.getElementById('btnConfigurar').addEventListener('click', function (e) {
  e.preventDefault();

  // pega o botão oficial da aba
  var trigger = document.getElementById('alertas-tab');
  if (trigger) {
    var tab = new bootstrap.Tab(trigger);
    tab.show();
  }
});
</script>
    <script>
  document.addEventListener("DOMContentLoaded", function () {
    if (window.location.hash === "#alertas") {
      var alertasTab = document.querySelector('a[data-bs-toggle="tab"][href="#alertas"]');
      if (alertasTab) {
        var tab = new bootstrap.Tab(alertasTab);
        tab.show();
      }
    }
  });
</script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggles = document.querySelectorAll('.toggle-password');

            toggles.forEach(function(toggle) {
                toggle.addEventListener('click', function() {
                    const input = this.previousElementSibling; // pega o input antes do ícone
                    if (input && input.classList.contains('password-input')) {
                        const type = input.type === 'password' ? 'text' : 'password';
                        input.type = type;

                        // Alterna ícone
                        if (type === 'password') {
                            this.src = '../img/view.png';
                            this.alt = 'Mostrar senha';
                            this.title = 'Mostrar senha';
                        } else {
                            this.src = '../img/hidden.png';
                            this.alt = 'Ocultar senha';
                            this.title = 'Ocultar senha';
                        }
                    }
                });
            });
        });
    </script>

    <script>
        // Preview da nova foto selecionada
        document.getElementById('novaFoto').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('previewFotoPerfil').src = event.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        // Inicializar modal quando clicar no botão "Editar Perfil"
        document.querySelector('.btn-cadastrar').addEventListener('click', function() {
            var myModal = new bootstrap.Modal(document.getElementById('editarPerfilModal'));
            myModal.show();
        });
    </script>
    <script>
        (function() {
            const cepInput = document.getElementById('endereco_cep');
            const ruaInput = document.getElementById('endereco_rua');
            const cidadeInput = document.getElementById('endereco_cidade');
            const ufInput = document.getElementById('endereco_estado');
            const cepHelp = document.getElementById('cepHelp');

            function limparEndereco() {
                // Não limpamos número e complemento
                ruaInput.value = '';
                cidadeInput.value = '';
                ufInput.value = '';
            }

            function setLoading(loading) {
                const text = loading ? 'Consultando CEP...' : '';
                cepHelp.textContent = text;
                cepInput.disabled = loading;
                ruaInput.readOnly = loading;
                cidadeInput.readOnly = loading;
                ufInput.readOnly = loading;
            }

            // Máscara simples de CEP enquanto digita
            function mascaraCEP(v) {
                v = v.replace(/\D/g, '').slice(0, 8);
                if (v.length > 5) v = v.replace(/(\d{5})(\d{1,3})/, '$1-$2');
                return v;
            }

            cepInput.addEventListener('input', function() {
                this.value = mascaraCEP(this.value);
            });

            // Dispara a consulta quando perde o foco ou ao pressionar Enter
            cepInput.addEventListener('blur', consultarCEP);
            cepInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    consultarCEP();
                }
            });

            async function consultarCEP() {
                let cep = (cepInput.value || '').replace(/\D/g, '');

                // Validação básica
                if (cep.length === 0) {
                    cepHelp.textContent = '';
                    return;
                }
                if (cep.length !== 8) {
                    cepHelp.textContent = 'CEP inválido. Use 8 dígitos (ex.: 01001000 ou 01001-000).';
                    limparEndereco();
                    return;
                }

                try {
                    setLoading(true);
                    const resp = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                    if (!resp.ok) throw new Error('Falha na consulta do CEP');

                    const data = await resp.json();
                    if (data.erro) {
                        cepHelp.textContent = 'CEP não encontrado.';
                        limparEndereco();
                        return;
                    }

                    // Preenche os campos (fallbacks para evitar "undefined")
                    ruaInput.value = data.logradouro || '';
                    cidadeInput.value = data.localidade || '';
                    ufInput.value = (data.uf || '').toUpperCase();

                    // Mensagem final
                    cepHelp.textContent = (data.bairro) ?
                        `Bairro: ${data.bairro}` :
                        'Endereço preenchido pelo ViaCEP.';
                } catch (err) {
                    console.error(err);
                    cepHelp.textContent = 'Não foi possível consultar o CEP agora.';
                    limparEndereco();
                } finally {
                    setLoading(false);
                }
            }
        })();
    </script>

    <!-- Scripts Bootstrap -->

</body>

</html>