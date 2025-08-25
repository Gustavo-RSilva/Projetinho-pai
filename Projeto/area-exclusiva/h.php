<?php
session_start();
include_once("../db/conexao.php");

// Definições de paginação
$vagas_por_pagina = 6;
$pagina_atual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$offset = ($pagina_atual - 1) * $vagas_por_pagina;

// Filtros de busca
$filtroPesquisa = isset($_GET['q']) ? trim($_GET['q']) : '';
$filtroLocal = isset($_GET['local']) ? trim($_GET['local']) : '';

$filtroSQL = "";
$params = [];
$types = "";

// Filtro por palavra-chave
if (!empty($filtroPesquisa)) {
    $filtroSQL .= " AND (v.titulo LIKE ? OR v.descricao LIKE ? OR e.nome LIKE ?)";
    $busca = "%" . $filtroPesquisa . "%";
    $params[] = $busca;
    $params[] = $busca;
    $params[] = $busca;
    $types .= "sss";
}

// Filtro por localização
if (!empty($filtroLocal)) {
    $filtroSQL .= " AND v.localizacao = ?";
    $params[] = $filtroLocal;
    $types .= "s";
}

// Contar total de vagas
$sql_total = "SELECT COUNT(*) as total 
              FROM vagas v 
              JOIN empresas e ON v.id_empresa = e.id_empresa 
              WHERE v.ativa = 1 AND v.data_expiracao >= CURDATE() $filtroSQL";

$stmt_total = $conn->prepare($sql_total);
if (!empty($params)) {
    $stmt_total->bind_param($types, ...$params);
}
$stmt_total->execute();
$total_vagas = $stmt_total->get_result()->fetch_assoc()['total'];
$stmt_total->close();

// Buscar vagas com paginação
$sql = "SELECT v.*, e.nome as empresa_nome, e.url_logo 
        FROM vagas v 
        JOIN empresas e ON v.id_empresa = e.id_empresa 
        WHERE v.ativa = 1 AND v.data_expiracao >= CURDATE() $filtroSQL
        ORDER BY v.data_publicacao DESC
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $bind_params = array_merge($params, [$vagas_por_pagina, $offset]);
    $stmt->bind_param($types . "ii", ...$bind_params);
} else {
    $stmt->bind_param("ii", $vagas_por_pagina, $offset);
}

$stmt->execute();
$vagas = $stmt->get_result();

$total_paginas = ceil($total_vagas / $vagas_por_pagina);
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
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">Contrata</a>
        </div>
    </nav>

    <!-- Banner com barra de pesquisa -->
    <section class="banner">
        <div class="banner-overlay">
            <div class="container text-center">
                <h1 class="text-white">Encontre sua próxima oportunidade</h1>
                <form method="GET" action="Pagina-vagas.php" class="search-bar">
                    <div class="search-section">
                        <span class="material-icons">search</span>
                        <input type="text" name="q" placeholder="Pesquisar por cargo, empresa ou palavra-chave..." value="<?php echo htmlspecialchars($filtroPesquisa); ?>">
                    </div>
                    <div class="search-section separator">
                        <span class="material-icons">location_on</span>
                        <select name="local" class="location-select">
                            <option value="">Todas localizações</option>
                            <option value="Remoto" <?php echo ($filtroLocal == "Remoto") ? "selected" : ""; ?>>Remoto</option>
                            <option value="São Paulo" <?php echo ($filtroLocal == "São Paulo") ? "selected" : ""; ?>>São Paulo</option>
                            <option value="Rio de Janeiro" <?php echo ($filtroLocal == "Rio de Janeiro") ? "selected" : ""; ?>>Rio de Janeiro</option>
                            <option value="Belo Horizonte" <?php echo ($filtroLocal == "Belo Horizonte") ? "selected" : ""; ?>>Belo Horizonte</option>
                            <option value="Porto Alegre" <?php echo ($filtroLocal == "Porto Alegre") ? "selected" : ""; ?>>Porto Alegre</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-search">Pesquisar</button>
                </form>
            </div>
        </div>
    </section>

    <!-- Listagem de vagas -->
    <section class="container my-5">
        <div class="row">
            <?php if ($vagas->num_rows > 0): ?>
                <?php while ($vaga = $vagas->fetch_assoc()): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <img src="<?php echo !empty($vaga['url_logo']) ? $vaga['url_logo'] : 'img/default-logo.png'; ?>" 
                                         alt="Logo da empresa" class="me-2" style="width:50px;height:50px;object-fit:contain;">
                                    <div>
                                        <h5 class="card-title mb-0"><?php echo htmlspecialchars($vaga['titulo']); ?></h5>
                                        <small class="text-muted"><?php echo htmlspecialchars($vaga['empresa_nome']); ?></small>
                                    </div>
                                </div>
                                <p class="card-text"><?php echo nl2br(htmlspecialchars(substr($vaga['descricao'], 0, 100))) . '...'; ?></p>
                                <p class="text-muted">
                                    <span class="material-icons" style="font-size:16px;vertical-align:middle;">location_on</span>
                                    <?php echo htmlspecialchars($vaga['localizacao']); ?>
                                </p>
                                <a href="detalhe-vaga.php?id=<?php echo $vaga['id_vaga']; ?>" class="btn btn-primary w-100">Ver detalhes</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <p class="text-muted">Nenhuma vaga encontrada.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Paginação -->
        <?php if ($total_paginas > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <li class="page-item <?php echo ($i == $pagina_atual) ? 'active' : ''; ?>">
                            <a class="page-link" href="?q=<?php echo urlencode($filtroPesquisa); ?>&local=<?php echo urlencode($filtroLocal); ?>&pagina=<?php echo $i; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </section>

    <!-- Footer -->
    <footer class="bg-primary text-white text-center py-3 mt-auto">
        <p class="mb-0">&copy; 2025 Contrata. Todos os direitos reservados.</p>
    </footer>
</body>
</html>
