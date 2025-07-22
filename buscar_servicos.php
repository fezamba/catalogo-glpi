<?php
// Inclui o arquivo de conexão com o banco de dados.
require_once 'conexao.php';
// Define o cabeçalho da resposta como JSON, indicando que o output será nesse formato.
header('Content-Type: application/json');

// --- Processamento do Termo de Busca ---
// Obtém o parâmetro 'termo' da URL, removendo espaços em branco do início e do fim.
$termo = trim($_GET['termo'] ?? '');
// Se o termo tiver menos de 2 caracteres, retorna um JSON vazio e encerra o script para evitar buscas muito amplas.
if (strlen($termo) < 2) {
    echo json_encode([]);
    exit;
}

// --- Tokenização do Termo de Busca ---
// Divide o termo de busca em palavras (tokens) usando o espaço como delimitador.
// `array_filter` remove quaisquer elementos vazios que possam surgir de múltiplos espaços.
$tokens = array_filter(explode(' ', $termo));
// Se não houver tokens válidos, retorna um JSON vazio.
if (empty($tokens)) {
    echo json_encode([]);
    exit;
}

// --- Construção Dinâmica da Query ---
$where_conditions = []; // Array para armazenar as condições WHERE para cada token.
$params = []; // Array para armazenar os parâmetros que serão vinculados à query.
$types = ''; // String para armazenar os tipos dos parâmetros (ex: 'ssss').

// Itera sobre cada palavra (token) do termo de busca.
foreach ($tokens as $token) {
    // Para cada token, cria uma condição que busca a palavra em 4 colunas diferentes.
    $where_conditions[] = "(s.Titulo LIKE ? OR s.Descricao LIKE ? OR sub.Titulo LIKE ? OR cat.Titulo LIKE ?)";
    
    // Prepara o parâmetro para a cláusula LIKE, adicionando '%' para buscar em qualquer parte do texto.
    $param = "%" . $token . "%";
    // Adiciona o parâmetro 4 vezes ao array de parâmetros (uma para cada '?').
    array_push($params, $param, $param, $param, $param);
    // Adiciona os tipos correspondentes à string de tipos. 's' para string.
    $types .= 'ssss';
}

// Junta todas as condições com 'AND', garantindo que todas as palavras do termo de busca estejam presentes nos resultados.
$where_clause = implode(' AND ', $where_conditions);

// --- Query SQL Final ---
$query = "
    SELECT 
        s.ID as id,
        s.Titulo as titulo,
        s.Descricao as descricao,
        s.UltimaAtualizacao as ultima_atualizacao,
        s.status_ficha,
        s.codigo_ficha,
        s.versao,
        sub.Titulo AS subcategoria,
        cat.Titulo AS categoria
    FROM servico s
    JOIN subcategoria sub ON s.ID_SubCategoria = sub.ID
    JOIN categoria cat ON sub.ID_Categoria = cat.ID
    WHERE $where_clause
    ORDER BY s.ID DESC
    LIMIT 25
";

// Prepara a query para execução segura.
$stmt = $mysqli->prepare($query);
if ($stmt) {
    // Vincula os parâmetros (tokens de busca) à query preparada.
    // O operador '...' (splat) expande o array de parâmetros.
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    // Pega todos os resultados e os coloca em um array associativo.
    $servicos = $result->fetch_all(MYSQLI_ASSOC);
    // Codifica o array de resultados em formato JSON e o envia como resposta.
    echo json_encode($servicos);
} else {
    // Se houver um erro na preparação da query, retorna um JSON vazio.
    echo json_encode([]);
}
?>