<?php
session_start();
$erro = "";
$sucesso = "";

include_once("../db/conexao.php");

if (!isset($_SESSION['id_usuario'])) {
    header("Location: Login.html");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];



/* ============================
   SALVAR ALTERAÇÕES
============================ */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    /* ===== Atualizar Dados Pessoais ===== */
    if (isset($_POST['acao']) && $_POST['acao'] === 'atualizar_dados') {
        // Get all form data with proper null checks
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
    // ... rest of your POST handling code ...
}

/* ============================
   CONSULTA DADOS DO USUÁRIO
============================ */
$sql_usuario = "
    SELECT nome_completo, email, telefone, data_nascimento,
           endereco_rua, endereco_numero, endereco_complemento,
           endereco_cidade, endereco_estado, endereco_cep,
           resumo_profissional, foto_perfil
    FROM usuarios
    WHERE id_usuario = ?
";

$stmt_usuario = $conn->prepare($sql_usuario);
$stmt_usuario->bind_param("i", $id_usuario);

if ($stmt_usuario->execute()) {
    $result_usuario = $stmt_usuario->get_result();

    if ($result_usuario->num_rows > 0) {
        $usuario = $result_usuario->fetch_assoc();

        // Set default values if fields are null
        $nome = $usuario['nome_completo'] ?? '';
        $email = $usuario['email'] ?? '';
        $telefone = $usuario['telefone'] ?? '';
        $data_nascimento = $usuario['data_nascimento'] ?? '';
        $endereco_rua = $usuario['endereco_rua'] ?? '';
        $endereco_numero = $usuario['endereco_numero'] ?? '';
        $endereco_complemento = $usuario['endereco_complemento'] ?? '';
        $endereco_cidade = $usuario['endereco_cidade'] ?? '';
        $endereco_estado = $usuario['endereco_estado'] ?? '';
        $endereco_cep = $usuario['endereco_cep'] ?? '';
        $resumo_profissional = $usuario['resumo_profissional'] ?? '';
        $foto = $usuario['foto_perfil'] ?? 'img/foto-perfil/default.png';
    } else {
        $erro = "Usuário não encontrado.";
        // Set all default values
        $nome = $email = $telefone = $endereco_rua = $endereco_numero =
            $endereco_complemento = $endereco_cidade = $endereco_estado =
            $endereco_cep = $resumo_profissional = '';
        $data_nascimento = null;
        $foto = 'img/foto-perfil/default.png';
    }
} else {
    $erro = "Erro ao consultar dados do usuário: " . $stmt_usuario->error;
}

// Ajusta caminho da foto
if (preg_match('/^https?:\/\//', $foto)) {
    $foto_url = $foto;
} else {
    $foto_url = '../' . ltrim($foto, '/');
}

/* ============================
   CONSULTA CANDIDATURAS
============================ */
$sql_candidaturas = "
    SELECT v.titulo, e.nome AS empresa, v.localizacao AS cidade, 
           SUBSTRING_INDEX(v.localizacao, ', ', -1) AS estado, c.data_candidatura 
    FROM candidaturas c
    JOIN vagas v ON c.id_vaga = v.id_vaga
    JOIN empresas e ON v.id_empresa = e.id_empresa
    WHERE c.id_usuario = ?
";
$stmt_candidaturas = $conn->prepare($sql_candidaturas);
$stmt_candidaturas->bind_param("i", $id_usuario);
$stmt_candidaturas->execute();
$res_candidaturas = $stmt_candidaturas->get_result();

