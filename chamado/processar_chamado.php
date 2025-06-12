<?php
require_once 'glpi_api.php';

$mysqli = new mysqli("localhost", "root", "sefazfer123@", "catalogo-teste");
if ($mysqli->connect_errno) {
    die("Erro ao conectar: " . $mysqli->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $servico_id = intval($_POST['servico']);

    $stmt = $mysqli->prepare("SELECT Titulo, Descricao FROM servico WHERE ID = ?");
    $stmt->bind_param("i", $servico_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $servico = $result->fetch_assoc();

    if (!$servico) {
        header("Location: abrir_chamado.php?erro=ServicoNaoEncontrado");
        exit;
    }

    $titulo = "Solicitação: " . $servico['Titulo'];
    $descricao = $servico['Descricao'];

    // Configurações GLPI
    $glpi_api_url = "http://localhost/glpi/apirest.php"; // CONFIGURAR ESSE TREM DEPOIS
    $app_token = "SEU_APP_TOKEN";
    $user_token = "SEU_USER_TOKEN";
    $requesttype_id = 1;
    $user_id = 2;
    $entity_id = 0;

    $session_token = iniciarSessaoGLPI($glpi_api_url, $app_token, $user_token);

    if (!$session_token) {
        header("Location: abrir_chamado.php?erro=FalhaSessaoGLPI");
        exit;
    }

    $resposta = criarChamadoGLPI($glpi_api_url, $session_token, $app_token, $titulo, $descricao, $requesttype_id, $user_id, $entity_id);

    if (isset($resposta['id'])) {
        header("Location: abrir_chamado.php?sucesso=1&id={$resposta['id']}");
        exit;
    } else {
        header("Location: abrir_chamado.php?erro=CriacaoFalhou");
        exit;
    }
}
