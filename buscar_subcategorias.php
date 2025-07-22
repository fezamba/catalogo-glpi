<?php
// Inclui o arquivo de conexão com o banco de dados.
require_once 'conexao.php';
// Define o cabeçalho da resposta como JSON, indicando que o output será nesse formato.
header('Content-Type: application/json');

// --- Processamento do Termo de Busca ---
// Obtém o parâmetro 'termo' da URL, removendo espaços em branco do início e do fim.
$termo = trim($_GET['termo'] ?? '');
// Se o termo tiver menos de 2 caracteres, retorna um JSON vazio e encerra o script.
if (strlen($termo) < 2) {
    echo json_encode([]);
    exit;
}

// --- Tokenização do Termo de Busca ---
// Divide o termo de busca em palavras (tokens) usando o espaço como delimitador.
$tokens = array_filter(explode(' ', $termo));
// Se não houver tokens válidos, retorna um JSON vazio.
if (empty($tokens)) {
    echo json_encode([]);
    exit;
}

// --- Construção Dinâmica da Cláusula WHERE ---
$likes = [];
// Itera sobre cada palavra (token) do termo de busca.
foreach ($tokens as $token) {
    // Escapa o token para prevenir SQL Injection básico.
    // NOTA DE SEGURANÇA: Embora real_escape_string ajude, o uso de Prepared Statements
    // (como no arquivo buscar_servicos.php) é a prática mais recomendada e segura.
    $t = $mysqli->real_escape_string($token);
    // Cria uma condição LIKE para buscar o token no título/descrição da subcategoria ou no título da categoria pai.
    $likes[] = "(s.Titulo LIKE '%$t%' OR s.Descricao LIKE '%$t%' OR c.Titulo LIKE '%$t%')";
}
// Junta todas as condições com 'AND', garantindo que todas as palavras do termo de busca estejam presentes.
$where = implode(' AND ', $likes);

// --- Query SQL Final ---
$query = "
    SELECT 
        s.ID,
        s.Titulo,
        s.Descricao,
        s.UltimaAtualizacao,
        s.ID_Categoria,
        c.Titulo AS categoria_nome,
        -- NOTA DE PERFORMANCE: Esta subconsulta é executada para cada linha retornada,
        -- o que pode impactar o desempenho. Uma alternativa seria usar um JOIN com GROUP BY.
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

// Executa a query.
$res = $mysqli->query($query);

// --- Processamento e Retorno dos Resultados ---
$subcategorias = [];
if ($res) {
    // Itera sobre os resultados e os armazena em um array.
    while ($row = $res->fetch_assoc()) {
        $subcategorias[] = $row;
    }
}

// Codifica o array de resultados em formato JSON e o envia como resposta.
echo json_encode($subcategorias);