$candidaturas = [];
while ($row = $res_candidaturas->fetch_assoc()) {
    $candidaturas[] = $row;
}
/* ===== Alterar Senha ===== */
if (isset($_POST['acao']) && $_POST['acao'] === 'alterar_senha') {
    // Verifica se todos os campos foram preenchidos
    if (empty($_POST['senha_atual']) || empty($_POST['nova_senha']) || empty($_POST['confirmar_senha'])) {
        $erro = "Todos os campos são obrigatórios.";
    } else {
        $senha_atual = md5($_POST['senha_atual']);
        $nova_senha = $_POST['nova_senha'];
        $confirmar_senha = $_POST['confirmar_senha'];

        if ($nova_senha !== $confirmar_senha) {
            $erro = "A nova senha e a confirmação não coincidem.";
        } else {
            // Verifica a senha atual
            $sql_check = "SELECT senha FROM usuarios WHERE id_usuario = ?";
            $stmt = $conn->prepare($sql_check);
            $stmt->bind_param("i", $id_usuario);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($result && $result['senha'] === $senha_atual) {
                // Aplica MD5 na nova senha antes de salvar
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
        // 1. Definir caminho base (relativo ao arquivo atual)
        $pastaBase = realpath(dirname(__FILE__)) . '/../img/foto-perfil/';
        
        // 2. Criar pasta base se não existir
        if (!file_exists($pastaBase)) {
            mkdir($pastaBase, 0777, true);
        }
        
        // 3. Criar pasta única para o usuário usando uniqid()
        $pastaUsuario = uniqid();
        $caminhoPastaUsuario = $pastaBase . $pastaUsuario;
        
        if (!file_exists($caminhoPastaUsuario)) {
            mkdir($caminhoPastaUsuario, 0777, true);
        }
        
        // 4. Processar o arquivo
        $extensao = strtolower(pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION));
        $nomeFoto = 'perfil.' . $extensao;
        $caminhoCompleto = $caminhoPastaUsuario . '/' . $nomeFoto;
        
        // 5. Verificar se é uma imagem válida
        $check = getimagesize($_FILES['foto_perfil']['tmp_name']);
        if ($check !== false && in_array($extensao, ['jpg', 'jpeg', 'png', 'gif'])) {
            if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $caminhoCompleto)) {
                // 6. Salvar caminho relativo no formato desejado
                $novaFoto = './img/foto-perfil/' . $pastaUsuario . '/' . $nomeFoto;
                
                $sql_update_foto = "UPDATE usuarios SET foto_perfil = ? WHERE id_usuario = ?";
                $stmt = $conn->prepare($sql_update_foto);
                $stmt->bind_param("si", $novaFoto, $id_usuario);
                
                if ($stmt->execute()) {
                    $sucesso = "Foto de perfil atualizada com sucesso!";
                    // Atualizar a sessão e recarregar a página
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
$stmt_usuario->close();
$stmt_candidaturas->close();
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
                            <input type="text" class="form-control" name="endereco_rua"
                                value="<?php echo htmlspecialchars($usuario['endereco_rua'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Número</label>
                            <input type="text" class="form-control" name="endereco_numero"
                                value="<?php echo htmlspecialchars($usuario['endereco_numero'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Complemento</label>
                            <input type="text" class="form-control" name="endereco_complemento"
                                value="<?php echo htmlspecialchars($usuario['endereco_complemento'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Cidade</label>
                            <input type="text" class="form-control" name="endereco_cidade"
                                value="<?php echo htmlspecialchars($usuario['endereco_cidade'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Estado (UF)</label>
                            <input type="text" class="form-control" name="endereco_estado" maxlength="2"
                                value="<?php echo htmlspecialchars($usuario['endereco_estado'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">CEP</label>
                            <input type="text" class="form-control" name="endereco_cep"
                                value="<?php echo htmlspecialchars($usuario['endereco_cep'] ?? ''); ?>">
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

            <!-- Aba Segurança -->
            <!-- Aba Segurança -->
            <div class="tab-pane fade" id="seguranca">
                <h2 class="mb-4">Configurações de Segurança</h2>
                <?php if (!empty($erro) && isset($_POST['acao']) && $_POST['acao'] === 'alterar_senha'): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
                <?php endif; ?>
                <?php if (!empty($sucesso) && isset($_POST['acao']) && $_POST['acao'] === 'alterar_senha'): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="acao" value="alterar_senha">
                    <div class="mb-3">
                        <label for="senha_atual" class="form-label">Senha Atual</label>
                        <input type="password" class="form-control" id="senha_atual" name="senha_atual" required>
                    </div>
                    <div class="mb-3">
                        <label for="nova_senha" class="form-label">Nova Senha</label>
                        <input type="password" class="form-control" id="nova_senha" name="nova_senha" required minlength="6">
                        <small class="text-muted">A senha deve ter pelo menos 6 caracteres</small>
                    </div>
                    <div class="mb-3">
                        <label for="confirmar_senha" class="form-label">Confirmar Nova Senha</label>
                        <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" required minlength="6">
                    </div>
                    <button type="submit" class="btn btn-primary">Alterar Senha</button>
                </form>
            </div>
    </main>
    <?php if (!empty($erro)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <?php if (!empty($sucesso)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>
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
    <!-- Scripts Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>