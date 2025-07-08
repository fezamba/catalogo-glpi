<?php
require_once 'conexao.php';

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
$stmt_dir = $mysqli->prepare("SELECT ID, Titulo FROM diretriz WHERE ID_Servico = ? ORDER BY ID");
$stmt_dir->bind_param("i", $id_servico);
$stmt_dir->execute();
$result_dir = $stmt_dir->get_result();
while ($d = $result_dir->fetch_assoc()) {
    $stmt_item_dir = $mysqli->prepare("SELECT Conteudo FROM itemdiretriz WHERE ID_Diretriz = ? ORDER BY ID");
    $stmt_item_dir->bind_param("i", $d['ID']);
    $stmt_item_dir->execute();
    $result_item_dir = $stmt_item_dir->get_result();
    $d['itens'] = $result_item_dir->fetch_all(MYSQLI_ASSOC);
    $diretrizes[] = $d;
    $stmt_item_dir->close();
}
$stmt_dir->close();

$padroes = [];
$stmt_pad = $mysqli->prepare("SELECT ID, Titulo FROM padrao WHERE ID_Servico = ? ORDER BY ID");
$stmt_pad->bind_param("i", $id_servico);
$stmt_pad->execute();
$result_pad = $stmt_pad->get_result();
while ($p = $result_pad->fetch_assoc()) {
    $stmt_item_pad = $mysqli->prepare("SELECT Conteudo FROM itempadrao WHERE ID_Padrao = ? ORDER BY ID");
    $stmt_item_pad->bind_param("i", $p['ID']);
    $stmt_item_pad->execute();
    $result_item_pad = $stmt_item_pad->get_result();
    $p['itens'] = $result_item_pad->fetch_all(MYSQLI_ASSOC);
    $padroes[] = $p;
    $stmt_item_pad->close();
}
$stmt_pad->close();

$checklist = [];
$stmt_check = $mysqli->prepare("SELECT NomeItem, Observacao FROM checklist WHERE ID_Servico = ? ORDER BY ID");
$stmt_check->bind_param("i", $id_servico);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
$checklist = $result_check->fetch_all(MYSQLI_ASSOC);
$stmt_check->close();
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Visualizar: <?= htmlspecialchars($servico['Titulo'] ?? 'Serviço') ?></title>
    <link rel="stylesheet" href="css/view_servico.css">
    <style>
        .chamado-form-container {
            margin-top: 40px;
            padding: 25px;
            border-top: 2px solid #f0ad4e;
            background-color: #fcfcfc;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }
        .chamado-form-container .section-title {
            margin-top: 0;
            border-bottom: none;
            padding-bottom: 0;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 1.1em;
            color: #333;
        }
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-family: inherit;
            font-size: 1em;
            line-height: 1.5;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .form-group textarea:focus {
            border-color: #f0ad4e;
            box-shadow: 0 0 0 3px rgba(240, 173, 78, 0.2);
            outline: none;
        }
        .form-group .btn-primary {
            background-color: #f0ad4e;
            color: white;
            padding: 12px 25px;
            font-size: 1.1em;
            font-weight: bold;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .form-group .btn-primary:hover {
            background-color: #ec971f;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
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
                <?php
                $kb_link = $servico['KBs'] ?? '';
                if (!empty($kb_link) && filter_var($kb_link, FILTER_VALIDATE_URL)) :
                ?>
                    <a href="<?= htmlspecialchars($kb_link) ?>" target="_blank"><?= htmlspecialchars($kb_link) ?></a>
                <?php else: ?>
                    <span><?= htmlspecialchars($servico['KBs'] ?? 'Nenhum KB informado') ?></span>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($diretrizes)): ?>
            <h2 class="section-title">Diretrizes</h2>
            <?php foreach ($diretrizes as $dir): ?>
                <div class="grupo">
                    <h4><?= htmlspecialchars($dir['Titulo']) ?></h4>
                    <?php if (!empty($dir['itens'])): ?>
                        <ul>
                            <?php foreach ($dir['itens'] as $item): ?>
                                <li><?= htmlspecialchars($item['Conteudo']) ?></li>
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
                    <h4><?= htmlspecialchars($pad['Titulo']) ?></h4>
                    <?php if (!empty($pad['itens'])): ?>
                        <ul>
                            <?php foreach ($pad['itens'] as $item): ?>
                                <li><?= htmlspecialchars($item['Conteudo']) ?></li>
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

        <div class="chamado-form-container">
            <h2 class="section-title">Abrir um Chamado para este Serviço</h2>
            <form action="../chamado/processar_chamado.php" method="POST">
                
                <input type="hidden" name="servico_id" value="<?= $servico['ID'] ?>">
                
                <div class="form-group">
                    <label for="descricao_chamado">Descreva sua solicitação:</label>
                    <textarea id="descricao_chamado" name="descricao_chamado" rows="5" required placeholder="Forneça detalhes sobre o seu problema ou solicitação..."></textarea>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Criar Chamado</button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>
