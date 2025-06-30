<?php
session_start();
require_once '../../conexao.php';

if ($mysqli->connect_errno) {
    die("Erro de conexão com o banco de dados: " . $mysqli->connect_error);
}

$_SESSION['username'] = 'Service-Desk/WD';

function fetch_by_id($mysqli, $table, $id)
{
    $stmt = $mysqli->prepare("SELECT * FROM $table WHERE ID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function fetch_all($mysqli, $table, $order_by = 'ID ASC')
{
    $data = [];
    $query = "SELECT * FROM $table";
    if ($order_by) {
        $query .= " ORDER BY $order_by";
    }
    $result = $mysqli->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    return $data;
}

function fetch_related_items($mysqli, $servico_id, $main_table, $item_table, $join_col_main, $join_col_item)
{
    $items = [];
    $query = "
        SELECT m.ID AS main_id, m.Titulo AS main_titulo, i.Conteudo AS item_conteudo
        FROM $main_table m
        LEFT JOIN $item_table i ON m.ID = i.$join_col_item
        WHERE m.$join_col_main = ?
        ORDER BY m.ID, i.ID
    ";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $servico_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if (!isset($items[$row['main_id']])) {
            $items[$row['main_id']] = ['titulo' => $row['main_titulo'], 'itens' => []];
        }
        if ($row['item_conteudo']) {
            $items[$row['main_id']]['itens'][] = $row['item_conteudo'];
        }
    }
    return array_values($items);
}

function fetch_checklist($mysqli, $servico_id)
{
    $checklist = [];
    $stmt = $mysqli->prepare("SELECT NomeItem, Observacao FROM checklist WHERE ID_Servico = ? ORDER BY ID");
    $stmt->bind_param("i", $servico_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $checklist[] = ['item' => $row['NomeItem'], 'observacao' => $row['Observacao']];
    }
    return $checklist;
}

function sync_related_data($mysqli, $servico_id, $data, $main_table, $item_table, $join_col_main, $join_col_item)
{
    $stmt_delete_main = $mysqli->prepare("DELETE FROM $main_table WHERE $join_col_main = ?");
    $stmt_delete_main->bind_param("i", $servico_id);
    $stmt_delete_main->execute();

    if (empty($data)) return;

    $stmt_insert_main = $mysqli->prepare("INSERT INTO $main_table (Titulo, $join_col_main) VALUES (?, ?)");
    $stmt_insert_item = $mysqli->prepare("INSERT INTO $item_table (Conteudo, $join_col_item) VALUES (?, ?)");

    foreach ($data as $main_item) {
        if (empty($main_item['titulo'])) continue;

        $stmt_insert_main->bind_param("si", $main_item['titulo'], $servico_id);
        $stmt_insert_main->execute();
        $main_id = $stmt_insert_main->insert_id;

        if (!empty($main_item['itens'])) {
            foreach ($main_item['itens'] as $item_conteudo) {
                if (!empty($item_conteudo)) {
                    $stmt_insert_item->bind_param("si", $item_conteudo, $main_id);
                    $stmt_insert_item->execute();
                }
            }
        }
    }
}

function sync_checklist_data($mysqli, $servico_id, $checklist_data)
{
    $stmt_delete = $mysqli->prepare("DELETE FROM checklist WHERE ID_Servico = ?");
    $stmt_delete->bind_param("i", $servico_id);
    $stmt_delete->execute();

    if (empty($checklist_data)) return;

    $stmt_insert = $mysqli->prepare("INSERT INTO checklist (ID_Servico, NomeItem, Observacao) VALUES (?, ?, ?)");
    foreach ($checklist_data as $item) {
        if (!empty($item['item'])) {
            $stmt_insert->bind_param("iss", $servico_id, $item['item'], $item['observacao']);
            $stmt_insert->execute();
        }
    }
}

function redirect($location)
{
    header("Location: " . $location);
    exit;
}

function get_status_label($status)
{
    $labels = [
        'rascunho' => '📝 Em Cadastro', 'em_revisao' => '🔍 Em revisão', 'revisada' => '✅ Revisada',
        'em_aprovacao' => '🕒 Em aprovação', 'aprovada' => '☑️ Aprovada', 'publicado' => '📢 Publicado',
        'cancelada' => '🚫 Cancelada', 'reprovado_revisor' => '❌ Reprovado pelo Revisor',
        'reprovado_po' => '❌ Reprovado pelo PO', 'substituida' => '♻️ Substituída', 'descontinuada' => '⏳ Descontinuada'
    ];
    return $labels[$status] ?? '—';
}

