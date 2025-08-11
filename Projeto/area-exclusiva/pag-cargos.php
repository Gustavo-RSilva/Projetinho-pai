<?php
session_start();
include_once("../db/conexao.php");

// Verifica se o usuário está logado
$usuarioLogado = isset($_SESSION['id_usuario']);

// Funções de busca
function buscarSugestoes($conn, $termo) {
    $termo = $termo . "%";
    $sql = "SELECT DISTINCT cargo FROM salarios_referencia WHERE cargo LIKE ? ORDER BY cargo LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $termo);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sugestoes = [];
    while ($row = $result->fetch_assoc()) {
        $sugestoes[] = $row['cargo'];
    }
    return $sugestoes;
}

function buscarSalarios($conn, $cargo) {
    $sql = "SELECT * FROM salarios_referencia WHERE cargo = ? ORDER BY nivel_experiencia";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $cargo);
    $stmt->execute();
    return $stmt->get_result();
}

function cargosPopulares($conn) {
    $sql = "SELECT cargo, AVG((salario_minimo + salario_maximo)/2) as media 
            FROM salarios_referencia 
            GROUP BY cargo 
            ORDER BY COUNT(*) DESC 
            LIMIT 6";
    return $conn->query($sql);
}

// Processar pesquisa
$resultados = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cargo'])) {
    $cargoPesquisa = trim($_POST['cargo']);
    $resultados = buscarSalarios($conn, $cargoPesquisa);
}

