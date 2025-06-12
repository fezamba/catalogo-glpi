<?php
// Define o cabeçalho como JSON
header('Content-Type: application/json');

// Conecta ao banco de dados
$mysqli = new mysqli("localhost", "root", "sefazfer123@", "catalogo-teste");
if ($mysqli->connect_errno) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao conectar com o banco de dados']);
    exit;
}

// Pega o ID da subcategoria da URL de forma segura
$subcategoria_id = intval($_GET['subcategoria_id'] ?? 0);

if ($subcategoria_id <= 0) {
    echo json_encode([]);
    exit;
}

// --- PREPARED STATEMENT (A FORMA SEGURA) ---

// 1. Prepara a consulta SEM o filtro de status
$stmt = $mysqli->prepare("
    SELECT ID, Titulo, Descricao 
    FROM servico 
    WHERE ID_SubCategoria = ?
    ORDER BY Titulo ASC
");

// 2. Vincula a variável $subcategoria_id ao placeholder ("i" para inteiro)
$stmt->bind_param("i", $subcategoria_id);

// 3. Executa a consulta
$stmt->execute();

// 4. Pega os resultados
$result = $stmt->get_result();

// 5. Monta o array de resultados
$servicos = $result->fetch_all(MYSQLI_ASSOC);

// 6. Fecha o statement
$stmt->close();

// Retorna os resultados em formato JSON
echo json_encode($servicos);
?>