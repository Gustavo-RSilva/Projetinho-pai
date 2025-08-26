<?php
header('Content-Type: application/json');
require 'db/conexao.php'; // ajustado caminho
require 'vendor/autoload.php';
require_once 'mailersend-config.php';
// Função para enviar email usando PHPMailer


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function enviarEmailMailerSend(string $email, string $codigo): array
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.mailersend.net'; 
        $mail->SMTPAuth = true;
        $mail->Username = 'apikey'; // seu email
        $mail->Password = 'mlsn.8a3cdcac2155274887138b0edb904e74cf6198059793618ef7e15ebadc5850f9';      // senha de app
        $mail->Port = 587;
        $mail->CharSet    = 'UTF-8';

        if (MS_SMTP_SECURE === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // porta 465
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // porta 587
        }

        $mail->setFrom('gustavo.rsilva66@senacsp.edu.br', 'Sistema');
        $mail->addAddress($email);
        $mail->isHTML(true);

        $mail->Subject = 'Código de Verificação';
        $mail->Body = "Seu código de verificação é: <b>$codigo</b>";

        $mail->send();
        return ['ok' => true, 'err' => null];
    } catch (Exception $e) {
        // Loga o erro detalhado no servidor (não expor ao usuário final)
        error_log('MailerSend SMTP error: ' . $mail->ErrorInfo);
        return ['ok' => false, 'err' => $mail->ErrorInfo];
    }
}


$action = $_POST['action'] ?? '';
// Helper: resposta JSON e fim
function respond(array $data): void {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'enviar_codigo') {
    $email = $_POST['email'] ?? '';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(["status" => "erro", "msg" => "E-mail inválido"]);
    }

    // 1) Verifica se o e-mail existe
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    if (!$stmt) respond(["status" => "erro", "msg" => "Falha interna (SELECT): ".$conn->error]);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($uid);
    $found = $stmt->fetch();
    $stmt->close();

    if (!$found) {
        respond(["status" => "erro", "msg" => "E-mail não encontrado"]);
    }

    // 2) Gera código criptograficamente forte (6 dígitos)
    $codigo = (string) random_int(100000, 999999);

    // 3) Atualiza o código na base
    $stmt = $conn->prepare("UPDATE usuarios SET cod_verificacao = ? WHERE email = ?");
    if (!$stmt) respond(["status" => "erro", "msg" => "Falha interna (UPDATE): ".$conn->error]);
    $stmt->bind_param("ss", $codigo, $email);
    $stmt->execute();
    $stmt->close();

    // 4) Envia o e-mail
    $r = enviarEmailMailerSend($email, $codigo);
    if ($r['ok']) {
        respond(["status" => "ok"]);
    } else {
        respond(["status" => "erro", "msg" => "Falha ao enviar e-mail"]);
    }
}

if ($action === 'verificar_codigo') {
    $email = $_POST['email'] ?? '';
    $code  = $_POST['code']  ?? '';

    if (!$email || !$code) {
        respond(["status" => "erro", "msg" => "Dados insuficientes"]);
    }

    $stmt = $conn->prepare("SELECT cod_verificacao FROM usuarios WHERE email = ?");
    if (!$stmt) respond(["status" => "erro", "msg" => "Falha interna (SELECT): ".$conn->error]);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($codDB);
    $stmt->fetch();
    $stmt->close();

    if ($codDB && hash_equals((string)$codDB, (string)$code)) {
        respond(["status" => "ok"]);
    } else {
        respond(["status" => "erro", "msg" => "Código inválido"]);
    }
}

if ($action === 'alterar_senha') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';

    if (!$email || strlen($senha) < 6) {
        respond(["status" => "erro", "msg" => "Senha muito curta"]);
    }

    $hash = password_hash($senha, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE usuarios SET senha = ?, cod_verificacao = NULL WHERE email = ?");
    if (!$stmt) respond(["status" => "erro", "msg" => "Falha interna (UPDATE): ".$conn->error]);
    $stmt->bind_param("ss", $hash, $email);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $stmt->close();
        respond(["status" => "ok"]);
    } else {
        $stmt->close();
        respond(["status" => "erro", "msg" => "Falha ao atualizar senha"]);
    }
}

// Action desconhecida
respond(["status" => "erro", "msg" => "Ação inválida"]);