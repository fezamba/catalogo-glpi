<?php
if (isset($_GET['fetch_data']) && $_GET['fetch_data'] === 'true') {
    $url = 'https://catalogo-glpi-production.up.railway.app/gerar_relatorio.php';
    
    header("Access-Control-Allow-Origin: *");
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

    if ($error) {
        http_response_code(500);
        echo "cURL Error: " . $error;
    } elseif ($httpcode >= 400) {
        http_response_code($httpcode);
        echo "HTTP Error: " . $httpcode;
    }
    else {
        echo $data;
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <title>Chatbot do Catálogo</title>
  <link rel="stylesheet" href="chatbot.css">
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

  <script src="chatbot.js"></script>
</body>

</html>
