<?php
//$mysqli = new mysqli("localhost", "root", "sefazfer123@", "catalogo-teste");
require_once '../../conexao.php';
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
    $likes[] = "(s.Titulo LIKE '%$t%' OR s.Descricao LIKE '%$t%' OR c.Titulo LIKE '%$t%')";
}
$where = implode(' AND ', $likes);

$query = "
    SELECT 
        s.ID,
        s.Titulo,
        s.Descricao,
        s.UltimaAtualizacao,
        s.ID_Categoria,
        c.Titulo AS categoria_nome,
        (
            SELECT COUNT(*) 
            FROM servico 
            WHERE ID_Subcategoria = s.ID
        ) AS qtd_servicos
    FROM subcategoria s
    LEFT JOIN categoria c ON s.ID_Categoria = c.ID
    WHERE $where
    ORDER BY s.ID DESC
";

$res = $mysqli->query($query);

$subcategorias = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $subcategorias[] = $row;
    }
}

echo json_encode($subcategorias);
