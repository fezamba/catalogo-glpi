<?php
// Define o cabeçalho como JSON desde o início
header('Content-Type: application/json');

// Conecta ao banco de dados
//$mysqli = new mysqli("localhost", "root", "sefazfer123@", "catalogo-teste");
require_once '../conexao.php';
if ($mysqli->connect_errno) {
    // Em caso de erro de conexão, retorna um JSON de erro
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Erro ao conectar com o banco de dados']);
    exit;
}

// Pega o ID da categoria da URL de forma segura
$categoria_id = intval($_GET['categoria_id'] ?? 0);

// Se o ID for inválido ou não fornecido, retorna uma lista vazia
if ($categoria_id <= 0) {
    echo json_encode([]);
    exit;
}

// --- PREPARED STATEMENT (A FORMA SEGURA E CORRETA) ---

// 1. Prepara a consulta com um placeholder (?)
$stmt = $mysqli->prepare("SELECT ID, Titulo FROM subcategoria WHERE ID_Categoria = ? ORDER BY Titulo ASC");

// 2. Vincula a variável $categoria_id ao placeholder, especificando que é um inteiro ("i")
$stmt->bind_param("i", $categoria_id);

// 3. Executa a consulta
$stmt->execute();

// 4. Pega os resultados
$result = $stmt->get_result();

// 5. Monta o array de resultados
$subcategorias = $result->fetch_all(MYSQLI_ASSOC);

// 6. Fecha o statement
$stmt->close();

// Retorna os resultados em formato JSON
echo json_encode($subcategorias);
?>
