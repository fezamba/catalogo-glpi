<?php
//$mysqli = new mysqli("localhost", "root", "sefazfer123@", "catalogo-teste");
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
  <style>
    .subcards-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 20px;
      margin-top: 25px;
    }

    .subcard {
      display: block;
      background-color: #ffffff;
      border: 1px solid #e0e6ed;
      border-radius: 8px;
      padding: 20px;
      text-decoration: none;
      color: inherit;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
      transition: all 0.2s ease-in-out;
    }

    .subcard:hover {
      transform: translateY(-5px);
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1);
      border-color: #f9b000;
    }

    .subcard h3 {
      margin-top: 0;
      margin-bottom: 10px;
      font-size: 1.3rem;
      color: #333;
    }

    .subcard p {
      margin: 0;
      font-size: 0.95rem;
      color: #667;
      display: -webkit-box;
      -webkit-line-clamp: 3;
      line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
      text-overflow: ellipsis;
    }
  </style>
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