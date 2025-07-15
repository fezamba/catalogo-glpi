<?php
session_start();
require_once 'glpi_api.php';
require_once '../conexao.php';

if ($mysqli->connect_errno) {
    die("Erro ao conectar: " . $mysqli->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_POST['servico_id']) || !isset($_POST['descricao_chamado'])) {
        header("Location: index.php?erro=DadosInvalidos");
        exit;
    }

    $servico_id = intval($_POST['servico_id']);
    $descricao_usuario = trim($_POST['descricao_chamado']);

    $stmt = $mysqli->prepare("SELECT Titulo FROM servico WHERE ID = ?");
    $stmt->bind_param("i", $servico_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $servico = $result->fetch_assoc();
    $stmt->close();

    if (!$servico) {
        header("Location: index.php?erro=ServicoNaoEncontrado");
        exit;
    }

    $titulo = "Solicitação de Serviço: " . $servico['Titulo'];
    $descricao_completa = "Descrição do usuário: \n\n" . $descricao_usuario;

    $glpi_api_url = "http://servicedesk.fazenda.rj.gov.br/glpi/apirest.php"; // URL da API do GLPI (essa é a de PROD - acho)
    $app_token = "SEU_APP_TOKEN"; 
    $user_token = "SEU_USER_TOKEN"; // Tente usar o token do Service Desk em PROD, não o 'seu'
    
    $requesttype_id = 1; 
    $entity_id = 0;
    
    $user_id = $_SESSION['glpi_user_id'] ?? 2;

    $session_token = iniciarSessaoGLPI($glpi_api_url, $app_token, $user_token);

    if (!$session_token) {
        header("Location: index.php?erro=FalhaSessaoGLPI");
        exit;
    }

    $resposta = criarChamadoGLPI($glpi_api_url, $session_token, $app_token, $titulo, $descricao_completa, $requesttype_id, $user_id, $entity_id);

    if (isset($resposta['id'])) {
        header("Location: index.php?sucesso=1&chamado_id={$resposta['id']}");
        exit;
    } else {
        $mensagem_erro = urlencode($resposta['message'] ?? 'CriacaoFalhou');
        header("Location: index.php?erro=$mensagem_erro");
        exit;
    }
} else {
    header("Location: index.php");
    exit;
}
?>
