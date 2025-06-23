<?php
require_once 'conexao.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID do serviço inválido.");
}
$id_servico = intval($_GET['id']);

$query = "
    SELECT 
        s.*, 
        sub.Titulo as subcategoria_titulo, 
        cat.Titulo as categoria_titulo,
        cat.ID as categoria_id,
        d.ID as diretriz_id, d.Titulo as diretriz_titulo, i_dir.Conteudo as item_diretriz_conteudo,
        p.ID as padrao_id, p.Titulo as padrao_titulo, i_pad.Conteudo as item_padrao_conteudo,
        ck.NomeItem as checklist_item, ck.Observacao as checklist_obs
    FROM servico s
    LEFT JOIN subcategoria sub ON s.ID_SubCategoria = sub.ID
    LEFT JOIN categoria cat ON sub.ID_Categoria = cat.ID
    LEFT JOIN diretriz d ON s.ID = d.ID_Servico
    LEFT JOIN itemdiretriz i_dir ON d.ID = i_dir.ID_Diretriz
    LEFT JOIN padrao p ON s.ID = p.ID_Servico
    LEFT JOIN itempadrao i_pad ON p.ID = i_pad.ID_Padrao
    LEFT JOIN checklist ck ON s.ID = ck.ID_Servico
    WHERE s.ID = ?
    ORDER BY d.ID, i_dir.ID, p.ID, i_pad.ID, ck.ID
";

$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $id_servico);
$stmt->execute();
$result = $stmt->get_result();

$servico = null;
$diretrizes = [];
$padroes = [];
$checklist = [];

while ($row = $result->fetch_assoc()) {
    if ($servico === null) {
        $servico = $row;
    }

    if (!empty($row['diretriz_id']) && !isset($diretrizes[$row['diretriz_id']])) {
        $diretrizes[$row['diretriz_id']] = ['titulo' => $row['diretriz_titulo'], 'itens' => []];
    }
    if (!empty($row['item_diretriz_conteudo'])) {
        $diretrizes[$row['diretriz_id']]['itens'][] = $row['item_diretriz_conteudo'];
    }

    if (!empty($row['padrao_id']) && !isset($padroes[$row['padrao_id']])) {
        $padroes[$row['padrao_id']] = ['titulo' => $row['padrao_titulo'], 'itens' => []];
    }
    if (!empty($row['item_padrao_conteudo'])) {
        $padroes[$row['padrao_id']]['itens'][] = $row['item_padrao_conteudo'];
    }

    if (!empty($row['checklist_item']) && !in_array($row, $checklist)) {
        $checklist[] = ['NomeItem' => $row['checklist_item'], 'Observacao' => $row['checklist_obs']];
    }
}
$stmt->close();

if (!$servico) {
    die("Serviço não encontrado.");
}
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
                <a href="../chamado/abrir_chamado.php?servico_id=<?= $servico['ID'] ?>" class="btn btn-primary">Criar Chamado</a>
            </div>
        </div>

        <h2 class="section-title">Descrição do Serviço</h2>
        <p><?= nl2br(htmlspecialchars($servico['Descricao'] ?? 'Descrição não informada.')) ?></p>

        <h2 class="section-title">Detalhes e Parâmetros</h2>
        <div class="info-grid">
            <div class="info-item">
                <strong>Área Especialista:</strong>
                <span><?= htmlspecialchars($servico['area_especialista'] ?? 'Não informado') ?></span>
            </div>
            <div class="info-item">
                <strong>PO Responsável:</strong>
                <span><?= htmlspecialchars($servico['po_responsavel'] ?? 'Não informado') ?></span>
            </div>
            <div class="info-item" style="grid-column: 1 / -1;">
                <strong>Base de Conhecimento (KB):</strong>
                <?php if (!empty($servico['KBs']) && filter_var($servico['KBs'], FILTER_VALIDATE_URL)): ?>
                    <a href="<?= htmlspecialchars($servico['KBs']) ?>" target="_blank">Acessar Documentação</a>
                <?php else: ?>
                    <span><?= htmlspecialchars($servico['KBs'] ?? 'Nenhum KB informado') ?></span>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($diretrizes)): ?>
            <h2 class="section-title">Diretrizes</h2>
            <?php foreach ($diretrizes as $dir): ?>
                <div class="grupo">
                    <h4><?= htmlspecialchars($dir['titulo']) ?></h4>
                    <?php if (!empty($dir['itens'])): ?>
                        <ul>
                            <?php foreach ($dir['itens'] as $item): ?>
                                <li><?= htmlspecialchars($item) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($padroes)): ?>
            <h2 class="section-title">Padrões</h2>
            <?php foreach ($padroes as $pad): ?>
                <div class="grupo">
                    <h4><?= htmlspecialchars($pad['titulo']) ?></h4>
                    <?php if (!empty($pad['itens'])): ?>
                        <ul>
                            <?php foreach ($pad['itens'] as $item): ?>
                                <li><?= htmlspecialchars($item) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($checklist)): ?>
            <h2 class="section-title">Checklist de Verificação</h2>
            <div class="grupo">
                <ul>
                    <?php foreach ($checklist as $item): ?>
                        <li>
                            <strong><?= htmlspecialchars($item['NomeItem']) ?>:</strong>
                            <?= htmlspecialchars($item['Observacao']) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <h2 class="section-title">Outras Informações</h2>
        <div class="grupo">
            <h4>Alçadas</h4>
            <p><?= nl2br(htmlspecialchars($servico['alcadas'] ?? 'Não informado.')) ?></p>
        </div>
        <div class="grupo">
            <h4>Procedimento de Exceção</h4>
            <p><?= nl2br(htmlspecialchars($servico['procedimento_excecao'] ?? 'Não informado.')) ?></p>
        </div>
        <div class="grupo">
            <h4>Observações Gerais</h4>
            <p><?= nl2br(htmlspecialchars($servico['observacoes'] ?? 'Não informado.')) ?></p>
        </div>
    </div>
</body>

</html>