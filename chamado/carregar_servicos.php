<?php
header('Content-Type: application/json');

//$mysqli = new mysqli("localhost", "root", "sefazfer123@", "catalogo-teste");
require_once '../conexao.php';
if ($mysqli->connect_errno) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao conectar com o banco de dados']);
    exit;
}

$subcategoria_id = intval($_GET['subcategoria_id'] ?? 0);

if ($subcategoria_id <= 0) {
    echo json_encode([]);
    exit;
}

$stmt = $mysqli->prepare("
    SELECT ID, Titulo, Descricao 
    FROM servico 
    WHERE ID_SubCategoria = ?
    ORDER BY Titulo ASC
");

$stmt->bind_param("i", $subcategoria_id);

$stmt->execute();

$result = $stmt->get_result();

$servicos = $result->fetch_all(MYSQLI_ASSOC);

$stmt->close();

echo json_encode($servicos);
?>
