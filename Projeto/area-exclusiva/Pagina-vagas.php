<?php
session_start();
include_once("../db/conexao.php");

$usuarioLogado = isset($_SESSION['id_usuario']);
$usuario = null;


if ($usuarioLogado) {
    $id_usuario = $_SESSION['id_usuario'];
    $sql = "SELECT nome_completo, foto_perfil FROM usuarios WHERE id_usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();

    // Atualiza nome na sessão (garante consistência)
    $_SESSION['nome_completo'] = $usuario['nome_completo'];
}

// Configuração da paginação
$vagas_por_pagina = 10; // Número de vagas por página
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_atual < 1) $pagina_atual = 1;

// Função para buscar vagas com paginação
function buscarVagas($conn, $filtro = '', $localizacao = '', $pagina = 1, $vagas_por_pagina = 10)
{
    $sql = "SELECT SQL_CALC_FOUND_ROWS v.*, e.nome as empresa_nome, e.url_logo 
            FROM vagas v 
            JOIN empresas e ON v.id_empresa = e.id_empresa 
            WHERE v.ativa = 1";

    $params = array();
    $types = "";

    if (!empty($filtro)) {
        $sql .= " AND (v.titulo LIKE ? OR e.nome LIKE ? OR v.localizacao LIKE ?)";
        $filtro_like = "%$filtro%";
        $params = array_merge($params, [$filtro_like, $filtro_like, $filtro_like]);
        $types .= "sss";
    }

    if (!empty($localizacao)) {
        $sql .= " AND v.localizacao LIKE ?";
        $local_like = "%$localizacao%";
        $params[] = $local_like;
        $types .= "s";
    }

    $sql .= " ORDER BY v.data_publicacao DESC LIMIT ?, ?";

    $offset = ($pagina - 1) * $vagas_por_pagina;
    $params = array_merge($params, [$offset, $vagas_por_pagina]);
    $types .= "ii";

    $stmt = $conn->prepare($sql);

    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    // Obter o total de vagas (para paginação)
    $total_vagas = $conn->query("SELECT FOUND_ROWS()")->fetch_row()[0];

    return array('result' => $result, 'total' => $total_vagas);
}

// Processar pesquisa
$filtro = isset($_GET['search']) ? trim($_GET['search']) : '';
$localizacao = isset($_GET['local']) ? trim($_GET['local']) : '';
$resultado_vagas = buscarVagas($conn, $filtro, $localizacao, $pagina_atual, $vagas_por_pagina);
$vagas = $resultado_vagas['result'];
$total_vagas = $resultado_vagas['total'];

