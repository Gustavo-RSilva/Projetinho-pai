<?php
// Inicia a sessão no início do arquivo para acessar as variáveis
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['nome_completo'])) {
    header("Location: login-google.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área do Usuário</title>
    <style>
        .card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            max-width: 400px;
            margin: 20px auto;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .profile-img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            border: 3px solid #f0f0f0;
        }
        .user-info {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="card"> 
        <?php if(isset($_SESSION['foto_perfil'])): ?>
            <img src="<?php echo htmlspecialchars($_SESSION['foto_perfil']); ?>" 
                 alt="Foto de perfil de <?php echo htmlspecialchars($_SESSION['nome_completo']); ?>" 
                 class="profile-img">
        <?php endif; ?>
        
        <div class="user-info">
            <h2>Bem-vindo, <?php echo htmlspecialchars($_SESSION['nome_completo']); ?></h2>
        </div>
        
        <div class="user-info">
            <strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['email']); ?>
        </div>
        
        <!-- Link para logout (opcional) -->
        <div style="margin-top: 20px;">
            <a href="logout.php" style="color: #ff0000; text-decoration: none;">Sair</a>
        </div>
    </div>
</body>
</html>