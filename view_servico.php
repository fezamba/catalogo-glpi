<?php
//$mysqli = new mysqli("localhost", "root", "sefazfer123@", "catalogo-teste");
require_once 'conexao.php';

if ($mysqli->connect_errno) {
    die("Erro de conexão: " . $mysqli->connect_error);
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID do serviço inválido.");
}
$id_servico = intval($_GET['id']);

$query_principal = "
    SELECT 
        s.*, 
        sub.Titulo as subcategoria_titulo, 
        cat.Titulo as categoria_titulo,
        cat.ID as categoria_id
    FROM servico s
    LEFT JOIN subcategoria sub ON s.ID_SubCategoria = sub.ID
    LEFT JOIN categoria cat ON sub.ID_Categoria = cat.ID
    WHERE s.ID = ?
";
$stmt = $mysqli->prepare($query_principal);
$stmt->bind_param("i", $id_servico);
$stmt->execute();
$servico = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$servico) {
    die("Serviço não encontrado.");
}

$diretrizes = [];
$res_dir = $mysqli->query("SELECT ID, Titulo FROM diretriz WHERE ID_Servico = $id_servico ORDER BY ID");
while ($d = $res_dir->fetch_assoc()) {
    $res_item_dir = $mysqli->query("SELECT Conteudo FROM itemdiretriz WHERE ID_Diretriz = {$d['ID']} ORDER BY ID");
    $d['itens'] = $res_item_dir->fetch_all(MYSQLI_ASSOC);
    $diretrizes[] = $d;
}
$checklist = $mysqli->query("SELECT NomeItem, Observacao FROM checklist WHERE ID_Servico = $id_servico ORDER BY ID")->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Visualizar: <?= htmlspecialchars($servico['Titulo'] ?? 'Serviço') ?></title>
    <link rel="stylesheet" href="view_servico.css">
</head>
<body>
    <div class="wrapper">
        <div style="margin-bottom: 20px;">
            <a href="index.php" class="btn btn-back">← Voltar ao Catálogo</a>
        </div>

        <div class="header">
            <div class="header-info">
                <p class="meta breadcrumb">
                    <a href="index.php">Categorias</a> &gt; 
                    <a href="categoria.php?id=<?= $servico['categoria_id'] ?? '0' ?>"><?= htmlspecialchars($servico['categoria_titulo'] ?? 'N/A') ?></a> &gt; 
                    <?= htmlspecialchars($servico['subcategoria_titulo'] ?? 'N/A') ?>
                </p>
                <h1><?= htmlspecialchars($servico['Titulo'] ?? 'Serviço Sem Título') ?></h1>
                <p class="meta">
                    Ficha: <strong><?= htmlspecialchars($servico['codigo_ficha'] ?? '-') ?></strong> | 
                    Versão: <strong><?= htmlspecialchars($servico['versao'] ?? '-') ?></strong> | 
                    Status: <strong><?= htmlspecialchars(ucfirst($servico['status_ficha'] ?? '-')) ?></strong>
                </p>
            </div>
            <div class="header-actions">
                <a href="/chamado/processar_chamado.php?servico_id=<?= $servico['ID'] ?>" class="btn btn-primary">Criar Chamado</a>
            </div>
        </div>

        <h2 class="section-title">Descrição do Serviço</h2>
        <p style="line-height: 1.6;"><?= nl2br(htmlspecialchars($servico['Descricao'] ?? 'Descrição não informada.')) ?></p>

        </div>
</body>
</html>