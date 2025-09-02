<?php
session_start();
include_once("../db/conexao.php");

$id_usuario = (int)($_GET['id'] ?? 0);

if ($id_usuario <= 0) {
    exit("Candidato inválido.");
}

// Buscar dados principais
$stmt = $conn->prepare("SELECT u.*, c.id_curriculo, c.pdf_caminho 
                        FROM usuarios u
                        LEFT JOIN curriculo c ON c.id_usuario = u.id_usuario
                        WHERE u.id_usuario = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$resultado = $stmt->get_result();
$candidato = $resultado->fetch_assoc();

if (!$candidato) {
    exit("Candidato não encontrado.");
}

// Função para corrigir o caminho da imagem
function corrigirCaminhoImagem($caminho) {
    if (empty($caminho)) {
        return '../img/user.png';
    }

    // Se for URL externa (Google, etc.)
    if (preg_match('/^https?:\/\//', $caminho)) {
        return $caminho;
    }

    // Se já vier no formato correto (../img/...)
    if (strpos($caminho, '../img/') === 0) {
        return $caminho;
    }

    // Se vier com ./img/, ajusta para ../img/
    if (strpos($caminho, './img/') === 0) {
        return '../' . substr($caminho, 2);
    }

    // Se não atender nenhum caso, imagem padrão
    return '../img/default-profile.png';
}

// Inicializar arrays vazios
$formacoes = [];
$experiencias = [];
$habilidades = [];

// Buscar dados relacionados apenas se o currículo existir
if (!empty($candidato['id_curriculo'])) {
    $id_curriculo = (int)$candidato['id_curriculo'];
    
    // Buscar formações
    $stmt_form = $conn->prepare("SELECT * FROM formacoes WHERE id_curriculo = ?");
    $stmt_form->bind_param("i", $id_curriculo);
    $stmt_form->execute();
    $formacoes = $stmt_form->get_result();
    
    // Buscar experiências
    $stmt_exp = $conn->prepare("SELECT * FROM experiencias WHERE id_curriculo = ?");
    $stmt_exp->bind_param("i", $id_curriculo);
    $stmt_exp->execute();
    $experiencias = $stmt_exp->get_result();
    
    // Buscar habilidades
    $stmt_hab = $conn->prepare("SELECT * FROM habilidades WHERE id_curriculo = ?");
    $stmt_hab->bind_param("i", $id_curriculo);
    $stmt_hab->execute();
    $habilidades = $stmt_hab->get_result();
}

// Verificar se há dados para exibir
$tem_formacoes = (!empty($formacoes) && is_object($formacoes) && $formacoes->num_rows > 0);
$tem_experiencias = (!empty($experiencias) && is_object($experiencias) && $experiencias->num_rows > 0);
$tem_habilidades = (!empty($habilidades) && is_object($habilidades) && $habilidades->num_rows > 0);
$tem_curriculo_pdf = (!empty($candidato['pdf_caminho']) && file_exists($candidato['pdf_caminho']));
?>

<div class="text-start">
    <div class="d-flex align-items-center mb-3">
        <img src="<?= htmlspecialchars(corrigirCaminhoImagem($candidato['foto_perfil'] ?? '')) ?>" 
             class="rounded-circle me-3" style="width:70px;height:70px;object-fit:cover;"
             onerror="this.src='../img/default-profile.png'">
        <div>
            <h5><?= htmlspecialchars($candidato['nome_completo'] ?? 'Nome não informado') ?></h5>
            <p class="mb-0"><?= htmlspecialchars($candidato['email'] ?? '') ?> | <?= htmlspecialchars($candidato['telefone'] ?? '-') ?></p>
        </div>
    </div>

    <h6>Resumo Profissional</h6>
    <p><?= !empty($candidato['resumo_profissional']) ? nl2br(htmlspecialchars($candidato['resumo_profissional'])) : 'Não informado' ?></p>

    <h6>Formações</h6>
    <ul>
        <?php if ($tem_formacoes): ?>
            <?php while ($f = $formacoes->fetch_assoc()): ?>
                <li>
                    <?= htmlspecialchars($f['curso'] ?? '') ?> - 
                    <?= htmlspecialchars($f['instituicao'] ?? '') ?> 
                    (<?= htmlspecialchars($f['nivel_formacao'] ?? '') ?>)
                </li>
            <?php endwhile; ?>
        <?php else: ?>
            <li>Nenhuma formação cadastrada</li>
        <?php endif; ?>
    </ul>

    <h6>Experiências</h6>
    <ul>
        <?php if ($tem_experiencias): ?>
            <?php while ($e = $experiencias->fetch_assoc()): ?>
                <li>
                    <strong><?= htmlspecialchars($e['cargo'] ?? '') ?></strong> na 
                    <?= htmlspecialchars($e['empresa'] ?? '') ?>
                    (<?= !empty($e['trabalho_atual']) ? 'Atual' : 
                        (htmlspecialchars($e['data_inicio'] ?? '') . ' até ' . htmlspecialchars($e['data_fim'] ?? '')) ?>)
                </li>
            <?php endwhile; ?>
        <?php else: ?>
            <li>Nenhuma experiência cadastrada</li>
        <?php endif; ?>
    </ul>

    <h6>Habilidades</h6>
    <div class="d-flex flex-wrap gap-2">
        <?php if ($tem_habilidades): ?>
            <?php while ($h = $habilidades->fetch_assoc()): ?>
                <span class="badge bg-secondary">
                    <?= htmlspecialchars($h['nome_habilidade'] ?? '') ?> 
                    (<?= htmlspecialchars($h['nivel_habilidade'] ?? '') ?>)
                </span>
            <?php endwhile; ?>
        <?php else: ?>
            <span>Nenhuma habilidade cadastrada</span>
        <?php endif; ?>
    </div>

    <?php if ($tem_curriculo_pdf): ?>
        <div class="mt-3">
            <a href="<?= htmlspecialchars($candidato['pdf_caminho']) ?>" target="_blank" class="btn btn-danger">
                <i class="fas fa-file-pdf"></i> Ver Currículo PDF
            </a>
        </div>
    <?php endif; ?>
</div>
