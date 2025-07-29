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

$sugestoes = [];
$stmt_sugestoes = $mysqli->prepare("SELECT autor_sugestao, data_sugestao, texto_sugestao FROM sugestoes WHERE servico_id = ? ORDER BY data_sugestao DESC");
$stmt_sugestoes->bind_param("i", $id_servico);
$stmt_sugestoes->execute();
$result_sugestoes = $stmt_sugestoes->get_result();
while ($sugestao = $result_sugestoes->fetch_assoc()) {
    $sugestoes[] = $sugestao;
}
$stmt_sugestoes->close();
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar: <?= htmlspecialchars($servico['Titulo'] ?? 'Serviço') ?></title>
    <link rel="stylesheet" href="css/view_servico.css">
    <style>
        .wrapper {
            max-width: 900px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .card {
            background-color: #fcfcfc;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            border-top: 2px solid #eee;
        }
        .card.header-card {
            border-top: 2px solid #f0ad4e;
        }

        .btn-back {
            display: inline-block;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            color: #212529;
            font-weight: 500;
        }
        .btn-back:hover {
            background-color: #e9ecef;
        }

        .breadcrumb {
            font-size: 0.9rem;
            color: #6c757d;
            margin: 0 0 10px 0;
        }
        .breadcrumb a {
            color: inherit;
            text-decoration: none;
        }
        .breadcrumb a:hover {
            text-decoration: underline;
        }

        h1 {
            font-size: 2.2rem;
            margin: 0 0 10px 0;
        }

        .meta {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .section-title {
            font-size: 1.5rem;
            border-bottom: 2px solid #f0ad4e;
            padding-bottom: 10px;
            margin-top: 0;
            margin-bottom: 20px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .info-item strong {
            display: block;
            margin-bottom: 5px;
        }
        .info-item a {
            color: #007bff;
        }

        .grupo {
            margin-bottom: 20px;
        }
        .grupo:last-child {
            margin-bottom: 0;
        }
        .grupo h4 {
            font-size: 1.2rem;
            margin-bottom: 10px;
        }
        .grupo ul {
            padding-left: 20px;
            margin: 0;
        }
        .grupo li {
            margin-bottom: 8px;
        }
        .grupo p {
            margin-top: 0;
        }

        .chamado-form-container {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
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
            box-sizing: border-box;
        }
        .form-group textarea:focus {
            border-color: #f0ad4e;
            box-shadow: 0 0 0 3px rgba(240, 173, 78, 0.2);
            outline: none;
        }
        .btn-primary {
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
        .btn-primary:hover {
            background-color: #ec971f;
        }

        .sugestoes-container {
            margin-top: 20px;
        }
        .sugestao-item {
            background-color: #f9fafb;
            border: 1px solid #e0e6ed;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .sugestao-meta {
            font-size: 0.85rem;
            color: #667;
            margin-bottom: 8px;
        }
        .sugestao-meta strong {
            color: #333;
        }
        .sugestao-texto {
            font-size: 0.95rem;
            line-height: 1.6;
            color: #444;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <div>
            <a href="subcategoria.php?id=<?= $servico['ID_SubCategoria'] ?? '0' ?>" class="btn-back">← Voltar ao Catálogo</a>
        </div>

        <div class="card header-card">
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

        <div class="card">
            <h2 class="section-title">Descrição do Serviço</h2>
            <p><?= nl2br(htmlspecialchars($servico['Descricao'] ?? 'Descrição não informada.')) ?></p>
        </div>

        <div class="card">
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
                        <a href="<?= htmlspecialchars($kb_link) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($kb_link) ?></a>
                    <?php else: ?>
                        <span><?= htmlspecialchars($servico['KBs'] ?? 'Nenhum KB informado') ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (!empty($diretrizes)): ?>
            <div class="card">
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
            </div>
        <?php endif; ?>

        <div class="card">
            <h2 class="section-title">Alçadas</h2>
            <div class="grupo">
                <p><?= nl2br(htmlspecialchars($servico['alcadas'] ?? 'Não informado.')) ?></p>
            </div>
        </div>

        <?php if (!empty($padroes)): ?>
            <div class="card">
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
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2 class="section-title">Procedimento de Exceção</h2>
            <div class="grupo">
                <p><?= nl2br(htmlspecialchars($servico['procedimento_excecao'] ?? 'Não informado.')) ?></p>
            </div>
        </div>

        <?php if (!empty($checklist)): ?>
            <div class="card">
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
            </div>
        <?php endif; ?>

        <div class="card">
            <h2 class="section-title">Abrir um Chamado para este Serviço</h2>
            <form action="../chamado/processar_chamado.php" method="POST">
                <input type="hidden" name="servico_id" value="<?= $servico['ID'] ?>">
                <div class="form-group">
                    <label for="descricao_chamado">Descreva sua solicitação:</label>
                    <textarea id="descricao_chamado" name="descricao_chamado" rows="5" required placeholder="Forneça detalhes sobre o seu problema ou solicitação..."></textarea>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <button type="submit" class="btn-primary">Criar Chamado</button>
                </div>
            </form>
        </div>
        
        <div class="card">
            <h2 class="section-title">Sugestões de Melhoria</h2>
            <div class="chamado-form-container" style="margin-top: 0; border-top: none; padding-top: 10px;">
                <form action="processar_sugestao.php" method="POST">
                    <input type="hidden" name="servico_id" value="<?= $servico['ID'] ?>">
                    <div class="form-group">
                        <label for="texto_sugestao">Deixe sua sugestão para este serviço:</label>
                        <textarea id="texto_sugestao" name="texto_sugestao" rows="4" required placeholder="Escreva aqui sua sugestão de melhoria..."></textarea>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <button type="submit" class="btn-primary">Enviar Sugestão</button>
                    </div>
                </form>
            </div>

            <?php if (count($sugestoes) > 0): ?>
                <div class="sugestoes-container">
                    <?php foreach ($sugestoes as $sugestao): ?>
                        <div class="sugestao-item">
                            <p class="sugestao-meta">
                                <strong><?= htmlspecialchars($sugestao['autor_sugestao']) ?></strong> em 
                                <?= date('d/m/Y \à\s H:i', strtotime($sugestao['data_sugestao'])) ?>
                            </p>
                            <p class="sugestao-texto"><?= nl2br(htmlspecialchars($sugestao['texto_sugestao'])) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="text-align: center; margin-top: 20px;">Ainda não há sugestões para este serviço. Seja o primeiro a contribuir!</p>
            <?php endif; ?>
        </div>

    </div>
</body>
</html>
