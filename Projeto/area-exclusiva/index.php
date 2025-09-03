<?php
session_start();
include_once("../db/conexao.php");

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
}



// Função para buscar vagas em destaque
function buscarVagasDestaque($conn)
{
    $sql = "SELECT v.*, e.nome as empresa_nome, e.url_logo 
            FROM vagas v 
            JOIN empresas e ON v.id_empresa = e.id_empresa 
            WHERE v.ativa = 1 AND v.data_expiracao >= CURDATE()
            ORDER BY v.data_publicacao DESC
            LIMIT 5";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Erro prepare: " . $conn->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result) {
        die("Erro get_result: " . $stmt->error);
    }
    return $result;
}

// Função para buscar áreas profissionais
function buscarAreasProfissionais($conn)
{
    $sql = "SELECT a.id_area, a.nome, 
               SUM(CASE WHEN v.ativa = 1 AND v.data_expiracao >= CURDATE() THEN 1 ELSE 0 END) AS quantidade_vagas_ativas
        FROM areas_profissionais a
        LEFT JOIN vagas_areas va ON a.id_area = va.id_area
        LEFT JOIN vagas v ON v.id_vaga = va.id_vaga
        GROUP BY a.id_area, a.nome
        ORDER BY quantidade_vagas_ativas DESC
        LIMIT 6";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die('Erro prepare: ' . $conn->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    return $result;
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
    <title>Contrata</title>
    <link rel="icon" href="../img/icon/icone-pag.png" />
    <?php
    include '../model/head.php'
    ?>
    <link rel="stylesheet" href="../css/pag-index.css" />
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

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content text-center">
                <h1 class="hero-title">Encontre seu <span class="destaque">emprego dos sonhos</span> hoje mesmo</h1>
                <p class="hero-subtitle">Milhares de oportunidades esperando por você</p>

                <!-- Barra de pesquisa -->
                <div class="search-container">
                    <form method="GET" action="Pagina-vagas.php" class="search-bar">
                        <div class="search-section">
                            <span class="material-icons">search</span>
                            <input type="text" id="search-input" name="search"
                                placeholder="Pesquisar por cargo, empresa ou palavra-chave..."
                                autocomplete="off">
                            <!-- Dropdown fica aqui dentro -->
                            <div id="suggestions-container" class="suggestions-dropdown"></div>
                        </div>

                        <div class="search-section separator">
                            <span class="material-icons">location_on</span>
                            <select name="local" class="location-select">
                                <option value="">Todas localizações</option>
                                <option value="Remoto">Remoto</option>
                                <option value="Acre">Acre (AC)</option>
                                <option value="Alagoas">Alagoas (AL)</option>
                                <option value="Amapá">Amapá (AP)</option>
                                <option value="Amazonas">Amazonas (AM)</option>
                                <option value="Bahia">Bahia (BA)</option>
                                <option value="Ceará">Ceará (CE)</option>
                                <option value="Distrito Federal">Distrito Federal (DF)</option>
                                <option value="Espírito Santo">Espírito Santo (ES)</option>
                                <option value="Goiás">Goiás (GO)</option>
                                <option value="Maranhão">Maranhão (MA)</option>
                                <option value="Mato Grosso">Mato Grosso (MT)</option>
                                <option value="Mato Grosso do Sul">Mato Grosso do Sul (MS)</option>
                                <option value="Minas Gerais">Minas Gerais (MG)</option>
                                <option value="Pará">Pará (PA)</option>
                                <option value="Paraíba">Paraíba (PB)</option>
                                <option value="Paraná">Paraná (PR)</option>
                                <option value="Pernambuco">Pernambuco (PE)</option>
                                <option value="Piauí">Piauí (PI)</option>
                                <option value="Rio de Janeiro">Rio de Janeiro (RJ)</option>
                                <option value="Rio Grande do Norte">Rio Grande do Norte (RN)</option>
                                <option value="Rio Grande do Sul">Rio Grande do Sul (RS)</option>
                                <option value="Rondônia">Rondônia (RO)</option>
                                <option value="Roraima">Roraima (RR)</option>
                                <option value="Santa Catarina">Santa Catarina (SC)</option>
                                <option value="São Paulo">São Paulo (SP)</option>
                                <option value="Sergipe">Sergipe (SE)</option>
                                <option value="Tocantins">Tocantins (TO)</option>
                            </select>
                        </div>

                        <button type="submit" class="btn-search">Pesquisar</button>
                    </form>
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
                                <span class="material-icons" style="font-size:1rem;">location_on</span>
                                <?php echo htmlspecialchars($vaga['localizacao']); ?>
                                <?php if ($vaga['remoto']): ?> (Remoto) <?php endif; ?><br>
                                <span class="material-icons" style="font-size:1rem;">attach_money</span>
                                <?php echo htmlspecialchars($vaga['faixa_salarial']); ?><br>
                                <span class="material-icons" style="font-size:1rem;">description</span>
                                <?php echo htmlspecialchars($vaga['tipo_contrato']); ?>
                            </p>
                            <div class="job-card-footer">
                                <a href="Pagina-vagas.php?id_vaga=<?php echo $vaga['id_vaga']; ?>" class="btn btn-apply">Ver Vaga</a>
                            </div>
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
                                    $icon = match ($area['id_area']) {
                                        1 => 'shopping_cart',
                                        2 => 'local_shipping',
                                        3 => 'restaurant',
                                        4 => 'business_center',
                                        5 => 'computer',
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
                <p class="footer-text">Conectamos talentos às melhores oportunidades.</p>
            </div>
            <div class="footer-section rightlinks">
                <h4 class="footer-title">Links Rápidos</h4>
                <ul class="footer-links">
                    <li><a href="index.php">Início</a></li>
                    <li><a href="Pagina-vagas.php">Vagas</a></li>
                    <li><a href="Meu-curriculo.php">Cadastrar Currículo</a></li>
                    <li><a href="fale-conosco.php">Contato</a></li>
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
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 Contrata. Todos os direitos reservados.</p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>

    <script>
        $(document).ready(function() {
            // Inicializar o carousel de vagas em destaque
            $("#featured-jobs-carousel").owlCarousel({
                loop: true,
                margin: 20,
                nav: true,
                dots: false,
                responsive: {
                    0: {
                        items: 1
                    },
                    600: {
                        items: 2
                    },
                    1000: {
                        items: 3
                    }
                }
            });
        });
        // Função para mostrar seção e atualizar menu
        function mostrarSecao(id) {
            // Esconder todas as seções
            document.querySelectorAll('.section').forEach(s => {
                s.style.display = 'none';
                s.classList.remove('active');
            });

            // Mostrar a seção solicitada
            const secao = document.getElementById(id);
            if (secao) {
                secao.style.display = 'block';
                secao.classList.add('active');
            }

            // Atualizar menu ativo
            document.querySelectorAll('.sidebar .nav-link').forEach(link => {
                link.classList.remove('active');
            });

            // Encontrar e ativar o link correspondente
            const linkMap = {
                'dashboard-section': 'mostrarDashboard',
                'vagas-section': 'mostrarVagas',
                'candidatos-section': 'mostrarCandidatos',
                'empresa-section': 'mostrarEmpresa',
                'relatorios-section': 'mostrarRelatorios',
                'configuracoes-section': 'mostrarConfiguracoes'
            };

            for (const [sectionId, functionName] of Object.entries(linkMap)) {
                if (sectionId === id) {
                    const link = document.querySelector(`[onclick="${functionName}(event)"]`);
                    if (link) {
                        link.classList.add('active');
                    }
                    break;
                }
            }

            // Atualizar URL com hash para manter a posição
            const hashMap = {
                'dashboard-section': 'dashboard',
                'vagas-section': 'vagas',
                'candidatos-section': 'candidatos',
                'empresa-section': 'empresa',
                'relatorios-section': 'relatorios',
                'configuracoes-section': 'configuracoes'
            };

            if (hashMap[id]) {
                window.location.hash = hashMap[id];
            }
        }

        // Funções de navegação
        function mostrarDashboard(e) {
            if (e) e.preventDefault();
            mostrarSecao('dashboard-section');
        }

        function mostrarVagas(e) {
            if (e) e.preventDefault();
            mostrarSecao('vagas-section');
        }

        function mostrarCandidatos(e) {
            if (e) e.preventDefault();
            mostrarSecao('candidatos-section');
        }

        function mostrarEmpresa(e) {
            if (e) e.preventDefault();
            mostrarSecao('empresa-section');
        }

        function mostrarRelatorios(e) {
            if (e) e.preventDefault();
            mostrarSecao('relatorios-section');
        }

        function mostrarConfiguracoes(e) {
            if (e) e.preventDefault();
            mostrarSecao('configuracoes-section');
        }

        // Verificar hash na URL ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            const hash = window.location.hash.substring(1);
            const sectionMap = {
                'dashboard': 'dashboard-section',
                'vagas': 'vagas-section',
                'candidatos': 'candidatos-section',
                'empresa': 'empresa-section',
                'relatorios': 'relatorios-section',
                'configuracoes': 'configuracoes-section'
            };

            if (hash && sectionMap[hash]) {
                mostrarSecao(sectionMap[hash]);
            } else {
                // Mostrar dashboard por padrão
                mostrarSecao('dashboard-section');
            }
        });

        // Interceptar envios de formulário para manter a seção
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                // Manter a seção atual no action do formulário
                const secaoAtual = document.querySelector('.section.active');
                if (secaoAtual && !form.action.includes('#')) {
                    const formAction = form.getAttribute('action') || '';
                    const sectionId = secaoAtual.id;
                    const sectionMap = {
                        'dashboard-section': 'dashboard',
                        'vagas-section': 'vagas',
                        'candidatos-section': 'candidatos',
                        'empresa-section': 'empresa',
                        'relatorios-section': 'relatorios',
                        'configuracoes-section': 'configuracoes'
                    };

                    if (sectionMap[sectionId]) {
                        form.setAttribute('action', formAction + '#' + sectionMap[sectionId]);
                    }
                }
            });
        });

        // Chart.js (Relatórios)
        const ctx = document.getElementById('chartCandidaturas');
        if (ctx) {
            const labels = <?= json_encode($labels ?? []) ?>;
            const data = <?= json_encode($data   ?? []) ?>;
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        label: 'Candidaturas por dia',
                        data,
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search-input');
            const suggestionsContainer = document.getElementById('suggestions-container');
            let timeoutId;
            let sugestaoAtual = -1;

            // garante que o pai tenha position:relative (se por acaso não tiver)
            const parentSection = searchInput.closest('.search-section');
            if (parentSection && getComputedStyle(parentSection).position === 'static') {
                parentSection.style.position = 'relative';
            }

            function showSuggestions() {
                suggestionsContainer.style.display = 'block';
            }

            function hideSuggestions() {
                suggestionsContainer.style.display = 'none';
                sugestaoAtual = -1;
            }

            // reposiciona o dropdown (útil se layouts mudarem)
            function positionSuggestions() {
                // como usamos width:100% e left:0 no CSS, não precisamos calcular left/top complexos.
                // porém, por segurança, forçamos top baseado no input.
                suggestionsContainer.style.top = (searchInput.offsetTop + searchInput.offsetHeight + 6) + 'px';
                suggestionsContainer.style.left = searchInput.offsetLeft + 'px';
                suggestionsContainer.style.width = searchInput.offsetWidth + 'px';
            }

            // atualiza navegação por teclado
            function updateActiveSuggestion(sugestoes, index) {
                sugestoes.forEach(s => s.classList.remove('active'));
                if (index >= 0 && sugestoes[index]) {
                    sugestoes[index].classList.add('active');
                    sugestoes[index].scrollIntoView({
                        block: 'nearest'
                    });
                    searchInput.value = sugestoes[index].textContent;
                }
            }

            searchInput.addEventListener('input', function() {
                const termo = this.value.trim();
                clearTimeout(timeoutId);

                if (termo === '') {
                    hideSuggestions();
                    return;
                }

                timeoutId = setTimeout(() => {
                    fetch(`buscar_sugestoes.php?termo=${encodeURIComponent(termo)}`)
                        .then(response => response.json())
                        .then(sugestoes => {
                            suggestionsContainer.innerHTML = '';
                            sugestaoAtual = -1;

                            if (sugestoes.length > 0) {
                                sugestoes.forEach(sugestao => {
                                    const div = document.createElement('div');
                                    div.className = 'suggestion-item';
                                    div.textContent = sugestao;
                                    div.addEventListener('click', function() {
                                        searchInput.value = this.textContent;
                                        hideSuggestions();
                                        // opcional: searchInput.form.submit();
                                    });
                                    suggestionsContainer.appendChild(div);
                                });
                                positionSuggestions();
                                showSuggestions();
                            } else {
                                hideSuggestions();
                            }
                        })
                        .catch(() => hideSuggestions());
                }, 250);
            });

            // fechar ao clicar fora
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
                    hideSuggestions();
                }
            });

            // navegação por teclado
            searchInput.addEventListener('keydown', function(e) {
                const sugestoes = suggestionsContainer.querySelectorAll('.suggestion-item');
                if (sugestoes.length === 0) return;

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    sugestaoAtual = (sugestaoAtual + 1) % sugestoes.length;
                    updateActiveSuggestion(sugestoes, sugestaoAtual);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    sugestaoAtual = sugestaoAtual <= 0 ? sugestoes.length - 1 : sugestaoAtual - 1;
                    updateActiveSuggestion(sugestoes, sugestaoAtual);
                } else if (e.key === 'Enter' && sugestaoAtual !== -1) {
                    e.preventDefault();
                    searchInput.value = sugestoes[sugestaoAtual].textContent;
                    hideSuggestions();
                    searchInput.form.submit();
                }
            });

            // reposiciona quando a janela muda (resize/scroll)
            window.addEventListener('resize', positionSuggestions);
            // se o pai for rolável, também ajusta no scroll do pai
            if (parentSection) parentSection.addEventListener('scroll', positionSuggestions);
        });
    </script>

</body>

</html>