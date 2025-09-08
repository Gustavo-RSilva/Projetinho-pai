<?php
session_start();

$erro = "";

include_once("../db/conexao.php");

$cnpj = isset($_POST["cnpj"]) ? $_POST["cnpj"] : "";
$senha = isset($_POST["senha"]) ? $_POST["senha"] : "";

if ($cnpj != "") {
    // Remover caracteres não numéricos do CNPJ digitado
    $cnpj_limpo = preg_replace('/[^0-9]/', '', $cnpj);

    // Consulta que remove os caracteres não numéricos do CNPJ no banco para comparar
    $sql = "SELECT * FROM empresas WHERE REPLACE(REPLACE(REPLACE(REPLACE(cnpj, '.', ''), '/', ''), '-', ''), ' ', '') = ? AND senha = MD5(?)";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param("ss", $cnpj_limpo, $senha);

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($linha = $result->fetch_object()) {
            $_SESSION["id_empresa"] = $linha->id_empresa;
            $_SESSION["nome_empresa"] = $linha->nome;
            $_SESSION["cnpj"] = $linha->cnpj;
            header("Location: index.php");
            exit;
        }
    } else {
        $erro = ("<div class='alert alert-danger'>
            CNPJ ou senha incorretos!</div>");
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Empresas</title>
    <link rel="icon" href="../img/icon/icone-pag.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Página login */
        .paginaLogin {
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background-color: #ffffff;
            min-height: 100vh;
        }

        .form-control {
            border: 1px solid #000000 !important;
            border-radius: 1rem !important;
            padding: 12px 15px;
            font-size: 16px;
        }

        .form-label {
            font-size: 20px;
            color: #144d78;
            font-weight: 500;
        }

        .form-container {
            margin-top: 2%;
            margin-bottom: 2%;
            max-width: 450px;
            background-color: #fff;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        }

        .mb-3 {
            height: auto;
            margin-bottom: 1.5rem !important;
        }

        .entrarcom {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 290%;
            text-align: center;
            margin-bottom: 10%;
            color: #144d78;
        }

        /* olho */
        .input-with-icon {
            position: relative;
        }

        .eye-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            width: 25px;
            height: 25px;
            z-index: 2;
        }

        /* link esqueci senha */
        .forgot-password-link {
            display: block;
            text-align: height;
            font-size: 18px;
            font-weight: 550 !important;
            color: #4299e1;
            text-decoration: none;
        }

        .forgot-password-link:hover {
            text-decoration: underline;
        }

        .cor {
            background-color: #144d78 !important;
            color: white !important;
            font-size: 20px !important;
            font-weight: 600 !important;
            margin-top: -16px !important;

        }


        .cor:hover {
            background-color: #0d3a5c !important;
        }

        .naotemconta {
            text-align: center;
            margin-top: 30px;
            font-size: 20px;
            color: #4a5568;
        }

        .criarc {
            position: relative;
            margin-top: 13%;
            padding-top: 15px !important;
            padding-bottom: 15px !important;
            bottom: 2rem;
            font-size: 18px !important;
            font-weight: 500 !important;

        }

        .logo-empresa {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo-empresa i {
            font-size: 50px;
            color: #144d78;
            background-color: #e9f0f7;
            padding: 20px;
            border-radius: 50%;
        }

        .cnpj-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .cnpj-info {
            font-size: 12px;
            color: #718096;
            background-color: #f7fafc;
            padding: 4px 8px;
            border-radius: 4px;
            margin-left: 10px;
        }

        .password-toggle-icon {
            position: relative;
            right: 12px;
            top: 50% !important;
            transform: translateY(-50%);
            cursor: pointer;
            color: #7f8c8d;
            z-index: 2;
        }

        .alert {
            border-radius: 1rem;
            margin-bottom: 20px;
        }

        .test-accounts {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
            font-size: 14px;
        }

        .test-accounts h6 {
            color: #144d78;
            margin-bottom: 10px;
        }

        .test-accounts ul {
            margin-bottom: 0;
            padding-left: 20px;
        }
    </style>
</head>

<body class="paginaLogin">

    <form class="form-container" method="POST" action="login-empresa.php">
        <div class="logo-empresa">
            <i class="fas fa-building"></i>
        </div>
        <h1 class="text-center entrarcom fw-bold">Área Empresas</h1>

        <?php
        // Exibir mensagem de erro se existir
        if (!empty($erro)) {
            echo $erro;
        }
        ?>

        <div class="mb-3">
            <div class="cnpj-label">
                <label for="cnpj" class="form-label">CNPJ</label>
                <span class="cnpj-info">14 dígitos</span>
            </div>
            <input type="text" class="form-control" id="cnpj" name="cnpj" placeholder="00.000.000/0000-00" required value="<?php echo htmlspecialchars($cnpj); ?>">
        </div>

        <div class="mb-3">
            <label for="senha" class="form-label">Senha</label>
            <div class="input-with-icon">
                <input type="password" class="form-control" id="senha" name="senha" required>
                <img src="../img/view.png" class="eye-icon" id="togglePassword" alt="Mostrar senha"
                    title="Mostrar senha">
            </div>
            <a href="./Esqueci-senha-empresas.html" class="forgot-password-link m-2">Esqueceu a senha?</a>
        </div>

        <button type="submit" class="btn cor w-100 py-3 rounded-5">Entrar</button>

        <div class="naotemconta">Não tem uma conta empresa?
            <a href="./Criar-conta-emp.php" class="btn criarc btn-outline-secondary w-100">Cadastrar Empresa</a>
        </div>

        <div class="candidato-link text-center mt-3">
            <p>É um candidato? <a href="../Login.php" class="text-decoration-none">Acesse aqui</a></p>
        </div>
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>


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
                        this.src = '../img/view.png';
                        this.alt = 'Mostrar senha';
                        this.title = 'Mostrar senha';
                    } else {
                        this.src = '../img/hidden.png';
                        this.alt = 'Ocultar senha';
                        this.title = 'Ocultar senha';
                    }
                });
            }
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Máscara para CNPJ
            const cnpjInput = document.getElementById('cnpj');
            cnpjInput.addEventListener('input', function() {
                let value = this.value.replace(/\D/g, '');
                if (value.length > 14) value = value.slice(0, 14);

                if (value.length > 12) {
                    value = value.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2}).*/, '$1.$2.$3/$4-$5');
                } else if (value.length > 8) {
                    value = value.replace(/^(\d{2})(\d{3})(\d{3})(\d{4}).*/, '$1.$2.$3/$4');
                } else if (value.length > 5) {
                    value = value.replace(/^(\d{2})(\d{3})(\d{3}).*/, '$1.$2.$3');
                } else if (value.length > 2) {
                    value = value.replace(/^(\d{2})(\d{3}).*/, '$1.$2');
                }
                this.value = value;
            });

            // Mostrar/Ocultar senha


            // Validação do formulário
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                const cnpj = document.getElementById('cnpj').value;
                const cnpjLimpo = cnpj.replace(/\D/g, '');

                if (cnpjLimpo.length !== 14) {
                    e.preventDefault();
                    alert('Por favor, insira um CNPJ válido com 14 dígitos.');
                    return;
                }
            });
        });
    </script>
</body>

</html>