<?php
//$mysqli = new mysqli("localhost", "root", "sefazfer123@", "catalogo-teste");
require_once '../../conexao.php';
if ($mysqli->connect_errno) {
  die("Erro: " . $mysqli->connect_error);
}

$mensagem_sucesso = "";
if (isset($_GET["sucesso"]) && $_GET["sucesso"] == "1") {
  $mensagem_sucesso = "Categoria salva com sucesso!";
}
if (isset($_GET["excluido"]) && $_GET["excluido"] == "1") {
  $mensagem_sucesso = "Categoria excluída com sucesso!";
}

$result = $mysqli->query("SELECT * FROM categoria");

$categorias = [];
while ($cat = $result->fetch_assoc()) {
  $serv = $mysqli->query("
        SELECT COUNT(*) as total 
        FROM servico s
        JOIN subcategoria sub ON s.ID_SubCategoria = sub.ID
        WHERE sub.ID_Categoria = {$cat['ID']}
    ");
  $cat['qtd_servicos'] = $serv->fetch_assoc()['total'];

  // Conta subcategorias diretas
  $sub = $mysqli->query("SELECT COUNT(*) as total FROM subcategoria WHERE ID_Categoria = {$cat['ID']}");
  $cat['qtd_subcategorias'] = $sub->fetch_assoc()['total'];

  $categorias[] = $cat;
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <title>Lista de Categorias</title>
  <link rel="stylesheet" href="style_lists.css">
</head>

<body>
  <div class="container">
    <div class="top-bar">
      <h2>Lista de Categorias</h2>
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
        <?php foreach ($categorias as $cat): ?>
          <tr>
            <td><?php echo htmlspecialchars($cat['Titulo']); ?></td>
            <td><?php echo $cat['ID']; ?></td>
            <td><?php echo date('d-m-Y H:i', strtotime($cat['UltimaAtualizacao'] ?? 'now')); ?></td>
            <td><?php echo htmlspecialchars($cat['Descricao']); ?></td>
            <td><?php echo $cat['qtd_servicos']; ?></td>
            <td><?php echo $cat['qtd_subcategorias']; ?></td>
            <td>
              <a href="../add/manage_addcategoria.php?id=<?php echo $cat['ID']; ?>" class="btn-editar">✏️</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

</body>

</html>