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
        header("Location: manage_rev.php");
        exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];
    $stmt = $mysqli->prepare("DELETE FROM revisores WHERE ID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_rev.php");
    exit;
}

$revisores = $mysqli->query("SELECT * FROM revisores ORDER BY nome ASC");
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciamento de Revisores</title>
    <link rel="stylesheet" href="../pos/manage_pos.css"> </head>
<body>
    <div class="container">
        <h1>Gerenciamento de Revisores</h1>
        <a href="../manage_index.php" class="btn-back">← Voltar</a>

        <div class="form-container">
            <h2>Cadastrar Novo Revisor</h2>
            <form method="post">
                <input type="text" name="nome" placeholder="Nome completo do Revisor" required>
                <input type="email" name="email" placeholder="E-mail do Revisor" required>
                <button type="submit" name="add_revisor">Adicionar</button>
            </form>
        </div>

        <div class="table-container">
            <h2>Revisores Cadastrados</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($revisor = $revisores->fetch_assoc()) : ?>
                        <tr>
                            <td><?= $revisor['ID'] ?></td>
                            <td><?= htmlspecialchars($revisor['nome']) ?></td>
                            <td><?= htmlspecialchars($revisor['email']) ?></td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="delete_id" value="<?= $revisor['ID'] ?>">
                                    <button type="submit" class="btn-excluir" onclick="return confirm('Tem certeza?');">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>