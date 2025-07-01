<?php
require_once '../../conexao.php';
if ($mysqli->connect_errno) {
  die("Erro: " . $mysqli->connect_error);
}

$limit = 30;
$page = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$totalResult = $mysqli->query("SELECT COUNT(*) as total FROM servico");
$totalRows = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

$query = "
  SELECT 
    s.*,
    sub.Titulo AS subcat_nome,
    cat.Titulo AS cat_nome,
    s.status_ficha
  FROM servico s
  LEFT JOIN subcategoria sub ON s.ID_Subcategoria = sub.ID
  LEFT JOIN categoria cat ON sub.ID_Categoria = cat.ID
  ORDER BY s.ID DESC
  LIMIT $limit OFFSET $offset
";

$result = $mysqli->query($query);

$servicos = [];
while ($srv = $result->fetch_assoc()) {
  $servicos[] = $srv;
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <title>Lista de Serviços</title>
  <link rel="stylesheet" href="../../css/style_lists.css">
</head>

<body>
  <div class="container">
    <div class="top-bar">
      <h2>Lista de Serviços</h2>
      <?php if (isset($_GET['sucesso']) || isset($_GET['excluido'])): ?>
        <div class="mensagem">
          <?php
          if (isset($_GET['sucesso']) && $_GET['sucesso'] == "1") {
            echo "Serviço salvo com sucesso!";
          } elseif (isset($_GET['excluido']) && $_GET['excluido'] == "1") {
            echo "Serviço excluído com sucesso!";
          }
          ?>
        </div>
      <?php endif; ?>
      <div class="button-group">
        <a href="../add/manage_addservico.php" class="btn-criar">+ Criar</a>
        <a href="../manage.php" class="btn-voltar">← Voltar</a>
      </div>
    </div>
    <div style="margin-bottom: 20px; display: flex; flex-direction: column; align-items: center;">
      <input type="text" id="busca-servico" placeholder="Pesquisar serviço..."
        style="width: 90%; padding: 10px; border-radius: 6px; border: 1px solid #ccc; font-size: 16px;">

      <div id="resultados-servico"
        style="width: 90%;padding: 0px 10px; background: white; border: 1px solid #ddd; border-radius: 6px; margin-top: 5px; text-align: left; display: none; max-height: 200px; overflow-y: auto;">
      </div>
    </div>
    <table class="tabela-servicos">
      <thead>
        <tr>
          <th>Nome</th>
          <th>ID</th>
          <th>Categoria</th>
          <th>Subcategoria</th>
          <th>Última Att</th>
          <th>Status</th>
          <th>Descrição</th>
          <th>Código</th>
          <th>Versão</th>
          <th>Edição</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($servicos as $srv): ?>
          <tr>
            <td><?php echo htmlspecialchars($srv['Titulo']); ?></td>
            <td><?php echo $srv['ID']; ?></td>
            <td><?php echo htmlspecialchars($srv['cat_nome']); ?></td>
            <td><?php echo htmlspecialchars($srv['subcat_nome']); ?></td>
            <td><?php echo date('d-m-Y H:i', strtotime($srv['UltimaAtualizacao'] ?? 'now')); ?></td>
            <td>
              <?php
              switch ($srv['status_ficha']) {
                case 'rascunho':
                  echo '📝 Em Cadastro';
                  break;
                case 'em_revisao':
                  echo '🔍 Em revisão';
                  break;
                case 'revisada':
                  echo '✅ Revisada';
                  break;
                case 'em_aprovacao':
                  echo '🕒 Em aprovação';
                  break;
                case 'aprovada':
                  echo '☑️ Aprovada';
                  break;
                case 'publicado':
                  echo '📢 Publicado';
                  break;
                case 'cancelada':
                  echo '🚫 Cancelada';
                  break;
                case 'reprovado_revisor':
                  echo '❌ Reprovado pelo revisor';
                  break;
                case 'reprovado_po':
                  echo '❌ Reprovado pelo PO';
                  break;
                case 'substituida':
                  echo "♻️ Substituída";
                  break;
                case 'descontinuada':
                  echo '🚫 Descontinuada';
                  break;
                default:
                  echo '—';
                  break;
              }
              ?>
            </td>
            <td><?php echo htmlspecialchars($srv['Descricao']); ?></td>
            <td><?php echo htmlspecialchars($srv['codigo_ficha'] ?? '—'); ?></td>
            <td><?php echo htmlspecialchars($srv['versao'] ?? '—'); ?></td>
            <td><?php if ($srv['status_ficha'] === 'publicado'): ?>
                <a href="../add/manage_addservico.php?id=<?php echo $srv['ID']; ?>&nova_versao=1" class="btn-nova-versao">✏️</a>
              <?php else: ?>
                <a href="../add/manage_addservico.php?id=<?php echo $srv['ID']; ?>" class="btn-editar">✏️</a>
              <?php endif; ?>
            </td>
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
          <a href="?pagina=<?php echo $i; ?>" class="<?php echo ($i == $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
          <a href="?pagina=<?php echo $page + 1; ?>">›</a>
          <a href="?pagina=<?php echo $totalPages; ?>">»</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const inputServico = document.getElementById('busca-servico');
      const resultadosServico = document.getElementById('resultados-servico');
      const corpoTabela = document.querySelector('.tabela-servicos tbody');
      const paginacao = document.getElementById('paginacao');
      let debounceTimer;

      if (inputServico && corpoTabela) {
        inputServico.addEventListener('input', function() {
          clearTimeout(debounceTimer);

          debounceTimer = setTimeout(() => {
            const termoBusca = this.value.trim();

            if (termoBusca === '') {
              location.reload();
              return;
            }

            fetch(`../../buscar_servicos.php?termo=${encodeURIComponent(termoBusca)}`)
              .then(res => res.json())
              .then(data => {
                corpoTabela.innerHTML = '';
                if (resultadosServico) {
                  resultadosServico.style.display = 'none';
                }
                if (paginacao) {
                  paginacao.style.display = 'none';
                }

                if (data.length === 0) {
                  const linha = corpoTabela.insertRow();
                  const cell = linha.insertCell();
                  cell.colSpan = 10;
                  cell.innerHTML = "Nenhum serviço encontrado.";
                  cell.style.textAlign = 'center';
                  cell.style.padding = '20px';
                  return;
                }

                data.forEach(servico => {
                  const linha = document.createElement('tr');

                  let statusTexto = '—';
                  switch (servico.status_ficha) {
                    case 'rascunho':
                      statusTexto = '📝 Em Cadastro';
                      break;
                    case 'em_revisao':
                      statusTexto = '🔍 Em revisão';
                      break;
                    case 'revisada':
                      statusTexto = '✅ Revisada';
                      break;
                    case 'em_aprovacao':
                      statusTexto = '🕒 Em aprovação';
                      break;
                    case 'aprovada':
                      statusTexto = '☑️ Aprovada';
                      break;
                    case 'publicado':
                      statusTexto = '📢 Publicado';
                      break;
                    case 'cancelada':
                      statusTexto = '🚫 Cancelada';
                      break;
                    case 'reprovado_revisor':
                      statusTexto = '❌ Reprovado pelo revisor';
                      break;
                    case 'reprovado_po':
                      statusTexto = '❌ Reprovado pelo PO';
                      break;
                    case 'substituida':
                      statusTexto = '♻️ Substituída';
                      break;
                    case 'inativa':
                      statusTexto = '🚫 Inativa';
                      break;
                  }

                  const ultimaAtt = servico.ultima_atualizacao ?
                    new Date(servico.ultima_atualizacao).toLocaleString('pt-BR') :
                    '—';

                  const botaoEdicao = servico.status_ficha === 'publicado' ?
                    `<a href="../add/manage_addservico.php?id=${servico.id}&nova_versao=1" class="btn-nova-versao">Nova versão</a>` :
                    `<a href="../add/manage_addservico.php?id=${servico.id}" class="btn-editar">✏️</a>`;

                  linha.innerHTML = `
                                <td>${servico.titulo}</td>
                                <td>${servico.id}</td>
                                <td>${servico.categoria}</td>
                                <td>${servico.subcategoria}</td>
                                <td>${ultimaAtt}</td>
                                <td>${statusTexto}</td>
                                <td>${servico.descricao}</td>
                                <td>${servico.codigo_ficha || '—'}</td>
                                <td>${servico.versao || '—'}</td>
                                <td>${botaoEdicao}</td>
                            `;
                  corpoTabela.appendChild(linha);
                });
              })
              .catch(err => {
                corpoTabela.innerHTML = '';
                const linha = corpoTabela.insertRow();
                const cell = linha.insertCell();
                cell.colSpan = 10;
                cell.innerHTML = "Erro ao buscar serviços.";
                cell.style.textAlign = 'center';
                cell.style.padding = '20px';
                console.error(err);
              });
          }, 300);
        });
      }
    });
  </script>
</body>

</html>