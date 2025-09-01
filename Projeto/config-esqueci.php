<?php
header('Content-Type: application/json');

// Corrigido caminho da conexão
require __DIR__ . '/db/conexao.php';

// Função para enviar email usando PHPMailer
function enviarEmail($email, $codigo) {
    require __DIR__ . '/vendor/autoload.php';
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp-mail.outlook.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'gustavo.rsilva66@senacsp.edu.br'; // seu email institucional
        $mail->Password ='xxxx';      // senha de app (não a normal!)
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom($mail->Username, 'Sistema');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Código de Verificação';
        $mail->Body = "Seu código de verificação é: <b>$codigo</b>";

        // Debug opcional
        // $mail->SMTPDebug = 2;
        // $mail->Debugoutput = 'error_log';

        $mail->send();
        return true;

    } catch (Exception $e) {
        echo json_encode([
            "status" => "erro",
            "msg" => "Erro no envio: " . $mail->ErrorInfo
        ]);
        exit;
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

    // Atualizar código na tabela usuarios
    $stmt = $conn->prepare("UPDATE usuarios SET cod_verificacao = ? WHERE email = ?");
    if (!$stmt) {
        echo json_encode(["status" => "erro", "msg" => "Erro na query: " . $conn->error]);
        exit;
    }

    $stmt->bind_param("ss", $codigo, $email);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        if (enviarEmail($email, $codigo)) {
            echo json_encode(["status" => "ok", "msg" => "Email enviado"]);
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
    if (!$stmt) {
        echo json_encode(["status" => "erro", "msg" => "Erro na query: " . $conn->error]);
        exit;
    }

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
    if (!$stmt) {
        echo json_encode(["status" => "erro", "msg" => "Erro na query: " . $conn->error]);
        exit;
    }

    $stmt->bind_param("ss", $hash, $email);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(["status" => "ok", "msg" => "Enviado"]);
    } else {
        echo json_encode(["status" => "erro", "msg" => "Falha ao atualizar senha"]);
    }

    $stmt->close();
    exit;
}

echo json_encode(["status" => "erro", "msg" => "Ação inválida"]);
