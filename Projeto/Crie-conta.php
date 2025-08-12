<?php
session_start();
include("db/conexao.php");

$erro = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    $foto = null;

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
                $pasta = "../img/foto-perfil/";
                if (!is_dir($pasta)) mkdir($pasta, 0777, true);
                
                $nomeUnico = uniqid() . '_' . time();
                $extensao = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
                $nomeFoto = $nomeUnico . '.' . $extensao;
                $caminho = $pasta . $nomeFoto;
                
                // Verificar se é uma imagem válida
                $check = getimagesize($_FILES['foto']['tmp_name']);
                if ($check !== false && in_array($extensao, ['jpg', 'jpeg', 'png', 'gif'])) {
                    if (move_uploaded_file($_FILES['foto']['tmp_name'], $caminho)) {
                        $foto = 'img/foto-perfil/' . $nomeFoto;
                    } else {
                        $erro = "Erro ao fazer upload da imagem.";
                    }
                } else {
                    $erro = "Arquivo inválido. Apenas imagens JPG, JPEG, PNG e GIF são permitidas.";
                }
            }
            
            if (empty($erro)) {
                // Hash da senha
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                
                // Inserir no banco de dados
                $sql = "INSERT INTO usuarios (nome_completo, email, senha, foto_perfil) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssss", $nome, $email, $senha_hash, $foto);
                
                if ($stmt->execute()) {
                    // Configurar sessão
                    $_SESSION['id_usuario'] = $stmt->insert_id;
                    $_SESSION['email'] = $email;
                    $_SESSION['nome_completo'] = $nome;
                    $_SESSION['foto_perfil'] = $foto ?: 'img/default-profile.png';
                    
                    header("Location: ./area-exclusiva/index.php");
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
    <title>Criar Conta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .form-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .foto-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #007bff;
            margin-bottom: 15px;
        }
        .upload-label {
            cursor: pointer;
            color: #007bff;
        }
        .form-title {
            color: #007bff;
            margin-bottom: 30px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2 class="form-title">Criar Conta</h2>
            
            <?php if (!empty($erro)): ?>
                <div class="alert alert-danger"><?php echo $erro; ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="text-center mb-4">
                    <img src="../img/default-profile.png" class="foto-preview" id="preview">
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
                    <input type="password" class="form-control" id="senha" name="senha" required>
                </div>
                
                <div class="mb-4">
                    <label for="confirmar_senha" class="form-label">Confirmar Senha</label>
                    <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" required>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">Criar Conta</button>
                
                <div class="mt-3 text-center">
                    Já tem uma conta? <a href="Login.php">Faça login</a>
                </div>
            </form>
        </div>
    </div>

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