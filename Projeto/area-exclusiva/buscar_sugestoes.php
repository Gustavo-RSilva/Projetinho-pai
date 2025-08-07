<?php
session_start();
include_once("../db/conexao.php");

header('Content-Type: application/json');

if (isset($_POST['termo']) && strlen(trim($_POST['termo'])) > 2) {
    $termo = trim($_POST['termo']) . "%";
    
    try {
        $sql = "SELECT DISTINCT cargo FROM salarios_referencia 
                WHERE cargo LIKE ? 
                ORDER BY cargo 
                LIMIT 5";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $termo);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $sugestoes = [];
        while ($row = $result->fetch_assoc()) {
            $sugestoes[] = $row['cargo'];
        }
        
        echo json_encode($sugestoes);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    echo json_encode([]);
}
?>