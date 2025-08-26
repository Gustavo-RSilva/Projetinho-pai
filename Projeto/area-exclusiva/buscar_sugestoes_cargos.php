<?php
// buscar_sugestoes_cargos.php
include_once("../db/conexao.php");

header('Content-Type: application/json');

if (isset($_POST['termo'])) {
    $termo = $_POST['termo'];
    
    // Buscar sugestões de cargos da tabela salarios_referencia
    $sql = "SELECT DISTINCT cargo FROM salarios_referencia WHERE cargo LIKE ? ORDER BY cargo LIMIT 5";
    
    $stmt = $conn->prepare($sql);
    $termoLike = $termo . "%";
    $stmt->bind_param("s", $termoLike);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sugestoes = [];
    while ($row = $result->fetch_assoc()) {
        $sugestoes[] = $row['cargo'];
    }
    
    echo json_encode($sugestoes);
}
?>