<?php
session_start();
include_once("../db/conexao.php");

if (!isset($_SESSION['id_empresa'])) {
    header("Location: login.php");
    exit();
}

$id_empresa = (int)$_SESSION['id_empresa'];

// Verificar se o formulário foi submetido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Coletar e sanitizar os dados do formulário
    $nome = trim($_POST['nome'] ?? '');
    $cnpj = trim($_POST['cnpj'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    
    // Processar upload da logo
    $logo_path = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $logo = $_FILES['logo'];
        
        // Validar tipo de arquivo
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = mime_content_type($logo['tmp_name']);
        
        if (in_array($file_type, $allowed_types)) {
            // Validar tamanho (máximo 2MB)
            if ($logo['size'] <= 2 * 1024 * 1024) {
                // Criar diretório se não existir
                $upload_dir = '../img/logo-empresa/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Gerar nome único para o arquivo
                $file_extension = pathinfo($logo['name'], PATHINFO_EXTENSION);
                $file_name = 'empresa_' . $id_empresa . '_' . time() . '.' . $file_extension;
                $file_path = $upload_dir . $file_name;
                
                // Mover arquivo
                if (move_uploaded_file($logo['tmp_name'], $file_path)) {
                    $logo_path = $file_path;
                    
                    // Se havia uma logo anterior, excluí-la
                    $stmt = $conn->prepare("SELECT url_logo FROM empresas WHERE id_empresa = ?");
                    $stmt->bind_param("i", $id_empresa);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $old_logo = $result->fetch_assoc()['url_logo'];
                    
                    if ($old_logo && file_exists($old_logo) && $old_logo !== $logo_path) {
                        unlink($old_logo);
                    }
                }
            }
        }
    }
    
    // Validações básicas
    $erros = [];
    
    if (empty($nome)) {
        $erros[] = "O nome da empresa é obrigatório.";
    }
    
    if (empty($cnpj)) {
        $erros[] = "O CNPJ é obrigatório.";
    }
    
    // Se não houver erros, proceder com a atualização
    if (empty($erros)) {
        try {
            if ($logo_path) {
                // Atualizar com logo
                $query = "UPDATE empresas SET nome = ?, cnpj = ?, email = ?, telefone = ?, endereco = ?, descricao = ?, url_logo = ? WHERE id_empresa = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("sssssssi", $nome, $cnpj, $email, $telefone, $endereco, $descricao, $logo_path, $id_empresa);
            } else {
                // Atualizar sem alterar a logo
                $query = "UPDATE empresas SET nome = ?, cnpj = ?, email = ?, telefone = ?, endereco = ?, descricao = ? WHERE id_empresa = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssssssi", $nome, $cnpj, $email, $telefone, $endereco, $descricao, $id_empresa);
            }
            
            if ($stmt->execute()) {
                $_SESSION['msg'] = "Informações da empresa atualizadas com sucesso!" . ($logo_path ? " Logo atualizada." : "");
                $_SESSION['tipo_msg'] = "success";
            } else {
                $_SESSION['msg'] = "Erro ao atualizar informações: " . $conn->error;
                $_SESSION['tipo_msg'] = "danger";
            }
            
            $stmt->close();
            
        } catch (Exception $e) {
            $_SESSION['msg'] = "Erro: " . $e->getMessage();
            $_SESSION['tipo_msg'] = "danger";
        }
    } else {
        $_SESSION['msg'] = implode("<br>", $erros);
        $_SESSION['tipo_msg'] = "danger";
    }
    
    // Redirecionar de volta para a página anterior
    header("Location: index.php#empresa");
    exit();
} else {
    // Se não for POST, redirecionar
    header("Location: index.php");
    exit();
}
?>