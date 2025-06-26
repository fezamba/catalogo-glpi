<?php
require_once 'conexao.php';

if ($mysqli->connect_errno) {
  die("Erro: " . $mysqli->connect_error);
}

$categorias = [];
$result = $mysqli->query("SELECT * FROM categoria");

while ($cat = $result->fetch_assoc()) {
  $cat['subcategorias'] = [];

  $subres = $mysqli->query("SELECT * FROM subcategoria WHERE ID_Categoria = {$cat['ID']}");
  while ($sub = $subres->fetch_assoc()) {
    $cat['subcategorias'][] = $sub;
  }

  $categorias[] = $cat;
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="index.css" />
  <title>Catálogo de Serviços</title>
</head>

<body>

  <div class="layout">
    <aside class="sidebar">
      <div class="menu-item">
        <button class="menu-button accordion-toggle" onclick="window.location.href='index.php'">
          Todas Categorias <span class="badge"><?php echo count($categorias); ?></span>
        </button>
      </div>

      <?php foreach ($categorias as $cat): ?>
        <div class="menu-item">
          <button class="menu-button accordion-toggle">
            <?php echo htmlspecialchars($cat['Titulo']); ?>
            <span class="badge"><?php echo count($cat['subcategorias']); ?></span>
          </button>
          <?php if (count($cat['subcategorias'])): ?>
            <div class="submenu">
              <?php foreach ($cat['subcategorias'] as $sub): ?>
                <a href="subcategoria.php?id=<?php echo $sub['ID']; ?>">
                  <?php echo htmlspecialchars($sub['Titulo']); ?>
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
      <!-- Botão Edição -->
      <div class="menu-item fixed-bottom">
        <button class="menu-button" style="margin-bottom: 10px;" onclick="window.location.href='/chatbot/chatbot.php'">
          Assistente Virtual <img src="img/chat.png" alt="Assistente-virtual" class="icon-left2">
        </button>
        <!-- Botão Edição -->
        <button class="menu-button" onclick="window.location.href='/manage/manage.php'">
          Edição <img src="img/edit.png" alt="Editar-catalogo" class="icon-left">
        </button>
      </div>
    </aside>

    <!-- CONTEÚDO PRINCIPAL -->
    <section class="content">
      <h1>Catálogo de Serviços - Todas as Categorias</h1>
      <p style="margin-bottom: 15px">Para te ajudar com mais agilidade e praticidade, disponibilizamos este catálogo com os principais serviços de TI oferecidos:</p>
      <div style="margin-bottom: 20px;">
        <input type="text" id="busca-global" placeholder="Que serviço você está procurando?"
          style="display: block; width: 100%; max-width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc; font-size: 14px; box-sizing: border-box;">
        <div id="resultados-busca" style="background: white; border: 1px solid #ddd; border-radius: 6px; text-align: left; display: none;"></div>
      </div>

      <div class="cards-grid">
        <?php foreach ($categorias as $cat): ?>
          <div class="card-button" onclick="window.location.href='categoria.php?id=<?php echo $cat['ID']; ?>'">
            <div class="card-header">
              <h3><?php echo htmlspecialchars($cat['Titulo']); ?></h3>
              <img src="img/<?php echo strtolower(str_replace(' ', '_', $cat['Titulo'])); ?>.png" alt="<?php echo htmlspecialchars($cat['Titulo']); ?>">
            </div>
            <p><?php echo htmlspecialchars($cat['Descricao']); ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  </div>

  <script src="script.js"></script>
</body>

</html>
