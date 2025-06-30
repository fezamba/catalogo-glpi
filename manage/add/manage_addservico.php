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
  $labels = [
    'rascunho' => '📝 Em Cadastro',
    'em_revisao' => '🔍 Em revisão',
    'revisada' => '✅ Revisada',
    'em_aprovacao' => '🕒 Em aprovação',
    'aprovada' => '☑️ Aprovada',
    'publicado' => '📢 Publicado',
    'cancelada' => '🚫 Cancelada',
    'reprovado_revisor' => '❌ Reprovado pelo Revisor',
    'reprovado_po' => '❌ Reprovado pelo PO',
    'substituida' => '♻️ Substituída',
    'descontinuada' => '⏳ Descontinuada'
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

      redirect("../list/manage_listservico.php?sucesso=1");
      break;

    case 'salvar_rascunho':
      $sql = "UPDATE servico SET Titulo = ?, Descricao = ?, ID_SubCategoria = ?, KBs = ?, UltimaAtualizacao = NOW(), area_especialista = ?, po_responsavel = ?, alcadas = ?, procedimento_excecao = ?, observacoes = ?, usuario_criador = ? WHERE ID = ?";
      $stmt = $mysqli->prepare($sql);
      $stmt->bind_param("ssisssssssi", $post_data['nome_servico'], $post_data['descricao_servico'], $post_data['id_subcategoria'], $post_data['base_conhecimento'], $post_data['area_especialista'], $post_data['po_responsavel'], $post_data['alcadas'], $post_data['procedimento_excecao'], $post_data['observacoes_gerais'], $_SESSION['username'], $id_post);
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
      $status_map = [
        'enviar_revisao' => 'em_revisao',
        'enviar_revisao_novamente' => 'em_revisao',
        'aprovar_revisor' => 'revisada',
        'aprovar_po' => 'aprovada',
        'reprovar_revisor' => 'reprovado_revisor',
        'reprovar_po' => 'reprovado_po'
      ];
      $novo_status = $status_map[$acao];
      $justificativa = in_array($acao, ['reprovar_revisor', 'reprovar_po', 'enviar_revisao_novamente']) ? ($post_data['justificativa'] ?? 'Sem justificativa') : null;

      $sql = "UPDATE servico SET Titulo = ?, Descricao = ?, ID_SubCategoria = ?, KBs = ?, UltimaAtualizacao = NOW(), area_especialista = ?, po_responsavel = ?, alcadas = ?, procedimento_excecao = ?, observacoes = ?, usuario_criador = ?, status_ficha = ?";
      $params = [$post_data['nome_servico'], $post_data['descricao_servico'], $post_data['id_subcategoria'], $post_data['base_conhecimento'], $post_data['area_especialista'], $post_data['po_responsavel'], $post_data['alcadas'], $post_data['procedimento_excecao'], $post_data['observacoes_gerais'], $_SESSION['username'], $novo_status];
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
    case 'publicar_ficha':
      $status_map_simple = ['enviar_para_aprovacao' => 'em_aprovacao', 'cancelar_ficha' => 'cancelada', 'publicar_ficha' => 'publicado'];
      $novo_status = $status_map_simple[$acao];
      $stmt = $mysqli->prepare("UPDATE servico SET status_ficha = ? WHERE ID = ?");
      $stmt->bind_param("si", $novo_status, $id_post);
      $stmt->execute();
      redirect("../list/manage_listservico.php?sucesso=1");
      break;
  }
}

