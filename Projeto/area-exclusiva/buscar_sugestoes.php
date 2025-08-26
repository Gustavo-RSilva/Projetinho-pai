<?php
session_start();
include_once("../db/conexao.php");

header('Content-Type: application/json');

if (isset($_GET['termo'])) {
    $termo = $_GET['termo'];
    
    // Buscar sugestões de vagas com base no termo
    $sql = "SELECT titulo FROM vagas 
            WHERE ativa = 1 AND data_expiracao >= CURDATE() 
            AND titulo LIKE ? 
            GROUP BY titulo 
            LIMIT 5";
    
    $stmt = $conn->prepare($sql);
    $termoLike = "%" . $termo . "%";
    $stmt->bind_param("s", $termoLike);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sugestoes = [];
    while ($row = $result->fetch_assoc()) {
        $sugestoes[] = $row['titulo'];
    }
    
    echo json_encode($sugestoes);
}
?>