<?php
session_start(); // Garante que a sessão está ativa para ler o ID do usuário
require_once 'glpi_api.php';
require_once '../conexao.php';

if ($mysqli->connect_errno) {
    die("Erro ao conectar: " . $mysqli->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Garante que os dados do formulário foram enviados
    if (!isset($_POST['servico_id']) || !isset($_POST['descricao_chamado'])) {
        header("Location: index.php?erro=DadosInvalidos");
        exit;
    }

    $servico_id = intval($_POST['servico_id']);
    $descricao_usuario = trim($_POST['descricao_chamado']);

    // Busca os dados do serviço para usar no título do chamado
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

    // Define o título e a descrição para a API do GLPI
    $titulo = "Solicitação de Serviço: " . $servico['Titulo'];
    $descricao_completa = "Descrição do usuário: \n\n" . $descricao_usuario;

    // --- Configurações da API do GLPI ---
    $glpi_api_url = "http://servicedesk.fazenda.rj.gov.br/glpi/apirest.php";
    $app_token = "SEU_APP_TOKEN"; 
    $user_token = "SEU_USER_TOKEN"; 
    
    // IDs de exemplo, ajuste conforme a sua configuração do GLPI
    $requesttype_id = 1; 
    $entity_id = 0;
    
    // ALTERAÇÃO PARA PRODUÇÃO: O ID do usuário agora é pego da sessão.
    // O seu sistema de login deve guardar o ID do GLPI do usuário em $_SESSION['glpi_user_id']
    $user_id = $_SESSION['glpi_user_id'] ?? 2; // Usa 2 como fallback para testes

    // 1. Inicia a sessão na API do GLPI
    $session_token = iniciarSessaoGLPI($glpi_api_url, $app_token, $user_token);

    if (!$session_token) {
        header("Location: index.php?erro=FalhaSessaoGLPI");
        exit;
    }

    // 2. Cria o chamado no GLPI com os dados fornecidos
    $resposta = criarChamadoGLPI($glpi_api_url, $session_token, $app_token, $titulo, $descricao_completa, $requesttype_id, $user_id, $entity_id);

    // 3. Redireciona o usuário com base na resposta da API
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
