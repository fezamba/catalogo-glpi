<?php
$mysqli = new mysqli("localhost", "root", "sefazfer123@", "catalogo-teste");

if ($mysqli->connect_errno) {
  die("Erro: " . $mysqli->connect_error);
}

$id_subcategoria = $_GET['id'] ?? 0;

// --- Carrega categorias e subcategorias (sidebar) ---
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

// --- Carrega Subcategoria Selecionada ---
$stmt = $mysqli->prepare("SELECT Titulo, Descricao FROM subcategoria WHERE ID = ?");
$stmt->bind_param("i", $id_subcategoria);
$stmt->execute();
$stmt->bind_result($subtitulo, $subdescricao);
$stmt->fetch();
$stmt->close();

// --- Carrega Serviços Relacionados ---
$servicos = [];
$stmt = $mysqli->prepare("SELECT ID, Titulo, Descricao FROM servico WHERE ID_SubCategoria = ?");
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
  <style>

    /* Define a grade que organiza os cards */
    .cards-list-sub {
      display: grid;
      /* Cria colunas que se ajustam, com um mínimo de 300px */
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 20px;
      /* Espaçamento entre os cards */
      margin-top: 25px;
    }

    /* O estilo principal do card (que é um link <a>) */
    .service-card {
      display: block;
      /* Faz o link ocupar todo o espaço do card */
      background-color: #ffffff;
      border: 1px solid #e0e6ed;
      /* Borda sutil */
      border-radius: 8px;
      /* Cantos arredondados */
      padding: 20px;
      text-decoration: none;
      /* Remove o sublinhado padrão do link */
      color: inherit;
      /* Faz o texto herdar a cor padrão, em vez de ficar azul */
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
      /* Sombra bem leve */
      transition: all 0.2s ease-in-out;
      /* Animação suave para todas as propriedades */
    }

    /* O efeito "hoverzinho" quando o mouse passa por cima */
    .service-card:hover {
      transform: translateY(-4px);
      /* Levanta o card um pouquinho */
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      /* Aumenta a sombra para dar profundidade */
      border-color: #f9b000;
      /* Destaca a borda com sua cor principal */
    }

    /* Estilo para o título dentro do card */
    .service-card h3 {
      margin-top: 0;
      margin-bottom: 8px;
      font-size: 1.2rem;
      /* Tamanho do título */
      color: #333;
    }

    /* Estilo para a descrição dentro do card */
    .service-card .card-description {
      margin: 0;
      font-size: 0.9rem;
      color: #667;
      /* Limita a descrição a 3 linhas para manter os cards uniformes */
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
    <!-- SIDEBAR -->
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

    <!-- CONTEÚDO PRINCIPAL -->
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