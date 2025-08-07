<?php
session_start();
include_once("../db/conexao.php");

// Verifica se o usuário está logado
$usuarioLogado = isset($_SESSION['id_usuario']);

// Função para buscar vagas
function buscarVagas($conn, $filtro = '') {
    $sql = "SELECT v.*, e.nome as empresa_nome, e.url_logo 
            FROM vagas v 
            JOIN empresas e ON v.id_empresa = e.id_empresa 
            WHERE v.ativa = 1";
    
    if (!empty($filtro)) {
        $sql .= " AND (v.titulo LIKE ? OR e.nome LIKE ? OR v.localizacao LIKE ?)";
        $filtro = "%$filtro%";
    }
    
    $sql .= " ORDER BY v.data_publicacao DESC";
    
    $stmt = $conn->prepare($sql);
    if (!empty($filtro)) {
        $stmt->bind_param("sss", $filtro, $filtro, $filtro);
    }
    $stmt->execute();
    return $stmt->get_result();
}

// Processar pesquisa
$filtro = isset($_GET['search']) ? trim($_GET['search']) : '';
$vagas = buscarVagas($conn, $filtro);

// Se uma vaga específica foi selecionada
$vagaSelecionada = null;
if (isset($_GET['id_vaga'])) {
    $id_vaga = intval($_GET['id_vaga']);
    $sql = "SELECT v.*, e.nome as empresa_nome, e.url_logo 
            FROM vagas v 
            JOIN empresas e ON v.id_empresa = e.id_empresa 
            WHERE v.id_vaga = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_vaga);
    $stmt->execute();
    $vagaSelecionada = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Vagas Disponíveis | JobSearch</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/pag2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .vaga-search-wrapper {
            position: relative;
        }
        .vaga-search-input {
            display: flex;
            align-items: center;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 8px 12px;
        }
        .vaga-search-input span {
            margin-right: 8px;
            color: #666;
        }
        .vaga-search-input input {
            border: none;
            outline: none;
            width: 100%;
        }
        .sticky-card {
            position: sticky;
            top: 20px;
        }
        .list-group-item {
            transition: all 0.3s ease;
        }
        .list-group-item:hover {
            background-color: #f8f9fa;
        }
        
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-md" role="navigation" aria-label="Menu principal">
        <div class="navbar-container">
            <a href="index.php" class="navbar-brand" aria-label="Página inicial JobSearch">
                <img style="width: 90px;" src="../img/Logo design for a job search platform named 'Contrata'. Use a modern, technological style with a bol(1) (1).png" alt="JobSearch">
            </a>

            <div class="nav-always-visible">
                <a href="Pagina-vagas.php" class="nav-link" tabindex="0">
                    <span class="material-icons" aria-hidden="true">list_alt</span>
                    Vagas Ativas
                </a>
                <a href="pag-cargos.php" class="nav-link" tabindex="0">
                    <span class="material-icons" aria-hidden="true">next_week</span>
                    Cargos/Salarios
                </a>
            </div>

            <div class="user-status" aria-live="polite" aria-atomic="true" aria-label="Usuário logado">
                <span class="material-icons" aria-hidden="true">account_circle</span>
                <?php if($usuarioLogado): ?>
                    Olá, <?php echo htmlspecialchars($_SESSION['nome_completo']); ?>
                <?php else: ?>
                    Visitante
                <?php endif; ?>
            </div>

            <button class="custom-toggle" type="button" aria-label="Abrir menu de navegação"
                data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav"
                aria-expanded="false">
                <span class="material-icons" aria-hidden="true">menu</span>
            </button>
        </div>

        <div class="collapse navbar-collapse navbar-expand-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto align-items-center" role="menu">
                <li class="nav-item" role="none">
                    <a href="pag-minha-conta.php" class="nav-link" tabindex="0" role="menuitem">
                        <span class="material-icons" aria-hidden="true">account_circle</span>
                        Minha Conta
                    </a>
                </li>
                <li class="nav-item" role="none">
                    <a href="Meu-curriculo.php" class="nav-link" tabindex="0" role="menuitem">
                        <span class="material-icons" aria-hidden="true">description</span>
                        Meu Currículo
                    </a>
                </li>
                <li class="nav-item" role="none">
                    <a href="pag-candidaturas.php" class="nav-link" tabindex="0" role="menuitem">
                        <span class="material-icons" aria-hidden="true">work_outline</span>
                        Minhas Candidaturas
                    </a>
                </li>
            </ul>
            <div class="auth-buttons">
                <?php if($usuarioLogado): ?>
                    <a href="../logout.php">
                        <button type="button" class="btn btn-entrar" tabindex="0">Sair</button>
                    </a>
                <?php else: ?>
                    <a href="Login.php">
                        <button type="button" class="btn btn-entrar" tabindex="0">Entrar</button>
                    </a>
                    <a href="Crie-conta.php">
                        <button type="button" class="btn btn-cadastrar" tabindex="0">Cadastrar</button>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row">
            <!-- Lista de vagas -->
            <div class="col-md-4">
                <form method="GET" action="Pagina-vagas.php" class="mb-3">
                    <div class="vaga-search-wrapper">
                        <div class="vaga-search-input">
                            <span class="material-icons" aria-hidden="true">search</span>
                            <input type="search" name="search" placeholder="Buscar vaga, empresa ou local..." 
                                   value="<?php echo htmlspecialchars($filtro); ?>" />
                        </div>
                    </div>
                </form>
                
                <h4 class="mb-3">Vagas Disponíveis</h4>
                <div class="list-group" id="jobList">
                    <?php if ($vagas->num_rows > 0): ?>
                        <?php while ($vaga = $vagas->fetch_assoc()): ?>
                            <a href="Pagina-vagas.php?search=<?php echo urlencode($filtro); ?>&id_vaga=<?php echo $vaga['id_vaga']; ?>"
                               class="list-group-item list-group-item-action d-flex gap-3 align-items-start <?php echo ($vagaSelecionada && $vagaSelecionada['id_vaga'] == $vaga['id_vaga']) ? 'active' : ''; ?>">
                                <img src="<?php echo htmlspecialchars($vaga['url_logo']); ?>" alt="<?php echo htmlspecialchars($vaga['empresa_nome']); ?>" width="48" height="48" style="object-fit: contain;">
                                <div class="text-start">
                                    <div class="fw-bold"><?php echo htmlspecialchars($vaga['empresa_nome']); ?></div>
                                    <small class="<?php echo ($vagaSelecionada && $vagaSelecionada['id_vaga'] == $vaga['id_vaga']) ?  : 'text-muted'; ?>">
                                        <?php echo htmlspecialchars($vaga['tipo_contrato']); ?> • <?php echo htmlspecialchars($vaga['localizacao']); ?>
                                    </small>
                                    <p class="mb-0 small <?php echo ($vagaSelecionada && $vagaSelecionada['id_vaga'] == $vaga['id_vaga']) ?  : 'text-secondary'; ?>">
                                        <?php echo htmlspecialchars($vaga['titulo']); ?>
                                    </p>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="alert alert-info">Nenhuma vaga encontrada.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Detalhes da vaga -->
            <div class="col-md-8">
                <div class="card shadow-sm sticky-card">
                    <div class="card-body">
                        <?php if ($vagaSelecionada): ?>
                            <h4 class="card-title"><?php echo htmlspecialchars($vagaSelecionada['titulo']); ?></h4>
                            <p class="card-text"><?php echo nl2br(htmlspecialchars($vagaSelecionada['descricao'])); ?></p>

                            <ul class="list-unstyled mt-4">
                                <li><strong>Empresa:</strong> <?php echo htmlspecialchars($vagaSelecionada['empresa_nome']); ?></li>
                                <li><strong>Tipo de contrato:</strong> <?php echo htmlspecialchars($vagaSelecionada['tipo_contrato']); ?></li>
                                <li><strong>Local:</strong> <?php echo htmlspecialchars($vagaSelecionada['localizacao']); ?></li>
                                <li><strong>Salário:</strong> <?php echo htmlspecialchars($vagaSelecionada['faixa_salarial']); ?></li>
                                <?php if ($vagaSelecionada['remoto']): ?>
                                    <li><strong>Trabalho Remoto:</strong> Sim</li>
                                <?php endif; ?>
                                <li><strong>Publicada em:</strong> <?php echo date('d/m/Y', strtotime($vagaSelecionada['data_publicacao'])); ?></li>
                            </ul>

                            <?php if ($usuarioLogado): ?>
                                <a href="formulario-curriculo.php?id_vaga=<?php echo $vagaSelecionada['id_vaga']; ?>" class="btn btn-entrar mt-4">Enviar Currículo</a>
                            <?php else: ?>
                                <div class="alert alert-warning mt-4">
                                    <a href="Login.php" class="alert-link">Faça login</a> para se candidatar a esta vaga.
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <h4 class="card-title">Selecione uma vaga</h4>
                            <p class="card-text">Clique em uma vaga à esquerda para visualizar os detalhes aqui.</p>
                            <ul class="list-unstyled mt-4">
                                <li><strong>Empresa:</strong> -</li>
                                <li><strong>Tipo de contrato:</strong> -</li>
                                <li><strong>Local:</strong> -</li>
                                <li><strong>Salário:</strong> -</li>
                            </ul>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script>
        // Filtro em tempo real (opcional - pode remover se quiser usar apenas o submit do form)
        $(document).ready(function() {
            $('[name="search"]').on('input', function() {
                const termo = $(this).val().toLowerCase();
                $('.list-group-item').each(function() {
                    const texto = $(this).text().toLowerCase();
                    $(this).toggle(texto.includes(termo));
                });
            });
        });
    </script>
</body>
</html>