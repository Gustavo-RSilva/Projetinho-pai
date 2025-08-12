<?php
session_start();
include("db/conexao.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = md5($_POST['senha']);
    $foto = null;

    if (!empty($_FILES['foto']['name'])) {
        // Configurações do upload
        $pastaBase = "img/foto-perfil/";
        $tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif'];
        $tamanhoMaximo = 2 * 1024 * 1024; // 2MB
        
        // Verifica tipo e tamanho do arquivo
        if (in_array($_FILES['foto']['type'], $tiposPermitidos) && 
            $_FILES['foto']['size'] <= $tamanhoMaximo) {
            
            // Cria a pasta base se não existir
            if (!is_dir($pastaBase)) mkdir($pastaBase, 0777, true);
            
            // Gera um ID único para a pasta do usuário
            $pastaUsuario = $pastaBase . uniqid() . '/';
            mkdir($pastaUsuario, 0777, true);
            
            // Gera nome seguro para o arquivo
            $extensao = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $nomeFoto = 'perfil.' . $extensao;
            $caminhoCompleto = $pastaUsuario . $nomeFoto;
            
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $caminhoCompleto)) {
                $foto = $caminhoCompleto;
            }
        }
    }
    
    // Restante do código de inserção no banco de dados
    $sql = "INSERT INTO usuarios (nome_completo, email, senha, foto_perfil) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $nome, $email, $senha, $foto);
    
    if ($stmt->execute()) {
        // Armazena todos os dados na sessão
        $_SESSION['id_usuario'] = $stmt->insert_id;
        $_SESSION['email'] = $email;
        $_SESSION['nome_completo'] = $nome;
        $_SESSION['foto_perfil'] = $foto ? $foto : 'img/default-profile.png';
        
        header("Location: ./area-exclusiva/index.php");
        exit();
    } else {
        $erro = "Erro ao criar conta.";
    }
}
?>


<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Criar Conta</title>
    <style>
        <?php include "./css/cadastro.css"; ?>

        .upload-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0;
            border: none;
            background: none;
        }

        .foto-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #0077b6;
            margin-bottom: 10px;
            background: #f0f8ff;
        }

        .upload-label {
            font-size: 14px;
            color: #0077b6;
            cursor: pointer;
        }

        input[type="file"] {
            display: none;
        }
    </style>
</head>
<body>
    <form method="POST" enctype="multipart/form-data">
        <h2>Criar Conta</h2>

        <div class="upload-box">
            <img src="https://via.placeholder.com/120" class="foto-preview" id="preview">
            <label for="foto" class="upload-label">Escolher Foto (opcional)</label>
            <input type="file" name="foto" id="foto" accept="image/*">
        </div>

        <label>Nome Completo:</label>
        <input type="text" name="nome" required>

        <label>Email:</label>
        <input type="email" name="email" required>

        <label>Senha:</label>
        <input type="password" name="senha" required>

        <button type="submit">Criar Conta</button>

        <?php if (!empty($erro)) echo "<p class='erro'>$erro</p>"; ?>
    </form>

    <script>
        document.getElementById('foto').addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                document.getElementById('preview').src = URL.createObjectURL(file);
            }
        });
    </script>
</body>
</html>
