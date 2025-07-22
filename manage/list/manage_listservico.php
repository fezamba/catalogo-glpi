<?php
// Inclui o arquivo de conexão com o banco de dados.
require_once '../../conexao.php';
// Verifica se a conexão com o banco de dados foi bem-sucedida.
if ($mysqli->connect_errno) {
  die("Erro: " . $mysqli->connect_error);
}

// --- Configuração da Paginação ---
$limit = 30; // Define o número de registros a serem exibidos por página.
// Obtém o número da página atual da URL. Se não for fornecido, assume a página 1.
$page = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
// Garante que o número da página não seja menor que 1.
if ($page < 1) $page = 1;
// Calcula o offset (deslocamento) para a consulta SQL, com base na página atual e no limite.
$offset = ($page - 1) * $limit;

// --- Cálculo do Total de Páginas ---
// Executa uma consulta para contar o número total de registros na tabela 'servico'.
$totalResult = $mysqli->query("SELECT COUNT(*) as total FROM servico");
$totalRows = $totalResult->fetch_assoc()['total'];
// Calcula o número total de páginas necessárias para exibir todos os registros.
$totalPages = ceil($totalRows / $limit);

// --- Consulta Principal para Buscar os Serviços da Página Atual ---
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
// Executa a consulta.
$result = $mysqli->query($query);

