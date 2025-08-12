<?php
session_start();
include("db/conexao.php"); // conexão MySQLi

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = md5($_POST['senha']); // Pode trocar para password_hash()
    $foto = null;

    if (!empty($_FILES['foto']['name'])) {
        $pasta = "uploads/";
        if (!is_dir($pasta)) mkdir($pasta, 0777, true);

        $nomeFoto = uniqid() . "-" . basename($_FILES['foto']['name']);
        $caminho = $pasta . $nomeFoto;

        if (move_uploaded_file($_FILES['foto']['tmp_name'], $caminho)) {
            $foto = $caminho;
        }
    }
    // Salva na sessão para já logar o usuário
    $_SESSION['id_usuario'] = $novo_id;
    $_SESSION['nome_completo'] = $nome;
    
    $sql = "INSERT INTO usuarios (nome_completo, email, senha, foto_perfil) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $nome, $email, $senha, $foto);
    if ($stmt->execute()) {
        $_SESSION['id_usuario'] = $stmt->insert_id;
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
