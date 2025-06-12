<?php
//$mysqli = new mysqli("localhost", "root", "sefazfer123@", "catalogo-teste");
require_once 'conexao.php';
if ($mysqli->connect_errno) {
  die("Erro: " . $mysqli->connect_error);
}

$mensagem = "";
$titulo = "";
$descricao = "";
$id_categoria = "";
$modo_edicao = false;

// Buscar categorias para o select
$categorias = [];
$res = $mysqli->query("SELECT ID, Titulo FROM categoria ORDER BY Titulo ASC");
while ($row = $res->fetch_assoc()) {
  $categorias[] = $row;
}

// Verifica se está editando
if (isset($_GET['id'])) {
  $id = intval($_GET['id']);
  $modo_edicao = true;

  $stmt = $mysqli->prepare("SELECT Titulo, Descricao, ID_Categoria FROM subcategoria WHERE ID = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $stmt->bind_result($titulo, $descricao, $id_categoria);
  $stmt->fetch();
  $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['acao'])) {
  $acao = $_POST['acao'];
  $titulo = trim($_POST['titulo'] ?? '');
  $descricao = trim($_POST['descricao'] ?? '');
  $id_categoria = (int)($_POST['id_categoria'] ?? 0);
  $id = intval($_POST['id'] ?? 0);

  if ($acao === 'excluir' && $id > 0) {
    // Verifica se existem serviços vinculados a essa subcategoria
    $stmt_verifica = $mysqli->prepare("SELECT COUNT(*) FROM servico WHERE ID_Subcategoria = ?");
    $stmt_verifica->bind_param("i", $id);
    $stmt_verifica->execute();
    $stmt_verifica->bind_result($qtd_servicos);
    $stmt_verifica->fetch();
    $stmt_verifica->close();

    if ($qtd_servicos > 0) {
      $mensagem = "Esta subcategoria possui serviços vinculados e não pode ser excluída.";
    } else {
      $stmt = $mysqli->prepare("DELETE FROM subcategoria WHERE ID = ?");
      $stmt->bind_param("i", $id);
      if ($stmt->execute()) {
        header("Location: ../list/manage_listsubcat.php?excluido=1");
        exit;
      } else {
        $mensagem = "Erro ao excluir: " . $stmt->error;
      }
      $stmt->close();
    }
  }

  if ($acao === 'salvar' && $titulo && $descricao && $id_categoria > 0) {
    if ($id > 0) {
      $stmt = $mysqli->prepare("UPDATE subcategoria SET Titulo = ?, Descricao = ?, ID_Categoria = ?, UltimaAtualizacao = NOW() WHERE ID = ?");
      $stmt->bind_param("ssii", $titulo, $descricao, $id_categoria, $id);
    } else {
      $stmt = $mysqli->prepare("INSERT INTO subcategoria (Titulo, Descricao, ID_Categoria, UltimaAtualizacao) VALUES (?, ?, ?, NOW())");
      $stmt->bind_param("ssi", $titulo, $descricao, $id_categoria);
    }

    if ($stmt->execute()) {
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


if (isset($_GET["sucesso"]) && $_GET["sucesso"] == "1") {
  $mensagem = $modo_edicao ? "Subcategoria atualizada com sucesso!" : "Subcategoria adicionada com sucesso!";
}
?>


<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <title><?php echo $modo_edicao ? "Editar Subcategoria" : "Nova Subcategoria"; ?></title>
  <link rel="stylesheet" href="style_manage_add.css">
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
          <input type="text" name="titulo" value="<?php echo htmlspecialchars($titulo); ?>" required>
        </label>

        <label>Descrição:
          <textarea name="descricao" rows="5" required><?php echo htmlspecialchars($descricao); ?></textarea>
        </label>

        <label>Categoria:
          <select name="id_categoria" required>
            <option value="">Selecione uma categoria</option>
            <?php foreach ($categorias as $cat): ?>
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
    </form>

    <script src="script_manage_add.js"></script>
  </div>

</body>

</html>