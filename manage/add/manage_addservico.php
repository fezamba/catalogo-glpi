<?php
session_start();
require_once '../../conexao.php';

if ($mysqli->connect_errno) {
    die("Erro de conexão com o banco de dados: " . $mysqli->connect_error);
}

// --- Funções Auxiliares de Banco de Dados ---

function fetch_by_id($mysqli, $table, $id)
{
    $stmt = $mysqli->prepare("SELECT * FROM $table WHERE ID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function fetch_glpi_revisor_by_id($mysqli, $id)
{
    $query = "
        SELECT u.id AS ID, CONCAT(u.firstname, ' ', u.realname) AS nome
        FROM glpi_users u
        JOIN glpi_profiles_users pu ON u.id = pu.users_id
        JOIN glpi_profiles p ON pu.profiles_id = p.id
        WHERE p.name = 'Revisor' AND u.is_active = 1 AND u.id = ?
    ";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function fetch_all($mysqli, $table, $order_by = null)
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
    $query = "SELECT m.ID AS main_id, m.Titulo AS main_titulo, i.Conteudo AS item_conteudo FROM $main_table m LEFT JOIN $item_table i ON m.ID = i.$join_col_item WHERE m.$join_col_main = ? ORDER BY m.ID, i.ID";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $servico_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $temp_items = [];
    while ($row = $result->fetch_assoc()) {
        if (!isset($temp_items[$row['main_id']])) {
            $temp_items[$row['main_id']] = ['titulo' => $row['main_titulo'], 'itens' => []];
        }
        if ($row['item_conteudo']) {
            $temp_items[$row['main_id']]['itens'][] = $row['item_conteudo'];
        }
    }
    return array_values($temp_items);
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
    $mysqli->query("DELETE FROM $main_table WHERE $join_col_main = $servico_id");
    if (empty($data)) return;
    foreach ($data as $main_item) {
        if (empty(trim($main_item['titulo']))) continue;
        $titulo_main = $mysqli->real_escape_string($main_item['titulo']);
        $mysqli->query("INSERT INTO $main_table (Titulo, $join_col_main) VALUES ('$titulo_main', $servico_id)");
        $main_id = $mysqli->insert_id;
        if (!empty($main_item['itens'])) {
            foreach ($main_item['itens'] as $item_conteudo) {
                if (!empty(trim($item_conteudo))) {
                    $conteudo_item = $mysqli->real_escape_string($item_conteudo);
                    $mysqli->query("INSERT INTO $item_table (Conteudo, $join_col_item) VALUES ('$conteudo_item', $main_id)");
                }
            }
        }
    }
}

function sync_checklist_data($mysqli, $servico_id, $checklist_data)
{
    $mysqli->query("DELETE FROM checklist WHERE ID_Servico = $servico_id");
    if (empty($checklist_data)) return;
    foreach ($checklist_data as $item) {
        if (!empty(trim($item['item']))) {
            $nome_item = $mysqli->real_escape_string($item['item']);
            $obs_item = $mysqli->real_escape_string($item['observacao']);
            $mysqli->query("INSERT INTO checklist (ID_Servico, NomeItem, Observacao) VALUES ($servico_id, '$nome_item', '$obs_item')");
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
    $labels = ['rascunho' => '📝 Em Cadastro', 'em_revisao' => '🔍 Em revisão', 'revisada' => '✅ Revisada', 'em_aprovacao' => '🕒 Em aprovação', 'aprovada' => '☑️ Aprovada', 'publicado' => '📢 Publicado', 'cancelada' => '🚫 Cancelada', 'reprovado_revisor' => '❌ Reprovado pelo Revisor', 'reprovado_po' => '❌ Reprovado pelo PO', 'substituida' => '♻️ Substituída', 'descontinuada' => '⏳ Descontinuada'];
    return $labels[$status] ?? '—';
}

// --- Carregamento de Dados Unificado ---
$lista_pos = fetch_all($mysqli, 'pos', 'nome ASC');
$subcategorias = fetch_all($mysqli, 'subcategoria', 'Titulo ASC');

$lista_revisores = [];
$query_revisores_glpi = "
    SELECT u.id AS ID, CONCAT(u.firstname, ' ', u.realname) AS nome, ue.email
    FROM glpi_users u
    JOIN glpi_profiles_users pu ON u.id = pu.users_id
    JOIN glpi_profiles p ON pu.profiles_id = p.id
    LEFT JOIN glpi_useremails ue ON u.id = ue.users_id AND ue.is_default = 1
    WHERE p.name = 'Revisor' AND u.is_active = 1
    ORDER BY nome ASC
";
$res_revisores = $mysqli->query($query_revisores_glpi);
if ($res_revisores) {
    $lista_revisores = $res_revisores->fetch_all(MYSQLI_ASSOC);
}

// --- Lógica de Simulação de Usuário (Painel de Debug) ---
$lista_revisores_debug = $lista_revisores; // Usa a mesma lista do GLPI
$lista_pos_debug = $lista_pos;
$usuario_logado = ['tipo' => 'criador', 'id' => 0, 'nome' => 'Service-Desk/WD'];

if (isset($_GET['simular_usuario']) && !empty($_GET['simular_usuario'])) {
    $simulacao = explode('_', $_GET['simular_usuario'], 2);
    $tipo_simulado = $simulacao[0];
    $id_simulado = intval($simulacao[1]);

    if ($tipo_simulado === 'revisor') {
        $user_data = fetch_glpi_revisor_by_id($mysqli, $id_simulado);
        if ($user_data) {
            $usuario_logado = ['tipo' => 'revisor', 'id' => $user_data['ID'], 'nome' => $user_data['nome']];
        }
    } elseif ($tipo_simulado === 'po') {
        $user_data = fetch_by_id($mysqli, 'pos', $id_simulado);
        if ($user_data) {
            $usuario_logado = ['tipo' => 'po', 'id' => $user_data['ID'], 'nome' => $user_data['nome']];
        }
    }
}
$_SESSION['usuario_logado'] = $usuario_logado;


// --- Inicialização de Variáveis Principais ---
$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$modo_edicao = !is_null($id);
$mensagem = '';

// --- Processamento do Formulário (POST) ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['acao'])) {
    $acao = $_POST['acao'];
    $id_post = $modo_edicao ? $id : null;
    $post_data = $_POST;
    $nome_usuario_logado = $usuario_logado['nome'];

    switch ($acao) {
        case 'nova_versao_auto':
            $servico_antigo = fetch_by_id($mysqli, 'servico', $id_post);
            if ($servico_antigo) {

                $codigo_ficha = $servico_antigo['codigo_ficha'];
                $stmt_max_ver = $mysqli->prepare("SELECT MAX(CAST(versao AS DECIMAL(10,2))) as max_versao FROM servico WHERE codigo_ficha = ?");
                $stmt_max_ver->bind_param("s", $codigo_ficha);
                $stmt_max_ver->execute();
                $resultado_max = $stmt_max_ver->get_result()->fetch_assoc();
                $versao_max_existente = $resultado_max['max_versao'] ?? 0;
                $stmt_max_ver->close();

                $nova_versao_major = floor($versao_max_existente) + 1;
                $nova_versao = $nova_versao_major . '.0';

                $stmt = $mysqli->prepare("INSERT INTO servico (codigo_ficha, versao, Titulo, Descricao, ID_SubCategoria, KBs, area_especialista, po_responsavel, alcadas, procedimento_excecao, observacoes, usuario_criador, status_ficha, UltimaAtualizacao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'rascunho', NOW())");
                $stmt->bind_param("ssssisssssss", $servico_antigo['codigo_ficha'], $nova_versao, $servico_antigo['Titulo'], $servico_antigo['Descricao'], $servico_antigo['ID_SubCategoria'], $servico_antigo['KBs'], $servico_antigo['area_especialista'], $servico_antigo['po_responsavel'], $servico_antigo['alcadas'], $servico_antigo['procedimento_excecao'], $servico_antigo['observacoes'], $nome_usuario_logado);
                $stmt->execute();
                $novo_id = $stmt->insert_id;

                $diretrizes_antigas = fetch_related_items($mysqli, $id_post, 'diretriz', 'itemdiretriz', 'ID_Servico', 'ID_Diretriz');
                sync_related_data($mysqli, $novo_id, $diretrizes_antigas, 'diretriz', 'itemdiretriz', 'ID_Servico', 'ID_Diretriz');
                $padroes_antigos = fetch_related_items($mysqli, $id_post, 'padrao', 'itempadrao', 'ID_Servico', 'ID_Padrao');
                sync_related_data($mysqli, $novo_id, $padroes_antigos, 'padrao', 'itempadrao', 'ID_Servico', 'ID_Padrao');
                $checklist_antigo = fetch_checklist($mysqli, $id_post);
                sync_checklist_data($mysqli, $novo_id, $checklist_antigo);
                redirect("manage_addservico.php?id=$novo_id&sucesso=1");
            }
            break;
        case 'publicar_ficha':
            $stmt = $mysqli->prepare("UPDATE servico SET status_ficha = 'publicado' WHERE ID = ?");
            $stmt->bind_param("i", $id_post);
            $stmt->execute();
            $servico_publicado = fetch_by_id($mysqli, 'servico', $id_post);
            if ($servico_publicado) {
                $stmt_substituir = $mysqli->prepare("UPDATE servico SET status_ficha = 'substituida' WHERE codigo_ficha = ? AND versao < ? AND status_ficha = 'publicado'");
                $stmt_substituir->bind_param("sd", $servico_publicado['codigo_ficha'], $servico_publicado['versao']);
                $stmt_substituir->execute();
            }
            redirect("../list/manage_listservico.php?sucesso=1");
            break;
        case 'criar_servico':
            $stmt = $mysqli->prepare("INSERT INTO servico (versao, Titulo, Descricao, ID_SubCategoria, KBs, UltimaAtualizacao, area_especialista, po_responsavel, alcadas, procedimento_excecao, observacoes, usuario_criador, status_ficha) VALUES ('1.0', ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, 'rascunho')");
            $stmt->bind_param("ssisssssss", $post_data['nome_servico'], $post_data['descricao_servico'], $post_data['id_subcategoria'], $post_data['base_conhecimento'], $post_data['area_especialista'], $post_data['po_responsavel'], $post_data['alcadas'], $post_data['procedimento_excecao'], $post_data['observacoes_gerais'], $nome_usuario_logado);
            $stmt->execute();
            $new_id = $stmt->insert_id;
            $codigo_ficha = "FCH-" . str_pad($new_id, 4, "0", STR_PAD_LEFT);
            $mysqli->query("UPDATE servico SET codigo_ficha = '$codigo_ficha' WHERE ID = $new_id");
            sync_related_data($mysqli, $new_id, $post_data['diretrizes'] ?? [], 'diretriz', 'itemdiretriz', 'ID_Servico', 'ID_Diretriz');
            sync_related_data($mysqli, $new_id, $post_data['padroes'] ?? [], 'padrao', 'itempadrao', 'ID_Servico', 'ID_Padrao');
            sync_checklist_data($mysqli, $new_id, $post_data['checklist'] ?? []);
            redirect("../list/manage_listservico.php?sucesso=1");
            break;
        case 'salvar_rascunho':
            $sql = "UPDATE servico SET Titulo = ?, Descricao = ?, ID_SubCategoria = ?, KBs = ?, UltimaAtualizacao = NOW(), area_especialista = ?, po_responsavel = ?, alcadas = ?, procedimento_excecao = ?, observacoes = ?, usuario_criador = ? WHERE ID = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("ssisssssssi", $post_data['nome_servico'], $post_data['descricao_servico'], $post_data['id_subcategoria'], $post_data['base_conhecimento'], $post_data['area_especialista'], $post_data['po_responsavel'], $post_data['alcadas'], $post_data['procedimento_excecao'], $post_data['observacoes_gerais'], $nome_usuario_logado, $id_post);
            $stmt->execute();
            sync_related_data($mysqli, $id_post, $post_data['diretrizes'] ?? [], 'diretriz', 'itemdiretriz', 'ID_Servico', 'ID_Diretriz');
            sync_related_data($mysqli, $id_post, $post_data['padroes'] ?? [], 'padrao', 'itempadrao', 'ID_Servico', 'ID_Padrao');
            sync_checklist_data($mysqli, $id_post, $post_data['checklist'] ?? []);
            redirect("manage_addservico.php?id=$id_post&sucesso=1");
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
            $status_map = ['enviar_revisao' => 'em_revisao', 'enviar_revisao_novamente' => 'em_revisao', 'aprovar_revisor' => 'revisada', 'aprovar_po' => 'aprovada', 'reprovar_revisor' => 'reprovado_revisor', 'reprovar_po' => 'reprovado_po'];
            $novo_status = $status_map[$acao];
            $justificativa = in_array($acao, ['reprovar_revisor', 'reprovar_po', 'enviar_revisao_novamente']) ? ($post_data['justificativa'] ?? 'Sem justificativa') : null;
            $sql = "UPDATE servico SET Titulo = ?, Descricao = ?, ID_SubCategoria = ?, KBs = ?, UltimaAtualizacao = NOW(), area_especialista = ?, po_responsavel = ?, alcadas = ?, procedimento_excecao = ?, observacoes = ?, usuario_criador = ?, status_ficha = ?";
            $params = [$post_data['nome_servico'], $post_data['descricao_servico'], $post_data['id_subcategoria'], $post_data['base_conhecimento'], $post_data['area_especialista'], $post_data['po_responsavel'], $post_data['alcadas'], $post_data['procedimento_excecao'], $post_data['observacoes_gerais'], $nome_usuario_logado, $novo_status];
            $types = "ssissssssss";
            if ($justificativa) {
                $sql .= ", justificativa_rejeicao = ?";
                $params[] = $justificativa;
                $types .= "s";
            }
            if ($acao === 'aprovar_revisor') {
                $sql .= ", data_revisao = NOW()";
            }
            if ($acao === 'aprovar_po') {
                $sql .= ", data_aprovacao = NOW()";
            }
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
            redirect("../list/manage_listservico.php?sucesso=1");
            break;
        case 'enviar_para_aprovacao':
        case 'cancelar_ficha':
            $status_map_simple = ['enviar_para_aprovacao' => 'em_aprovacao', 'cancelar_ficha' => 'cancelada'];
            $novo_status = $status_map_simple[$acao];
            $stmt = $mysqli->prepare("UPDATE servico SET status_ficha = ? WHERE ID = ?");
            $stmt->bind_param("si", $novo_status, $id_post);
            $stmt->execute();
            redirect("../list/manage_listservico.php?sucesso=1");
            break;
    }
}

// --- Mensagens de Feedback (GET) ---
if (isset($_GET["sucesso"])) {
    $mensagem = "Operação realizada com sucesso!";
}
if (isset($_GET["excluido"])) {
    $mensagem = "Serviço excluído com sucesso!";
}

// --- Carregamento de Dados para Exibição (Modo de Edição) ---
$dados_edicao = [];
$diretrizes = [];
$padroes = [];
$checklist = [];
$revisores_servico = [];
if ($modo_edicao) {
    $dados_edicao = fetch_by_id($mysqli, 'servico', $id);
    if (!$dados_edicao) {
        die("Serviço não encontrado.");
    }
    if (isset($_GET['forcar_status']) && !empty($_GET['forcar_status'])) {
        $dados_edicao['status_ficha'] = $_GET['forcar_status'];
    }
    $diretrizes = fetch_related_items($mysqli, $id, 'diretriz', 'itemdiretriz', 'ID_Servico', 'ID_Diretriz');
    $padroes = fetch_related_items($mysqli, $id, 'padrao', 'itempadrao', 'ID_Servico', 'ID_Padrao');
    $checklist = fetch_checklist($mysqli, $id);
    $revisores_servico_raw = fetch_all($mysqli, 'servico_revisores WHERE servico_id = ' . $id, null);
    $revisores_servico = array_column($revisores_servico_raw, 'revisor_id');
}

// --- Lógica de Permissões e Estado da UI ---
$tipo_usuario_atual = $usuario_logado['tipo'];
$id_usuario_atual = $usuario_logado['id'];
$nome_usuario_atual = $usuario_logado['nome'];
$status = $dados_edicao['status_ficha'] ?? 'rascunho';

$isRevisorAutorizado = $tipo_usuario_atual === 'revisor' && in_array($id_usuario_atual, $revisores_servico);
$isPOAutorizado = $tipo_usuario_atual === 'po' && ($nome_usuario_atual === ($dados_edicao['po_responsavel'] ?? ''));

$podeSalvarRascunho = $tipo_usuario_atual === 'criador' && in_array($status, ['rascunho', 'reprovado_revisor', 'reprovado_po']);
$podeEnviarRevisao = $podeSalvarRascunho;
$podeEnviarAprovacao = $tipo_usuario_atual === 'criador' && $status === 'revisada';
$podeDevolverRevisao = ($tipo_usuario_atual === 'criador' && $status === 'revisada') || ($tipo_usuario_atual === 'po' && $status === 'em_aprovacao' && $isPOAutorizado);
$podeCriarNovaVersao = ($tipo_usuario_atual === 'criador' && $status === 'publicado') || ($tipo_usuario_atual === 'criador' && $status === 'substituida');
$podePublicar = $tipo_usuario_atual === 'criador' && $status === 'aprovada';
$podeCancelar = $podePublicar;
$podeExcluir = $modo_edicao && $podeSalvarRascunho;
$podeAprovarRevisor = $status === 'em_revisao' && $isRevisorAutorizado;
$podeAprovarPO = $status === 'em_aprovacao' && $isPOAutorizado;

$isReadOnly = in_array($status, ['publicado', 'cancelada', 'substituida', 'descontinuada']) || ($tipo_usuario_atual === 'revisor' && !$isRevisorAutorizado && $status === 'em_revisao') || ($tipo_usuario_atual === 'po' && !$isPOAutorizado && $status === 'em_aprovacao');
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title><?php echo $modo_edicao ? "Editar Serviço" : "Adicionar Serviço"; ?></title>
    <link rel="stylesheet" href="../../css/addservico.css">
</head>

<body>
    <div id="debug-panel">
        <h4>Painel de Testes</h4>
        <label for="debug-status-ficha">Forçar Status:</label>
        <select id="debug-status-ficha">
            <option value="">-- Status Atual --</option>
            <?php $todos_status = ['rascunho', 'em_revisao', 'revisada', 'em_aprovacao', 'aprovada', 'publicado', 'cancelada', 'reprovado_revisor', 'reprovado_po', 'substituida', 'descontinuada'];
            foreach ($todos_status as $status_opcao) : ?>
                <option value="<?= $status_opcao ?>" <?= (($dados_edicao['status_ficha'] ?? '') === $status_opcao) ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $status_opcao)) ?></option>
            <?php endforeach; ?>
        </select>
        <label for="debug-simular-usuario">Simular como Usuário:</label>
        <select id="debug-simular-usuario">
            <option value="criador_0" <?= $usuario_logado['tipo'] === 'criador' ? 'selected' : '' ?>>Criador (Padrão)</option>
            <optgroup label="Revisores">
                <?php foreach ($lista_revisores_debug as $rev) : ?>
                    <option value="revisor_<?= $rev['ID'] ?>" <?= ($usuario_logado['tipo'] === 'revisor' && $usuario_logado['id'] == $rev['ID']) ? 'selected' : '' ?>><?= htmlspecialchars($rev['nome']) ?></option>
                <?php endforeach; ?>
            </optgroup>
            <optgroup label="Product Owners">
                <?php foreach ($lista_pos_debug as $po) : ?>
                    <option value="po_<?= $po['ID'] ?>" <?= ($usuario_logado['tipo'] === 'po' && $usuario_logado['id'] == $po['ID']) ? 'selected' : '' ?>><?= htmlspecialchars($po['nome']) ?></option>
                <?php endforeach; ?>
            </optgroup>
        </select>
        <button id="debug-apply-btn">Aplicar e Recarregar</button>
    </div>

    <div class="form-wrapper">
        <h2 class="form-title"><?php echo $modo_edicao ? "Editar Ficha " . htmlspecialchars($dados_edicao['codigo_ficha'] ?? '') . " (v" . htmlspecialchars($dados_edicao['versao'] ?? '') . ")" : "Adicionar Serviço"; ?></h2>
        <a href="../list/manage_listservico.php" class="btn-back">← Voltar para lista</a>

        <?php if ($modo_edicao) : ?><p><strong>Status da Ficha:</strong> <?php echo get_status_label($status); ?></p><?php endif; ?>
        <div id="form-error-message" class="mensagem erro" style="display:none;"></div>
        <?php if (!empty($dados_edicao['justificativa_rejeicao']) && in_array($status, ['rascunho', 'em_revisao', 'reprovado_revisor', 'reprovado_po'])) : ?><div class="rejection-notice"><strong>Justificativa da Reprovação:</strong><br><em><?php echo nl2br(htmlspecialchars($dados_edicao['justificativa_rejeicao'])); ?></em></div><?php endif; ?>
        <?php if (!empty($mensagem)) : ?><div class="mensagem"><?php echo htmlspecialchars($mensagem); ?></div><?php endif; ?>

        <form id="form-ficha" method="post">
            <div class="form-grid">
                <div class="form-column">
                    <label>Nome do Serviço:<textarea name="nome_servico" maxlength="255" rows="1" required <?= $isReadOnly ? 'readonly' : '' ?>><?php echo htmlspecialchars($dados_edicao['Titulo'] ?? '') ?></textarea></label>
                    <label>Descrição do Serviço:<textarea name="descricao_servico" maxlength="1000" rows="4" <?= $isReadOnly ? 'readonly' : '' ?>><?php echo htmlspecialchars($dados_edicao['Descricao'] ?? '') ?></textarea></label>
                    
                    <h3>Detalhes e Parâmetros</h3>
                    <label>Área Especialista:<textarea name="area_especialista" maxlength="255" rows="1" required <?= $isReadOnly ? 'readonly' : '' ?>><?php echo htmlspecialchars($dados_edicao['area_especialista'] ?? '') ?></textarea></label>
                    <label>PO Responsável:
                        <select name="po_responsavel" required <?= $isReadOnly ? 'disabled' : '' ?>>
                            <option value="">Selecione um PO...</option>
                            <?php foreach ($lista_pos as $po) : ?><option value="<?= htmlspecialchars($po['nome']) ?>" <?= (($dados_edicao['po_responsavel'] ?? '') === $po['nome']) ? 'selected' : '' ?>><?= htmlspecialchars($po['nome']) ?></option><?php endforeach; ?>
                        </select>
                    </label>
                     <label>Subcategoria:
                        <select name="id_subcategoria" required <?= $isReadOnly ? 'disabled' : '' ?>>
                            <option value="">Selecione uma subcategoria</option>
                            <?php foreach ($subcategorias as $sub) : ?><option value="<?php echo $sub['ID']; ?>" <?php if (($dados_edicao['ID_SubCategoria'] ?? '') == $sub['ID']) echo 'selected'; ?>><?php echo htmlspecialchars($sub['Titulo']); ?></option><?php endforeach; ?>
                        </select>
                    </label>
                    <label>Base de Conhecimento:<textarea name="base_conhecimento" maxlength="1000" rows="1" <?= $isReadOnly ? 'readonly' : '' ?>><?php echo htmlspecialchars($dados_edicao['KBs'] ?? '') ?></textarea></label>
                    <?php if ($modo_edicao) : ?>
                        <div class="revisores-container">
                            <label>Revisores Designados</label>
                            <div class="checkbox-list">
                                <?php foreach ($lista_revisores as $revisor) : ?><label class="checkbox-label"><input type="checkbox" name="revisores_ids[]" value="<?= $revisor['ID'] ?>" <?= in_array($revisor['ID'], $revisores_servico) ? 'checked' : '' ?> <?= $isReadOnly || !$podeEnviarRevisao ? 'disabled' : '' ?>> <?= htmlspecialchars($revisor['nome']) ?></label><?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <h3>Diretrizes</h3>
                    <div id="diretrizes">
                        <?php $index = 0;
                        foreach ($diretrizes as $diretriz) : ?>
                            <div class="grupo">
                                <label>Diretriz <?= $index + 1 ?> - Título:</label>
                                <textarea name="diretrizes[<?= $index ?>][titulo]" rows="1" maxlength="255" oninput="autoResize(this)" <?= $isReadOnly ? 'readonly' : '' ?>><?= htmlspecialchars($diretriz['titulo']) ?></textarea>
                                <div id="itens_diretriz_<?= $index ?>">
                                    <?php foreach ($diretriz['itens'] as $item) : ?><textarea name="diretrizes[<?= $index ?>][itens][]" rows="1" maxlength="1000" oninput="autoResize(this)" placeholder="Item da diretriz" <?= $isReadOnly ? 'readonly' : '' ?>><?= htmlspecialchars($item) ?></textarea><br><?php endforeach; ?>
                                </div>
                                <?php if (!$isReadOnly) : ?><button type="button" class="btn-salvar" onclick="adicionarItemDiretriz(<?= $index ?>)">+ Item</button><?php endif; ?>
                            </div>
                        <?php $index++;
                        endforeach; ?>
                    </div>
                    <?php if (!$isReadOnly) : ?><button type="button" class="btn-salvar" onclick="adicionarDiretriz()">+ Adicionar Diretriz</button><?php endif; ?>
                </div>

                <div class="form-column">
                    <h3>Alçadas</h3>
                    <textarea name="alcadas" maxlength="1000" rows="1" oninput="autoResize(this)" <?= $isReadOnly ? 'readonly' : '' ?>><?php echo htmlspecialchars($dados_edicao['alcadas'] ?? '') ?></textarea>
                    
                    <h3>Padrões</h3>
                    <div id="padroes">
                        <?php $index = 0;
                        foreach ($padroes as $padrao) : ?>
                            <div class="grupo">
                                <label>Padrão <?= $index + 1 ?> - Título:</label>
                                <textarea name="padroes[<?= $index ?>][titulo]" rows="1" maxlength="255" oninput="autoResize(this)" <?= $isReadOnly ? 'readonly' : '' ?>><?= htmlspecialchars($padrao['titulo']) ?></textarea>
                                <div id="itens_padrao_<?= $index ?>">
                                    <?php foreach ($padrao['itens'] as $item) : ?><textarea name="padroes[<?= $index ?>][itens][]" rows="1" maxlength="1000" oninput="autoResize(this)" placeholder="Item do padrão" <?= $isReadOnly ? 'readonly' : '' ?>><?= htmlspecialchars($item) ?></textarea><br><?php endforeach; ?>
                                </div>
                                <?php if (!$isReadOnly) : ?><button type="button" class="btn-salvar" onclick="adicionarItemPadrao(<?= $index ?>)">+ Item</button><?php endif; ?>
                            </div>
                        <?php $index++;
                        endforeach; ?>
                    </div>
                    <?php if (!$isReadOnly) : ?><button type="button" class="btn-salvar" onclick="adicionarPadrao()">+ Adicionar Padrão</button><?php endif; ?>

                    <h3>Procedimento de Exceção</h3>
                    <textarea name="procedimento_excecao" maxlength="1000" rows="1" oninput="autoResize(this)" <?= $isReadOnly ? 'readonly' : '' ?>><?php echo htmlspecialchars($dados_edicao['procedimento_excecao'] ?? '') ?></textarea>

                    <h3>Checklist de Verificação</h3>
                    <div id="checklist">
                        <?php $index = 0;
                        foreach ($checklist as $item) : ?>
                            <div class="grupo">
                                <label>Item <?= $index + 1 ?>:</label><textarea name="checklist[<?= $index ?>][item]" rows="1" maxlength="255" oninput="autoResize(this)" <?= $isReadOnly ? 'readonly' : '' ?>><?= htmlspecialchars($item['item']) ?></textarea>
                                <label>Observação <?= $index + 1 ?>:</label><textarea name="checklist[<?= $index ?>][observacao]" rows="1" maxlength="1000" oninput="autoResize(this)" <?= $isReadOnly ? 'readonly' : '' ?>><?= htmlspecialchars($item['observacao']) ?></textarea>
                            </div>
                        <?php $index++;
                        endforeach; ?>
                    </div>
                    <?php if (!$isReadOnly) : ?><button type="button" class="btn-salvar" onclick="adicionarChecklist()">+ Adicionar Item</button><?php endif; ?>

                    <h3>Observações Gerais</h3>
                    <textarea name="observacoes_gerais" rows="4" maxlength="1000" oninput="autoResize(this)" <?= $isReadOnly ? 'readonly' : '' ?>><?php echo htmlspecialchars($dados_edicao['observacoes'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="form-actions-horizontal">
                <?php if (!$modo_edicao) : ?>
                    <button type="submit" name="acao" value="criar_servico" class="btn-salvar">Criar Serviço</button>
                <?php else : ?>
                    <?php if ($podeSalvarRascunho) : ?><button type="submit" name="acao" value="salvar_rascunho" class="btn-info">Salvar Alterações</button><?php endif; ?>
                    <?php if ($podeEnviarRevisao) : ?><button type="submit" id="btn-enviar-revisao" name="acao" value="enviar_revisao" class="btn-salvar">Enviar para Revisão</button><?php endif; ?>
                    <?php if ($podeEnviarAprovacao) : ?><button type="submit" name="acao" value="enviar_para_aprovacao" class="btn-salvar">Enviar para Aprovação do PO</button><?php endif; ?>
                    <?php if ($podeDevolverRevisao && $tipo_usuario_atual === 'criador') : ?><button type="button" class="btn-info" onclick="mostrarJustificativa('enviar_revisao_novamente')">Devolver para Revisão</button><?php endif; ?>
                    <?php if ($podePublicar) : ?><button type="submit" name="acao" value="publicar_ficha" class="btn-salvar">Publicar Ficha</button><?php endif; ?>
                    <?php if ($podeCancelar) : ?><button type="submit" name="acao" value="cancelar_ficha" class="btn-danger" onclick="return confirm('Tem certeza que deseja cancelar esta ficha?')">Cancelar</button><?php endif; ?>
                    <?php if ($podeCriarNovaVersao) : ?><button type="submit" name="acao" value="nova_versao_auto" class="btn-salvar">Nova Versão</button><?php endif; ?>
                    <?php if ($podeAprovarRevisor) : ?><button type="submit" name="acao" value="aprovar_revisor" class="btn-salvar">Concluir Revisão</button><button type="button" class="btn-danger" onclick="mostrarJustificativa('reprovar_revisor')">Reprovar</button><?php endif; ?>
                    <?php if ($podeAprovarPO) : ?><button type="submit" name="acao" value="aprovar_po" class="btn-salvar">Aprovar Ficha</button><?php endif; ?>
                    <?php if ($podeDevolverRevisao && $tipo_usuario_atual === 'po') : ?><button type="button" class="btn-info" onclick="mostrarJustificativa('enviar_revisao_novamente')">Devolver para Revisão</button><?php endif; ?>
                    <?php if ($podeExcluir) : ?>
                        <input type="hidden" name="delete_id" value="<?php echo $id; ?>">
                        <button type="submit" name="acao" value="excluir" class="btn-danger" onclick="return confirm('Tem certeza que deseja excluir permanentemente este serviço?')">Excluir</button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div id="justificativa-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="document.getElementById('justificativa-modal').style.display='none'">&times;</span>
            <h3>Justificativa</h3>
            <p>Por favor, informe o motivo da devolução/reprovação.</p>
            <textarea id="justificativa-texto" rows="5" maxlength="1000" style="width: 100%"></textarea>
            <button id="justificativa-submit" class="btn-salvar" style="margin-top: 10px;">Enviar</button>
        </div>
    </div>
    <script src="../../js/addservico.js"></script>
</body>

</html>
