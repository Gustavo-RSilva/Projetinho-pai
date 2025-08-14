<?php
session_start();
include_once("../db/conexao.php");

if (!isset($_SESSION['id_usuario'])) {
    header("Location: Login.html");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

// Processar envio do formulário
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Processar informações pessoais
    $nome_completo = $_POST['fullName'];
    $email = $_POST['email'];
    $telefone = $_POST['phone'];
    $data_nascimento = $_POST['birthDate'];
    $resumo_profissional = $_POST['about'];
    
    // Processar endereço
    $endereco_rua = $_POST['street'];
    $endereco_numero = $_POST['number'];
    $endereco_complemento = $_POST['complement'];
    $endereco_cidade = $_POST['city'];
    $endereco_estado = $_POST['state'];
    $endereco_cep = $_POST['zipCode'];
    
    // Atualizar informações do usuário
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
    
    $stmt->execute();
    $stmt->close();
    
    // Verificar se o usuário já tem um currículo
    $sql_curriculo = "SELECT id_curriculo FROM Curriculo WHERE id_usuario = ? LIMIT 1";
    $stmt = $conn->prepare($sql_curriculo);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $curriculo = $result->fetch_assoc();
        $id_curriculo = $curriculo['id_curriculo'];
        
        // Limpar dados antigos
        $sql_delete = "DELETE FROM formacoes WHERE id_curriculo = ?";
        $stmt = $conn->prepare($sql_delete);
        $stmt->bind_param("i", $id_curriculo);
        $stmt->execute();
        
        $sql_delete = "DELETE FROM experiencias WHERE id_curriculo = ?";
        $stmt = $conn->prepare($sql_delete);
        $stmt->bind_param("i", $id_curriculo);
        $stmt->execute();
        
        $sql_delete = "DELETE FROM habilidades WHERE id_curriculo = ?";
        $stmt = $conn->prepare($sql_delete);
        $stmt->bind_param("i", $id_curriculo);
        $stmt->execute();
    } else {
        // Criar novo currículo
        $sql_insert = "INSERT INTO Curriculo (id_usuario, pdf_nome, pdf_tipo, pdf_caminho) 
                      VALUES (?, 'Currículo Online', 'online', './meu-curriculo.php')";
        $stmt = $conn->prepare($sql_insert);
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $id_curriculo = $stmt->insert_id;
        $stmt->close();
    }
    
    // Processar formações
    if (isset($_POST['institution'])) {
        for ($i = 0; $i < count($_POST['institution']); $i++) {
            if (!empty($_POST['institution'][$i])) {
                $instituicao = $_POST['institution'][$i];
                $curso = $_POST['course'][$i];
                $nivel_formacao = $_POST['degree'][$i];
                $data_inicio = $_POST['startDate'][$i];
                $data_conclusao = $_POST['endDate'][$i];
                $cursando = isset($_POST['currentlyStudying'][$i]) ? 1 : 0;
                
                $sql = "INSERT INTO formacoes (id_curriculo, instituicao, curso, nivel_formacao, data_inicio, data_conclusao, cursando) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isssssi", $id_curriculo, $instituicao, $curso, $nivel_formacao, $data_inicio, $data_conclusao, $cursando);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
    
    // Processar experiências
    if (isset($_POST['company'])) {
        for ($i = 0; $i < count($_POST['company']); $i++) {
            if (!empty($_POST['company'][$i])) {
                $empresa = $_POST['company'][$i];
                $cargo = $_POST['position'][$i];
                $data_inicio = $_POST['jobStartDate'][$i];
                $data_fim = $_POST['jobEndDate'][$i];
                $trabalho_atual = isset($_POST['currentlyWorking'][$i]) ? 1 : 0;
                $responsabilidades = $_POST['responsibilities'][$i];
                
                $sql = "INSERT INTO experiencias (id_curriculo, empresa, cargo, data_inicio, data_fim, trabalho_atual, responsabilidades) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("issssis", $id_curriculo, $empresa, $cargo, $data_inicio, $data_fim, $trabalho_atual, $responsabilidades);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
    
    // Processar habilidades
    if (isset($_POST['skills'])) {
        foreach ($_POST['skills'] as $habilidade) {
            $sql = "INSERT INTO habilidades (id_curriculo, nome_habilidade) 
                    VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $id_curriculo, $habilidade);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    header("Location: curriculos.php?sucesso=Currículo atualizado com sucesso!");
    exit();
}

// Consultar dados do usuário
$sql_usuario = "SELECT nome_completo, email, telefone, data_nascimento,
               endereco_rua, endereco_numero, endereco_complemento,
               endereco_cidade, endereco_estado, endereco_cep,
               resumo_profissional
        FROM usuarios
        WHERE id_usuario = ?";
$stmt = $conn->prepare($sql_usuario);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();

// Consultar currículo existente
$sql_curriculo = "SELECT c.id_curriculo, 
                 GROUP_CONCAT(DISTINCT f.instituicao, '|', f.curso, '|', f.nivel_formacao, '|', 
                 f.data_inicio, '|', IFNULL(f.data_conclusao, ''), '|', f.cursando SEPARATOR ';;') as formacoes,
                 GROUP_CONCAT(DISTINCT e.empresa, '|', e.cargo, '|', e.data_inicio, '|', 
                 IFNULL(e.data_fim, ''), '|', e.trabalho_atual, '|', IFNULL(e.responsabilidades, '') SEPARATOR ';;') as experiencias,
                 GROUP_CONCAT(DISTINCT h.nome_habilidade SEPARATOR ';;') as habilidades
          FROM Curriculo c
          LEFT JOIN formacoes f ON c.id_curriculo = f.id_curriculo
          LEFT JOIN experiencias e ON c.id_curriculo = e.id_curriculo
          LEFT JOIN habilidades h ON c.id_curriculo = h.id_curriculo
          WHERE c.id_usuario = ?
          GROUP BY c.id_curriculo";
$stmt = $conn->prepare($sql_curriculo);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
$curriculo = $result->fetch_assoc();

$formacoes = [];
$experiencias = [];
$habilidades = [];

if ($curriculo) {
    if ($curriculo['formacoes']) {
        $formacoes_array = explode(';;', $curriculo['formacoes']);
        foreach ($formacoes_array as $formacao_str) {
            $parts = explode('|', $formacao_str);
            $formacoes[] = [
                'instituicao' => $parts[0],
                'curso' => $parts[1],
                'nivel_formacao' => $parts[2],
                'data_inicio' => $parts[3],
                'data_conclusao' => $parts[4],
                'cursando' => $parts[5]
            ];
        }
    }
    
    if ($curriculo['experiencias']) {
        $experiencias_array = explode(';;', $curriculo['experiencias']);
        foreach ($experiencias_array as $experiencia_str) {
            $parts = explode('|', $experiencia_str);
            $experiencias[] = [
                'empresa' => $parts[0],
                'cargo' => $parts[1],
                'data_inicio' => $parts[2],
                'data_fim' => $parts[3],
                'trabalho_atual' => $parts[4],
                'responsabilidades' => $parts[5]
            ];
        }
    }
    
    if ($curriculo['habilidades']) {
        $habilidades = explode(';;', $curriculo['habilidades']);
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulário de Currículo</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome para ícones -->
    <link rel="stylesheet" href="../css/Meu-curriculo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-md" role="navigation" aria-label="Menu principal">
        <div class="navbar-container">
            <!-- Botão Voltar -->
            <a href="curriculos.php" class="nav-back-button" aria-label="Voltar para currículos">
                <span class="material-icons" aria-hidden="true">arrow_back</span>
                Voltar
            </a>
           
            <!-- Logo -->
            <a href="#" class="navbar-brand" aria-label="Página inicial">
                <img src="../img/Logo design for a job search platform named 'Contrata'. Use a modern, technological style with a bol(1) (1).png" alt="JobSearch">
            </a>
        </div>
    </nav>
    
    <div class="container">
        <h1 class="header-title mb-4">
            <i class="fas fa-file-alt me-2"></i>Formulário de Currículo
        </h1>
        
        <form id="resumeForm" method="POST">
            <!-- Informações Pessoais -->
            <div class="resume-card">
                <div class="card-header" id="personalInfoHeader">
                    <span><i class="fas fa-user card-icon"></i>Informações Pessoais</span>
                    <button type="button" class="toggle-btn">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
                <div class="card-body show" id="personalInfoBody">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="fullName" class="form-label">Nome Completo*</label>
                                <input type="text" class="form-control" id="fullName" name="fullName" 
                                       value="<?php echo htmlspecialchars($usuario['nome_completo'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email" class="form-label">E-mail*</label>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="phone" class="form-label">Telefone</label>
                                <input type="tel" class="form-control" id="phone" name="phone"
                                       value="<?php echo htmlspecialchars($usuario['telefone'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="birthDate" class="form-label">Data de Nascimento</label>
                                <input type="date" class="form-control" id="birthDate" name="birthDate"
                                       value="<?php echo htmlspecialchars($usuario['data_nascimento'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label for="about" class="form-label">Breve Resumo Profissional</label>
                                <textarea class="form-control" id="about" name="about" rows="3"><?php 
                                    echo htmlspecialchars($usuario['resumo_profissional'] ?? ''); 
                                ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Endereço -->
            <div class="resume-card">
                <div class="card-header" id="addressHeader">
                    <span><i class="fas fa-map-marker-alt card-icon"></i>Endereço</span>
                    <button type="button" class="toggle-btn">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
                <div class="card-body" id="addressBody">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="street" class="form-label">Rua</label>
                                <input type="text" class="form-control" id="street" name="street"
                                       value="<?php echo htmlspecialchars($usuario['endereco_rua'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="number" class="form-label">Número</label>
                                <input type="text" class="form-control" id="number" name="number"
                                       value="<?php echo htmlspecialchars($usuario['endereco_numero'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <label for="city" class="form-label">Cidade</label>
                                <input type="text" class="form-control" id="city" name="city"
                                       value="<?php echo htmlspecialchars($usuario['endereco_cidade'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="state" class="form-label">Estado</label>
                                <input type="text" class="form-control" id="state" name="state"
                                       value="<?php echo htmlspecialchars($usuario['endereco_estado'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="zipCode" class="form-label">CEP</label>
                                <input type="text" class="form-control" id="zipCode" name="zipCode"
                                       value="<?php echo htmlspecialchars($usuario['endereco_cep'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label for="complement" class="form-label">Complemento</label>
                                <input type="text" class="form-control" id="complement" name="complement"
                                       value="<?php echo htmlspecialchars($usuario['endereco_complemento'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Formação Acadêmica -->
            <div class="resume-card">
                <div class="card-header" id="educationHeader">
                    <span><i class="fas fa-graduation-cap card-icon"></i>Formação Acadêmica</span>
                    <button type="button" class="toggle-btn">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
                <div class="card-body" id="educationBody">
                    <div id="educationItems">
                        <?php if (empty($formacoes)): ?>
                            <div class="repeater-item">
                                <button type="button" class="remove-item">
                                    <i class="fas fa-times"></i>
                                </button>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Instituição de Ensino</label>
                                            <input type="text" class="form-control" name="institution[]">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Curso</label>
                                            <input type="text" class="form-control" name="course[]">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label">Grau</label>
                                            <select class="form-control" name="degree[]">
                                                <option value="">Selecione...</option>
                                                <option value="Ensino Médio">Ensino Médio</option>
                                                <option value="Técnico">Técnico</option>
                                                <option value="Graduação">Graduação</option>
                                                <option value="Pós-Graduação">Pós-Graduação</option>
                                                <option value="Mestrado">Mestrado</option>
                                                <option value="Doutorado">Doutorado</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label">Data de Início</label>
                                            <input type="month" class="form-control" name="startDate[]">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label">Data de Conclusão</label>
                                            <input type="month" class="form-control" name="endDate[]">
                                            <div class="form-check mt-2">
                                                <input class="form-check-input" type="checkbox" name="currentlyStudying[]" id="currentlyStudying">
                                                <label class="form-check-label" for="currentlyStudying">Cursando atualmente</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($formacoes as $formacao): ?>
                                <div class="repeater-item">
                                    <button type="button" class="remove-item">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">Instituição de Ensino</label>
                                                <input type="text" class="form-control" name="institution[]" 
                                                       value="<?php echo htmlspecialchars($formacao['instituicao']); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">Curso</label>
                                                <input type="text" class="form-control" name="course[]"
                                                       value="<?php echo htmlspecialchars($formacao['curso']); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label">Grau</label>
                                                <select class="form-control" name="degree[]">
                                                    <option value="">Selecione...</option>
                                                    <option value="Ensino Médio" <?php echo $formacao['nivel_formacao'] == 'Ensino Médio' ? 'selected' : ''; ?>>Ensino Médio</option>
                                                    <option value="Técnico" <?php echo $formacao['nivel_formacao'] == 'Técnico' ? 'selected' : ''; ?>>Técnico</option>
                                                    <option value="Graduação" <?php echo $formacao['nivel_formacao'] == 'Graduação' ? 'selected' : ''; ?>>Graduação</option>
                                                    <option value="Pós-Graduação" <?php echo $formacao['nivel_formacao'] == 'Pós-Graduação' ? 'selected' : ''; ?>>Pós-Graduação</option>
                                                    <option value="Mestrado" <?php echo $formacao['nivel_formacao'] == 'Mestrado' ? 'selected' : ''; ?>>Mestrado</option>
                                                    <option value="Doutorado" <?php echo $formacao['nivel_formacao'] == 'Doutorado' ? 'selected' : ''; ?>>Doutorado</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label">Data de Início</label>
                                                <input type="month" class="form-control" name="startDate[]"
                                                       value="<?php echo htmlspecialchars($formacao['data_inicio']); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label">Data de Conclusão</label>
                                                <input type="month" class="form-control" name="endDate[]"
                                                       value="<?php echo htmlspecialchars($formacao['data_conclusao']); ?>">
                                                <div class="form-check mt-2">
                                                    <input class="form-check-input" type="checkbox" name="currentlyStudying[]" 
                                                           id="currentlyStudying" <?php echo $formacao['cursando'] ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="currentlyStudying">Cursando atualmente</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn-add" id="addEducation">
                        <i class="fas fa-plus"></i> Adicionar Formação
                    </button>
                </div>
            </div>
            
            <!-- Experiência Profissional -->
            <div class="resume-card">
                <div class="card-header" id="experienceHeader">
                    <span><i class="fas fa-briefcase card-icon"></i>Experiência Profissional</span>
                    <button type="button" class="toggle-btn">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
                <div class="card-body" id="experienceBody">
                    <div id="experienceItems">
                        <?php if (empty($experiencias)): ?>
                            <div class="repeater-item">
                                <button type="button" class="remove-item">
                                    <i class="fas fa-times"></i>
                                </button>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Empresa</label>
                                            <input type="text" class="form-control" name="company[]">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Cargo</label>
                                            <input type="text" class="form-control" name="position[]">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Data de Início</label>
                                            <input type="month" class="form-control" name="jobStartDate[]">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Data de Término</label>
                                            <input type="month" class="form-control" name="jobEndDate[]">
                                            <div class="form-check mt-2">
                                                <input class="form-check-input" type="checkbox" name="currentlyWorking[]" id="currentlyWorking">
                                                <label class="form-check-label" for="currentlyWorking">Trabalho atual</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label class="form-label">Principais Responsabilidades</label>
                                            <textarea class="form-control" name="responsibilities[]" rows="3"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($experiencias as $experiencia): ?>
                                <div class="repeater-item">
                                    <button type="button" class="remove-item">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">Empresa</label>
                                                <input type="text" class="form-control" name="company[]"
                                                       value="<?php echo htmlspecialchars($experiencia['empresa']); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">Cargo</label>
                                                <input type="text" class="form-control" name="position[]"
                                                       value="<?php echo htmlspecialchars($experiencia['cargo']); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">Data de Início</label>
                                                <input type="month" class="form-control" name="jobStartDate[]"
                                                       value="<?php echo htmlspecialchars($experiencia['data_inicio']); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">Data de Término</label>
                                                <input type="month" class="form-control" name="jobEndDate[]"
                                                       value="<?php echo htmlspecialchars($experiencia['data_fim']); ?>">
                                                <div class="form-check mt-2">
                                                    <input class="form-check-input" type="checkbox" name="currentlyWorking[]" 
                                                           id="currentlyWorking" <?php echo $experiencia['trabalho_atual'] ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="currentlyWorking">Trabalho atual</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label class="form-label">Principais Responsabilidades</label>
                                                <textarea class="form-control" name="responsibilities[]" rows="3"><?php 
                                                    echo htmlspecialchars($experiencia['responsabilidades']); 
                                                ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn-add" id="addExperience">
                        <i class="fas fa-plus"></i> Adicionar Experiência
                    </button>
                </div>
            </div>
        
            <!-- Habilidades -->
            <div class="resume-card">
                <div class="card-header" id="skillsHeader">
                    <span><i class="fas fa-code card-icon"></i>Habilidades</span>
                    <button type="button" class="toggle-btn">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
                <div class="card-body" id="skillsBody">
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label">Selecione suas habilidades principais</label>
                                <div class="skills-container">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="leadership" name="skills[]" value="Liderança"
                                            <?php echo in_array('Liderança', $habilidades) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="leadership">Liderança</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="teamwork" name="skills[]" value="Trabalho em Equipe"
                                            <?php echo in_array('Trabalho em Equipe', $habilidades) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="teamwork">Trabalho em Equipe</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="communication" name="skills[]" value="Comunicação"
                                            <?php echo in_array('Comunicação', $habilidades) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="communication">Comunicação</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="problemSolving" name="skills[]" value="Resolução de Problemas"
                                            <?php echo in_array('Resolução de Problemas', $habilidades) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="problemSolving">Resolução de Problemas</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="creativity" name="skills[]" value="Criatividade"
                                            <?php echo in_array('Criatividade', $habilidades) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="creativity">Criatividade</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="adaptability" name="skills[]" value="Adaptabilidade"
                                            <?php echo in_array('Adaptabilidade', $habilidades) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="adaptability">Adaptabilidade</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-end align-content-end mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Salvar Currículo
                </button>
            </div>
        </form>
    </div>

    <!-- Bootstrap JS e Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Função para alternar os cards
            function setupCardToggle(headerId, bodyId) {
                const header = document.getElementById(headerId);
                const body = document.getElementById(bodyId);
                const btn = header.querySelector('.toggle-btn');
                
                header.addEventListener('click', function() {
                    body.classList.toggle('show');
                    btn.classList.toggle('rotated');
                    
                    // Acessibilidade - alternar aria-expanded
                    const isExpanded = body.classList.contains('show');
                    header.setAttribute('aria-expanded', isExpanded);
                });
            }
            
            // Configurar o toggle para cada card
            setupCardToggle('personalInfoHeader', 'personalInfoBody');
            setupCardToggle('addressHeader', 'addressBody');
            setupCardToggle('educationHeader', 'educationBody');
            setupCardToggle('experienceHeader', 'experienceBody');
            setupCardToggle('skillsHeader', 'skillsBody');
            
            // Adicionar dinamicamente mais itens de formação
            document.getElementById('addEducation').addEventListener('click', function() {
                const container = document.getElementById('educationItems');
                const newItem = container.firstElementChild.cloneNode(true);
                
                // Limpar os valores dos inputs
                const inputs = newItem.querySelectorAll('input, textarea, select');
                inputs.forEach(input => {
                    if (input.type !== 'button') {
                        input.value = '';
                        if (input.type === 'checkbox') {
                            input.checked = false;
                        }
                    }
                });
                
                // Adicionar evento de remoção
                const removeBtn = newItem.querySelector('.remove-item');
                removeBtn.addEventListener('click', function() {
                    container.removeChild(newItem);
                });
                
                container.appendChild(newItem);
            });
            
            // Adicionar dinamicamente mais itens de experiência
            document.getElementById('addExperience').addEventListener('click', function() {
                const container = document.getElementById('experienceItems');
                const newItem = container.firstElementChild.cloneNode(true);
                
                // Limpar os valores dos inputs
                const inputs = newItem.querySelectorAll('input, textarea');
                inputs.forEach(input => {
                    if (input.type !== 'button') {
                        input.value = '';
                        if (input.type === 'checkbox') {
                            input.checked = false;
                        }
                    }
                });
                
                // Adicionar evento de remoção
                const removeBtn = newItem.querySelector('.remove-item');
                removeBtn.addEventListener('click', function() {
                    container.removeChild(newItem);
                });
                
                container.appendChild(newItem);
            });
            
            // Configurar remoção para itens existentes
            document.querySelectorAll('.remove-item').forEach(btn => {
                btn.addEventListener('click', function() {
                    const item = this.closest('.repeater-item');
                    const container = item.parentElement;
                    
                    // Não permitir remover o último item
                    if (container.children.length > 1) {
                        container.removeChild(item);
                    }
                });
            });
        });
    </script>
</body>
</html>