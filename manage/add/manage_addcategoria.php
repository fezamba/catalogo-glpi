<?php
// Inclui o arquivo de conexão com o banco de dados.
require_once '../../conexao.php';
// Verifica se ocorreu um erro na conexão. Se sim, encerra o script.
if ($mysqli->connect_error) {
  die("Erro: " . $mysqli->connect_error);
}

// --- Inicialização de Variáveis ---
$mensagem = ""; // Armazena mensagens de feedback para o usuário (sucesso ou erro).
$titulo = ""; // Armazena o título da categoria.
$descricao = ""; // Armazena a descrição da categoria.
$modo_edicao = false; // Flag para determinar se o formulário está em modo de adição ou edição.

// --- Modo de Edição (Carregar Dados) ---
// Verifica se um 'id' foi passado via GET na URL.
if (isset($_GET['id'])) {
  $id = intval($_GET['id']); // Converte o ID para um inteiro por segurança.
  $modo_edicao = true; // Ativa o modo de edição.

  // Prepara e executa uma consulta para buscar os dados da categoria a ser editada.
  $stmt = $mysqli->prepare("SELECT Titulo, Descricao FROM categoria WHERE ID = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $stmt->bind_result($titulo, $descricao); // Associa os resultados às variáveis.
  $stmt->fetch(); // Busca o resultado.
  $stmt->close();
}

// --- Processamento do Formulário (Salvar/Excluir) ---
// Verifica se a requisição é do tipo POST e se uma 'acao' foi definida.
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['acao'])) {
  $acao = $_POST['acao'];
  $titulo = $_POST["titulo"] ?? ''; // Pega o título do POST, ou string vazia se não existir.
  $descricao = $_POST["descricao"] ?? ''; // Pega a descrição do POST.

  // --- Ação de Excluir ---
  if ($acao === 'excluir' && isset($_POST['id'])) {
    $delete_id = intval($_POST['id']);

    // Passo 1: Verificar se a categoria tem subcategorias associadas antes de excluir.
    $stmt_verifica = $mysqli->prepare("SELECT COUNT(*) FROM subcategoria WHERE ID_Categoria = ?");
    $stmt_verifica->bind_param("i", $delete_id);
    $stmt_verifica->execute();
    $stmt_verifica->bind_result($qtd_subcategorias);
    $stmt_verifica->fetch();
    $stmt_verifica->close();

    // Se houver subcategorias, a exclusão é bloqueada.
    if ($qtd_subcategorias > 0) {
      $mensagem = "Esta categoria possui subcategorias associadas e não pode ser excluída.";
    } else {
      // Se não houver, prossegue com a exclusão.
      $stmt = $mysqli->prepare("DELETE FROM categoria WHERE ID = ?");
      $stmt->bind_param("i", $delete_id);
      if ($stmt->execute()) {
        // Se a exclusão for bem-sucedida, redireciona para a lista com uma mensagem.
        header("Location: ../list/manage_listcategoria.php?excluido=1");
        exit;
      } else {
        $mensagem = "Erro ao excluir: " . $stmt->error;
      }
      $stmt->close();
    }
  }

  // --- Ação de Salvar (Criar ou Atualizar) ---
  if ($acao === 'salvar' && $titulo && $descricao) {
    // Se um 'id' está presente no POST, é uma atualização (UPDATE).
    if (isset($_POST['id'])) {
      $id = intval($_POST['id']);
      $stmt = $mysqli->prepare("UPDATE categoria SET Titulo = ?, Descricao = ? WHERE ID = ?");
      $stmt->bind_param("ssi", $titulo, $descricao, $id);
    } else {
      // Caso contrário, é uma inserção (INSERT).
      $stmt = $mysqli->prepare("INSERT INTO categoria (Titulo, Descricao) VALUES (?, ?)");
      $stmt->bind_param("ss", $titulo, $descricao);
    }

    // Executa a consulta de inserção ou atualização.
    if ($stmt->execute()) {
      // Se for bem-sucedida, redireciona para a lista com uma mensagem de sucesso.
      header("Location: ../list/manage_listcategoria.php?sucesso=1");
      exit;
    } else {
      $mensagem = "Erro ao salvar: " . $stmt->error;
    }
    $stmt->close();
  } elseif ($acao === 'salvar') {
    // Se a ação for 'salvar', mas os campos não estiverem preenchidos.
    $mensagem = "Preencha todos os campos.";
  }
}

// --- Mensagem de Sucesso Pós-Redirecionamento ---
// Verifica se o parâmetro 'sucesso' está na URL para exibir a mensagem correta.
if (isset($_GET["sucesso"]) && $_GET["sucesso"] == "1") {
  $mensagem = $modo_edicao ? "Categoria atualizada com sucesso!" : "Categoria adicionada com sucesso!";
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <title>Nova Categoria</title>
  <link rel="stylesheet" href="../../css/style_manage_add.css">
</head>

<body>

  <div class="form-wrapper">
    <h2 class="form-title">
      <!-- O título da página muda dinamicamente se estiver em modo de edição ou adição -->
      <?php echo $modo_edicao ? "Editar Categoria" : "Novo item – Categoria"; ?>
    </h2>

    <a href="../list/manage_listcategoria.php" class="btn-back">← Voltar para lista</a>

    <!-- Exibe a mensagem de feedback, se houver alguma -->
    <?php if (!empty($mensagem)): ?>
      <p class="mensagem"><?php echo htmlspecialchars($mensagem); ?></p>
    <?php endif; ?>

    <form method="post" class="form-grid">
      <!-- Se estiver em modo de edição, inclui um campo oculto com o ID do item -->
      <?php if ($modo_edicao): ?>
        <input type="hidden" name="id" value="<?php echo $id; ?>">
      <?php endif; ?>

      <div class="form-column">
        <label>Nome:
          <!-- O valor do campo é preenchido com os dados da categoria (em modo de edição) -->
          <input type="text" name="titulo" maxlength="255" value="<?php echo htmlspecialchars($titulo); ?>" required>
        </label>

        <label>Descrição:
          <textarea name="descricao" rows="5" maxlength="1000" required><?php echo htmlspecialchars($descricao); ?></textarea>
        </label>
      </div>

      <div class="form-actions-horizontal">
        <div class="group-btns">
          <!-- O texto do botão de salvar também muda dinamicamente -->
          <button type="submit" name="acao" value="salvar" class="btn-salvar">
            <?php echo $modo_edicao ? "Salvar alterações" : "+ Adicionar"; ?>
          </button>

          <!-- O botão de excluir só aparece no modo de edição -->
          <?php if ($modo_edicao): ?>
            <button type="submit" name="acao" value="excluir" class="btn-danger" onclick="return confirm('Tem certeza que deseja excluir esta categoria?');">
              Excluir
            </button>
          <?php endif; ?>
        </div>
      </div>
    </form>
  </div>

  <!-- Inclui o script JavaScript para funcionalidades do frontend, como ocultar a mensagem de feedback -->
  <script src="../../js/script_manage_add.js"></script>
</body>

</html>
