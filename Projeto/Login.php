<?php
session_start();
$erro = "";
include_once("db/conexao.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $senha = $_POST["senha"];

    if (!empty($email) && !empty($senha)) {
        // Usar MD5 para compatibilidade com o banco
        $senha_md5 = md5($senha);

        $sql = "SELECT * FROM usuarios WHERE email = ? AND senha = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $email, $senha_md5);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $linha = $result->fetch_object();
            $_SESSION["id_usuario"] = $linha->id_usuario;
            $_SESSION["nome_completo"] = $linha->nome_completo;
            $_SESSION["email"] = $linha->email;
            header("Location: ./area-exclusiva/index.php");
            exit;
        } else {
            $erro = "<div class='alert alert-danger'>Usuário ou senha incorretos!</div>";
        }
    } else {
        $erro = "<div class='alert alert-warning'>Preencha todos os campos!</div>";
    }
}
?>


<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bootstrap demo</title>
    <link rel="stylesheet" href="./css/Login.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
</head>

<body class="paginaLogin">

    <form class="form-container" method="POST" action="Login.php">
        <h1 class="text-center entrarcom fw-bold">Entrar com sua conta </h1>
        <div class="mb-3">
            <label for="exampleInputEmail1" class="form-label">E-mail</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>
        <div class="mb-3">
            <label for="senha" class="form-label">Senha</label>
            <div class="input-with-icon">
                <input type="password" class="form-control" id="senha" name="senha" required> <img src="./img/view.png" class="eye-icon" id="togglePassword" alt="Mostrar senha"
                    title="Mostrar senha">
            </div>
            <a href="./Esqueci-senha.html" class="forgot-password-link m-3">Esqueceu a senha?</a>

            <button type="submit" class="btn cor w-100 py-3  rounded-5 ">Entrar</button>
        </div>
        <div class="dividir ">Ou entrar com</div>


        <div class="social2-button">
            <a href="login-google.php" class="btn social-btn btn-outline-danger">
                <img src="./img/icons8-google-logo-48.png" width="20" height="20" alt="Google">
                Google
            </a>
            <a href="#" class="btn social-btn btn-outline-primary">
                <img src="./img/linkedin.png" width="20" height="20" alt="LinkedIn">
                LinkedIn
            </a>
        </div>

        </div>
        <div class="naotemconta">Não tem uma conta?
            <a href="./Criar-conta.html" class="btn criarc btn-outline-secondary w-100 ">Criar Conta </a></button>
        </div>
        <div class="empresa-link text-center mt-3">
            <p>É uma empresa? <a href="./area-empresa/login-empresa.php" class="text-decoration-none">Acesse aqui</a></p>
        </div>

    </form>


</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q"
    crossorigin="anonymous"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('senha');

        // Verifica se os elementos existem
        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);

                // Alterna entre os textos e títulos
                if (type === 'password') {
                    this.src = './img/view.png';
                    this.alt = 'Mostrar senha';
                    this.title = 'Mostrar senha';
                } else {
                    this.src = './img/hidden.png';
                    this.alt = 'Ocultar senha';
                    this.title = 'Ocultar senha';
                }
            });
        }
    });
</script>

</html>