// --- Processamento dos Resultados ---
$servicos = [];
// Itera sobre os resultados da consulta e os armazena em um array.
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
      <!-- Exibe mensagens de feedback (sucesso/exclusão) se os parâmetros existirem na URL. -->
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
    <!-- Campo de busca para filtrar serviços dinamicamente -->
    <div style="margin-bottom: 20px; display: flex; flex-direction: column; align-items: center;">
      <input type="text" id="busca-servico" placeholder="Pesquisar serviço..."
        style="width: 90%; padding: 10px; border-radius: 6px; border: 1px solid #ccc; font-size: 16px;">
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
        <!-- Itera sobre o array de serviços e exibe cada um em uma linha da tabela. -->
        <?php foreach ($servicos as $srv): ?>
          <tr>
            <td><?php echo htmlspecialchars($srv['Titulo']); ?></td>
            <td><?php echo $srv['ID']; ?></td>
            <td><?php echo htmlspecialchars($srv['cat_nome']); ?></td>
            <td><?php echo htmlspecialchars($srv['subcat_nome']); ?></td>
            <td><?php echo date('d-m-Y H:i', strtotime($srv['UltimaAtualizacao'] ?? 'now')); ?></td>
            <td>
              <?php
              // Utiliza um switch para exibir uma label amigável para cada status.
              switch ($srv['status_ficha']) {
                case 'rascunho': echo '📝 Em Cadastro'; break;
                case 'em_revisao': echo '🔍 Em revisão'; break;
                case 'revisada': echo '✅ Revisada'; break;
                case 'em_aprovacao': echo '🕒 Em aprovação'; break;
                case 'aprovada': echo '☑️ Aprovada'; break;
                case 'publicado': echo '📢 Publicado'; break;
                case 'cancelada': echo '🚫 Cancelada'; break;
                case 'reprovado_revisor': echo '❌ Reprovado pelo revisor'; break;
                case 'reprovado_po': echo '❌ Reprovado pelo PO'; break;
                case 'substituida': echo "♻️ Substituída"; break;
                case 'descontinuada': echo '🚫 Descontinuada'; break;
                default: echo '—'; break;
              }
              ?>
            </td>
            <td><?php echo htmlspecialchars($srv['Descricao']); ?></td>
            <td><?php echo htmlspecialchars($srv['codigo_ficha'] ?? '—'); ?></td>
            <td><?php echo htmlspecialchars($srv['versao'] ?? '—'); ?></td>
            <td>
              <!-- Lógica condicional para o botão de edição. -->
              <?php if ($srv['status_ficha'] === 'publicado'): ?>
                <!-- Se o serviço está publicado, o link leva para a criação de uma nova versão. -->
                <a href="../add/manage_addservico.php?id=<?php echo $srv['ID']; ?>&nova_versao=1" class="btn-nova-versao">✏️</a>
              <?php else: ?>
                <!-- Para outros status, o link leva para a edição normal. -->
                <a href="../add/manage_addservico.php?id=<?php echo $srv['ID']; ?>" class="btn-editar">✏️</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <!-- Controles da Paginação -->
    <div class="pagination-controls" id="paginacao">
      <span>Exibindo <?php echo $offset + 1; ?> a <?php echo min($offset + $limit, $totalRows); ?> de <?php echo $totalRows; ?> linhas</span>
      <div class="page-links">
        <?php if ($page > 1): ?>
          <a href="?pagina=1">«</a>
          <a href="?pagina=<?php echo $page - 1; ?>">‹</a>
        <?php endif; ?>

        <!-- Gera os links para as páginas próximas à atual. -->
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
      // --- Seleção dos Elementos do DOM ---
      const inputServico = document.getElementById('busca-servico');
      const corpoTabela = document.querySelector('.tabela-servicos tbody');
      const paginacao = document.getElementById('paginacao');
      let debounceTimer; // Variável para controlar o debounce.

      if (inputServico && corpoTabela) {
        inputServico.addEventListener('input', function() {
          // Cancela o timer anterior para evitar múltiplas requisições.
          clearTimeout(debounceTimer);

          // Inicia um novo timer. A busca só será executada 300ms após o usuário parar de digitar.
          debounceTimer = setTimeout(() => {
            const termoBusca = this.value.trim();

            // Se o campo de busca estiver vazio, recarrega a página para restaurar a lista completa.
            if (termoBusca === '') {
              location.reload();
              return;
            }

            // Faz a requisição para o script de busca no backend.
            fetch(`../../buscar_servicos.php?termo=${encodeURIComponent(termoBusca)}`)
              .then(res => res.json())
              .then(data => {
                // Limpa o conteúdo atual da tabela e oculta a paginação.
                corpoTabela.innerHTML = '';
                if (paginacao) {
                  paginacao.style.display = 'none';
                }

                // Se não houver resultados, exibe uma mensagem.
                if (data.length === 0) {
                  const linha = corpoTabela.insertRow();
                  const cell = linha.insertCell();
                  cell.colSpan = 10;
                  cell.innerHTML = "Nenhum serviço encontrado.";
                  cell.style.textAlign = 'center';
                  return;
                }

                // Itera sobre os dados retornados e cria as linhas da tabela dinamicamente.
                data.forEach(servico => {
                  const linha = document.createElement('tr');
                  
                  // Lógica para formatar o status (similar ao PHP, mas em JS).
                  let statusTexto = '—';
                  switch (servico.status_ficha) {
                    case 'rascunho': statusTexto = '📝 Em Cadastro'; break;
                    case 'em_revisao': statusTexto = '🔍 Em revisão'; break;
                    case 'revisada': statusTexto = '✅ Revisada'; break;
                    case 'em_aprovacao': statusTexto = '🕒 Em aprovação'; break;
                    case 'aprovada': statusTexto = '☑️ Aprovada'; break;
                    case 'publicado': statusTexto = '📢 Publicado'; break;
                    case 'cancelada': statusTexto = '🚫 Cancelada'; break;
                    case 'reprovado_revisor': statusTexto = '❌ Reprovado pelo revisor'; break;
                    case 'reprovado_po': statusTexto = '❌ Reprovado pelo PO'; break;
                    case 'substituida': statusTexto = '♻️ Substituída'; break;
                    case 'inativa': statusTexto = '🚫 Inativa'; break;
                  }

                  // Formata a data de atualização.
                  const ultimaAtt = servico.UltimaAtualizacao ?
                    new Date(servico.UltimaAtualizacao).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' }) :
                    '—';
                  
                  // Define o botão de edição correto.
                  const botaoEdicao = servico.status_ficha === 'publicado' ?
                    `<a href="../add/manage_addservico.php?id=${servico.ID}&nova_versao=1" class="btn-nova-versao">✏️</a>` :
                    `<a href="../add/manage_addservico.php?id=${servico.ID}" class="btn-editar">✏️</a>`;

                  // Preenche o HTML da linha com os dados do serviço.
                  linha.innerHTML = `
                    <td>${servico.Titulo}</td>
                    <td>${servico.ID}</td>
                    <td>${servico.cat_nome}</td>
                    <td>${servico.subcat_nome}</td>
                    <td>${ultimaAtt}</td>
                    <td>${statusTexto}</td>
                    <td>${servico.Descricao}</td>
                    <td>${servico.codigo_ficha || '—'}</td>
                    <td>${servico.versao || '—'}</td>
                    <td>${botaoEdicao}</td>
                  `;
                  corpoTabela.appendChild(linha);
                });
              })
              .catch(err => {
                // Em caso de erro na requisição, exibe uma mensagem de erro na tabela.
                corpoTabela.innerHTML = '';
                const linha = corpoTabela.insertRow();
                const cell = linha.insertCell();
                cell.colSpan = 10;
                cell.innerHTML = "Erro ao buscar serviços.";
                cell.style.textAlign = 'center';
                console.error(err);
              });
          }, 300); // Tempo de debounce de 300ms.
        });
      }
    });
  </script>
</body>

</html>
