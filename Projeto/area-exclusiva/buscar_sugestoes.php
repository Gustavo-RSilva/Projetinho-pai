<?php
include_once("../db/conexao.php");

$termo = $_GET['termo'] ?? '';
$termo = trim($termo);

$sugestoes = [];

if ($termo !== '') {
    $like = "%" . $termo . "%";

    $sql = "
        SELECT titulo AS sugestao
        FROM vagas
        WHERE ativa = 1 AND titulo LIKE ?
        UNION
        SELECT nome AS sugestao
        FROM empresas
        WHERE nome LIKE ?
        UNION
        SELECT nome AS sugestao
        FROM areas_profissionais
        WHERE nome LIKE ?
        LIMIT 10
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $like, $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $sugestoes[] = $row['sugestao'];
    }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($sugestoes);
