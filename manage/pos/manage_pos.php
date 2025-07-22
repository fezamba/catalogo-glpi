<?php
// Inclui o arquivo de conexão com o banco de dados.
require_once '../../conexao.php';
// Verifica se a conexão foi bem-sucedida.
if ($mysqli->connect_errno) {
    die("Erro ao conectar: " . $mysqli->connect_error);
}

// Inicializa a variável de mensagem para feedback ao usuário.
$mensagem = '';

// --- Lógica de Exclusão ---
// Verifica se um 'delete_id' foi passado via GET e se é um número.
if (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
    $id_para_excluir = intval($_GET['delete_id']); // Garante que o ID é um inteiro.
    // Prepara e executa a query de exclusão. Usar prepared statements previne SQL Injection.
    $stmt = $mysqli->prepare("DELETE FROM pos WHERE ID = ?");
    $stmt->bind_param("i", $id_para_excluir);
    $stmt->execute();
    $stmt->close();
    // Redireciona para a mesma página com um parâmetro de sucesso para exibir a mensagem.
    header("Location: manage_pos.php?sucesso=excluido");
    exit; // Encerra o script após o redirecionamento.
}

// --- Lógica de Adição (Cadastro) ---
// Verifica se a requisição é do tipo POST e se os campos 'nome' e 'email' foram enviados.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome']) && isset($_POST['email'])) {
    // Remove espaços em branco do início e do fim dos campos.
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);

    // Valida se os campos não estão vazios.
    if (!empty($nome) && !empty($email)) {
        // Passo 1: Verificar se o nome ou e-mail já existem no banco para evitar duplicatas.
        $stmt_check = $mysqli->prepare("SELECT nome, email FROM pos WHERE nome = ? OR email = ?");
        $stmt_check->bind_param("ss", $nome, $email);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        // Se encontrou algum registro, significa que há uma duplicata.
        if ($result_check->num_rows > 0) {
            $existente = $result_check->fetch_assoc();
            // Compara (ignorando maiúsculas/minúsculas) para ver qual campo é o duplicado.
            if (strcasecmp($existente['nome'], $nome) == 0) {
                $mensagem = "<div class='mensagem erro'>Erro: O nome '<strong>" . htmlspecialchars($nome) . "</strong>' já está cadastrado.</div>";
            } else if (strcasecmp($existente['email'], $email) == 0) {
                $mensagem = "<div class='mensagem erro'>Erro: O e-mail '<strong>" . htmlspecialchars($email) . "</strong>' já está cadastrado.</div>";
            }
        } else {
            // Se não houver duplicatas, prossegue com a inserção.
            $stmt = $mysqli->prepare("INSERT INTO pos (nome, email) VALUES (?, ?)");
            $stmt->bind_param("ss", $nome, $email);
            if ($stmt->execute()) {
                // Se a inserção for bem-sucedida, redireciona com mensagem de sucesso.
                header("Location: manage_pos.php?sucesso=adicionado");
                exit;
            } else {
                $mensagem = "<div class='mensagem erro'>Erro ao cadastrar o PO.</div>";
            }
            $stmt->close();
        }
        $stmt_check->close();
    } else {
        $mensagem = "<div class='mensagem erro'>Nome e e-mail são obrigatórios.</div>";
    }
}

// --- Lógica de Mensagens de Feedback (após redirecionamento) ---
if (isset($_GET['sucesso'])) {
    if ($_GET['sucesso'] == 'adicionado') $mensagem = "<div class='mensagem'>PO cadastrado com sucesso!</div>";
    if ($_GET['sucesso'] == 'excluido') $mensagem = "<div class='mensagem'>PO excluído com sucesso!</div>";
}

// --- Busca de Dados para Listagem ---
// Busca todos os POs cadastrados, ordenados por nome.
$result = $mysqli->query("SELECT * FROM pos ORDER BY nome ASC");
// Pega todos os resultados e os coloca em um array associativo.
$pos = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciamento de Product Owners (POs)</title>
    <link rel="stylesheet" href="../../css/manage_pos.css"> 
</head>
<body>

<div class="container">
    <div class="top-bar">
        <h2>Gerenciamento de Product Owners (POs)</h2>
        <div class="button-group">
            <a href="../manage.php" class="btn-voltar">← Voltar</a>
        </div>
    </div>

    <!-- Exibe a mensagem de feedback (sucesso ou erro) para o usuário. -->
    <?= $mensagem ?>

    <!-- Formulário para adicionar um novo PO. -->
    <div class="form-add-po">
        <h3>Cadastrar Novo PO</h3>
        <form method="post" action="manage_pos.php">
            <div class="form-group">
                <input type="text" name="nome" maxlength="255" placeholder="Nome completo do PO" required>
                <input type="email" name="email" maxlength="255" placeholder="E-mail do PO" required>
                <button type="submit" class="btn-criar">Adicionar</button>
            </div>
        </form>
    </div>

    <!-- Tabela para listar os POs existentes. -->
    <div class="table-wrapper">
        <table class="tabela-pos">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <!-- Verifica se há POs para listar. -->
                <?php if (count($pos) > 0): ?>
                    <!-- Itera sobre o array de POs e exibe cada um em uma linha. -->
                    <?php foreach ($pos as $po): ?>
                        <tr>
                            <td><?= $po['ID'] ?></td>
                            <td><?= htmlspecialchars($po['nome']) ?></td>
                            <td><?= htmlspecialchars($po['email']) ?></td>
                            <td class="actions">
                                <!-- Link para excluir o PO, com confirmação via JavaScript. -->
                                <a href="manage_pos.php?delete_id=<?= $po['ID'] ?>" class="btn-danger" onclick="return confirm('Tem certeza que deseja excluir este PO?');">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Se não houver POs, exibe uma mensagem na tabela. -->
                    <tr>
                        <td colspan="4">Nenhum PO cadastrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
