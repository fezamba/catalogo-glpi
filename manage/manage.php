<?php
// Inclui o arquivo de conexão com o banco de dados.
require_once '../conexao.php';

// Verifica se a conexão com o banco de dados foi bem-sucedida.
if ($mysqli->connect_errno) {
  die("Erro: " . $mysqli->connect_error);
}

// --- Busca de Dados ---
$categorias = [];
// Busca todas as categorias principais.
$result = $mysqli->query("SELECT * FROM categoria");
// Itera sobre cada categoria encontrada.
while ($cat = $result->fetch_assoc()) {
  $cat['subcategorias'] = [];
  // NOTA DE PERFORMANCE: A execução de uma query dentro de um loop (N+1 Query Problem)
  // pode impactar o desempenho se houver muitas categorias.
  // Para esta página de menu, o impacto é provavelmente baixo, mas é uma boa prática a ser observada.
  $subres = $mysqli->query("SELECT * FROM subcategoria WHERE ID_Categoria = {$cat['ID']}");
  // Itera sobre as subcategorias encontradas para a categoria atual e as adiciona a um array.
  while ($sub = $subres->fetch_assoc()) {
    $cat['subcategorias'][] = $sub;
  }
  // Adiciona a categoria completa (com suas subcategorias) ao array final.
  $categorias[] = $cat;
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="../css/manage.css"/>
  <title>Gerenciamento do Catálogo de Serviços</title>
</head>

<body>

  <div class="layout">
    <!-- A barra lateral (sidebar) contém links de navegação principais. -->
    <aside class="sidebar">
      <div class="menu-item">
        <button class="menu-button" onclick="window.location.href='../index.php'">
          ← Voltar ao Catálogo
        </button>
      </div>
    </aside>

    <!-- A seção de conteúdo principal onde os cartões de gerenciamento são exibidos. -->
    <section class="content">
      <h1>Gerenciamento do Catálogo de Serviços</h1>
      <p>Área criada para o gerenciamento do Catálogo de Serviços</p>

      <!-- Grid para organizar os cartões de navegação. -->
      <div class="cards-grid">
        <!-- Cada 'card-button' é um link clicável para uma página de gerenciamento específica. -->
        <div class="card-button" onclick="window.location.href='list/manage_listcategoria.php'">
          <div class="card-header">
            <h3>Categorias</h3>
            <img src="../img/edit.png" alt="Categorias">
          </div>
          <p>Gerencie as categorias principais do catálogo.</p>
        </div>

        <div class="card-button" onclick="window.location.href='list/manage_listsubcat.php'">
          <div class="card-header">
            <h3>SubCategorias</h3>
            <img src="../img/edit.png" alt="SubCategorias">
          </div>
          <p>Gerencie as subcategorias ligadas às categorias.</p>
        </div>

        <div class="card-button" onclick="window.location.href='list/manage_listservico.php'">
          <div class="card-header">
            <h3>Serviços</h3>
            <img src="../img/edit.png" alt="Serviços">
          </div>
          <p>Gerencie os serviços associados às subcategorias.</p>
        </div>
        <div class="card-button" onclick="window.location.href='pos/manage_pos.php'">
          <div class="card-header">
            <h3>Product Owners (POs)</h3>
            <img src="../img/edit.png" alt="Product Owners">
          </div>
          <p>Gerencie os Product Owners e seus dados de contato.</p>
        </div>
        <div class="card-button" onclick="window.location.href='revisores/manage_rev.php'">
          <div class="card-header">
            <h3>Revisores</h3>
            <img src="../img/edit.png" alt="Revisores">
          </div>
          <p>Gerencie os Revisores e seus dados de contato.</p>
        </div>
      </div>
    </section>
  </div>

</body>

</html>
