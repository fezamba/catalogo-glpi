<?php
require_once 'conexao.php';
if ($mysqli->connect_errno) {
  die("Erro: " . $mysqli->connect_error);
}

$id_categoria = $_GET['id'] ?? 0;

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

$stmt = $mysqli->prepare("SELECT Titulo, Descricao FROM categoria WHERE ID = ?");
$stmt->bind_param("i", $id_categoria);
$stmt->execute();
$stmt->bind_result($tituloCategoria, $descricaoCategoria);
$stmt->fetch();
$stmt->close();

$subcategorias = [];
$result = $mysqli->query("SELECT * FROM subcategoria WHERE ID_Categoria = {$id_categoria}");
while ($sub = $result->fetch_assoc()) {
  $subcategorias[] = $sub;
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="categoria.css" />
  <title><?php echo htmlspecialchars($tituloCategoria); ?></title>
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
      <h1><?php echo htmlspecialchars($tituloCategoria); ?></h1>
      <p><?php echo htmlspecialchars($descricaoCategoria); ?></p>

      <div class="subcards-grid">
        <?php foreach ($subcategorias as $sub): ?>
          <a href="subcategoria.php?id=<?php echo $sub['ID']; ?>" class="subcard">
            <h3><?php echo htmlspecialchars($sub['Titulo']); ?></h3>
            <p><?php echo htmlspecialchars($sub['Descricao']); ?></p>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  </div>

  <script src="script.js"></script>
</body>

</html>