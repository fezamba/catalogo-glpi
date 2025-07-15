<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function fetch_context_data() {
    $url = 'https://catalogo-glpi-production.up.railway.app/gerar_relatorio.php';
    
    header("Content-Type: text/plain; charset=utf-8");

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $data = curl_exec($ch);
    $error = curl_error($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error || $httpcode >= 400) {
        http_response_code(500);
        echo "Erro ao buscar dados do catálogo.";
    } else {
        echo $data;
    }
    exit;
}

// O ideal é usar uma I.A. local e não uma API externa, estudar como fazer isso. Por enquanto, vamos usar a API do Gemini.
// Provavelmente será necessário criar uma conta no Google Cloud e ativar a API do Gemini, além de configurar as credenciais adequadamente. (Vou revogar a chave atual)
function call_gemini_api() {
    $apiKey = "AIzaSyD5nmuNijtSJVpHe28ztDAXtMQBTkNxyNQ";

    $requestBody = file_get_contents('php://input');
    $data = json_decode($requestBody, true);

    $geminiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $apiKey;

    $ch = curl_init($geminiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    header('Content-Type: application/json');
    http_response_code($httpcode);
    echo $response;
    exit;
}

if (isset($_GET['action'])) {
    if ($_GET['action'] === 'fetch_context') {
        fetch_context_data();
    } elseif ($_GET['action'] === 'get_response') {
        call_gemini_api();
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Chatbot do Catálogo</title>
  <link rel="stylesheet" href="css/chatbot.css">
</head>
<body>
  <a href="../index.php" class="botao-voltar">← Voltar ao Catálogo</a>
  
  <div class="page-wrapper">
    <div class="container">
      <h2 class="titulo">Assistente Virtual</h2>
      <div id="chatbot-container">
        <div id="chatbot-output"></div>
        <form id="chatbot-form">
          <input type="text" id="chatbot-input" placeholder="Digite sua dúvida..." required>
          <button type="submit" class="botao">Enviar</button>
        </form>
      </div>
    </div>
  </div>

  <script src="js/chatbot.js"></script>
</body>
</html>
