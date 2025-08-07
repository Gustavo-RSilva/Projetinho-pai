<?php 
session_start();

$erro ="";

include_once ("db/conexao.php");

$email = isset($_POST["email"])? $_POST["email"] : "";
$senha = isset($_POST["senha"])? $_POST["senha"] : "";

if ($email != "") {
    $sql = "SELECT * FROM usuarios WHERE email = ? and senha = MD5(?)";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param("ss", $email, $senha);

    $stmt->execute();

    $result = $stmt->get_result();

    if($result->num_rows > 0) {
        while ($linha = $result->fetch_object()){
            $_SESSION["id_usuario"] = $linha->id_usuario;
            $_SESSION["nome_completo"] = $linha->nome_completo;
            $_SESSION["email"] = $linha->email;
            header("Location: ./area-exclusiva/index.php");
            exit;
        }   
    } else {
     $erro = ("<div class='alert alert-danger'>
            Usuário ou senha incorretos!</div>");
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
            <label for="exampleInputPassword1" class="form-label">Senha</label>
            <div class="input-with-icon">
            <input type="password" class="form-control" id="senha"  name="senha"required>                <img src="./img/view.png" class="eye-icon" id="togglePassword" alt="Mostrar senha"
                    title="Mostrar senha">
            </div>
            <a href="./Esqueci-senha.html" class="forgot-password-link">Esqueceu a senha?</a>
        </div>
        <button type="submit" class="btn cor w-100 py-3 rounded-5">Entrar</button>

        <div class="dividir">Ou entrar com</div>

        <p><a class="link-opacity-100-hover" href="#"></a></p>
        <div class="social2-button">
            <button type="submit" class="btn btnicon btn-outline-danger"><img src="./img/icons8-google-logo-48.png"
                    width="18" height="18">Google</button>

                    <button type="submit" class="btn btnicon btn-outline-primary">
                        <img src="./img/linkedin.png"
                        width="18" height="18">LinkedIn</button>
    
        </div>
        <div class="naotemconta">Não tem uma conta?
            <a href="./Criar-conta.html" class="btn criarc btn-outline-secondary w-100 ">Criar Conta </a></button>
        </div>
    </form>


</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q"
    crossorigin="anonymous"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('exampleInputPassword1');

        // Verifica se os elementos existem
        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function () {
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