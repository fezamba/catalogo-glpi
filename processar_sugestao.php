<?php
session_start();
require_once 'conexao.php'; // Verifique se o caminho para a conexão está correto

if ($mysqli->connect_errno) {
    die("Erro ao conectar: " . $mysqli->connect_error);
}

// 1. Verifica se o formulário foi enviado pelo método POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 2. Valida se os campos necessários foram enviados
    if (!isset($_POST['servico_id']) || !isset($_POST['texto_sugestao'])) {
        // Se os dados estiverem em falta, redireciona com um erro genérico
        header("Location: index.php?erro=dados_invalidos");
        exit;
    }

    // 3. Obtém e limpa os dados do formulário
    $servico_id = intval($_POST['servico_id']);
    $texto_sugestao = trim($_POST['texto_sugestao']);
    
    // Obtém o nome do autor da sugestão a partir da sessão do usuário logado
    $autor_sugestao = $_SESSION['username'] ?? 'Usuário Anônimo';

    // 4. Valida se os dados são válidos
    if (empty($texto_sugestao) || $servico_id <= 0) {
        // Se a sugestão estiver vazia ou o ID do serviço for inválido, redireciona com um erro
        header("Location: view_servico.php?id=$servico_id&sugestao=erro_dados");
        exit;
    }

    // 5. Prepara e executa a inserção no banco de dados
    $stmt = $mysqli->prepare("INSERT INTO sugestoes (servico_id, texto_sugestao, autor_sugestao) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $servico_id, $texto_sugestao, $autor_sugestao);

    if ($stmt->execute()) {
        // 6. Se a inserção for bem-sucedida, redireciona com uma mensagem de sucesso
        header("Location: view_servico.php?id=$servico_id&sugestao=sucesso");
        exit;
    } else {
        // 7. Se ocorrer um erro no banco de dados, redireciona com uma mensagem de erro
        header("Location: view_servico.php?id=$servico_id&sugestao=erro_db");
        exit;
    }
    $stmt->close();

} else {
    // Se alguém tentar aceder a este arquivo diretamente, redireciona para a página inicial
    header("Location: index.php");
    exit;
}
?>