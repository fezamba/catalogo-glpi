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

$likes = [];
foreach ($tokens as $token) {
    $t = $mysqli->real_escape_string($token);
    $likes[] = "(s.Titulo LIKE '%$t%' OR sub.Titulo LIKE '%$t%' OR cat.Titulo LIKE '%$t%')";
}
$where = implode(' AND ', $likes);

$query = "
    SELECT 
        s.ID,
        s.Titulo,
        s.Descricao,
        s.ID_SubCategoria,
        sub.ID_Categoria AS ID_Categoria,
        sub.Titulo AS subcategoria,
        cat.Titulo AS categoria
    FROM servico s
    JOIN subcategoria sub ON s.ID_SubCategoria = sub.ID
    JOIN categoria cat ON sub.ID_Categoria = cat.ID
    WHERE
        ($where)
    AND s.status_ficha = 'publicado'
    LIMIT 10
";

$res = $mysqli->query($query);

$servicos = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $servicos[] = [
            'id' => $row['ID'],
            'titulo' => $row['Titulo'],
            'descricao' => $row['Descricao'],
            'id_subcategoria' => $row['ID_SubCategoria'],
            'subcategoria' => $row['subcategoria'],
            'id_categoria' => $row['ID_Categoria'],
            'categoria' => $row['categoria']
        ];
    }
}

echo json_encode($servicos);
