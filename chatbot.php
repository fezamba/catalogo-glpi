<?php
// Configurações de erro para ambiente de desenvolvimento.
// Mostra todos os erros na tela para facilitar a depuração.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Busca o conteúdo de texto do catálogo de serviços de uma URL externa.
 * Atua como um proxy para buscar a base de conhecimento para o chatbot.
 */
function fetch_context_data() {
    // URL do script que gera o relatório de texto com os dados do catálogo.
    $url = 'url-da-aplicação/gerar_relatorio.php'; // Substituir pela URL da aplicação /gerar_relatorio.php
    
    // Define o cabeçalho da resposta como texto puro com codificação UTF-8.
    header("Content-Type: text/plain; charset=utf-8");

    // Inicia uma sessão cURL.
    $ch = curl_init();
    // Configura as opções do cURL.
    curl_setopt($ch, CURLOPT_URL, $url); // URL a ser buscada.
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Retorna a resposta como uma string.
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Segue redirecionamentos.
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Desativa a verificação do certificado SSL (não recomendado em produção).
    
    // Executa a requisição e obtém os dados.
    $data = curl_exec($ch);
    $error = curl_error($ch); // Pega qualquer erro do cURL.
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Pega o código de status HTTP da resposta.
    curl_close($ch); // Fecha a sessão cURL.

    // Verifica se houve um erro na requisição cURL ou se o status HTTP indica um erro.
    if ($error || $httpcode >= 400) {
        http_response_code(500); // Define o código de resposta do nosso script como erro.
        echo "Erro ao buscar dados do catálogo.";
    } else {
        // Se tudo correu bem, envia os dados obtidos como resposta.
        echo $data;
    }
    exit; // Encerra o script.
}


/**
 * Atua como um proxy para a API do Google Gemini.
 * Recebe a requisição do frontend, adiciona a chave da API e a repassa para o Google.
 * Isso evita expor a chave da API no lado do cliente (JavaScript).
 */
function call_gemini_api() {
    // NOTA DE SEGURANÇA: A chave da API está visível no código. O ideal é armazená-la em uma variável de ambiente
    // ou em um arquivo de configuração seguro fora do diretório público da web para maior segurança.
    $apiKey = "chave-api-do-gemini"; //Substituir pela Chave da API do Gemini

    // Pega o corpo da requisição POST enviada pelo JavaScript.
    $requestBody = file_get_contents('php://input');
    // Decodifica o corpo JSON em um array associativo do PHP.
    $data = json_decode($requestBody, true);

    // URL do endpoint da API do Gemini.
    $geminiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $apiKey;

    // Inicia uma nova sessão cURL para a API do Gemini.
    $ch = curl_init($geminiUrl);
    // Configura as opções do cURL.
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Retorna a resposta como string.
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']); // Define o cabeçalho como JSON.
    curl_setopt($ch, CURLOPT_POST, true); // Define o método da requisição como POST.
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); // Anexa os dados do chatbot ao corpo da requisição.
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Desativa a verificação SSL.

    // Executa a requisição.
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Pega o código de status da resposta da API.
    curl_close($ch);
    
    // Repassa o cabeçalho e a resposta da API do Gemini diretamente para o frontend.
    header('Content-Type: application/json');
    http_response_code($httpcode);
    echo $response;
    exit; // Encerra o script.
}

// --- Roteador Principal ---
// Verifica se o parâmetro 'action' foi passado na URL.
if (isset($_GET['action'])) {
    // Se a ação for 'fetch_context', chama a função correspondente.
    if ($_GET['action'] === 'fetch_context') {
        fetch_context_data();
    // Se a ação for 'get_response', chama a função que faz a proxy para a API da IA.
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
        <!-- A div 'chatbot-output' será preenchida com as mensagens do chat via JavaScript. -->
        <div id="chatbot-output"></div>
        <!-- Formulário para o usuário digitar e enviar mensagens. -->
        <form id="chatbot-form">
          <input type="text" id="chatbot-input" placeholder="Digite sua dúvida..." required>
          <button type="submit" class="botao">Enviar</button>
        </form>
      </div>
    </div>
  </div>

  <!-- O arquivo JavaScript que controla toda a lógica do frontend do chatbot. -->
  <script src="js/chatbot.js"></script>
</body>
</html>
