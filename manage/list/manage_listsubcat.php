<?php
//$mysqli = new mysqli("localhost", "root", "sefazfer123@", "catalogo-teste");
require_once '../../conexao.php';
if ($mysqli->connect_errno) {
  die("Erro: " . $mysqli->connect_error);
}

$limit = 30;
$page = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$totalResult = $mysqli->query("SELECT COUNT(*) as total FROM subcategoria");
$totalRows = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

$query = "
  SELECT s.*, c.Titulo AS categoria_nome 
  FROM subcategoria s
  LEFT JOIN categoria c ON s.ID_Categoria = c.ID
  LIMIT $limit OFFSET $offset
";
$result = $mysqli->query($query);

$subcategorias = [];
while ($sub = $result->fetch_assoc()) {
  $srv = $mysqli->query("SELECT COUNT(*) as total FROM servico WHERE ID_Subcategoria = {$sub['ID']}");
  $sub['qtd_servicos'] = $srv->fetch_assoc()['total'];
  $subcategorias[] = $sub;
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <title>Lista de Subcategorias</title>
  <link rel="stylesheet" href="style_lists.css">
</head>

<body>
  <div class="container">
    <div class="top-bar">
      <h2>Lista de Subcategorias</h2>
      
      <?php if (isset($_GET['sucesso']) && $_GET['sucesso'] == 1): ?>
        <div class="mensagem">Subcategoria salva com sucesso!</div>
      <?php elseif (isset($_GET['excluido']) && $_GET['excluido'] == 1): ?>
        <div class="mensagem">Subcategoria excluída com sucesso!</div>
      <?php endif; ?>
      <div class="button-group">
        <a href="../add/manage_addsubcategoria.php" class="btn-criar">+ Criar</a>
        <a href="../manage.php" class="btn-voltar">← Voltar</a>
      </div>
    </div>
    <div style="margin-bottom: 20px; display: flex; flex-direction: column; align-items: center;">
      <input type="text" id="busca-subcat" placeholder="Pesquisar subcategoria..."
        style="width: 90%; padding: 10px; border-radius: 6px; border: 1px solid #ccc; font-size: 16px;">

      <div id="resultados-subcat"
        style="width: 90%;padding: 0px 10px; background: white; border: 1px solid #ddd; border-radius: 6px; margin-top: 5px; text-align: left; display: none; max-height: 200px; overflow-y: auto;">
    </div>
    </div>
    <table class="tabela-subcategorias">
      <thead>
        <tr>
          <th>Nome</th>
          <th>ID</th>
          <th>Categoria Pai</th>
          <th>Última Att</th>
          <th>Descrição</th>
          <th>Qtd Serviços</th>
          <th>Edição</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($subcategorias as $sub): ?>
          <tr>
            <td><?php echo htmlspecialchars($sub['Titulo']); ?></td>
            <td><?php echo $sub['ID']; ?></td>
            <td><?php echo htmlspecialchars($sub['categoria_nome']); ?></td>
            <td><?php echo date('d-m-Y H:i', strtotime($sub['UltimaAtualizacao'] ?? 'now')); ?></td>
            <td><?php echo htmlspecialchars($sub['Descricao']); ?></td>
            <td><?php echo $sub['qtd_servicos']; ?></td>
            <td><a href="../add/manage_addsubcategoria.php?id=<?php echo $sub['ID']; ?>" class="btn-editar">✏️</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="pagination-controls" id="paginacao">
      <span>Exibindo <?php echo $offset + 1; ?> a <?php echo min($offset + $limit, $totalRows); ?> de <?php echo $totalRows; ?> linhas</span>
      <div class="page-links">
        <?php if ($page > 1): ?>
          <a href="?pagina=1">«</a>
          <a href="?pagina=<?php echo $page - 1; ?>">‹</a>
        <?php endif; ?>

        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
          <a href="?pagina=<?php echo $i; ?>" class="<?php echo ($i == $page) ? 'active' : ''; ?>">
            <?php echo $i; ?>
          </a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
          <a href="?pagina=<?php echo $page + 1; ?>">›</a>
          <a href="?pagina=<?php echo $totalPages; ?>">»</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
<script>
  const inputSubcat = document.getElementById('busca-subcat');
  const resultados = document.getElementById('resultados-subcat');
  const corpoTabela = document.querySelector('.tabela-subcategorias tbody');
  const paginacao = document.getElementById('paginacao');

  inputSubcat.addEventListener('input', function () {
    const termoBusca = this.value.trim();

    if (termoBusca === '') {
      // campo de busca apagado: recarrega a página
      location.reload();
      return;
    }

    fetch(`buscar_subcategorias.php?termo=${encodeURIComponent(termoBusca)}`)
      .then(res => res.json())
      .then(data => {
        corpoTabela.innerHTML = '';
        resultados.style.display = 'none';
        paginacao.style.display = 'none';

        if (data.length === 0) {
          resultados.innerHTML = '<div style="padding:10px;">Nenhuma subcategoria encontrada.</div>';
          resultados.style.display = 'block';
          return;
        }

        data.forEach(sub => {
          const linha = document.createElement('tr');
          const ultimaAtt = sub.UltimaAtualizacao
            ? new Date(sub.UltimaAtualizacao).toLocaleString('pt-BR')
            : '—';

          linha.innerHTML = `
            <td>${sub.Titulo}</td>
            <td>${sub.ID}</td>
            <td>${sub.categoria_nome}</td>
            <td>${ultimaAtt}</td>
            <td>${sub.Descricao}</td>
            <td>${sub.qtd_servicos}</td>
            <td>
              <a href="../add/manage_addsubcategoria.php?id=${sub.ID}" class="btn-editar">✏️</a>
            </td>
          `;
          corpoTabela.appendChild(linha);
        });
      })
      .catch(err => {
        resultados.innerHTML = '<div style="padding:10px;">Erro ao buscar subcategorias.</div>';
        resultados.style.display = 'block';
        console.error(err);
      });
  });
</script>
</body>

</html>