if (isset($_GET["sucesso"])) {
  $mensagem = "Operação realizada com sucesso!";
}
if (isset($_GET["excluido"])) {
  $mensagem = "Serviço excluído com sucesso!";
}

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
  if (isset($_GET['forcar_status'])) {
    $dados_edicao['status_ficha'] = $_GET['forcar_status'];
  }

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
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background-color: #f4f6f9;
    }

    .form-wrapper {
      max-width: 800px;
      margin: 40px auto;
      padding: 30px;
      background-color: white;
      border-radius: 12px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .form-title {
      font-size: 24px;
      margin-bottom: 20px;
      text-align: center;
    }

    .form-grid {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .form-column {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    label {
      display: flex;
      flex-direction: column;
      font-weight: bold;
    }

    input[type='text'],
    textarea,
    select {
      padding: 8px;
      font-size: 14px;
      border: 1px solid #ccc;
      border-radius: 6px;
      width: 100%;
      box-sizing: border-box;
    }

    textarea {
      resize: vertical;
      min-height: 40px;
    }

    .form-actions-horizontal {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 20px;
      width: 100%;
    }

    .btn-salvar,
    .btn-danger,
    .btn-info {
      color: white;
      border: none;
      padding: 10px 20px;
      font-weight: bold;
      border-radius: 6px;
      cursor: pointer;
      text-decoration: none;
    }

    .btn-salvar {
      background-color: #f9b000;
    }

    .btn-salvar:hover {
      background-color: #d89a00;
    }

    .btn-danger {
      background-color: #d9534f;
    }

    .btn-danger:hover {
      background-color: #c9302c;
    }

    .btn-info {
      background-color: #5bc0de;
    }

    .btn-info:hover {
      background-color: #46b8da;
    }

    .btn-back {
      padding: 10px 20px;
      margin-bottom: 20px;
      background-color: #e0e0e0;
      color: #333;
      border: none;
      border-radius: 6px;
      font-weight: bold;
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
    }

    .btn-back:hover {
      background-color: #cfcfcf;
    }

    .mensagem {
      background-color: #d4edda;
      color: #155724;
      padding: 10px 15px;
      border-radius: 6px;
      border: 1px solid #c3e6cb;
      margin-bottom: 15px;
      text-align: center;
      font-weight: bold;
    }

    .mensagem.erro {
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }

    .rejection-notice {
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
      border-left: 5px solid #d9534f;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
    }

    .rejection-notice strong {
      font-weight: bold;
      display: block;
      margin-bottom: 5px;
    }

    .grupo {
      background-color: #fffaf0;
      padding: 15px;
      border-left: 4px solid #f9b000;
      margin-bottom: 10px;
      border-radius: 6px;
    }

    h3 {
      margin-bottom: 7px;
      border-bottom: 1px solid #eee;
      padding-bottom: 5px;
    }

    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0, 0, 0, 0.4);
    }

    .modal-content {
      background-color: #fefefe;
      margin: 15% auto;
      padding: 20px;
      border: 1px solid #888;
      width: 80%;
      max-width: 500px;
      border-radius: 8px;
    }

    .close-btn {
      color: #aaa;
      float: right;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
    }

    #debug-panel {
      position: fixed;
      bottom: 15px;
      right: 15px;
      width: 250px;
      background-color: #2c3e50;
      color: white;
      padding: 15px;
      border-radius: 8px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
      z-index: 1000;
      font-size: 14px;
    }

    #debug-panel h4 {
      margin: 0 0 10px 0;
      font-size: 16px;
      border-bottom: 1px solid #4a627a;
      padding-bottom: 5px;
    }

    #debug-panel label,
    #debug-panel select,
    #debug-panel button {
      width: 100%;
      display: block;
      margin-bottom: 10px;
      box-sizing: border-box;
    }

    #debug-panel button {
      background-color: #f9b000;
      color: #fff;
      font-weight: bold;
      cursor: pointer;
      border: none;
      padding: 8px;
    }

    .revisores-container {
      margin-top: 15px;
      padding: 20px;
      background-color: #f8f9fa;
      border-radius: 6px;
      border: 1px solid #e9ecef;
    }

    .checkbox-list {
      max-height: 220px;
      overflow-y: auto;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      background-color: #fff;
    }

    .checkbox-label {
      display: block;
      padding: 8px;
      font-weight: normal;
      cursor: pointer;
    }

    .checkbox-label input[type='checkbox'] {
      margin-right: 10px;
    }
  </style>
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
        <?php $todos_status = ['rascunho', 'em_revisao', 'revisada', 'em_aprovacao', 'aprovada', 'publicado', 'cancelada', 'reprovado_revisor', 'reprovado_po', 'substituida', 'descontinuada'];
        foreach ($todos_status as $status_opcao): ?>
          <option value="<?= $status_opcao ?>" <?= (($dados_edicao['status_ficha'] ?? '') === $status_opcao) ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $status_opcao)) ?></option>
        <?php endforeach; ?>
      </select>
    <?php endif; ?>
    <button id="debug-apply-btn">Aplicar e Recarregar</button>
  </div>

  <div class="form-wrapper">
    <h2 class="form-title"><?php echo $modo_edicao ? "Editar Ficha " . htmlspecialchars($dados_edicao['codigo_ficha'] ?? '') . " (v" . htmlspecialchars($dados_edicao['versao'] ?? '') . ")" : "Adicionar Serviço"; ?></h2>
    <a href="../list/manage_listservico.php" class="btn-back">← Voltar para lista</a>

    <?php if ($modo_edicao): ?><p><strong>Status da Ficha:</strong> <?php echo get_status_label($status); ?></p><?php endif; ?>
    <div id="form-error-message" class="mensagem erro" style="display:none;"></div>
    <?php if (!empty($dados_edicao['justificativa_rejeicao']) && in_array($status, ['rascunho', 'em_revisao', 'reprovado_revisor', 'reprovado_po'])): ?><div class="rejection-notice"><strong>Justificativa da Reprovação:</strong><br><em><?php echo nl2br(htmlspecialchars($dados_edicao['justificativa_rejeicao'])); ?></em></div><?php endif; ?>
    <?php if (!empty($mensagem)): ?><div class="mensagem"><?php echo htmlspecialchars($mensagem); ?></div><?php endif; ?>

    <form id="form-ficha" method="post">
      <div class="form-grid">
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
              <?php foreach ($lista_pos as $po): ?><option value="<?= htmlspecialchars($po['nome']) ?>" <?= (($dados_edicao['po_responsavel'] ?? '') === $po['nome']) ? 'selected' : '' ?>><?= htmlspecialchars($po['nome']) ?></option><?php endforeach; ?>
            </select>
          </label>
          <label>Subcategoria:
            <select name="id_subcategoria" required <?= $isReadOnly ? 'disabled' : '' ?>>
              <option value="">Selecione uma subcategoria</option>
              <?php foreach ($subcategorias as $sub): ?><option value="<?php echo $sub['ID']; ?>" <?php if (($dados_edicao['ID_SubCategoria'] ?? '') == $sub['ID']) echo 'selected'; ?>><?php echo htmlspecialchars($sub['Titulo']); ?></option><?php endforeach; ?>
            </select>
          </label>
          <?php if ($modo_edicao): ?>
            <div class="revisores-container">
              <label>Revisores Designados</label>
              <div class="checkbox-list">
                <?php foreach ($lista_revisores as $revisor): ?><label class="checkbox-label"><input type="checkbox" name="revisores_ids[]" value="<?= $revisor['ID'] ?>" <?= in_array($revisor['ID'], $revisores_servico) ? 'checked' : '' ?> <?= $isReadOnly || !$podeEnviarRevisao ? 'disabled' : '' ?>> <?= htmlspecialchars($revisor['nome']) ?></label><?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
          <h3>Diretrizes</h3>
          <div id="diretrizes">
            <?php $index = 0;
            foreach ($diretrizes as $diretriz): ?>
              <div class="grupo">
                <label>Diretriz <?= $index + 1 ?> - Título:</label>
                <textarea name="diretrizes[<?= $index ?>][titulo]" rows="1" oninput="autoResize(this)" <?= $isReadOnly ? 'readonly' : '' ?>><?= htmlspecialchars($diretriz['titulo']) ?></textarea>
                <div id="itens_diretriz_<?= $index ?>">
                  <?php foreach ($diretriz['itens'] as $item): ?><textarea name="diretrizes[<?= $index ?>][itens][]" rows="1" oninput="autoResize(this)" placeholder="Item da diretriz" <?= $isReadOnly ? 'readonly' : '' ?>><?= htmlspecialchars($item) ?></textarea><br><?php endforeach; ?>
                </div>
                <?php if (!$isReadOnly): ?><button type="button" class="btn-salvar" onclick="adicionarItemDiretriz(<?= $index ?>)">+ Item</button><?php endif; ?>
              </div>
            <?php $index++;
            endforeach; ?>
          </div>
          <?php if (!$isReadOnly): ?><button type="button" class="btn-salvar" onclick="adicionarDiretriz()">+ Adicionar Diretriz</button><?php endif; ?>
          <h3>Padrões</h3>
          <div id="padroes">
            <?php $index = 0;
            foreach ($padroes as $padrao): ?>
              <div class="grupo">
                <label>Padrão <?= $index + 1 ?> - Título:</label>
                <textarea name="padroes[<?= $index ?>][titulo]" rows="1" oninput="autoResize(this)" <?= $isReadOnly ? 'readonly' : '' ?>><?= htmlspecialchars($padrao['titulo']) ?></textarea>
                <div id="itens_padrao_<?= $index ?>">
                  <?php foreach ($padrao['itens'] as $item): ?><textarea name="padroes[<?= $index ?>][itens][]" rows="1" oninput="autoResize(this)" placeholder="Item do padrão" <?= $isReadOnly ? 'readonly' : '' ?>><?= htmlspecialchars($item) ?></textarea><br><?php endforeach; ?>
                </div>
                <?php if (!$isReadOnly): ?><button type="button" class="btn-salvar" onclick="adicionarItemPadrao(<?= $index ?>)">+ Item</button><?php endif; ?>
              </div>
            <?php $index++;
            endforeach; ?>
          </div>
          <?php if (!$isReadOnly): ?><button type="button" class="btn-salvar" onclick="adicionarPadrao()">+ Adicionar Padrão</button><?php endif; ?>
          <h3>Checklist de Verificação</h3>
          <div id="checklist">
            <?php $index = 0;
            foreach ($checklist as $item): ?>
              <div class="grupo">
                <label>Item <?= $index + 1 ?>:</label><textarea name="checklist[<?= $index ?>][item]" rows="1" oninput="autoResize(this)" <?= $isReadOnly ? 'readonly' : '' ?>><?= htmlspecialchars($item['item']) ?></textarea>
                <label>Observação <?= $index + 1 ?>:</label><textarea name="checklist[<?= $index ?>][observacao]" rows="1" oninput="autoResize(this)" <?= $isReadOnly ? 'readonly' : '' ?>><?= htmlspecialchars($item['observacao']) ?></textarea>
              </div>
            <?php $index++;
            endforeach; ?>
          </div>
          <?php if (!$isReadOnly): ?><button type="button" class="btn-salvar" onclick="adicionarChecklist()">+ Adicionar Item</button><?php endif; ?>
          <h3>Observações Gerais</h3><textarea name="observacoes_gerais" rows="4" oninput="autoResize(this)" <?= $isReadOnly ? 'readonly' : '' ?>><?php echo htmlspecialchars($dados_edicao['observacoes'] ?? '') ?></textarea>
        </div>
      </div>
      <div class="form-actions-horizontal">
        <?php if (!$modo_edicao): ?>
          <button type="submit" name="acao" value="criar_servico" class="btn-salvar">Criar Serviço</button>
        <?php else: ?>
          <?php if ($podeSalvarRascunho): ?><button type="submit" name="acao" value="salvar_rascunho" class="btn-info">Salvar Alterações</button><?php endif; ?>
          <?php if ($podeEnviarRevisao): ?><button type="submit" id="btn-enviar-revisao" name="acao" value="enviar_revisao" class="btn-salvar">Enviar para Revisão</button><?php endif; ?>
          <?php if ($podeEnviarAprovacao): ?><button type="submit" name="acao" value="enviar_para_aprovacao" class="btn-salvar">Enviar para Aprovação do PO</button><?php endif; ?>
          <?php if ($podeDevolverRevisao && $tipo_usuario === 'criador'): ?><button type="button" class="btn-info" onclick="mostrarJustificativa('enviar_revisao_novamente')">Devolver para Revisão</button><?php endif; ?>
          <?php if ($podePublicar): ?><button type="submit" name="acao" value="publicar_ficha" class="btn-salvar">Publicar Ficha</button><?php endif; ?>
          <?php if ($podeCancelar): ?><button type="submit" name="acao" value="cancelar_ficha" class="btn-danger" onclick="return confirm('Tem certeza que deseja cancelar esta ficha?')">Cancelar</button><?php endif; ?>
          <?php if ($podeCriarNovaVersao): ?><button type="submit" name="acao" value="nova_versao_auto" class="btn-salvar">Nova Versão</button><?php endif; ?>
          <?php if ($podeAprovarRevisor): ?><button type="submit" name="acao" value="aprovar_revisor" class="btn-salvar">Concluir Revisão</button><?php endif; ?>
          <?php if ($podeReprovarRevisor): ?><button type="button" class="btn-danger" onclick="mostrarJustificativa('reprovar_revisor')">Reprovar</button><?php endif; ?>
          <?php if ($podeAprovarPO): ?><button type="submit" name="acao" value="aprovar_po" class="btn-salvar">Aprovar Ficha</button><?php endif; ?>
          <?php if ($podeDevolverRevisao && $tipo_usuario === 'po'): ?><button type="button" class="btn-info" onclick="mostrarJustificativa('enviar_revisao_novamente')">Devolver para Revisão</button><?php endif; ?>
          <?php if ($podeExcluir): ?>
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
      <textarea id="justificativa-texto" rows="5" style="width: 100%"></textarea>
      <button id="justificativa-submit" class="btn-salvar" style="margin-top: 10px;">Enviar</button>
    </div>
  </div>

  <script>
    function autoResize(el) {
      el.style.height = 'auto';
      el.style.height = el.scrollHeight + 'px';
    }
    document.querySelectorAll('textarea').forEach(el => autoResize(el));

    function adicionarDiretriz() {
      const index = document.querySelectorAll('#diretrizes .grupo').length;
      const container = document.createElement('div');
      container.classList.add('grupo');
      container.innerHTML = `<label>Diretriz ${index + 1} - Título:</label><textarea name="diretrizes[${index}][titulo]" rows="1" oninput="autoResize(this)"></textarea><div id="itens_diretriz_${index}"></div><button type="button" class="btn-salvar" onclick="adicionarItemDiretriz(${index})">+ Item</button>`;
      document.getElementById('diretrizes').appendChild(container);
    }

    function adicionarItemDiretriz(index) {
      document.getElementById(`itens_diretriz_${index}`).insertAdjacentHTML('beforeend', `<textarea name="diretrizes[${index}][itens][]" rows="1" oninput="autoResize(this)" placeholder="Item da diretriz"></textarea><br>`);
    }

    function adicionarPadrao() {
      const index = document.querySelectorAll('#padroes .grupo').length;
      const container = document.createElement('div');
      container.classList.add('grupo');
      container.innerHTML = `<label>Padrão ${index + 1} - Título:</label><textarea name="padroes[${index}][titulo]" rows="1" oninput="autoResize(this)"></textarea><div id="itens_padrao_${index}"></div><button type="button" class="btn-salvar" onclick="adicionarItemPadrao(${index})">+ Item</button>`;
      document.getElementById('padroes').appendChild(container);
    }

    function adicionarItemPadrao(index) {
      document.getElementById(`itens_padrao_${index}`).insertAdjacentHTML('beforeend', `<textarea name="padroes[${index}][itens][]" rows="1" oninput="autoResize(this)" placeholder="Item do padrão"></textarea><br>`);
    }

    function adicionarChecklist() {
      const index = document.querySelectorAll('#checklist .grupo').length;
      const container = document.createElement('div');
      container.classList.add('grupo');
      container.innerHTML = `<label>Item ${index + 1}:</label><textarea name="checklist[${index}][item]" rows="1" oninput="autoResize(this)"></textarea><label>Observação ${index + 1}:</label><textarea name="checklist[${index}][observacao]" rows="1" oninput="autoResize(this)"></textarea>`;
      document.getElementById('checklist').appendChild(container);
    }

    function mostrarJustificativa(acao) {
      const modal = document.getElementById('justificativa-modal');
      modal.style.display = 'block';
      document.getElementById('justificativa-submit').onclick = function() {
        const justificativa = document.getElementById('justificativa-texto').value;
        if (!justificativa.trim()) {
          alert('A justificativa é obrigatória.');
          return;
        }
        const form = document.getElementById('form-ficha');
        form.insertAdjacentHTML('beforeend', `<input type="hidden" name="acao" value="${acao}">`);
        form.insertAdjacentHTML('beforeend', `<input type="hidden" name="justificativa" value="${justificativa}">`);
        form.submit();
      }
    }

    document.addEventListener('DOMContentLoaded', function() {
      document.getElementById('debug-apply-btn')?.addEventListener('click', function() {
        const url = new URL(window.location.href);
        url.searchParams.set('tipo', document.getElementById('debug-tipo-usuario').value);
        const statusSelect = document.getElementById('debug-status-ficha');
        if (statusSelect && statusSelect.value) {
          url.searchParams.set('forcar_status', statusSelect.value);
        } else {
          url.searchParams.delete('forcar_status');
        }
        window.location.href = url.toString();
      });

      const btnEnviarRevisao = document.getElementById('btn-enviar-revisao');
      if (btnEnviarRevisao) {
        btnEnviarRevisao.addEventListener('click', function(event) {
          const revisoresMarcados = document.querySelectorAll('input[name="revisores_ids[]"]:checked').length;
          if (document.querySelector('input[name="revisores_ids[]"]') && revisoresMarcados === 0) {
            event.preventDefault();
            alert('Erro: Por favor, selecione ao menos um revisor para continuar.');
            return;
          }
          const diretrizesTitulos = document.querySelectorAll('textarea[name^="diretrizes"][name$="[titulo]"]');
          const algumTituloPreenchido = Array.from(diretrizesTitulos).some(t => t.value.trim() !== '');
          if (diretrizesTitulos.length > 0 && !algumTituloPreenchido) {
            event.preventDefault();
            alert('Erro: Você precisa preencher o título de pelo menos uma diretriz.');
            return;
          }
        });
      }
    });
  </script>
</body>

</html>