$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$modo_edicao = !is_null($id);
$mensagem = '';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['acao'])) {
    $acao = $_POST['acao'];
    $id_post = $modo_edicao ? $id : null;
    $post_data = $_POST;

    switch ($acao) {
        case 'criar_servico':
            $stmt = $mysqli->prepare("INSERT INTO servico (versao, Titulo, Descricao, ID_SubCategoria, KBs, UltimaAtualizacao, area_especialista, po_responsavel, alcadas, procedimento_excecao, observacoes, usuario_criador, status_ficha) VALUES ('1.0', ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, 'rascunho')");
            $stmt->bind_param("ssisssssss", $post_data['nome_servico'], $post_data['descricao_servico'], $post_data['id_subcategoria'], $post_data['base_conhecimento'], $post_data['area_especialista'], $post_data['po_responsavel'], $post_data['alcadas'], $post_data['procedimento_excecao'], $post_data['observacoes_gerais'], $_SESSION['username']);
            $stmt->execute();
            $new_id = $stmt->insert_id;
            
            $codigo_ficha = "FCH-" . str_pad($new_id, 4, "0", STR_PAD_LEFT);
            $mysqli->query("UPDATE servico SET codigo_ficha = '$codigo_ficha' WHERE ID = $new_id");

            sync_related_data($mysqli, $new_id, $post_data['diretrizes'] ?? [], 'diretriz', 'itemdiretriz', 'ID_Servico', 'ID_Diretriz');
            sync_related_data($mysqli, $new_id, $post_data['padroes'] ?? [], 'padrao', 'itempadrao', 'ID_Servico', 'ID_Padrao');
            sync_checklist_data($mysqli, $new_id, $post_data['checklist'] ?? []);

            redirect("?id=$new_id&sucesso=1");
            break;

        case 'excluir':
            $delete_id = intval($_POST['delete_id']);
            $stmt = $mysqli->prepare("DELETE FROM servico WHERE ID = ?");
            $stmt->bind_param("i", $delete_id);
            $stmt->execute();
            redirect("../list/manage_listservico.php?excluido=1");
            break;

        case 'enviar_revisao':
        case 'enviar_revisao_novamente':
        case 'aprovar_revisor':
        case 'aprovar_po':
        case 'reprovar_revisor':
        case 'reprovar_po':
            $status_map = [
                'enviar_revisao' => 'em_revisao', 'enviar_revisao_novamente' => 'em_revisao',
                'aprovar_revisor' => 'revisada', 'aprovar_po' => 'aprovada',
                'reprovar_revisor' => 'reprovado_revisor', 'reprovar_po' => 'reprovado_po'
            ];
            $novo_status = $status_map[$acao];
            $justificativa = in_array($acao, ['reprovar_revisor', 'reprovar_po', 'enviar_revisao_novamente']) ? ($post_data['justificativa'] ?? 'Sem justificativa') : null;

            $sql = "UPDATE servico SET Titulo = ?, Descricao = ?, ID_SubCategoria = ?, KBs = ?, UltimaAtualizacao = NOW(), area_especialista = ?, po_responsavel = ?, alcadas = ?, procedimento_excecao = ?, observacoes = ?, usuario_criador = ?, status_ficha = ?";
            $params = [$post_data['nome_servico'], $post_data['descricao_servico'], $post_data['id_subcategoria'], $post_data['base_conhecimento'], $post_data['area_especialista'], $post_data['po_responsavel'], $post_data['alcadas'], $post_data['procedimento_excecao'], $post_data['observacoes_gerais'], $_SESSION['username'], $novo_status];
            $types = "ssissssssss";

            if ($justificativa) { $sql .= ", justificativa_rejeicao = ?"; $params[] = $justificativa; $types .= "s"; }
            if ($acao === 'aprovar_revisor') { $sql .= ", data_revisao = NOW()"; }
            if ($acao === 'aprovar_po') { $sql .= ", data_aprovacao = NOW()"; }
            $sql .= " WHERE ID = ?";
            $params[] = $id_post;
            $types .= "i";

            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();

            sync_related_data($mysqli, $id_post, $post_data['diretrizes'] ?? [], 'diretriz', 'itemdiretriz', 'ID_Servico', 'ID_Diretriz');
            sync_related_data($mysqli, $id_post, $post_data['padroes'] ?? [], 'padrao', 'itempadrao', 'ID_Servico', 'ID_Padrao');
            sync_checklist_data($mysqli, $id_post, $post_data['checklist'] ?? []);

            if ($acao === 'enviar_revisao') {
                $mysqli->query("DELETE FROM servico_revisores WHERE servico_id = $id_post");
                if (!empty($post_data['revisores_ids'])) {
                    $stmt_assign = $mysqli->prepare("INSERT INTO servico_revisores (servico_id, revisor_id) VALUES (?, ?)");
                    foreach ($post_data['revisores_ids'] as $revisor_id) {
                        $stmt_assign->bind_param("ii", $id_post, $revisor_id);
                        $stmt_assign->execute();
                    }
                }
            }
            redirect("?id=$id_post&sucesso=1");
            break;

        case 'enviar_para_aprovacao':
        case 'cancelar_ficha':
        case 'publicar_ficha':
            $status_map_simple = ['enviar_para_aprovacao' => 'em_aprovacao', 'cancelar_ficha' => 'cancelada', 'publicar_ficha' => 'publicado'];
            $novo_status = $status_map_simple[$acao];
            $stmt = $mysqli->prepare("UPDATE servico SET status_ficha = ? WHERE ID = ?");
            $stmt->bind_param("si", $novo_status, $id_post);
            $stmt->execute();
            redirect("?id=$id_post&sucesso=1");
            break;
    }
}

