<?php
// Inclui o arquivo de conexão com o banco de dados.
require_once '../../conexao.php';
// Verifica se ocorreu um erro na conexão. Se sim, encerra o script.
if ($mysqli->connect_errno) {
  die("Erro: " . $mysqli->connect_error);
}

// --- Tratamento de Mensagens de Feedback ---
$mensagem_sucesso = "";
// Verifica se a página foi acessada com um parâmetro 'sucesso' na URL (após uma inserção/atualização).
if (isset($_GET["sucesso"]) && $_GET["sucesso"] == "1") {
  $mensagem_sucesso = "Categoria salva com sucesso!";
}
// Verifica se a página foi acessada com um parâmetro 'excluido' na URL.
if (isset($_GET["excluido"]) && $_GET["excluido"] == "1") {
  $mensagem_sucesso = "Categoria excluída com sucesso!";
}

// --- Busca de Dados ---
// Busca todas as categorias da tabela 'categoria'.
$result = $mysqli->query("SELECT * FROM categoria");

$categorias = [];
// Itera sobre cada categoria encontrada.
while ($cat = $result->fetch_assoc()) {
  // NOTA DE PERFORMANCE: A execução de queries dentro de um loop (conhecido como N+1 Query Problem)
  // pode degradar a performance em tabelas com muitos registros.
  // Uma abordagem mais otimizada seria usar uma única query com JOIN e GROUP BY
  // para calcular as contagens de serviços e subcategorias de uma só vez.

  // Para cada categoria, executa uma query para contar os serviços associados.
  $serv = $mysqli->query("
        SELECT COUNT(*) as total 
        FROM servico s
        JOIN subcategoria sub ON s.ID_SubCategoria = sub.ID
        WHERE sub.ID_Categoria = {$cat['ID']}
    ");
  // Adiciona a contagem de serviços ao array da categoria.
  $cat['qtd_servicos'] = $serv->fetch_assoc()['total'];

  // Para cada categoria, executa outra query para contar as subcategorias filhas.
  $sub = $mysqli->query("SELECT COUNT(*) as total FROM subcategoria WHERE ID_Categoria = {$cat['ID']}");
  // Adiciona a contagem de subcategorias ao array da categoria.
  $cat['qtd_subcategorias'] = $sub->fetch_assoc()['total'];

  // Adiciona a categoria (já com as contagens) ao array final.
  $categorias[] = $cat;
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <title>Lista de Categorias</title>
  <link rel="stylesheet" href="../../css/style_lists.css">
</head>

<body>
  <div class="container">
    <div class="top-bar">
      <h2>Lista de Categorias</h2>
      <!-- Exibe a mensagem de sucesso, se houver alguma. -->
      <?php if (!empty($mensagem_sucesso)): ?>
        <p class="mensagem"><?php echo htmlspecialchars($mensagem_sucesso); ?></p>
      <?php endif; ?>
      <div class="button-group">
        <a href="../add/manage_addcategoria.php" class="btn-criar">+ Criar</a>
        <a href="../manage.php" class="btn-voltar">← Voltar</a>
      </div>
    </div>

    <table class="tabela-categorias">
      <thead>
        <tr>
          <th>Nome</th>
          <th>ID</th>
          <th>Última Att</th>
          <th>Descrição</th>
          <th>Qtd Serviços</th>
          <th>Qtd Subcategorias</th>
          <th>Edição</th>
        </tr>
      </thead>
      <tbody>
        <!-- Verifica se existem categorias para listar. -->
        <?php if (empty($categorias)): ?>
          <tr>
            <!-- A propriedade colspan="7" faz a célula ocupar todas as 7 colunas do cabeçalho. -->
            <td colspan="7">Nenhuma categoria encontrada.</td>
          </tr>
        <?php else: ?>
          <!-- Itera sobre o array de categorias e exibe cada uma em uma linha da tabela. -->
          <?php foreach ($categorias as $cat): ?>
            <tr>
              <td><?php echo htmlspecialchars($cat['Titulo']); ?></td>
              <td><?php echo $cat['ID']; ?></td>
              <!-- Formata a data de última atualização para o padrão brasileiro (dd-mm-YYYY HH:ii). -->
              <td><?php echo date('d-m-Y H:i', strtotime($cat['UltimaAtualizacao'] ?? 'now')); ?></td>
              <td><?php echo htmlspecialchars($cat['Descricao']); ?></td>
              <td><?php echo $cat['qtd_servicos']; ?></td>
              <td><?php echo $cat['qtd_subcategorias']; ?></td>
              <td>
                <!-- Link para a página de edição, passando o ID da categoria como parâmetro na URL. -->
                <a href="../add/manage_addcategoria.php?id=<?php echo $cat['ID']; ?>" class="btn-editar">✏️</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Script para ocultar a mensagem de sucesso após alguns segundos -->
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const mensagem = document.querySelector('.mensagem');
      if (mensagem) {
        setTimeout(() => {
          mensagem.style.display = 'none';
        }, 3000); // Oculta após 3 segundos
      }
    });
  </script>

</body>

</html>
