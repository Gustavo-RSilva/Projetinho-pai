<?php
// Início da sessão deve ser a primeira coisa no arquivo
session_start();

// Inclui a conexão com o banco de dados
require_once("../db/conexao.php");

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
    $nome_usuario = $_SESSION['nome_completo'] ?? 'Usuário';
}

// Inicializar variáveis de filtro
$filtro_status = isset($_GET['status']) ? $_GET['status'] : '';
$filtro_busca = isset($_GET['search']) ? $_GET['search'] : '';

// Construir a consulta SQL base
$sql = "
    SELECT 
        c.id_candidatura,
        v.titulo AS vaga_titulo,
        e.nome AS empresa_nome,
        v.localizacao,
        c.status,
        DATE_FORMAT(c.data_candidatura, '%d/%m/%Y') AS data_formatada,
        c.observacoes
    FROM candidaturas c
    INNER JOIN vagas v ON c.id_vaga = v.id_vaga
    INNER JOIN empresas e ON v.id_empresa = e.id_empresa
    WHERE c.id_usuario = ?
";

// Adicionar condições de filtro
$params = array($id_usuario);
$param_types = "i";

if (!empty($filtro_status)) {
    $sql .= " AND c.status = ?";
    $params[] = $filtro_status;
    $param_types .= "s";
}

if (!empty($filtro_busca)) {
    $sql .= " AND (v.titulo LIKE ? OR e.nome LIKE ?)";
    $search_term = "%" . $filtro_busca . "%";
    $params[] = $search_term;
    $params[] = $search_term;
    $param_types .= "ss";
}

$sql .= " ORDER BY c.data_candidatura DESC";

// Preparar e executar a consulta
$stmt = $conn->prepare($sql);

// Vincular parâmetros dinamicamente
if (count($params) > 1) {
    $stmt->bind_param($param_types, ...$params);
} else {
    $stmt->bind_param($param_types, $id_usuario);
}

$stmt->execute();
$result = $stmt->get_result();

// Cores para os status
$status_colors = [
    'Em análise' => 'warning',
    'Aprovado' => 'success',
    'Rejeitado' => 'danger',
    'Cancelado' => 'secondary'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Minhas Candidaturas</title>
    <link rel="icon" href="../img/icon/icone-pag.png" />

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Google Material Icocns -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
    <!-- CSS Personalizado -->
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/pag-candidatura.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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


    <!-- CONTEÚDO PRINCIPAL -->
    <div class="container my-5">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">Minhas Candidaturas</h1>

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-6">
                                <label for="status" class="form-label">Filtrar por status:</label>
                                <select id="status" name="status" class="form-select">
                                    <option value="">Todos</option>
                                    <option value="Em análise" <?= $filtro_status == 'Em análise' ? 'selected' : '' ?>>Em análise</option>
                                    <option value="Aprovado" <?= $filtro_status == 'Aprovado' ? 'selected' : '' ?>>Aprovado</option>
                                    <option value="Rejeitado" <?= $filtro_status == 'Rejeitado' ? 'selected' : '' ?>>Rejeitado</option>
                                    <option value="Cancelado" <?= $filtro_status == 'Cancelado' ? 'selected' : '' ?>>Cancelado</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="search" class="form-label">Buscar:</label>
                                <div class="input-group">
                                    <input type="text" id="search" name="search" class="form-control"
                                        placeholder="Vaga ou empresa" value="<?= htmlspecialchars($filtro_busca) ?>">
                                    <button type="submit" class="btn azul">Filtrar</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Lista de Candidaturas -->
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($candidatura = $result->fetch_assoc()): ?>
                        <div class="card mb-3 candidatura-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h3 class="card-title"><?= htmlspecialchars($candidatura['vaga_titulo']) ?></h3>
                                        <p class="card-subtitle mb-2 text-muted">
                                            <?= htmlspecialchars($candidatura['empresa_nome']) ?> -
                                            <?= htmlspecialchars($candidatura['localizacao']) ?>
                                        </p>
                                        <span class="badge bg-<?= $status_colors[$candidatura['status']] ?>">
                                            <?= $candidatura['status'] ?>
                                        </span>
                                        <p class="card-text mt-2">
                                            <small class="text-muted">
                                                Candidatura em: <?= $candidatura['data_formatada'] ?>
                                            </small>
                                        </p>
                                    </div>
                                    <div class="btn-group">
                                        <a href="detalhes-candidatura.php?id=<?= $candidatura['id_candidatura'] ?>"
                                            class="btn btn-outline-primary btn-sm">
                                            Ver Vaga
                                        </a>
                                        <?php if ($candidatura['status'] === 'Em análise'): ?>
                                            <button class="btn btn-outline-danger btn-sm cancelar-btn"
                                                data-id="<?= $candidatura['id_candidatura'] ?>">
                                                Cancelar
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if (!empty($candidatura['observacoes'])): ?>
                                    <div class="mt-3 pt-3 border-top">
                                        <h6>Observações:</h6>
                                        <p class="mb-0"><?= nl2br(htmlspecialchars($candidatura['observacoes'])) ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        <div class="d-flex align-items-center">
                            <span class="material-icons me-2">info</span>
                            <span>Você ainda não se candidatou a nenhuma vaga.</span>
                        </div>
                        <a href="../Pagina-vagas.php" class="btn btn-primary mt-3">Buscar Vagas</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal de Cancelamento -->
    <div class="modal fade" id="cancelarModal" tabindex="-1" aria-labelledby="cancelarModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelarModalLabel">Cancelar Candidatura</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja cancelar esta candidatura? Esta ação não pode ser desfeita.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Voltar</button>
                    <form id="formCancelar" method="POST" action="cancelar-candidatura.php">
                        <input type="hidden" name="id_candidatura" id="idCandidatura">
                        <button type="submit" class="btn btn-danger">Confirmar Cancelamento</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
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

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <script>
        // Configuração do modal de cancelamento
        document.querySelectorAll('.cancelar-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const idCandidatura = this.getAttribute('data-id');
                document.getElementById('idCandidatura').value = idCandidatura;

                const modal = new bootstrap.Modal(document.getElementById('cancelarModal'));
                modal.show();
            });
        });

        // Confirmação antes de cancelar
        document.getElementById('formCancelar').addEventListener('submit', function(e) {
            if (!confirm('Tem certeza que deseja cancelar esta candidatura?')) {
                e.preventDefault();
            }
        });
    </script>
</body>

</html>