if (isset($_GET["sucesso"])) { $mensagem = $modo_edicao ? "Serviço atualizado com sucesso!" : "Serviço salvo com sucesso!"; }
if (isset($_GET["excluido"])) { $mensagem = "Serviço excluído com sucesso!"; }

$dados_edicao = [];
$diretrizes = [];
$padroes = [];
$checklist = [];
$revisores_servico = [];

if ($modo_edicao) {
    $dados_edicao = fetch_by_id($mysqli, 'servico', $id);
    if (!$dados_edicao) { die("Serviço não encontrado."); }
    if (isset($_GET['forcar_status'])) { $dados_edicao['status_ficha'] = $_GET['forcar_status']; }

    $diretrizes = fetch_related_items($mysqli, $id, 'diretriz', 'itemdiretriz', 'ID_Servico', 'ID_Diretriz');
    $padroes = fetch_related_items($mysqli, $id, 'padrao', 'itempadrao', 'ID_Servico', 'ID_Padrao');
    $checklist = fetch_checklist($mysqli, $id);
    $revisores_servico_raw = fetch_all($mysqli, 'servico_revisores WHERE servico_id = ' . $id, null);
    $revisores_servico = array_column($revisores_servico_raw, 'revisor_id');
}

$subcategorias = fetch_all($mysqli, 'subcategoria', 'Titulo ASC');
$lista_pos = fetch_all($mysqli, 'pos', 'nome ASC');
$lista_revisores = fetch_all($mysqli, 'revisores', 'nome ASC');

$tipo_usuario = $_GET['tipo'] ?? 'criador';
$status = $dados_edicao['status_ficha'] ?? 'rascunho';

$podeSalvarRascunho = $tipo_usuario === 'criador' && in_array($status, ['rascunho', 'reprovado_revisor', 'reprovado_po']);
$podeEnviarRevisao = $podeSalvarRascunho;
$podeEnviarAprovacao = $tipo_usuario === 'criador' && $status === 'revisada';
$podeDevolverRevisao = ($tipo_usuario === 'criador' && $status === 'revisada') || ($tipo_usuario === 'po' && $status === 'em_aprovacao');
$podeCriarNovaVersao = $tipo_usuario === 'criador' && $status === 'publicado';
$podePublicar = $tipo_usuario === 'criador' && $status === 'aprovada';
$podeCancelar = $podePublicar;
$podeExcluir = $modo_edicao && $podeSalvarRascunho;
$podeAprovarRevisor = $tipo_usuario === 'revisor' && $status === 'em_revisao';
$podeReprovarRevisor = $podeAprovarRevisor;
$podeAprovarPO = $tipo_usuario === 'po' && $status === 'em_aprovacao';
$isReadOnly = in_array($status, ['publicado', 'cancelada', 'substituida', 'descontinuada']) || ($tipo_usuario === 'revisor' && $status !== 'em_revisao') || ($tipo_usuario === 'po' && $status !== 'em_aprovacao');

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?php echo $modo_edicao ? "Editar Serviço" : "Adicionar Serviço"; ?></title>
    <link rel="stylesheet" href="../../css/style_manage_add.css">
