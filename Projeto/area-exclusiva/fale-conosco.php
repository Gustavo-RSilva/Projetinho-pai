<?php
session_start();
include_once("../db/conexao.php");

$usuarioLogado = isset($_SESSION['id_usuario']) && !empty($_SESSION['email']);
$usuario = [];

// Se o usuário estiver logado, busque os dados do usuário
if ($usuarioLogado) {
    $id_usuario = $_SESSION['id_usuario'];
    $query = "SELECT * FROM usuarios WHERE id_usuario = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $usuario = $resultado->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Fale Conosco - Contrata</title>
    <link rel="icon" href="../img/icon/icone-pag.png" />
    <?php include '../model/head.php'; ?>
    <link rel="stylesheet" href="../css/pag-fale-conosco.css" />
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
                    <a href="./curriculos.php" class="nav-link" tabindex="0" role="menuitem">
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

    <!-- Fale Conosco Section -->
    <section class="container my-5">
        <h2 class="section-title">Fale Conosco</h2>
        <p>Se você tiver alguma dúvida, sugestão ou feedback, preencha o formulário abaixo e entraremos em contato o mais breve possível.</p>

        <form action="processar_fale_conosco.php" method="POST" class="contact-form">
            <div class="form-group">
                <label for="nome">Nome:</label>
                <input type="text" id="nome" name="nome" required>
            </div>
            <div class="form-group">
                <label for="email">E-mail:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="mensagem">Mensagem:</label>
                <textarea id="mensagem" name="mensagem" rows="5" required></textarea>
            </div>
            <button type="submit" class="btn btn-submit">Enviar</button>
        </form>
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
</body>

</html>
