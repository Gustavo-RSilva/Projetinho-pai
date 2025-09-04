<?php
session_start();
include_once("../db/conexao.php");

if (isset($_POST['termo'])) {
    $termo = trim($_POST['termo']) . "%";

    $sql = "SELECT DISTINCT cargo 
            FROM salarios_referencia 
            WHERE cargo LIKE ? 
            ORDER BY cargo 
            LIMIT 8";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $termo);
    $stmt->execute();
    $result = $stmt->get_result();

    $sugestoes = [];
    while ($row = $result->fetch_assoc()) {
        $sugestoes[] = $row['cargo'];
    }

    echo json_encode($sugestoes);
    exit;
}

echo json_encode([]);
