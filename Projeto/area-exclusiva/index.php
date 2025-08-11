<?php
session_start();
include_once("../db/conexao.php");

// Verifica se o usuário está logado
$usuarioLogado = isset($_SESSION['id_usuario']);

// Função para buscar vagas em destaque
function buscarVagasDestaque($conn) {
    $sql = "SELECT v.*, e.nome as empresa_nome, e.url_logo 
            FROM vagas v 
            JOIN empresas e ON v.id_empresa = e.id_empresa 
            WHERE v.ativa = 1 AND v.data_expiracao >= CURDATE()
            ORDER BY v.data_publicacao DESC
            LIMIT 5";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->get_result();
}

// Função para buscar áreas profissionais
function buscarAreasProfissionais($conn) {
    $sql = "SELECT * FROM areas_profissionais 
            WHERE quantidade_vagas_ativas > 0
            ORDER BY quantidade_vagas_ativas DESC
            LIMIT 6";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->get_result();
}

// Buscar dados
$vagasDestaque = buscarVagasDestaque($conn);
$areasProfissionais = buscarAreasProfissionais($conn);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
      <meta charset="UTF-8" />
      <meta name="viewport" content="width=device-width, initial-scale=1" />
      <title>Job Search Navbar with Always Visible Hamburger and Animations</title>
      
        <?php 
        include '../model/head.php'
        ?>
        <link rel="stylesheet" href="../css/pag-index.css" />
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-md" role="navigation" aria-label="Menu principal">
        <div class="navbar-container">
            <a href="index.php" class="navbar-brand" aria-label="Página inicial JobSearch">
                <img style="width: 90px;" src="../img/Logo design for a job search platform named 'Contrata'. Use a modern, technological style with a bol(1) (1).png" alt="Contrata">
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
                    <a href="logout2.php">
                        <button type="button" class="btn btn-entrar" tabindex="0">Sair</button>
                    </a>
                <?php else: ?>
                    <a href="../Login.php">
                        <button type="button" class="btn btn-entrar" tabindex="0">Entrar</button>
                    </a>
                    <a href="Crie-conta.php">
                        <button type="button" class="btn btn-cadastrar" tabindex="0">Cadastrar</button>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content text-center">
                <h1 class="hero-title">Encontre seu <span class="destaque">emprego dos sonhos</span> hoje mesmo</h1>
                <p class="hero-subtitle">Milhares de oportunidades esperando por você</p>
                
                <!-- Barra de pesquisa -->
                <div class="search-container">
                    <div class="search-bar">
                        <div class="search-section">
                            <span class="material-icons">search</span>
                            <input type="text" id="search-input" placeholder="Pesquisar por cargo, empresa ou palavra-chave..." autocomplete="off">
                            <div id="search-suggestions" class="suggestions-dropdown"></div>
                        </div>
                        <div class="search-section separator">
                            <span class="material-icons">location_on</span>
                            <select id="location-filter" class="location-select">
                                <option value="">Todas localizações</option>
                                <option value="Remoto">Remoto</option>
                                <option value="São Paulo">São Paulo</option>
                                <option value="Rio de Janeiro">Rio de Janeiro</option>
                                <option value="Belo Horizonte">Belo Horizonte</option>
                                <option value="Porto Alegre">Porto Alegre</option>
                            </select>
                        </div>
                        <button class="btn-search" id="search-button">Pesquisar</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Vagas em Destaque -->
    <section class="container my-5">
        <h2 class="section-title">Vagas em Destaque</h2>
        
        <div class="owl-carousel owl-theme" id="featured-jobs-carousel">
            <?php if ($vagasDestaque->num_rows > 0): ?>
                <?php while ($vaga = $vagasDestaque->fetch_assoc()): ?>
                    <div class="item">
                        <div class="job-card">
                            <div class="d-flex align-items-center mb-3">
                                
                                <div>
                                    <h3><?php echo htmlspecialchars($vaga['titulo']); ?></h3>
                                    <p class="company"><?php echo htmlspecialchars($vaga['empresa_nome']); ?></p>
                                </div>
                            </div>
                            <p class="details">
                                <span class="material-icons" style="font-size: 1rem;">location_on</span> 
                                <?php echo htmlspecialchars($vaga['localizacao']); ?>
                                <?php if ($vaga['remoto']): ?>
                                    (Remoto)
                                <?php endif; ?>
                                <br>
                                <span class="material-icons" style="font-size: 1rem;">attach_money</span> 
                                <?php echo htmlspecialchars($vaga['faixa_salarial']); ?>
                                <br>
                                <span class="material-icons" style="font-size: 1rem;">description</span> 
                                <?php echo htmlspecialchars($vaga['tipo_contrato']); ?>
                            </p>
                            <a href="Pagina-vagas.php?id_vaga=<?php echo $vaga['id_vaga']; ?>" class="btn btn-apply">Ver Vaga</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">Nenhuma vaga em destaque no momento.</div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Principais Áreas -->
    <section class="container my-5">
        <h2 class="section-title">Principais Áreas</h2>
        
        <div class="row">
            <?php if ($areasProfissionais->num_rows > 0): ?>
                <?php while ($area = $areasProfissionais->fetch_assoc()): ?>
                    <div class="col-md-4 col-sm-6 mb-4">
                        <a href="Pagina-vagas.php?area=<?php echo $area['id_area']; ?>" class="text-decoration-none">
                            <div class="area-card">
                                <div class="icon">
                                    <?php 
                                        // Ícones diferentes para cada área
                                        $icon = match($area['id_area']) {
                                            1 => 'shopping_cart',    // Comercial/Vendas
                                            2 => 'local_shipping',   // Logística
                                            3 => 'restaurant',       // Alimentação/Gastronomia
                                            4 => 'business_center',  // Administração
                                            5 => 'computer',         // Tecnologia da Informação
                                            default => 'work'
                                        };
                                    ?>
                                    <span class="material-icons"><?php echo $icon; ?></span>
                                </div>
                                <h3><?php echo htmlspecialchars($area['nome']); ?></h3>
                                <p class="count"><?php echo number_format($area['quantidade_vagas_ativas'], 0, ',', '.'); ?> vagas disponíveis</p>
                            </div>
                        </a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">Nenhuma área profissional disponível no momento.</div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
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
            <p>&copy; 2025 Contrata. Todos os direitos reservados.</p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <!-- Owl Carousel JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Inicializar carrossel de vagas em destaque
            $('#featured-jobs-carousel').owlCarousel({
                loop: true,
                margin: 20,
                nav: true,
                dots: false,
                responsive: {
                    0: { items: 1 },
                    768: { items: 2 },
                    992: { items: 3 }
                }
            });
            
            // Dados de exemplo para sugestões (substituir por chamada AJAX para seu backend)
            const jobTitles = [
                "Desenvolvedor Front-End",
                "Analista de Dados",
                "UX Designer",
                "Auxiliar Administrativo",
                "Técnico de Suporte",
                "Gerente de Projetos",
                "Engenheiro de Software"
            ];
            
            const companies = [
                "TechCode",
                "DataMax",
                "Inovart",
                "Grupo Zênite",
                "TecnoPlus Soluções"
            ];
            
            // Evento de input na barra de pesquisa
            $('#search-input').on('input', function() {
                const searchTerm = $(this).val().trim().toLowerCase();
                
                if (searchTerm.length === 0) {
                    $('#search-suggestions').hide();
                    return;
                }
                
                // Filtrar sugestões
                const titleMatches = jobTitles.filter(title => 
                    title.toLowerCase().includes(searchTerm)
                );
                
                const companyMatches = companies.filter(company => 
                    company.toLowerCase().includes(searchTerm)
                );
                
                // Exibir sugestões
                showSuggestions([...titleMatches, ...companyMatches]);
            });
            
            // Função para exibir sugestões
            function showSuggestions(suggestions) {
                if (suggestions.length === 0) {
                    $('#search-suggestions').hide();
                    return;
                }
                
                $('#search-suggestions').empty();
                suggestions.slice(0, 5).forEach(suggestion => {
                    const item = $('<div class="suggestion-item"></div>').text(suggestion);
                    
                    item.on('click', function() {
                        $('#search-input').val(suggestion);
                        $('#search-suggestions').hide();
                        performSearch();
                    });
                    
                    $('#search-suggestions').append(item);
                });
                
                $('#search-suggestions').show();
            }
            
            // Fechar sugestões ao clicar fora
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#search-input, #search-suggestions').length) {
                    $('#search-suggestions').hide();
                }
            });
            
            // Evento de pesquisa
            $('#search-button').on('click', performSearch);
            
            // Pesquisar ao pressionar Enter
            $('#search-input').on('keypress', function(e) {
                if (e.which === 13) {
                    performSearch();
                }
            });
            
            // Função para realizar a pesquisa
            function performSearch() {
                const searchTerm = $('#search-input').val().trim();
                const location = $('#location-filter').val();
                
                // Redirecionar para a página de vagas com os parâmetros de busca
                window.location.href = `Pagina-vagas.php?search=${encodeURIComponent(searchTerm)}&location=${encodeURIComponent(location)}`;
            }
        });
    </script>
</body>
</html>