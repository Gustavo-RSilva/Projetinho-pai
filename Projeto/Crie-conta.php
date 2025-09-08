<?php
session_start();
include("db/conexao.php");

$erro = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    $foto = 'img/default-profile.png'; // Valor padrão

    // Validações
    if (empty($nome) || empty($email) || empty($senha)) {
        $erro = "Todos os campos obrigatórios devem ser preenchidos!";
    } elseif ($senha !== $confirmar_senha) {
        $erro = "As senhas não coincidem!";
    } elseif (strlen($senha) < 6) {
        $erro = "A senha deve ter pelo menos 6 caracteres!";
    } else {
        // Verificar se email já existe
        $sql = "SELECT id_usuario FROM usuarios WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $erro = "Este email já está cadastrado!";
        } else {
            // Processar upload da foto (se existir)
            if (!empty($_FILES['foto']['name'])) {
                // 1. Definir caminho base
                $pastaBase = __DIR__ . '/img/foto-perfil/';

                // 2. Criar pasta base se não existir
                if (!file_exists($pastaBase)) {
                    mkdir($pastaBase, 0777, true);
                }

                // 3. Criar pasta única para o usuário
                $pastaUsuario = uniqid();
                $caminhoPastaUsuario = $pastaBase . $pastaUsuario;

                if (!file_exists($caminhoPastaUsuario)) {
                    mkdir($caminhoPastaUsuario, 0777, true);
                }

                // 4. Processar o arquivo
                $nomeArquivo = $_FILES['foto']['name'];
                $extensao = strtolower(pathinfo($nomeArquivo, PATHINFO_EXTENSION));
                $nomeFoto = 'perfil.' . $extensao;
                $caminhoCompleto = $caminhoPastaUsuario . '/' . $nomeFoto;

                // 5. Verificar e mover o arquivo (somente jpg, jpeg e png)
                $check = getimagesize($_FILES['foto']['tmp_name']);
                if ($check !== false && in_array($extensao, ['jpg', 'jpeg', 'png'])) {
                    if (move_uploaded_file($_FILES['foto']['tmp_name'], $caminhoCompleto)) {
                        // Caminho salvo no banco deve ser relativo ao projeto
                        $foto = 'img/foto-perfil/' . $pastaUsuario . '/' . $nomeFoto;
                    } else {
                        $erro = "Erro ao mover o arquivo. Verifique as permissões.";
                    }
                } else {
                    $erro = "Arquivo inválido. Apenas imagens JPG, JPEG e PNG são permitidas.";
                }
            }

            // Só prossegue com o cadastro se não houver erro
            if (empty($erro)) {
                // Hash da senha
                $senha_hash = md5($senha);

                // Inserir no banco de dados
                $sql = "INSERT INTO usuarios (nome_completo, email, senha, foto_perfil) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssss", $nome, $email, $senha_hash, $foto);

                if ($stmt->execute()) {
                    // Configurar sessão
                    $_SESSION['id_usuario'] = $stmt->insert_id;
                    $_SESSION['email'] = $email;
                    $_SESSION['nome_completo'] = $nome;
                    $_SESSION['foto_perfil'] = $foto;

                    header("Location: area-exclusiva/index.php");
                    exit();
                } else {
                    $erro = "Erro ao criar conta. Por favor, tente novamente.";
                }
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta</title>
    <link rel="icon" href="img/icon/icone-pag.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="./css/crie-conta.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>

<body>
    <div class="form-container">
        <?php if (!empty($erro)): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($erro) ?>
            </div>
        <?php endif; ?>
        <h2 class="form-title">Criar Conta</h2>
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-4">
                <img src="img/default-profile.png" class="foto-preview" id="preview">
                <label for="foto" class="upload-label">
                    <i class="bi bi-camera-fill"></i> Escolher Foto (opcional)
                </label>
                <input type="file" name="foto" id="foto" accept="image/*" class="d-none">
            </div>

            <div class="mb-3">
                <label for="nome" class="form-label">Nome Completo</label>
                <input type="text" class="form-control" id="nome" name="nome" required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>

            <div class="mb-3">
                <label for="senha" class="form-label">Senha</label>
                <div class="input-with-icon">
                    <input type="password" class="form-control password-input" id="senha" name="senha" required>
                    <img src="./img/view.png" class="toggle-password eye-icon" alt="Mostrar senha" title="Mostrar senha">
                </div>
            </div>

            <div class="mb-4">
                <label for="confirmar_senha" class="form-label">Confirmar Senha</label>
                <div class="input-with-icon">
                    <input type="password" class="form-control password-input" id="confirmar_senha" name="confirmar_senha" required>
                    <img src="./img/view.png" class="toggle-password eye-icon" alt="Mostrar senha" title="Mostrar senha">
                </div>
            </div>

            <button type="submit" class="btn cor w-100 mb-3">Criar Conta</button>

            <div class="mt-3 text-center">
                Já tem uma conta? <a href="Login.php">Faça login</a>
            </div>
            <div class="empresa-link text-center mt-3">
                <p>É uma empresa? <a href="./area-empresa/criar-conta-emp.php" class="text-decoration-none">Acesse aqui</a></p>
            </div>
        </form>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggles = document.querySelectorAll('.toggle-password');

            toggles.forEach(function(toggle) {
                toggle.addEventListener('click', function() {
                    const input = this.previousElementSibling;
                    if (input && input.classList.contains('password-input')) {
                        const type = input.type === 'password' ? 'text' : 'password';
                        input.type = type;

                        // Alterna ícone
                        if (type === 'password') {
                            this.src = './img/view.png';
                            this.alt = 'Mostrar senha';
                            this.title = 'Mostrar senha';
                        } else {
                            this.src = './img/hidden.png';
                            this.alt = 'Ocultar senha';
                            this.title = 'Ocultar senha';
                        }
                    }
                });
            });
        });
    </script>
    <script>
        // Preview da foto selecionada
        document.getElementById('foto').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('preview').src = event.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>
