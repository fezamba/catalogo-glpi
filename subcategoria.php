<?php
require_once 'conexao.php';

if ($mysqli->connect_errno) {
  die("Erro: " . $mysqli->connect_error);
}

$id_subcategoria = $_GET['id'] ?? 0;

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

$stmt = $mysqli->prepare("SELECT Titulo, Descricao FROM subcategoria WHERE ID = ?");
$stmt->bind_param("i", $id_subcategoria);
$stmt->execute();
$stmt->bind_result($subtitulo, $subdescricao);
$stmt->fetch();
$stmt->close();

$servicos = [];
$stmt = $mysqli->prepare("SELECT ID, Titulo, Descricao FROM servico WHERE ID_SubCategoria = ? AND status_ficha = 'publicado'");
$stmt->bind_param("i", $id_subcategoria);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
  $servicos[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="subcategoria.css" />
  <title>Subcategoria</title>
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
    </aside>

    <section class="content">
      <h1><?php echo htmlspecialchars($subtitulo); ?></h1>
      <p><?php echo htmlspecialchars($subdescricao); ?></p>

      <div class="cards-list-sub">
        <?php foreach ($servicos as $serv): ?>
          <a href="view_servico.php?id=<?php echo $serv['ID']; ?>" class="service-card">
            <h3><?php echo htmlspecialchars($serv['Titulo']); ?></h3>
            <p class="card-description"><?php echo htmlspecialchars($serv['Descricao']); ?></p>
            <?php if (!empty($serv['KBs'])): ?>
              <span class="card-kb-link">Ver KB</span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  </div>

  <script src="script.js"></script>
</body>

</html>