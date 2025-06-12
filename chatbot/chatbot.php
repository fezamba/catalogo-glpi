<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <title>Chatbot do Catálogo</title>
  <link rel="stylesheet" href="chatbot.css">
</head>

<body>
  <?php
  // chatbot.php
  ?>
  <!DOCTYPE html>
  <html lang="pt-BR">

  <head>
    <meta charset="UTF-8">
    <title>Chatbot - Catálogo de Serviços</title>
    <link rel="stylesheet" href="chatbot.css">
  </head>

  <body>

    <div class="container">
      <h2 class="titulo">Assistente Virtual</h2>
      <div id="chatbot-container">
        <div id="chatbot-output"></div>
        <form id="chatbot-form">
          <input type="text" id="chatbot-input" placeholder="Digite sua dúvida..." required>
          <button type="submit" class="botao">Enviar</button>
        </form>
      </div>

      <div style="margin-top: 20px;">
        <a href="../index/index.php" class="botao-voltar">← Voltar ao Catálogo</a>
      </div>
    </div>

    <script src="chatbot.js"></script>
  </body>

  </html>