// Calcular o total de páginas
$total_paginas = ceil($total_vagas / $vagas_por_pagina);

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

    // Se a vaga não for encontrada, redirecionar para evitar erro
    if (!$vagaSelecionada) {
        header("Location: Pagina-vagas.php?search=" . urlencode($filtro) . "&local=" . urlencode($localizacao) . "&pagina=" . $pagina_atual);
        exit();
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Vagas Disponíveis</title>
    <link rel="icon" href="../img/icon/icone-pag.png" />
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

        .pagination {
            margin-top: 20px;
            justify-content: center;
        }

        .page-info {
            text-align: center;
            margin-top: 10px;
            color: #6c757d;
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
        <div class="row">
            <!-- Lista de vagas -->
            <div class="col-md-4">
                <form method="GET" action="Pagina-vagas.php" class="mb-3">
                    <input type="hidden" name="pagina" value="1">
                    <div class="vaga-search-wrapper">
                        <div class="vaga-search-input">
                            <span class="material-icons" aria-hidden="true">search</span>
                            <input type="search" id="searchInput" name="search"
                                placeholder="Buscar vaga, empresa ou local..."
                                value="<?php echo htmlspecialchars($filtro); ?>" autocomplete="off" />
                        </div>
                        <div id="suggestions" class="suggestions-box"></div>
                    </div>
                    <div class="mt-2">
                        <select name="local" class="form-select">
                            <option value="">Todas as localizações</option>
                            <option value="Remoto" <?php echo ($localizacao == 'Remoto') ? 'selected' : ''; ?>>Remoto</option>
                            <option value="São Paulo" <?php echo ($localizacao == 'São Paulo') ? 'selected' : ''; ?>>São Paulo</option>
                            <option value="Rio de Janeiro" <?php echo ($localizacao == 'Rio de Janeiro') ? 'selected' : ''; ?>>Rio de Janeiro</option>
                            <option value="Belo Horizonte" <?php echo ($localizacao == 'Belo Horizonte') ? 'selected' : ''; ?>>Belo Horizonte</option>
                            <option value="Porto Alegre" <?php echo ($localizacao == 'Porto Alegre') ? 'selected' : ''; ?>>Porto Alegre</option>
                        </select>
                    </div>
                </form>

                <h4 class="mb-3">Vagas Disponíveis</h4>
                <div class="list-group" id="jobList">
                    <?php if ($vagas->num_rows > 0): ?>
                        <?php while ($vaga = $vagas->fetch_assoc()): ?>
                            <a href="Pagina-vagas.php?search=<?php echo urlencode($filtro); ?>&local=<?php echo urlencode($localizacao); ?>&id_vaga=<?php echo $vaga['id_vaga']; ?>&pagina=<?php echo $pagina_atual; ?>"
                                class="list-group-item list-group-item-action d-flex gap-3 align-items-start <?php echo ($vagaSelecionada && $vagaSelecionada['id_vaga'] == $vaga['id_vaga']) ? 'active' : ''; ?>">
                                <img src="<?php echo htmlspecialchars($vaga['url_logo']); ?>" alt="<?php echo htmlspecialchars($vaga['empresa_nome']); ?>" width="48" height="48" style="object-fit: contain;">
                                <div class="text-start">
                                    <div class="fw-bold"><?php echo htmlspecialchars($vaga['empresa_nome']); ?></div>
                                    <small class="<?php echo ($vagaSelecionada && $vagaSelecionada['id_vaga'] == $vaga['id_vaga']) ?: 'text-muted'; ?>">
                                        <?php echo htmlspecialchars($vaga['tipo_contrato']); ?> • <?php echo htmlspecialchars($vaga['localizacao']); ?>
                                    </small>
                                    <p class="mb-0 small <?php echo ($vagaSelecionada && $vagaSelecionada['id_vaga'] == $vaga['id_vaga']) ?: 'text-secondary'; ?>">
                                        <?php echo htmlspecialchars($vaga['titulo']); ?>
                                    </p>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="alert alert-info">Nenhuma vaga encontrada.</div>
                    <?php endif; ?>
                </div>

                <!-- Paginação -->
                <?php if ($total_paginas > 1): ?>
                    <nav aria-label="Navegação de páginas de vagas">
                        <ul class="pagination">
                            <?php if ($pagina_atual > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="Pagina-vagas.php?search=<?php echo urlencode($filtro); ?>&local=<?php echo urlencode($localizacao); ?>&pagina=<?php echo $pagina_atual - 1; ?><?php echo $vagaSelecionada ? '&id_vaga=' . $vagaSelecionada['id_vaga'] : ''; ?>" aria-label="Página anterior">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link" aria-hidden="true">&laquo;</span>
                                </li>
                            <?php endif; ?>

                            <?php
                            // Exibir até 5 páginas ao redor da página atual
                            $inicio = max(1, $pagina_atual - 2);
                            $fim = min($total_paginas, $inicio + 4);

                            // Ajustar se estiver no final
                            if ($fim - $inicio < 4) {
                                $inicio = max(1, $fim - 4);
                            }

                            for ($i = $inicio; $i <= $fim; $i++):
                            ?>
                                <li class="page-item <?php echo ($i == $pagina_atual) ? 'active' : ''; ?>">
                                    <a class="page-link" href="Pagina-vagas.php?search=<?php echo urlencode($filtro); ?>&local=<?php echo urlencode($localizacao); ?>&pagina=<?php echo $i; ?><?php echo $vagaSelecionada ? '&id_vaga=' . $vagaSelecionada['id_vaga'] : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($pagina_atual < $total_paginas): ?>
                                <li class="page-item">
                                    <a class="page-link" href="Pagina-vagas.php?search=<?php echo urlencode($filtro); ?>&local=<?php echo urlencode($localizacao); ?>&pagina=<?php echo $pagina_atual + 1; ?><?php echo $vagaSelecionada ? '&id_vaga=' . $vagaSelecionada['id_vaga'] : ''; ?>" aria-label="Próxima página">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link" aria-hidden="true">&raquo;</span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <div class="page-info">
                        Exibindo <?php echo $vagas->num_rows; ?> de <?php echo $total_vagas; ?> vagas
                    </div>
                <?php endif; ?>
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

                            <?php
                            // Verificar se o usuário já se candidatou a esta vaga
                            $jaCandidatado = false;
                            if ($usuarioLogado && $vagaSelecionada) {
                                $sql_verificar = "SELECT * FROM candidaturas 
                     WHERE id_vaga = ? AND id_usuario = ? AND status != 'Cancelado'";
                                $stmt_verificar = $conn->prepare($sql_verificar);
                                $stmt_verificar->bind_param("ii", $vagaSelecionada['id_vaga'], $id_usuario);
                                $stmt_verificar->execute();
                                $jaCandidatado = $stmt_verificar->get_result()->num_rows > 0;
                            }
                            ?>

                            <?php if ($usuarioLogado): ?>
                                <?php if ($jaCandidatado): ?>
                                    <div class="alert alert-info mt-4">
                                        Você já se candidatou a esta vaga.
                                        <a href="pag-candidaturas.php" class="alert-link">Ver minhas candidaturas</a>
                                    </div>
                                <?php else: ?>
                                    <a href="formulario-curriculo.php?id_vaga=<?php echo $vagaSelecionada['id_vaga']; ?>" class="btn btn-entrar mt-4">Enviar Currículo</a>
                                <?php endif; ?>
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
                <p class="footer-text">Conectamos talentos às melhores oportunidades. Nosso compromisso é facilitar o acesso ao mercado de trabalho com simplicidade и eficiência.</p>
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

            // Enviar formulário quando o select de localização for alterado
            $('select[name="local"]').on('change', function() {
                $('form').submit();
            });

            // Adicionar evento de submit para garantir que a pesquisa funcione corretamente
            $('form').on('submit', function() {
                // Garantir que a página seja resetada para 1 ao pesquisar
                $('input[name="pagina"]').val(1);
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            // Buscar sugestões em tempo real
            $("#searchInput").on("keyup", function() {
                let query = $(this).val();
                if (query.length > 1) {
                    $.ajax({
                        url: "buscar_sugestoes.php",
                        method: "GET",
                        dataType: "json", // IMPORTANTE: especificar que esperamos JSON
                        data: {
                            termo: query
                        },
                        success: function(data) {
                            if (data.length > 0) {
                                let suggestionsHtml = '';
                                data.forEach(function(sugestao) {
                                    suggestionsHtml += '<div>' + sugestao + '</div>';
                                });
                                $("#suggestions").html(suggestionsHtml).show();
                            } else {
                                $("#suggestions").hide();
                            }
                        },
                        error: function() {
                            $("#suggestions").hide();
                        }
                    });
                } else {
                    $("#suggestions").hide();
                }
            });

            // Clique em sugestão → preenche input
            $(document).on("click", "#suggestions div", function() {
                $("#searchInput").val($(this).text());
                $("#suggestions").hide();
                $("form").submit(); // envia automaticamente
            });

            // Fecha sugestões se clicar fora
            $(document).click(function(e) {
                if (!$(e.target).closest('.vaga-search-wrapper').length) {
                    $("#suggestions").hide();
                }
            });
        });
    </script>
</body>

</html>