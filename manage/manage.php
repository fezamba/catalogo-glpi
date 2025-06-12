<?php
//$mysqli = new mysqli("localhost", "root", "sefazfer123@", "catalogo-teste");
require_once '../conexao.php';

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
  <link rel="stylesheet" href="style4.css" />
  <title>Gerenciamento do Catálogo de Serviços</title>
</head>

<body>

  <div class="layout">
    <!-- SIDEBAR -->
    <aside class="sidebar">
      <div class="menu-item">
        <button class="menu-button" onclick="window.location.href='../index.php'">
          Todas Categorias <span class="badge"><?php echo count($categorias); ?></span>
        </button>
      </div>

      <?php foreach ($categorias as $cat): ?>
        <div class="menu-item">
          <button class="menu-button">
            <?php echo htmlspecialchars($cat['Titulo']); ?>
            <span class="badge"><?php echo count($cat['subcategorias']); ?></span>
          </button>
          <?php if (count($cat['subcategorias'])): ?>
            <div class="submenu">
              <?php foreach ($cat['subcategorias'] as $sub): ?>
                <a href="../index/subcategoria.php?id=<?php echo $sub['ID']; ?>">
                  <?php echo htmlspecialchars($sub['Titulo']); ?>
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </aside>

    <!-- CONTEÚDO PRINCIPAL -->
    <section class="content">
      <h1>Gerenciamento do Catálogo de Serviços</h1>
      <p>Área criada para o gerenciamento do Catálogo de Serviços</p>

      <div class="cards-grid">
        <div class="card-button" onclick="window.location.href='list/manage_listcategoria.php'">
          <div class="card-header">
            <h3>Categorias</h3>
            <img src="../index/img/edit.png" alt="Categorias">
          </div>
          <p>Gerencie as categorias principais do catálogo.</p>
        </div>

        <div class="card-button" onclick="window.location.href='list/manage_listsubcat.php'">
          <div class="card-header">
            <h3>SubCategorias</h3>
            <img src="../index/img/edit.png" alt="SubCategorias">
          </div>
          <p>Gerencie as subcategorias ligadas às categorias.</p>
        </div>

        <div class="card-button" onclick="window.location.href='list/manage_listservico.php'">
          <div class="card-header">
            <h3>Serviços</h3>
            <img src="../index/img/edit.png" alt="Serviços">
          </div>
          <p>Gerencie os serviços associados às subcategorias.</p>
        </div>
        <div class="card-button" onclick="window.location.href='pos/manage_pos.php'">
          <div class="card-header">
            <h3>Product Owners (POs)</h3>
            <img src="../index/img/edit.png" alt="Product Owners">
          </div>
          <p>Gerencie os Product Owners e seus dados de contato.</p>
        </div>
      </div>
    </section>
  </div>

</body>

</html>