// Buscar cargos populares
$cargosPopulares = cargosPopulares($conn);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Cargos e Salários | JobSearch</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/cargos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Estilos específicos para esta página */
        #sugestoesLista {
            position: absolute;
            z-index: 1000;
            width: 100%;
            max-height: 200px;
            overflow-y: auto;
            display: none;
            border: 1px solid #ddd;
            border-radius: 0 0 4px 4px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        #sugestoesLista li {
            padding: 8px 12px;
            cursor: pointer;
            background: white;
            border-bottom: 1px solid #eee;
        }
        #sugestoesLista li:hover {
            background-color: #f8f9fa;
        }
        .card-cargo {
            transition: all 0.3s ease;
            height: 100%;
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .card-cargo:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .card-cargo .card-body {
            padding: 1.5rem;
        }
        .media-salarial {
            font-size: 1.2rem;
            color: var(--brand-color);
            font-weight: bold;
        }
    </style>
</head>

<body>
    <!-- Navbar Original Mantida -->
    <nav class="navbar navbar-expand-md" role="navigation" aria-label="Menu principal">
        <div class="navbar-container">
            <a href="index.php" class="navbar-brand" aria-label="Página inicial JobSearch"><img style="width: 90px;"
                    src="../img/Logo design for a job search platform named 'Contrata'. Use a modern, technological style with a bol(1) (1).png"
                    alt="JobSearch"></a>

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

            <!-- User status container -->
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
                    <a href="../logout2.php">
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

    <main class="container my-5">
        <section class="pesquisa">
            <h2 class="mb-3">Consulte o salário de um cargo</h2>
            <form id="formPesquisa" method="POST" class="d-flex mb-4 position-relative">
                <input type="text" id="inputCargo" name="cargo" class="form-control me-2" 
                       placeholder="Digite o cargo..." autocomplete="off" required />
                <button type="submit" class="btn btn-primary">Pesquisar</button>
                <ul id="sugestoesLista" class="list-group"></ul>
            </form>          
            
            <?php if (!empty($resultados) && $resultados->num_rows > 0): ?>
                <div id="resultadoBusca">
                    <h3 class="mb-3">Resultados para: <?php echo htmlspecialchars($cargoPesquisa); ?></h3>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nível</th>
                                    <th>Ramo</th>
                                    <th>Localização</th>
                                    <th>Faixa Salarial</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $resultados->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['nivel_experiencia']); ?></td>
                                        <td><?php echo htmlspecialchars($row['ramo_atuacao']); ?></td>
                                        <td><?php echo htmlspecialchars($row['localizacao']); ?></td>
                                        <td>R$ <?php echo number_format($row['salario_minimo'], 2, ',', '.'); ?> - 
                                            R$ <?php echo number_format($row['salario_maximo'], 2, ',', '.'); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php elseif (!empty($cargoPesquisa)): ?>
                <div class="alert alert-info" role="alert">
                    Nenhum resultado encontrado para "<?php echo htmlspecialchars($cargoPesquisa); ?>"
                </div>
            <?php endif; ?>
        </section>
        
        <!-- Seção de Cargos Populares (Nova) -->
        <section class="sugestoes mt-5">
            <h3 class="mb-4">Cargos em Destaque</h3>
            <div id="cardsContainer" class="row g-4">
                <?php while ($cargo = $cargosPopulares->fetch_assoc()): ?>
                    <div class="col-md-4 col-sm-6">
                        <div class="card card-cargo h-100">
                            <div class="card-body text-center">
                                <h4 class="card-title"><?php echo htmlspecialchars($cargo['cargo']); ?></h4>
                                <p class="media-salarial">
                                    R$ <?php echo number_format($cargo['media'], 2, ',', '.'); ?>
                                </p>
                                <p class="text-muted">Média salarial</p>
                                <form method="POST">
                                    <input type="hidden" name="cargo" value="<?php echo htmlspecialchars($cargo['cargo']); ?>">
                                    <button type="submit" class="btn btn-outline-primary">Ver detalhes</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </section>
    </main>

    <!-- Footer Original Mantido -->
    <footer class="site-footer">
        <div class="footer-container">
            <div class="footer-section">
                <h4 class="footer-title">Sobre Nós</h4>
                <p class="footer-text">Conectamos talentos às melhores oportunidades. Nosso compromisso é
                    facilitar o acesso ao mercado de trabalho com simplicidade e eficiência.</p>
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
                    <a href="#" class="social-btn facebook" aria-label="Facebook"><i
                            class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-btn instagram" aria-label="Instagram"><i
                            class="fab fa-instagram"></i></a>
                    <a href="#" class="social-btn linkedin" aria-label="LinkedIn"><i
                            class="fab fa-linkedin-in"></i></a>
                    <a href="#" class="social-btn whatsapp" aria-label="WhatsApp"><i
                            class="fab fa-whatsapp"></i></a>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; 2025 JobSearch. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        // Auto-complete para campo de pesquisa
        $('#inputCargo').on('input', function() {
            const termo = $(this).val().trim();
            const $lista = $('#sugestoesLista');
            
            if (termo.length > 2) {
                $.ajax({
                    url: 'buscar_sugestoes.php',
                    method: 'POST',
                    data: { termo: termo },
                    dataType: 'json',
                    success: function(sugestoes) {
                        $lista.empty();
                        
                        if (sugestoes.length > 0) {
                            sugestoes.forEach(function(cargo) {
                                $lista.append(
                                    $('<li>')
                                        .addClass('list-group-item list-group-item-action')
                                        .text(cargo)
                                        .on('click', function() {
                                            $('#inputCargo').val(cargo);
                                            $lista.hide();
                                            $('#formPesquisa').submit();
                                        })
                                );
                            });
                            $lista.show();
                        } else {
                            $lista.hide();
                        }
                    },
                    error: function() {
                        $lista.hide();
                    }
                });
            } else {
                $lista.hide();
            }
        });

        // Esconder sugestões ao clicar fora
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#formPesquisa').length) {
                $('#sugestoesLista').hide();
            }
        });

        // Mostrar sugestões ao focar no campo (se houver termo)
        $('#inputCargo').on('focus', function() {
            if ($(this).val().trim().length > 2 && $('#sugestoesLista').children().length > 0) {
                $('#sugestoesLista').show();
            }
        });
    });
    </script>
</body>
</html>