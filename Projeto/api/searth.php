<?php
header('Content-Type: application/json; charset=utf-8');
include_once("../db/conexao.php");

$term = $_GET['term'] ?? '';
$location = $_GET['location'] ?? '';

$sql = "SELECT v.titulo, e.nome AS empresa_nome, v.localizacao
        FROM vagas v
        JOIN empresas e ON v.id_empresa = e.id_empresa
        WHERE v.ativa = 1 
          AND v.data_expiracao >= CURDATE()
          AND (v.titulo LIKE ? OR e.nome LIKE ?)";

$params = ["%$term%", "%$term%"];

// Filtro por estado/localização
if (!empty($location)) {
    $sql .= " AND v.localizacao LIKE ?";
    $params[] = "%$location%";
}

$sql .= " ORDER BY v.data_publicacao DESC LIMIT 10";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["error" => $conn->error, "sql" => $sql]);
    exit;
}

$types = str_repeat("s", count($params));
$stmt->bind_param($types, ...$params);

$stmt->execute();
$result = $stmt->get_result();

$suggestions = [];
while ($row = $result->fetch_assoc()) {
    $suggestions[] = [
        "titulo" => $row['titulo'],
        "empresa" => $row['empresa_nome'],
        "localizacao" => $row['localizacao']
    ];
}

echo json_encode($suggestions, JSON_UNESCAPED_UNICODE);
