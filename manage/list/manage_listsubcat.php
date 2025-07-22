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
// Executa uma consulta para contar o número total de registros na tabela 'subcategoria'.
$totalResult = $mysqli->query("SELECT COUNT(*) as total FROM subcategoria");
$totalRows = $totalResult->fetch_assoc()['total'];
// Calcula o número total de páginas necessárias para exibir todos os registros.
$totalPages = ceil($totalRows / $limit);

// --- Consulta Principal para Buscar as Subcategorias da Página Atual ---
$query = "
  SELECT s.*, c.Titulo AS categoria_nome 
  FROM subcategoria s
  LEFT JOIN categoria c ON s.ID_Categoria = c.ID
  ORDER BY s.ID DESC
  LIMIT $limit OFFSET $offset
";
// Executa a consulta.
$result = $mysqli->query($query);

// --- Processamento dos Resultados ---
$subcategorias = [];
// Itera sobre os resultados da consulta.
while ($sub = $result->fetch_assoc()) {
  // NOTA DE PERFORMANCE: A execução de uma query dentro de um loop (N+1 Query Problem)
  // pode causar lentidão se a lista de subcategorias for muito grande.
  // Uma abordagem mais otimizada seria usar uma única query com subconsultas ou JOINs e GROUP BY
  // para obter essa contagem de uma vez só.
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
  <link rel="stylesheet" href="../../css/style_lists.css">
</head>

<body>
  <div class="container">
    <div class="top-bar">
      <h2>Lista de Subcategorias</h2>
      
      <!-- Exibe mensagens de feedback (sucesso/exclusão) se os parâmetros existirem na URL. -->
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
    <!-- Campo de busca para filtrar subcategorias dinamicamente -->
    <div style="margin-bottom: 20px; display: flex; flex-direction: column; align-items: center;">
      <input type="text" id="busca-subcat" placeholder="Pesquisar subcategoria..."
        style="width: 90%; padding: 10px; border-radius: 6px; border: 1px solid #ccc; font-size: 16px;">
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
        <!-- Itera sobre o array de subcategorias e exibe cada uma em uma linha da tabela. -->
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
// --- Lógica de Busca Dinâmica ---

// Seleciona os elementos do DOM necessários para a funcionalidade de busca.
const inputSubcat = document.getElementById('busca-subcat');
// O div de resultados 'resultados-subcat' parece não ser usado ativamente para exibir a lista,
// mas sim para mensagens de erro/nenhum resultado.
const resultados = document.getElementById('resultados-subcat');
const corpoTabela = document.querySelector('.tabela-subcategorias tbody');
const paginacao = document.getElementById('paginacao');

// Adiciona um ouvinte de eventos ao campo de busca que é acionado a cada tecla digitada.
inputSubcat.addEventListener('input', function () {
  const termoBusca = this.value.trim();

  // Se o campo de busca for limpo, recarrega a página para mostrar a lista completa com paginação.
  if (termoBusca === '') {
    location.reload();
    return;
  }

  // Faz a requisição para o script de busca no backend, passando o termo de busca.
  fetch(`../../buscar_subcategorias.php?termo=${encodeURIComponent(termoBusca)}`)
    .then(res => res.json()) // Converte a resposta para JSON.
    .then(data => {
      // Limpa o conteúdo atual da tabela e oculta a paginação e a área de resultados.
      corpoTabela.innerHTML = '';
      resultados.style.display = 'none';
      paginacao.style.display = 'none'; // Esconde os controles de paginação durante a busca.

      // Se a busca não retornar resultados, exibe uma mensagem na tabela.
      if (data.length === 0) {
        const linha = corpoTabela.insertRow();
        const cell = linha.insertCell();
        cell.colSpan = 7; // Abrange todas as colunas da tabela.
        cell.innerHTML = "Nenhuma subcategoria encontrada.";
        cell.style.textAlign = 'center';
        return;
      }

      // Itera sobre os dados retornados e cria as linhas da tabela dinamicamente.
      data.forEach(sub => {
        const linha = document.createElement('tr');
        // Formata a data de atualização.
        const ultimaAtt = sub.UltimaAtualizacao
          ? new Date(sub.UltimaAtualizacao).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' })
          : '—';

        // Preenche o HTML da linha com os dados da subcategoria.
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
      // Em caso de erro na requisição, exibe uma mensagem de erro na tabela.
      corpoTabela.innerHTML = '';
      const linha = corpoTabela.insertRow();
      const cell = linha.insertCell();
      cell.colSpan = 7;
      cell.innerHTML = "Erro ao buscar subcategorias.";
      cell.style.textAlign = 'center';
      console.error(err);
    });
});
</script>
</body>

</html>
