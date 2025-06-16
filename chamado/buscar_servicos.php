<?php
header('Content-Type: application/json');

//$mysqli = new mysqli("localhost", "root", "sefazfer123@", "catalogo-teste");
require_once '../conexao.php';
if ($mysqli->connect_errno) {
    echo json_encode(['error' => 'Erro de conexão']);
    exit;
}

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

$whereConditions = [];
$params = [];
$types = '';

foreach ($tokens as $token) {

    $whereConditions[] = "(s.Titulo LIKE ? OR s.Descricao LIKE ? OR cat.Titulo LIKE ?)";
    $param = "%" . $token . "%";
    array_push($params, $param, $param, $param);
    $types .= 'sss';
}


$whereClause = implode(' AND ', $whereConditions);

$query = "
    SELECT DISTINCT
        s.ID,
        s.Titulo,
        s.Descricao,
        s.ID_SubCategoria,
        sub.ID_Categoria,
        sub.Titulo as subcategoria,
        cat.Titulo as categoria
    FROM servico s
    JOIN subcategoria sub ON s.ID_SubCategoria = sub.ID
    JOIN categoria cat ON sub.ID_Categoria = cat.ID
    WHERE 
        ($whereClause) -- A condição de busca permanece
    -- A LINHA 'AND s.status_ficha = 'publicado'' FOI REMOVIDA DAQUI
    LIMIT 10
";

$stmt = $mysqli->prepare($query);

if ($stmt) {

    $stmt->bind_param($types, ...$params);
    
    $stmt->execute();
    $result = $stmt->get_result();

    $servicos = [];
    while ($row = $result->fetch_assoc()) {
        $servicos[] = [
            'id' => $row['ID'],
            'titulo' => $row['Titulo'],
            'descricao' => $row['Descricao'],
            'id_subcategoria' => $row['ID_SubCategoria'],
            'id_categoria' => $row['ID_Categoria'],
            'subcategoria' => $row['subcategoria'],
            'categoria' => $row['categoria']
        ];
    }
    $stmt->close();

    echo json_encode($servicos);
} else {
    echo json_encode(['error' => 'Erro na preparação da consulta']);
}
?>
