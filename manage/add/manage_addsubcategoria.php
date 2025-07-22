<?php
// Inclui o arquivo de conexão com o banco de dados.
require_once '../../conexao.php';
// Verifica se ocorreu um erro na conexão. Se sim, encerra o script.
if ($mysqli->connect_errno) {
  die("Erro: " . $mysqli->connect_error);
}

// --- Inicialização de Variáveis ---
$mensagem = ""; // Armazena mensagens de feedback para o usuário.
$titulo = ""; // Armazena o título da subcategoria.
$descricao = ""; // Armazena a descrição da subcategoria.
$id_categoria = ""; // Armazena o ID da categoria pai.
$modo_edicao = false; // Flag para determinar se o formulário está em modo de adição ou edição.

// --- Carregar Categorias para o Dropdown ---
$categorias = []; // Array para armazenar todas as categorias pai.
// Executa uma consulta para buscar todas as categorias existentes, ordenadas por título.
$res = $mysqli->query("SELECT ID, Titulo FROM categoria ORDER BY Titulo ASC");
// Itera sobre os resultados e os adiciona ao array $categorias.
while ($row = $res->fetch_assoc()) {
  $categorias[] = $row;
}

// --- Modo de Edição (Carregar Dados da Subcategoria) ---
// Verifica se um 'id' de subcategoria foi passado via GET na URL.
if (isset($_GET['id'])) {
  $id = intval($_GET['id']); // Converte o ID para um inteiro.
  $modo_edicao = true; // Ativa o modo de edição.

  // Prepara e executa uma consulta para buscar os dados da subcategoria a ser editada.
  $stmt = $mysqli->prepare("SELECT Titulo, Descricao, ID_Categoria FROM subcategoria WHERE ID = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $stmt->bind_result($titulo, $descricao, $id_categoria); // Associa os resultados às variáveis.
  $stmt->fetch(); // Busca o resultado.
  $stmt->close();
}

// --- Processamento do Formulário (Salvar/Excluir) ---
// Verifica se a requisição é do tipo POST e se uma 'acao' foi definida.
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['acao'])) {
  $acao = $_POST['acao'];
  // Obtém e limpa os dados do formulário.
  $titulo = trim($_POST['titulo'] ?? '');
  $descricao = trim($_POST['descricao'] ?? '');
  $id_categoria = (int)($_POST['id_categoria'] ?? 0);
  $id = intval($_POST['id'] ?? 0); // ID da subcategoria (se estiver em edição).

  // --- Ação de Excluir ---
  if ($acao === 'excluir' && $id > 0) {
    // Antes de excluir, verifica se existem serviços vinculados a esta subcategoria.
    $stmt_verifica = $mysqli->prepare("SELECT COUNT(*) FROM servico WHERE ID_Subcategoria = ?");
    $stmt_verifica->bind_param("i", $id);
    $stmt_verifica->execute();
    $stmt_verifica->bind_result($qtd_servicos);
    $stmt_verifica->fetch();
    $stmt_verifica->close();

    // Se houver serviços vinculados, a exclusão é bloqueada.
    if ($qtd_servicos > 0) {
      $mensagem = "Esta subcategoria possui serviços vinculados e não pode ser excluída.";
    } else {
      // Caso contrário, prossegue com a exclusão.
      $stmt = $mysqli->prepare("DELETE FROM subcategoria WHERE ID = ?");
      $stmt->bind_param("i", $id);
      if ($stmt->execute()) {
        // Redireciona para a lista com mensagem de sucesso.
        header("Location: ../list/manage_listsubcat.php?excluido=1");
        exit;
      } else {
        $mensagem = "Erro ao excluir: " . $stmt->error;
      }
      $stmt->close();
    }
  }

  // --- Ação de Salvar (Criar ou Atualizar) ---
  // Valida se todos os campos necessários foram preenchidos.
  if ($acao === 'salvar' && $titulo && $descricao && $id_categoria > 0) {
    // Se um 'id' > 0 existe, é uma atualização (UPDATE).
    if ($id > 0) {
      $stmt = $mysqli->prepare("UPDATE subcategoria SET Titulo = ?, Descricao = ?, ID_Categoria = ?, UltimaAtualizacao = NOW() WHERE ID = ?");
      $stmt->bind_param("ssii", $titulo, $descricao, $id_categoria, $id);
    } else {
      // Caso contrário, é uma inserção (INSERT).
      $stmt = $mysqli->prepare("INSERT INTO subcategoria (Titulo, Descricao, ID_Categoria, UltimaAtualizacao) VALUES (?, ?, ?, NOW())");
      $stmt->bind_param("ssi", $titulo, $descricao, $id_categoria);
    }

    if ($stmt->execute()) {
      // Redireciona para a lista com mensagem de sucesso.
      header("Location: ../list/manage_listsubcat.php?sucesso=1");
      exit;
    } else {
      $mensagem = "Erro ao salvar: " . $stmt->error;
    }
    $stmt->close();
  } elseif ($acao === 'salvar') {
    $mensagem = "Preencha todos os campos.";
  }
}

// --- Mensagem de Sucesso Pós-Redirecionamento ---
if (isset($_GET["sucesso"]) && $_GET["sucesso"] == "1") {
  $mensagem = $modo_edicao ? "Subcategoria atualizada com sucesso!" : "Subcategoria adicionada com sucesso!";
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <title><?php echo $modo_edicao ? "Editar Subcategoria" : "Nova Subcategoria"; ?></title>
  <link rel="stylesheet" href="../../css/style_manage_add.css">
</head>

<body>

  <div class="form-wrapper">
    <h2 class="form-title"><?php echo $modo_edicao ? "Editar Subcategoria" : "Novo item – Subcategoria"; ?></h2>

    <a href="../list/manage_listsubcat.php" class="btn-back">← Voltar para lista</a>

    <?php if (!empty($mensagem)): ?>
      <p class="mensagem"><?php echo htmlspecialchars($mensagem); ?></p>
    <?php endif; ?>

    <form method="post" class="form-grid">
      <?php if ($modo_edicao): ?>
        <input type="hidden" name="id" value="<?php echo $id; ?>">
      <?php endif; ?>

      <div class="form-column">
        <label>Nome:
          <input type="text" name="titulo" maxlength="255" value="<?php echo htmlspecialchars($titulo); ?>" required>
        </label>

        <label>Descrição:
          <textarea name="descricao" rows="5" maxlength="1000" required><?php echo htmlspecialchars($descricao); ?></textarea>
        </label>

        <label>Categoria:
          <select name="id_categoria" required>
            <option value="">Selecione uma categoria</option>
            <?php foreach ($categorias as $cat): ?>
              <!-- Define a categoria correta como 'selected' durante a edição -->
              <option value="<?php echo $cat['ID']; ?>" <?php if ($cat['ID'] == $id_categoria) echo 'selected'; ?>>
                <?php echo htmlspecialchars($cat['Titulo']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>
      </div>

      <div class="form-actions-horizontal">
        <div class="group-btns">
          <button type="submit" name="acao" value="salvar" class="btn-salvar">
            <?php echo $modo_edicao ? "Salvar alterações" : "+ Adicionar"; ?>
          </button>

          <?php if ($modo_edicao): ?>
            <button type="submit" name="acao" value="excluir" class="btn-danger"
              onclick="return confirm('Tem certeza que deseja excluir esta subcategoria?');">
              Excluir
            </button>
          <?php endif; ?>
        </div>
      </div>
    </form>
  </div>

  <script src="../../js/script_manage_add.js"></script>

</body>

</html>
