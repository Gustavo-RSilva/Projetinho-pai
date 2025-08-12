<?php
session_start();
include_once("db/conexao.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);

    // Foto padrão
    $foto = "default.png";

    // Se o usuário enviou uma foto
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $ext_permitidas = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array(strtolower($ext), $ext_permitidas)) {
            $novo_nome = uniqid() . "." . $ext;
            $destino = "uploads/" . $novo_nome;

            if (move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
                $foto = $novo_nome;
            }
        }
    }

    // Inserir no banco de dados
    $sql = "INSERT INTO usuarios (nome, email, senha, foto) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $nome, $email, $senha, $foto);

    if ($stmt->execute()) {
        echo "<p>Conta criada com sucesso!</p>";
    } else {
        echo "<p>Erro: " . $stmt->error . "</p>";
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Enviar Foto</title>
    <link rel="stylesheet" href="./css/upload-foto.css">
</head>
<body>
<form action="upload-foto.php" method="POST" enctype="multipart/form-data">
    <h2>Crie sua conta</h2>

    <label>Nome</label>
    <input type="text" name="nome" required>

    <label>Email</label>
    <input type="email" name="email" required>

    <label>Senha</label>
    <input type="password" name="senha" required>

    <label>Foto de perfil (opcional)</label>
    <label for="foto" class="upload-btn">Escolher Foto</label>
    <input type="file" id="foto" name="foto" accept="image/*" onchange="previewFoto(event)">
    <img id="preview" class="preview" src="uploads/default.png" alt="Pré-visualização">

    <button type="submit">Criar Conta</button>
</form>

<script>
function previewFoto(event) {
    const output = document.getElementById('preview');
    output.src = URL.createObjectURL(event.target.files[0]);
}
</script>

</body>
</html>
