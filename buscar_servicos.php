<?php
require_once 'conexao.php';
header('Content-Type: application/json');

$termo = trim($_GET['termo'] ?? '');
if (strlen($termo) < 2) {
    echo json_encode([]);
    exit;
}

$tokens = array_filter(explode(' ', $termo));
if (empty($tokens)) {
    echo json_encode([]);
    exit;
}

$where_conditions = [];
$params = [];
$types = '';

foreach ($tokens as $token) {
    $where_conditions[] = "(s.Titulo LIKE ? OR s.Descricao LIKE ? OR sub.Titulo LIKE ? OR cat.Titulo LIKE ?)";
    
    $param = "%" . $token . "%";
    array_push($params, $param, $param, $param, $param);
    $types .= 'ssss';
}

$where_clause = implode(' AND ', $where_conditions);

$query = "
    SELECT 
        s.ID as id,
        s.Titulo as titulo,
        s.Descricao as descricao,
        sub.Titulo AS subcategoria,
        cat.Titulo AS categoria
    FROM servico s
    JOIN subcategoria sub ON s.ID_SubCategoria = sub.ID
    JOIN categoria cat ON sub.ID_Categoria = cat.ID
    WHERE $where_clause
      AND s.status_ficha = 'publicado'
    ORDER BY s.ID DESC
    LIMIT 10
";

$stmt = $mysqli->prepare($query);
if ($stmt) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $servicos = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($servicos);
} else {
    echo json_encode([]);
}
?>
