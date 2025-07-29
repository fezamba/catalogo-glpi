<?php
session_start();

require_once 'glpi_api.php';
require_once '../conexao.php';

if ($mysqli->connect_errno) {
    die("Erro ao conectar: " . $mysqli->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_POST['servico_id']) || !isset($_POST['descricao_chamado'])) {
        header("Location: ../index.php?erro=DadosInvalidos");
        exit;
    }

    $servico_id = intval($_POST['servico_id']);
    $descricao_usuario = trim($_POST['descricao_chamado']);

    $redirect_url = "../view_servico.php?id=" . $servico_id;

    $stmt = $mysqli->prepare("SELECT Titulo FROM servico WHERE ID = ?");
    $stmt->bind_param("i", $servico_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $servico = $result->fetch_assoc();
    $stmt->close();

    if (!$servico) {
        header("Location: ../index.php?erro=ServicoNaoEncontrado");
        exit;
    }

    $titulo = "Solicitação de Serviço: " . $servico['Titulo'];
    $descricao_completa = "Descrição do usuário: \n\n" . $descricao_usuario;

    // --- Configurações da API do GLPI ---
    $glpi_api_url = "http://prhel8glpi002v.sefnet.rj/glpi/apirest.php/";
    $app_token = "D50oijbJcf6RRgv5k7MHlPsAtm7HdjArqdFR0Nie";
    $user_token = "0HzCAomqUDw6c9AGMGtgjax5ffmfooVmWvEJOQ2b";
    
    $requesttype_id = 1;
    $entity_id = 0;
    $user_id = $_SESSION['glpi_user_id'] ?? 2;

    // Inicializa a variável de erro e chama a função com 4 argumentos.
    $error_details = '';
    $session_token = iniciarSessaoGLPI($glpi_api_url, $app_token, $user_token, $error_details);

    if (!$session_token) {
        $error_message = "<h1>Erro Crítico</h1><p>Não foi possível iniciar a sessão com a API do GLPI.</p>";
        if (!empty($error_details)) {
            $error_message .= "<h3>Detalhes do Erro:</h3><pre>" . htmlspecialchars($error_details) . "</pre>";
        }
        $error_message .= "<p>Verifique se a URL da API, o App-Token e o User-Token estão corretos e se o servidor PHP consegue alcançar o servidor do GLPI.</p>";
        die($error_message);
    }

    $resposta = criarChamadoGLPI($glpi_api_url, $session_token, $app_token, $titulo, $descricao_completa, $requesttype_id, $user_id, $entity_id);

    if (isset($resposta['id']) && is_numeric($resposta['id'])) {
        header("Location: " . $redirect_url . "&sucesso=1&chamado_id={$resposta['id']}");
        exit;
    } else {
        echo "<!DOCTYPE html><html><head><title>Erro da API do GLPI</title><style>body{font-family: sans-serif; padding: 20px;} h1{color: #c00;} pre{background-color: #f0f0f0; padding: 15px; border: 1px solid #ccc; border-radius: 5px;}</style></head><body>";
        echo "<h1>Erro ao Criar Chamado no GLPI</h1>";
        echo "<p>A API do GLPI retornou uma resposta inesperada. Veja os detalhes abaixo:</p>";
        echo "<pre>";
        print_r($resposta);
        echo "</pre>";
        echo "<p><a href='" . htmlspecialchars($redirect_url) . "'>Voltar para a página do serviço</a></p>";
        echo "</body></html>";
        exit;
    }
} else {
    header("Location: ../index.php");
    exit;
}
?>
