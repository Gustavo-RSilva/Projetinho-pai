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
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');
    $senha_confirm = trim($_POST['senha_confirm'] ?? '');
    
    // Validações
    $erros = [];
    
    if (empty($nome)) {
        $erros[] = "O nome da empresa é obrigatório.";
    }
    
    if (empty($email)) {
        $erros[] = "O e-mail é obrigatório.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = "Formato de e-mail inválido.";
    }
    
    // Verificar se a senha foi preenchida
    if (!empty($senha)) {
        if (strlen($senha) < 6) {
            $erros[] = "A senha deve ter pelo menos 6 caracteres.";
        } elseif ($senha !== $senha_confirm) {
            $erros[] = "As senhas não coincidem.";
        }
    }
    
    // Verificar se o email já existe (exceto para a própria empresa)
    $stmt_check = $conn->prepare("SELECT id_empresa FROM empresas WHERE email = ? AND id_empresa != ?");
    $stmt_check->bind_param("si", $email, $id_empresa);
    $stmt_check->execute();
    
    if ($stmt_check->get_result()->num_rows > 0) {
        $erros[] = "Este e-mail já está em uso por outra empresa.";
    }
    
    // Se não houver erros, proceder com a atualização
    if (empty($erros)) {
        try {
            // DEBUG: Verificar o que está sendo recebido
            error_log("Nome: $nome, Email: $email, Senha: $senha");
            
            if (!empty($senha)) {
                // Se senha foi fornecida, atualizar com senha em MD5
                $senha_md5 = md5($senha);
                error_log("Senha MD5: $senha_md5");
                
                $query = "UPDATE empresas SET nome = ?, email = ?, senha = ? WHERE id_empresa = ?";
                $stmt = $conn->prepare($query);
                
                if ($stmt) {
                    $stmt->bind_param("sssi", $nome, $email, $senha_md5, $id_empresa);
                    
                    if ($stmt->execute()) {
                        $_SESSION['msg'] = "Configurações e senha atualizadas com sucesso!";
                        $_SESSION['tipo_msg'] = "success";
                        $_SESSION['nome_empresa'] = $nome;
                    } else {
                        $_SESSION['msg'] = "Erro ao atualizar: " . $conn->error;
                        $_SESSION['tipo_msg'] = "danger";
                    }
                    $stmt->close();
                } else {
                    $_SESSION['msg'] = "Erro na preparação da query: " . $conn->error;
                    $_SESSION['tipo_msg'] = "danger";
                }
            } else {
                // Se senha não foi fornecida, manter a senha atual
                $query = "UPDATE empresas SET nome = ?, email = ? WHERE id_empresa = ?";
                $stmt = $conn->prepare($query);
                
                if ($stmt) {
                    $stmt->bind_param("ssi", $nome, $email, $id_empresa);
                    
                    if ($stmt->execute()) {
                        $_SESSION['msg'] = "Configurações atualizadas com sucesso!";
                        $_SESSION['tipo_msg'] = "success";
                        $_SESSION['nome_empresa'] = $nome;
                    } else {
                        $_SESSION['msg'] = "Erro ao atualizar: " . $conn->error;
                        $_SESSION['tipo_msg'] = "danger";
                    }
                    $stmt->close();
                } else {
                    $_SESSION['msg'] = "Erro na preparação da query: " . $conn->error;
                    $_SESSION['tipo_msg'] = "danger";
                }
            }
            
        } catch (Exception $e) {
            $_SESSION['msg'] = "Erro: " . $e->getMessage();
            $_SESSION['tipo_msg'] = "danger";
        }
    } else {
        $_SESSION['msg'] = implode("<br>", $erros);
        $_SESSION['tipo_msg'] = "danger";
    }
    
    // Redirecionar de volta para a página anterior
    header("Location: index.php");
    exit();
} else {
    // Se não for POST, redirecionar
    header("Location: index.php");
    exit();
}
?>