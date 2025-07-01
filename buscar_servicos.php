<?php
require_once 'conexao.php';
header('Content-Type: application/json');

$termo = trim($_GET['termo'] ?? '');
if (strlen($termo) < 2) {
    echo json_encode(['servicos' => [], 'subcategorias' => []]);
    exit;
}

$tokens = array_filter(explode(' ', $termo));
if (empty($tokens)) {
    echo json_encode(['servicos' => [], 'subcategorias' => []]);
    exit;
}

$where_conditions_servicos = [];
$params_servicos = [];
$types_servicos = '';

foreach ($tokens as $token) {
    $where_conditions_servicos[] = "(s.Titulo LIKE ? OR s.Descricao LIKE ?)";
    $param = "%" . $token . "%";
    array_push($params_servicos, $param, $param);
    $types_servicos .= 'ss';
}
$where_clause_servicos = implode(' AND ', $where_conditions_servicos);

$query_servicos = "
    SELECT 
        s.ID as id,
        s.Titulo as titulo,
        s.Descricao as descricao,
        sub.Titulo AS subcategoria,
        cat.Titulo AS categoria
    FROM servico s
    JOIN subcategoria sub ON s.ID_SubCategoria = sub.ID
    JOIN categoria cat ON sub.ID_Categoria = cat.ID
    WHERE $where_clause_servicos
      AND s.status_ficha = 'publicado'
    ORDER BY s.ID DESC
    LIMIT 7
";

$stmt_servicos = $mysqli->prepare($query_servicos);
$stmt_servicos->bind_param($types_servicos, ...$params_servicos);
$stmt_servicos->execute();
$result_servicos = $stmt_servicos->get_result();
$servicos = $result_servicos->fetch_all(MYSQLI_ASSOC);

$termo_like = "%" . $termo . "%";
$stmt_subcat = $mysqli->prepare("SELECT ID as id, Titulo as titulo FROM subcategoria WHERE Titulo LIKE ? LIMIT 3");
$stmt_subcat->bind_param("s", $termo_like);
$stmt_subcat->execute();
$result_subcat = $stmt_subcat->get_result();
$subcategorias = $result_subcat->fetch_all(MYSQLI_ASSOC);

$resposta = [
    'servicos' => $servicos,
    'subcategorias' => $subcategorias
];

echo json_encode($resposta);
?>
