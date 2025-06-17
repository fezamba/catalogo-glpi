<?php
require_once '../../conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_revisor'])) {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    if (!empty($nome) && !empty($email)) {
        $stmt = $mysqli->prepare("INSERT INTO revisores (nome, email) VALUES (?, ?)");
        $stmt->bind_param("ss", $nome, $email);
        $stmt->execute();
        $stmt->close();
        header("Location: manage_rev.php?sucesso=1");
        exit;
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];
    $stmt = $mysqli->prepare("DELETE FROM revisores WHERE ID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_rev.php?sucesso=1");
    exit;
}

$revisores = $mysqli->query("SELECT * FROM revisores ORDER BY nome ASC");
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Gerenciamento de Revisores</title>
    <link rel="stylesheet" href="manage_rev.css">
</head>

<body>
    <div class="container">
        <div class="top-bar">
            <h2>Gerenciamento de Revisores</h2>
            <div class="button-group">
                <a href="../manage.php" class="btn-voltar">← Voltar</a>
            </div>
        </div>

        <div class="form-add-revisor">
            <h3>Cadastrar Novo Revisor</h3>
            <form method="post">
                <div class="form-group">
                    <input type="text" name="nome" placeholder="Nome completo do Revisor" required>
                    <input type="email" name="email" placeholder="E-mail do Revisor" required>
                    <button type="submit" name="add_revisor" class="btn-criar">Adicionar</button>
                </div>
            </form>
        </div>

        <div class="table-wrapper">
            <table class="tabela-revisores">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($revisores && $revisores->num_rows > 0): ?>
                        <?php while ($revisor = $revisores->fetch_assoc()) : ?>
                            <tr>
                                <td><?= $revisor['ID'] ?></td>
                                <td><?= htmlspecialchars($revisor['nome']) ?></td>
                                <td><?= htmlspecialchars($revisor['email']) ?></td>
                                <td class="actions">
                                    <a href="manage_rev.php?delete_id=<?= $revisor['ID'] ?>" class="btn-danger" onclick="return confirm('Tem certeza que deseja excluir este revisor?');">Excluir</a>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
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