<?php
require_once '../../conexao.php';

$mensagem = '';

if (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
    $id_para_excluir = intval($_GET['delete_id']);
    $stmt = $mysqli->prepare("DELETE FROM revisores WHERE ID = ?");
    $stmt->bind_param("i", $id_para_excluir);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_rev.php?sucesso=excluido");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome']) && isset($_POST['email'])) {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);

    if (!empty($nome) && !empty($email)) {
        $stmt = $mysqli->prepare("INSERT INTO revisores (nome, email) VALUES (?, ?)");
        $stmt->bind_param("ss", $nome, $email);
        if ($stmt->execute()) {
            header("Location: manage_rev.php?sucesso=adicionado");
            exit;
        } else {
            $mensagem = ($mysqli->errno === 1062) ? 
                "<div class='mensagem erro'>Erro: Este e-mail já está cadastrado.</div>" : 
                "<div class='mensagem erro'>Erro ao cadastrar o Revisor.</div>";
        }
        $stmt->close();
    } else {
        $mensagem = "<div class='mensagem erro'>Nome e e-mail são obrigatórios.</div>";
    }
}

if (isset($_GET['sucesso'])) {
    if ($_GET['sucesso'] == 'adicionado') $mensagem = "<div class='mensagem'>Revisor cadastrado com sucesso!</div>";
    if ($_GET['sucesso'] == 'excluido') $mensagem = "<div class='mensagem'>Revisor excluído com sucesso!</div>";
}

$result = $mysqli->query("SELECT * FROM revisores ORDER BY nome ASC");
$revisores = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciamento de Revisores</title>
    <link rel="stylesheet" href="../pos/manage_pos.css"> 
</head>
<body>

<div class="container">
    <div class="top-bar">
        <h2>Gerenciamento de Revisores</h2>
        <div class="button-group">
            <a href="../manage.php" class="btn-voltar">← Voltar</a>
        </div>
    </div>

    <?= $mensagem ?>

    <div class="form-add-po"> <h3>Cadastrar Novo Revisor</h3>
        <form method="post" action="manage_rev.php">
            <div class="form-group">
                <input type="text" name="nome" placeholder="Nome completo do Revisor" required>
                <input type="email" name="email" placeholder="E-mail do Revisor" required>
                <button type="submit" class="btn-criar">Adicionar</button>
            </div>
        </form>
    </div>

    <div class="table-wrapper">
        <table class="tabela-pos"> <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($revisores) > 0): ?>
                    <?php foreach ($revisores as $revisor): ?>
                        <tr>
                            <td><?= $revisor['ID'] ?></td>
                            <td><?= htmlspecialchars($revisor['nome']) ?></td>
                            <td><?= htmlspecialchars($revisor['email']) ?></td>
                            <td class="actions">
                                <a href="manage_rev.php?delete_id=<?= $revisor['ID'] ?>" class="btn-danger" onclick="return confirm('Tem certeza que deseja excluir este revisor?');">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">Nenhum revisor cadastrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>