</head>
<body>
    <div id="debug-panel">
        <h4>Painel de Testes</h4>
        <label for="debug-tipo-usuario">Simular como:</label>
        <select id="debug-tipo-usuario">
            <option value="criador" <?= ($tipo_usuario === 'criador') ? 'selected' : '' ?>>Criador</option>
            <option value="revisor" <?= ($tipo_usuario === 'revisor') ? 'selected' : '' ?>>Revisor</option>
            <option value="po" <?= ($tipo_usuario === 'po') ? 'selected' : '' ?>>PO</option>
        </select>
        <?php if ($modo_edicao): ?>
            <label for="debug-status-ficha">Forçar Status:</label>
            <select id="debug-status-ficha">
                <option value="">-- Status Atual --</option>
                <?php
                $todos_status = ['rascunho', 'em_revisao', 'revisada', 'em_aprovacao', 'aprovada', 'publicado', 'cancelada', 'reprovado_revisor', 'reprovado_po', 'substituida', 'descontinuada'];
                foreach ($todos_status as $status_opcao): ?>
                    <option value="<?= $status_opcao ?>" <?= (($dados_edicao['status_ficha'] ?? '') === $status_opcao) ? 'selected' : '' ?>>
                        <?= ucfirst(str_replace('_', ' ', $status_opcao)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
        <button id="debug-apply-btn">Aplicar e Recarregar</button>
    </div>

    <div class="form-wrapper">
        <h2 class="form-title">
            <?php echo $modo_edicao ? "Editar Ficha " . htmlspecialchars($dados_edicao['codigo_ficha'] ?? '') . " (v" . htmlspecialchars($dados_edicao['versao'] ?? '') . ")" : "Adicionar Serviço"; ?>
        </h2>
        <a href="../list/manage_listservico.php" class="btn-back">← Voltar para lista</a>

        <?php if ($modo_edicao): ?>
            <p><strong>Status da Ficha:</strong> <?php echo get_status_label($status); ?></p>
        <?php endif; ?>
        
        <div id="form-error-message" class="mensagem-erro" style="display:none;"></div>

        <?php if (!empty($dados_edicao['justificativa_rejeicao']) && in_array($status, ['rascunho', 'em_revisao', 'reprovado_revisor', 'reprovado_po'])): ?>
            <div class="rejection-notice">
                <strong>Justificativa da Reprovação:</strong><br>
                <em><?php echo nl2br(htmlspecialchars($dados_edicao['justificativa_rejeicao'])); ?></em>
            </div>
        <?php endif; ?>

        <?php if (!empty($mensagem)): ?>
            <div class="mensagem-sucesso"><?php echo htmlspecialchars($mensagem); ?></div>
        <?php endif; ?>

        <form id="form-ficha" method="post" class="form-grid">
            <input type="hidden" name="acao" id="form-action-field" value="">

            <div class="form-column">
                <label>Nome do Serviço:<textarea name="nome_servico" rows="1" required <?= $isReadOnly ? 'readonly' : '' ?>><?php echo htmlspecialchars($dados_edicao['Titulo'] ?? '') ?></textarea></label>
                <label>Descrição do Serviço:<textarea name="descricao_servico" rows="4" <?= $isReadOnly ? 'readonly' : '' ?>><?php echo htmlspecialchars($dados_edicao['Descricao'] ?? '') ?></textarea></label>
                <h3>Informações Adicionais</h3>
                <label>Área Especialista:<textarea name="area_especialista" rows="1" required <?= $isReadOnly ? 'readonly' : '' ?>><?php echo htmlspecialchars($dados_edicao['area_especialista'] ?? '') ?></textarea></label>
                <label>Alçadas:<textarea name="alcadas" rows="1" <?= $isReadOnly ? 'readonly' : '' ?>><?php echo htmlspecialchars($dados_edicao['alcadas'] ?? '') ?></textarea></label>
                <label>Procedimento de Exceção:<textarea name="procedimento_excecao" rows="1" <?= $isReadOnly ? 'readonly' : '' ?>><?php echo htmlspecialchars($dados_edicao['procedimento_excecao'] ?? '') ?></textarea></label>
                <label>Base de Conhecimento:<textarea name="base_conhecimento" rows="1" <?= $isReadOnly ? 'readonly' : '' ?>><?php echo htmlspecialchars($dados_edicao['KBs'] ?? '') ?></textarea></label>
                <label>PO Responsável:
                    <select name="po_responsavel" required <?= $isReadOnly ? 'disabled' : '' ?>>
                        <option value="">Selecione um PO...</option>
                        <?php foreach ($lista_pos as $po): ?>
                            <option value="<?= htmlspecialchars($po['nome']) ?>" <?= (($dados_edicao['po_responsavel'] ?? '') === $po['nome']) ? 'selected' : '' ?>><?= htmlspecialchars($po['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>

            <div class="form-column">
                <label>Subcategoria:
                    <select name="id_subcategoria" required <?= $isReadOnly ? 'disabled' : '' ?>>
                        <option value="">Selecione uma subcategoria</option>
                        <?php foreach ($subcategorias as $sub): ?>
                            <option value="<?php echo $sub['ID']; ?>" <?php if (($dados_edicao['ID_SubCategoria'] ?? '') == $sub['ID']) echo 'selected'; ?>><?php echo htmlspecialchars($sub['Titulo']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                
                <?php if ($modo_edicao): ?>
                <div class="revisores-container">
                    <label>Revisores Designados</label>
                    <div class="checkbox-list">
                        <?php foreach ($lista_revisores as $revisor): ?>
                            <label class="checkbox-label"><input type="checkbox" name="revisores_ids[]" value="<?= $revisor['ID'] ?>" <?= in_array($revisor['ID'], $revisores_servico) ? 'checked' : '' ?> <?= $isReadOnly || !$podeEnviarRevisao ? 'disabled' : '' ?>> <?= htmlspecialchars($revisor['nome']) ?></label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <h3>Diretrizes</h3><div id="diretrizes"></div><?php if (!$isReadOnly): ?><button type="button" class="btn-add" onclick="adicionarDiretriz()">+ Adicionar Diretriz</button><?php endif; ?>
                <h3>Padrões</h3><div id="padroes"></div><?php if (!$isReadOnly): ?><button type="button" class="btn-add" onclick="adicionarPadrao()">+ Adicionar Padrão</button><?php endif; ?>
                <h3>Checklist de Verificação</h3><div id="checklist"></div><?php if (!$isReadOnly): ?><button type="button" class="btn-add" onclick="adicionarChecklist()">+ Adicionar Item</button><?php endif; ?>
                <h3>Observações Gerais</h3><textarea name="observacoes_gerais" rows="4" <?= $isReadOnly ? 'readonly' : '' ?>><?php echo htmlspecialchars($dados_edicao['observacoes'] ?? '') ?></textarea>
            </div>

            <div class="form-full-width">
                <?php if ($modo_edicao && isset($dados_edicao['codigo_ficha'])): ?>
                    <div class="historico-versoes">
                        <strong>Versões:</strong>
                        <?php
                        $codigo = $dados_edicao['codigo_ficha'];
                        $res_versoes = $mysqli->query("SELECT ID, versao, status_ficha FROM servico WHERE codigo_ficha = '$codigo' ORDER BY versao ASC");
                        while ($ver = $res_versoes->fetch_assoc()) { echo "<a href='?id={$ver['ID']}&tipo={$tipo_usuario}' class='versao-link'>v{$ver['versao']} (" . get_status_label($ver['status_ficha']) . ")</a> "; }
                        ?>
                    </div>
                <?php endif; ?>

                <div class="form-actions-horizontal">
                    <?php if (!$modo_edicao): ?>
                        <button type="button" class="btn-salvar btn-action" data-action="criar_servico">Criar Serviço</button>
                    <?php else: ?>
                        <?php if ($podeSalvarRascunho) echo '<button type="button" class="btn-info btn-action" data-action="enviar_revisao">Salvar Alterações</button>'; ?>
                        <?php if ($podeEnviarRevisao) echo '<button type="button" class="btn-salvar btn-action" data-action="enviar_revisao">Enviar para Revisão</button>'; ?>
                        <?php if ($podeEnviarAprovacao) echo '<button type="button" class="btn-salvar btn-action" data-action="enviar_para_aprovacao">Enviar para Aprovação do PO</button>'; ?>
                        <?php if ($podeDevolverRevisao && $tipo_usuario === 'criador') echo '<button type="button" class="btn-info btn-action" data-action="enviar_revisao_novamente" data-requires-justification="true">Devolver para Revisão</button>'; ?>
                        <?php if ($podePublicar) echo '<button type="button" class="btn-salvar btn-action" data-action="publicar_ficha">Publicar Ficha</button>'; ?>
                        <?php if ($podeCancelar) echo '<button type="button" class="btn-danger btn-action" data-action="cancelar_ficha" data-confirm-message="Tem certeza que deseja cancelar esta ficha?">Cancelar</button>'; ?>
                        <?php if ($podeCriarNovaVersao) echo '<button type="button" class="btn-salvar btn-action" data-action="nova_versao_auto">Nova Versão</button>'; ?>
                        <?php if ($podeAprovarRevisor) echo '<button type="button" class="btn-salvar btn-action" data-action="aprovar_revisor">Concluir Revisão</button>'; ?>
                        <?php if ($podeReprovarRevisor) echo '<button type="button" class="btn-danger btn-action" data-action="reprovar_revisor" data-requires-justification="true">Reprovar</button>'; ?>
                        <?php if ($podeAprovarPO) echo '<button type="button" class="btn-salvar btn-action" data-action="aprovar_po">Aprovar Ficha</button>'; ?>
                        <?php if ($podeDevolverRevisao && $tipo_usuario === 'po') echo '<button type="button" class="btn-info btn-action" data-action="enviar_revisao_novamente" data-requires-justification="true">Devolver para Revisão</button>'; ?>
                        <?php if ($podeExcluir): ?>
                            <input type="hidden" name="delete_id" value="<?php echo $id; ?>">
                            <button type="button" class="btn-danger btn-action" data-action="excluir" data-confirm-message="Tem certeza que deseja excluir permanentemente este serviço?">Excluir</button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <div id="justificativa-modal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close-btn" onclick="document.getElementById('justificativa-modal').style.display='none'">&times;</span>
            <h3>Justificativa</h3>
            <p>Por favor, informe o motivo da devolução/reprovação.</p>
            <textarea id="justificativa-texto" rows="5"></textarea>
            <button id="justificativa-submit" class="btn-salvar">Enviar</button>
        </div>
    </div>

    <script>
        const isReadOnly = <?= json_encode($isReadOnly) ?>;
        let diretrizes = <?= json_encode($diretrizes ?? []) ?>;
        let padroes = <?= json_encode($padroes ?? []) ?>;
        let checklist = <?= json_encode($checklist ?? []) ?>;

        function renderSection(containerId, data, fields, titlePrefix, itemPlaceholder) {
            const container = document.getElementById(containerId);
            container.innerHTML = '';
            data.forEach((mainItem, mainIndex) => {
                const group = document.createElement('div');
                group.className = 'grupo';
                let html = `<label>${titlePrefix} ${mainIndex + 1} - Título:</label>
                            <textarea name="${containerId}[${mainIndex}][${fields[0]}]" rows="1" ${isReadOnly ? 'readonly' : ''}>${mainItem.titulo || ''}</textarea>
                            <div id="itens_${containerId}_${mainIndex}">`;
                mainItem.itens.forEach(item => { html += `<textarea name="${containerId}[${mainIndex}][${fields[1]}][]" rows="1" placeholder="${itemPlaceholder}" ${isReadOnly ? 'readonly' : ''}>${item || ''}</textarea>`; });
                html += `</div>`;
                if (!isReadOnly) { html += `<button type="button" class="btn-add-item" onclick="adicionarSubItem('${containerId}', ${mainIndex})">+ Item</button>`; }
                group.innerHTML = html;
                container.appendChild(group);
            });
            autoResizeAllTextareas();
        }

        function renderChecklist() {
            const container = document.getElementById('checklist');
            container.innerHTML = '';
            checklist.forEach((item, index) => {
                const group = document.createElement('div');
                group.className = 'grupo';
                group.innerHTML = `<label>Item ${index + 1}:</label><textarea name="checklist[${index}][item]" rows="1" ${isReadOnly ? 'readonly' : ''}>${item.item || ''}</textarea>
                                   <label>Observação ${index + 1}:</label><textarea name="checklist[${index}][observacao]" rows="1" ${isReadOnly ? 'readonly' : ''}>${item.observacao || ''}</textarea>`;
                container.appendChild(group);
            });
            autoResizeAllTextareas();
        }
        
        function renderAllSections() {
            renderSection('diretrizes', diretrizes, ['titulo', 'itens'], 'Diretriz', 'Item da diretriz');
            renderSection('padroes', padroes, ['titulo', 'itens'], 'Padrão', 'Item do padrão');
            renderChecklist();
        }

        function adicionarDiretriz() { diretrizes.push({ titulo: '', itens: [''] }); renderAllSections(); }
        function adicionarPadrao() { padroes.push({ titulo: '', itens: [''] }); renderAllSections(); }
        function adicionarChecklist() { checklist.push({ item: '', observacao: '' }); renderAllSections(); }
        function adicionarSubItem(type, index) {
            if (type === 'diretrizes') diretrizes[index].itens.push('');
            if (type === 'padroes') padroes[index].itens.push('');
            renderAllSections();
        }

        function autoResizeAllTextareas() {
            document.querySelectorAll('textarea').forEach(textarea => {
                textarea.style.height = 'auto';
                textarea.style.height = (textarea.scrollHeight) + 'px';
                textarea.addEventListener('input', () => {
                    textarea.style.height = 'auto';
                    textarea.style.height = (textarea.scrollHeight) + 'px';
                }, { once: true });
            });
        }

        function submitForm(action) {
            document.getElementById('form-action-field').value = action;
            document.getElementById('form-ficha').submit();
        }
        
        function showFormError(message) {
            const errorDiv = document.getElementById('form-error-message');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
        }

        function validateAndSubmit(action) {
            document.getElementById('form-error-message').style.display = 'none';

            if (action === 'enviar_revisao') {
                const revisoresMarcados = document.querySelectorAll('input[name="revisores_ids[]"]:checked').length;
                if (document.querySelector('input[name="revisores_ids[]"]') && revisoresMarcados === 0) {
                    showFormError('Erro: Por favor, selecione ao menos um revisor para continuar.');
                    return;
                }
                const diretrizesTitulos = document.querySelectorAll('textarea[name^="diretrizes"][name$="[titulo]"]');
                const algumTituloPreenchido = Array.from(diretrizesTitulos).some(t => t.value.trim() !== '');
                if (diretrizesTitulos.length > 0 && !algumTituloPreenchido) {
                    showFormError('Erro: Você precisa preencher o título de pelo menos uma diretriz.');
                    return;
                }
            }
            submitForm(action);
        }

        function mostrarJustificativa(action) {
            const modal = document.getElementById('justificativa-modal');
            modal.style.display = 'block';
            document.getElementById('justificativa-submit').onclick = function() {
                const justificativa = document.getElementById('justificativa-texto').value;
                if (!justificativa.trim()) { alert('A justificativa é obrigatória.'); return; }
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'justificativa';
                hiddenInput.value = justificativa;
                document.getElementById('form-ficha').appendChild(hiddenInput);
                modal.style.display = 'none';
                submitForm(action);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            renderAllSections();

            document.getElementById('debug-apply-btn')?.addEventListener('click', function() {
                const url = new URL(window.location.href);
                url.searchParams.set('tipo', document.getElementById('debug-tipo-usuario').value);
                const statusSelect = document.getElementById('debug-status-ficha');
                if (statusSelect && statusSelect.value) { url.searchParams.set('forcar_status', statusSelect.value); } 
                else { url.searchParams.delete('forcar_status'); }
                window.location.href = url.toString();
            });

            document.querySelector('.form-actions-horizontal').addEventListener('click', function(e) {
                if (!e.target.matches('.btn-action')) return;
                const button = e.target;
                const action = button.dataset.action;
                if (button.dataset.requiresJustification) {
                    mostrarJustificativa(action);
                } else if (button.dataset.confirmMessage) {
                    if (confirm(button.dataset.confirmMessage)) {
                        validateAndSubmit(action);
                    }
                } else {
                    validateAndSubmit(action);
                }
            });
        });
    </script>
</body>
</html>
