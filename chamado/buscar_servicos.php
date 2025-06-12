<?php
header('Content-Type: application/json');

//$mysqli = new mysqli("localhost", "root", "sefazfer123@", "catalogo-teste");
require_once '../conexao.php';
if ($mysqli->connect_errno) {
    echo json_encode(['error' => 'Erro de conexão']);
    exit;
}

// Pega e sanitiza o termo de busca
$termo = trim($_GET['termo'] ?? '');
if (strlen($termo) < 2) {
    echo json_encode([]);
    exit;
}

// Quebra o termo em palavras individuais (tokens)
$tokens = array_filter(explode(' ', $termo));
if (empty($tokens)) {
    echo json_encode([]);
    exit;
}

// Constrói a consulta SQL segura e com busca ampla
$whereConditions = [];
$params = [];
$types = '';

foreach ($tokens as $token) {
    // A condição de busca
    $whereConditions[] = "(s.Titulo LIKE ? OR s.Descricao LIKE ? OR cat.Titulo LIKE ?)";
    $param = "%" . $token . "%";
    array_push($params, $param, $param, $param);
    $types .= 'sss';
}

// Junta todas as condições com 'AND'
$whereClause = implode(' AND ', $whereConditions);

// A consulta agora busca em todos os serviços, sem filtro de status
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
    // Vincula os parâmetros dinamicamente
    $stmt->bind_param($types, ...$params);
    
    $stmt->execute();
    $result = $stmt->get_result();
    // Renomeia as chaves para corresponder ao seu JS original
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
