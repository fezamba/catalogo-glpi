<?php
//$mysqli = new mysqli("localhost", "root", "sefazfer123@", "catalogo-teste");
require_once 'conexao.php';
if ($mysqli->connect_errno) {
    die("Erro de conexão: " . $mysqli->connect_error);
}

// 1. Validar e buscar o ID do serviço da URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID do serviço inválido.");
}
$id_servico = intval($_GET['id']);

// 2. Buscar os dados principais do serviço e nomes da Categoria/Subcategoria
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

// 3. Buscar todos os dados relacionados (listas)
$atendimentos = $mysqli->query("SELECT atendimento, descricao_tecnica FROM servico_atendimento WHERE id_servico = $id_servico ORDER BY FIELD(atendimento, 'N1', 'N2', 'N3', 'WD')")->fetch_all(MYSQLI_ASSOC);
$software = $mysqli->query("SELECT nome_software, versao_software FROM servico_software WHERE id_servico = $id_servico LIMIT 1")->fetch_assoc();
$sistema_info = $mysqli->query("SELECT si.nome_sistema, se.nome_equipe FROM servico_sistema si LEFT JOIN servico_equipe_externa se ON si.id = se.id_sistema WHERE si.id_servico = $id_servico LIMIT 1")->fetch_assoc();
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
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; color: #333; margin: 0; padding: 20px; }
        .wrapper { max-width: 900px; margin: 0 auto; background-color: #fff; padding: 30px 40px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .btn-back { display: inline-block; margin-bottom: 20px; padding: 10px 18px; background-color: #e0e0e0; color: #333; text-decoration: none; border-radius: 6px; font-weight: bold; transition: background-color 0.2s; }
        .btn-back:hover { background-color: #d1d1d1; }
        .header { border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 25px; }
        .header h1 { margin: 0; font-size: 28px; }
        .header .meta { font-size: 14px; color: #777; margin-top: 5px; }
        .header .meta strong { color: #000; }
        .header .breadcrumb a { color: #007bff; text-decoration: none; }
        .header .breadcrumb a:hover { text-decoration: underline; }
        
        .section-title { font-size: 20px; color: #333; border-bottom: 3px solid #f9b000; padding-bottom: 8px; margin-top: 35px; margin-bottom: 20px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px 30px; }
        .info-item strong { display: block; font-size: 14px; color: #555; margin-bottom: 4px; }
        .info-item span, .info-item p { font-size: 16px; color: #000; margin: 0; line-height: 1.6; }
        
        .grupo { background-color: #f8f9fa; border-left: 4px solid #ccc; margin-bottom: 15px; padding: 15px 20px; border-radius: 0 8px 8px 0; }
        .grupo h4 { margin: 0 0 10px 0; font-size: 16px; }
        .grupo p, .grupo li { font-size: 15px; line-height: 1.6; }
        .grupo ul { padding-left: 20px; margin: 0; }
    </style>
</head>
<body>
    <div class="wrapper">
        <a href="index.php" class="btn-back">← Voltar ao Catálogo</a>

        <div class="header">
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

        <h2 class="section-title">Descrição do Serviço</h2>
        <p style="line-height: 1.6;"><?= nl2br(htmlspecialchars($servico['Descricao'] ?? 'Descrição não informada.')) ?></p>

        <h2 class="section-title">Detalhes e Parâmetros</h2>
        <div class="info-grid">
            <div class="info-item"><strong>Tipo de Ficha:</strong> <span><?= htmlspecialchars($servico['tipo'] ?? '-') ?></span></div>
            <div class="info-item"><strong>Área Especialista:</strong> <span><?= htmlspecialchars($servico['area_especialista'] ?? '-') ?></span></div>
            <div class="info-item"><strong>PO Responsável:</strong> <span><?= htmlspecialchars($servico['po_responsavel'] ?? '-') ?></span></div>
            <div class="info-item"><strong>Base de Conhecimento (KBs):</strong> <span><?= htmlspecialchars($servico['KBs'] ?? 'Nenhum KB informado') ?></span></div>
            <?php if ($software): ?>
                <div class="info-item"><strong>Software:</strong> <span><?= htmlspecialchars($software['nome_software'] ?? '-') ?> (v<?= htmlspecialchars($software['versao_software'] ?? '-') ?>)</span></div>
            <?php endif; ?>
            <?php if ($sistema_info): ?>
                <div class="info-item"><strong>Sistema/Portal:</strong> <span><?= htmlspecialchars($sistema_info['nome_sistema'] ?? '-') ?></span></div>
                <?php if (!empty($sistema_info['nome_equipe'])): ?>
                    <div class="info-item"><strong>Equipe Externa:</strong> <span><?= htmlspecialchars($sistema_info['nome_equipe']) ?></span></div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <?php if (!empty($atendimentos)): ?>
            <h2 class="section-title">Procedimento por Equipe de Atendimento</h2>
            <?php foreach ($atendimentos as $att): ?>
                <div class="grupo">
                    <h4><?= htmlspecialchars($att['atendimento'] ?? 'Equipe N/A') ?></h4>
                    <p><?= nl2br(htmlspecialchars($att['descricao_tecnica'] ?? '')) ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($diretrizes)): ?>
            <h2 class="section-title">Diretrizes</h2>
            <?php foreach ($diretrizes as $dir): ?>
                <div class="grupo">
                    <h4><?= htmlspecialchars($dir['Titulo'] ?? 'Diretriz sem título') ?></h4>
                    <?php if (!empty($dir['itens'])): ?>
                        <ul>
                            <?php foreach ($dir['itens'] as $item): ?>
                                <li><?= htmlspecialchars($item['Conteudo'] ?? '') ?></li>
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
                           <strong><?= htmlspecialchars($item['NomeItem'] ?? 'Item sem nome') ?>:</strong>
                           <?= htmlspecialchars($item['Observacao'] ?? 'Sem observação.') ?>
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
        <div class="grupo">
            <h4>Determinação / Orientação / Norma</h4>
            <p><?= nl2br(htmlspecialchars($servico['determinacao_orientacao_norma'] ?? 'Não informado.')) ?></p>
        </div>

        <h2 class="section-title">Histórico de Aprovação</h2>
        <div class="info-grid">
            <div class="info-item"><strong>Usuário Criador:</strong> <span><?= htmlspecialchars($servico['usuario_criador'] ?? '-') ?></span></div>
            <div class="info-item"><strong>Data da Última Atualização:</strong> <span><?= !empty($servico['UltimaAtualizacao']) ? date('d/m/Y H:i:s', strtotime($servico['UltimaAtualizacao'])) : '-' ?></span></div>
            
            <?php if (!empty($servico['revisor_nome'])): ?>
                <div class="info-item"><strong>Revisor:</strong> <span><?= htmlspecialchars($servico['revisor_nome']) ?> (<?= htmlspecialchars($servico['revisor_email'] ?? '-') ?>)</span></div>
                <div class="info-item"><strong>Data da Revisão:</strong> <span><?= !empty($servico['data_revisao']) ? date('d/m/Y H:i:s', strtotime($servico['data_revisao'])) : '-' ?></span></div>
            <?php endif; ?>

            <?php if (!empty($servico['po_aprovador_nome'])): ?>
                <div class="info-item"><strong>Aprovador (PO):</strong> <span><?= htmlspecialchars($servico['po_aprovador_nome']) ?> (<?= htmlspecialchars($servico['po_aprovador_email'] ?? '-') ?>)</span></div>
                <div class="info-item"><strong>Data da Aprovação:</strong> <span><?= !empty($servico['data_aprovacao']) ? date('d/m/Y H:i:s', strtotime($servico['data_aprovacao'])) : '-' ?></span></div>
            <?php endif; ?>
            
            <?php if (($servico['status_ficha'] === 'reprovado_po' || $servico['status_ficha'] === 'reprovado_revisor') && !empty($servico['justificativa_rejeicao'])): ?>
                <div class="info-item" style="grid-column: 1 / -1;"><strong>Justificativa da Rejeição:</strong> <span style="color: #d9534f;"><?= htmlspecialchars($servico['justificativa_rejeicao']) ?></span></div>
            <?php endif; ?>
        </div>

    </div>
</body>
</html>