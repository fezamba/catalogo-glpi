<?php
require_once '../../conexao.php';
if ($mysqli->connect_error) {
  die("Erro: " . $mysqli->connect_error);
}

$mensagem = "";
$titulo = "";
$descricao = "";
$modo_edicao = false;

if (isset($_GET['id'])) {
  $id = intval($_GET['id']);
  $modo_edicao = true;

  $stmt = $mysqli->prepare("SELECT Titulo, Descricao FROM categoria WHERE ID = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $stmt->bind_result($titulo, $descricao);
  $stmt->fetch();
  $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['acao'])) {
  $acao = $_POST['acao'];
  $titulo = $_POST["titulo"] ?? '';
  $descricao = $_POST["descricao"] ?? '';

  if ($acao === 'excluir' && isset($_POST['id'])) {
    $delete_id = intval($_POST['id']);

    // Verifica se existem subcategorias associadas
    $stmt_verifica = $mysqli->prepare("SELECT COUNT(*) FROM subcategoria WHERE ID_Categoria = ?");
    $stmt_verifica->bind_param("i", $delete_id);
    $stmt_verifica->execute();
    $stmt_verifica->bind_result($qtd_subcategorias);
    $stmt_verifica->fetch();
    $stmt_verifica->close();

    if ($qtd_subcategorias > 0) {
      $mensagem = "Esta categoria possui subcategorias associadas e não pode ser excluída.";
    } else {
      $stmt = $mysqli->prepare("DELETE FROM categoria WHERE ID = ?");
      $stmt->bind_param("i", $delete_id);
      if ($stmt->execute()) {
        header("Location: ../list/manage_listcategoria.php?excluido=1");
        exit;
      } else {
        $mensagem = "Erro ao excluir: " . $stmt->error;
      }
      $stmt->close();
    }
  }

  if ($acao === 'salvar' && $titulo && $descricao) {
    if (isset($_POST['id'])) {
      $id = intval($_POST['id']);
      $stmt = $mysqli->prepare("UPDATE categoria SET Titulo = ?, Descricao = ? WHERE ID = ?");
      $stmt->bind_param("ssi", $titulo, $descricao, $id);
    } else {
      $stmt = $mysqli->prepare("INSERT INTO categoria (Titulo, Descricao) VALUES (?, ?)");
      $stmt->bind_param("ss", $titulo, $descricao);
    }

    if ($stmt->execute()) {
      header("Location: ../list/manage_listcategoria.php?sucesso=1");
      exit;
    } else {
      $mensagem = "Erro ao salvar: " . $stmt->error;
    }
    $stmt->close();
  } elseif ($acao === 'salvar') {
    $mensagem = "Preencha todos os campos.";
  }
}

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
      <?php echo $modo_edicao ? "Editar Categoria" : "Novo item – Categoria"; ?>
    </h2>

    <a href="../list/manage_listcategoria.php" class="btn-back">← Voltar para lista</a>

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
      </div>

      <div class="form-actions-horizontal">
        <div class="group-btns">
          <button type="submit" name="acao" value="salvar" class="btn-salvar">
            <?php echo $modo_edicao ? "Salvar alterações" : "+ Adicionar"; ?>
          </button>

          <?php if ($modo_edicao): ?>
            <button type="submit" name="acao" value="excluir" class="btn-danger" onclick="return confirm('Tem certeza que deseja excluir esta categoria?');">
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