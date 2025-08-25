<?php
header('Content-Type: application/json');
require 'db/conexao.php'; // ajustado caminho

// Função para enviar email usando PHPMailer
function enviarEmail($email, $codigo) {
    require 'vendor/autoload.php';
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp-mail.outlook.com'; 
        $mail->SMTPAuth = true;
        $mail->Username = 'gustavo.rsilva66@senacsp.edu.br'; // seu email
        $mail->Password = '@Guchng10';      // senha de app
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('gustavo.rsilva66@senacsp.edu.br', 'Sistema');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Código de Verificação';
        $mail->Body = "Seu código de verificação é: <b>$codigo</b>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

$action = $_POST['action'] ?? '';

if ($action === 'enviar_codigo') {
    $email = $_POST['email'] ?? '';
    if (!$email) {
        echo json_encode(["status" => "erro", "msg" => "Email vazio"]);
        exit;
    }

    // Gerar código de 6 dígitos
    $codigo = rand(100000, 999999);

    // Atualizar código na tabela usuários
    $stmt = $conn->prepare("UPDATE usuarios SET cod_verificacao = ? WHERE email = ?");
    $stmt->bind_param("ss", $codigo, $email);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        if (enviarEmail($email, $codigo)) {
            echo json_encode(["status" => "ok"]);
        } else {
            echo json_encode(["status" => "erro", "msg" => "Falha ao enviar email"]);
        }
    } else {
        echo json_encode(["status" => "erro", "msg" => "Email não encontrado"]);
    }
    $stmt->close();
    exit;   
}

if ($action === 'verificar_codigo') {
    $email = $_POST['email'] ?? '';
    $code = $_POST['code'] ?? '';

    $stmt = $conn->prepare("SELECT cod_verificacao FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($cod);
    $stmt->fetch();
    $stmt->close();

    if ($cod && $cod == $code) {
        echo json_encode(["status" => "ok"]);
    } else {
        echo json_encode(["status" => "erro", "msg" => "Código inválido"]);
    }
    exit;
}

if ($action === 'alterar_senha') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $hash = password_hash($senha, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE usuarios SET senha = ?, cod_verificacao = NULL WHERE email = ?");
    $stmt->bind_param("ss", $hash, $email);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(["status" => "ok"]);
    } else {
        echo json_encode(["status" => "erro", "msg" => "Falha ao atualizar senha"]);
    }
    $stmt->close();
    exit;
}

echo json_encode(["status" => "erro", "msg" => "Ação inválida"]);
