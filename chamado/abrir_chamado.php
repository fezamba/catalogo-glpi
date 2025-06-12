<?php
//$mysqli = new mysqli("localhost", "root", "sefazfer123@", "catalogo-teste");
require_once 'conexao.php';
if ($mysqli->connect_errno) {
  die("Erro ao conectar: " . $mysqli->connect_error);
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <title>Abrir Chamado</title>
  <link rel="stylesheet" href="chamado.css" />
</head>

<body>
  <div class="form-wrapper">
    <h2 class="form-title">Abrir Chamado</h2>
    <form method="post" action="processar_chamado.php">
      <div class="search-container">
        <input type="text" id="busca-global" placeholder="Pesquisar serviço...">
        <div id="resultados-busca"></div>
      </div>

      <div id="descricao-container" style="display: none; padding: 10px; background: #fffaf0; border-left: 4px solid #f9b000; border-radius: 6px; margin-bottom: 20px;">
        <strong>Descrição do Serviço:</strong>
        <p id="descricao-servico" style="margin-top: 5px;"></p>
      </div>
      <div class="form-grid">
        <div class="form-column">

          <!-- CATEGORIA -->
          <label for="categoria">Categoria
            <select id="categoria" name="categoria" required>
              <option value="">Selecione...</option>
              <?php
              $res = $mysqli->query("SELECT ID, Titulo FROM categoria");
              if ($res && $res->num_rows > 0) {
                while ($row = $res->fetch_assoc()) {
                  echo "<option value='{$row['ID']}'>{$row['Titulo']}</option>";
                }
              } else {
                echo "<option value=''>⚠ Nenhuma categoria encontrada</option>";
              }
              ?>
            </select>
          </label>
          <!-- SUBCATEGORIA -->
          <div id="subcategoria-wrapper" style="display: none;">
            <label for="subcategoria">Subcategoria
              <select id="subcategoria" name="subcategoria" required>
                <option value="">Selecione uma categoria primeiro</option>
              </select>
            </label>
          </div>

          <!-- SERVIÇO -->
          <div id="servico-wrapper" style="display: none;">
            <label for="servico">Serviço
              <select id="servico" name="servico" required>
                <option value="">Selecione uma subcategoria primeiro</option>
              </select>
            </label>
          </div>

          <!-- DESCRIÇÃO DO SERVIÇO -->
          <div id="descricao-container" class="grupo" style="display:none;">
            <strong>Descrição do Serviço:</strong>
            <p id="descricao-servico" style="margin-top: 5px;"></p>
          </div>

        </div>
      </div>

      <div class="form-actions-horizontal">
        <button type="submit" class="btn btn-primary">Criar</button>
        <a href="../index/index.php" class="btn btn-secondary">
          ← Voltar
        </a>
      </div>
    </form>
  </div>
  <script src="chamado.js"></script>
</body>

</html>