<?php
session_start();
$_SESSION['username'] = 'Service-Desk/WD'; // Simulação de usuário logado
//$mysqli = new mysqli("localhost", "root", "sefazfer123@", "catalogo-teste");
require_once '../../conexao.php';
if ($mysqli->connect_errno) {
  die("Erro: " . $mysqli->connect_error);
}

$modo_edicao = isset($_GET['id']);
$ficha_publicada = false;

$id = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
  $id = intval($_GET['id']);
}

$eh_publicada = false;
if ($modo_edicao && isset($dados_edicao) && isset($dados_edicao['status_ficha'])) {
  $eh_publicada = $dados_edicao['status_ficha'] === 'publicado';
}

if ($modo_edicao) {
  $stmt = $mysqli->prepare("SELECT * FROM servico WHERE ID = ?");
  $stmt->bind_param("i", $_GET['id']);
  $stmt->execute();
  $dados_edicao = $stmt->get_result()->fetch_assoc();
  $ficha_publicada = $dados_edicao['status_ficha'] === 'publicado';
}

$modo_edicao = isset($_GET['id']);
$mensagem = "";

if (isset($_GET["excluido"]) && $_GET["excluido"] == "1") {
  $mensagem = "Serviço excluído com sucesso!";
}

if (isset($_GET["sucesso"]) && $_GET["sucesso"] == "1") {
  $mensagem = $modo_edicao ? "Serviço atualizado com sucesso!" : "Serviço salvo com sucesso!";
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_id']) && $_POST['acao'] === 'excluir') {
  $delete_id = intval($_POST['delete_id']);
  $stmt = $mysqli->prepare("DELETE FROM servico WHERE ID = ?");
  $stmt->bind_param("i", $delete_id);

  if ($stmt->execute()) {
    header("Location: ../list/manage_listservico.php?excluido=1");
    exit;
  } else {
    $mensagem = "Erro ao excluir: " . $stmt->error;
  }

  $stmt->close();
}

$subcategorias = [];
$res = $mysqli->query("SELECT ID, Titulo FROM subcategoria ORDER BY Titulo ASC");
if (!$res) {
  die("Erro ao buscar subcategorias: " . $mysqli->error);
}
while ($row = $res->fetch_assoc()) {
  $subcategorias[] = $row;
}

$lista_pos = [];
$res_pos = $mysqli->query("SELECT nome FROM pos ORDER BY nome ASC");
if ($res_pos) {
  while ($row_po = $res_pos->fetch_assoc()) {
    $lista_pos[] = $row_po;
  }
}

$lista_revisores = [];
$res_revisores = $mysqli->query("SELECT ID, nome, email FROM revisores ORDER BY nome ASC");
if (!$res_revisores) {
  die("Erro ao buscar revisores: " . $mysqli->error);
}
while ($row = $res_revisores->fetch_assoc()) {
  $lista_revisores[] = $row;
}

$revisores_servico = [];
if ($modo_edicao && $id) {
  $stmt = $mysqli->prepare("SELECT revisor_id FROM servico_revisores WHERE servico_id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $result = $stmt->get_result();

  while ($row = $result->fetch_assoc()) {
    $revisores_servico[] = $row['revisor_id'];
  }
  $stmt->close();
}

$dados_edicao = null;
if (isset($_GET['id'])) {
  $id = intval($_GET['id']);
  $res = $mysqli->query("SELECT * FROM servico WHERE ID = $id LIMIT 1");
  if ($res && $res->num_rows > 0) {
    $dados_edicao = $res->fetch_assoc();
  }
}

$eh_publicada = isset($dados_edicao['status_ficha']) && $dados_edicao['status_ficha'] === 'publicado';

if ($_SERVER["REQUEST_METHOD"] === "POST" && $_POST['acao'] === 'nova_versao_auto') {
  $codigo_ficha = $dados_edicao['codigo_ficha'];
  $versao_atual = $dados_edicao['versao'] ?? '1.0';

  $partes = explode('.', $versao_atual);
  $nova_versao = ((int)$partes[0] + 1) . '.0';

  $titulo       = $_POST['nome_servico'];
  $descricao    = $_POST['descricao_servico'];
  $subcategoria = $_POST['id_subcategoria'];
  $kbs          = $_POST['base_conhecimento'];
  $area         = $_POST['area_especialista'];
  $po           = $_POST['po_responsavel'];
  $alcadas      = $_POST['alcadas'];
  $excecao      = $_POST['procedimento_excecao'];
  $obs          = $_POST['observacoes_gerais'];
  $criador      = $_SESSION['username'];

  $stmt = $mysqli->prepare("INSERT INTO servico (
        codigo_ficha, versao, Titulo, Descricao, ID_SubCategoria, KBs, UltimaAtualizacao, area_especialista,
        po_responsavel, alcadas, procedimento_excecao, observacoes, usuario_criador, status_ficha
    ) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, 'rascunho')");

  $stmt->bind_param(
    "ssssisssssss",
    $codigo_ficha,
    $nova_versao,
    $titulo,
    $descricao,
    $subcategoria,
    $kbs,
    $area,
    $po,
    $alcadas,
    $excecao,
    $obs,
    $criador
  );

  if ($stmt->execute()) {
    $id_atual = intval($_GET['id']);

    $novo_id = $stmt->insert_id;

    $res = $mysqli->query("SELECT * FROM diretriz WHERE ID_Servico = $id_atual");
    while ($d = $res->fetch_assoc()) {
      $titulo = $mysqli->real_escape_string($d['Titulo']);
      $mysqli->query("INSERT INTO diretriz (Titulo, ID_Servico) VALUES ('$titulo', $novo_id)");
      $id_diretriz_nova = $mysqli->insert_id;

      $itens = $mysqli->query("SELECT * FROM itemdiretriz WHERE ID_Diretriz = {$d['ID']}");
      while ($item = $itens->fetch_assoc()) {
        $conteudo = $mysqli->real_escape_string($item['Conteudo']);
        $mysqli->query("INSERT INTO itemdiretriz (ID_Diretriz, Conteudo) VALUES ($id_diretriz_nova, '$conteudo')");
      }
    }

    $res = $mysqli->query("SELECT * FROM padrao WHERE ID_Servico = $id_atual");
    while ($p = $res->fetch_assoc()) {
      $titulo = $mysqli->real_escape_string($p['Titulo']);
      $mysqli->query("INSERT INTO padrao (Titulo, ID_Servico) VALUES ('$titulo', $novo_id)");
      $id_padrao_novo = $mysqli->insert_id;

      $itens = $mysqli->query("SELECT * FROM itempadrao WHERE ID_Padrao = {$p['ID']}");
      while ($item = $itens->fetch_assoc()) {
        $conteudo = $mysqli->real_escape_string($item['Conteudo']);
        $mysqli->query("INSERT INTO itempadrao (ID_Padrao, Conteudo) VALUES ($id_padrao_novo, '$conteudo')");
      }
    }

    $res = $mysqli->query("SELECT * FROM checklist WHERE ID_Servico = $id_atual");
    while ($c = $res->fetch_assoc()) {
      $item = $mysqli->real_escape_string($c['NomeItem']);
      $obs = $mysqli->real_escape_string($c['Observacao']);
      $mysqli->query("INSERT INTO checklist (ID_Servico, NomeItem, Observacao) VALUES ($novo_id, '$item', '$obs')");
    }

    header("Location: manage_addservico.php?id=$novo_id&sucesso=1");
    exit;
  } else {
    echo "Erro ao criar nova versão: " . $stmt->error;
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['acao'] === 'criar_servico' && !$modo_edicao) {
  $titulo = $_POST['nome_servico'];
  $descricao = $_POST['descricao_servico'];
  $subcategoria = $_POST['id_subcategoria'];
  $kbs = $_POST['base_conhecimento'] ?? 'Não tem';
  $area = $_POST['area_especialista'];
  $po = $_POST['po_responsavel'];
  $alcadas = $_POST['alcadas'] ?? 'Não tem';
  $excecao = $_POST['procedimento_excecao'] ?? 'Não tem';
  $obs = $_POST['observacoes_gerais'] ?? 'Não tem';
  $criador = $_POST['usuario_criador'];
  $versao = "1.0";

  $stmt_insert = $mysqli->prepare("INSERT INTO servico 
        (versao, Titulo, Descricao, ID_SubCategoria, KBs, UltimaAtualizacao, 
        area_especialista, po_responsavel, alcadas, procedimento_excecao, observacoes, 
        usuario_criador, status_ficha) 
        VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, 'rascunho')");

  $stmt_insert->bind_param(
    "sssisssssss",
    $versao,
    $titulo,
    $descricao,
    $subcategoria,
    $kbs,
    $area,
    $po,
    $alcadas,
    $excecao,
    $obs,
    $criador
  );

  if ($stmt_insert->execute()) {
    $id_servico = $stmt_insert->insert_id;
    $stmt_insert->close();

    $codigo_ficha = "FCH-" . str_pad($id_servico, 4, "0", STR_PAD_LEFT);

    $stmt_update = $mysqli->prepare("UPDATE servico SET codigo_ficha = ? WHERE ID = ?");
    $stmt_update->bind_param("si", $codigo_ficha, $id_servico);
    $stmt_update->execute();
    $stmt_update->close();

    if (!empty($_POST['diretrizes'])) {
      foreach ($_POST['diretrizes'] as $diretriz) {
        if (!empty($diretriz['titulo'])) {
          $titulo_dir = $mysqli->real_escape_string($diretriz['titulo']);
          $mysqli->query("INSERT INTO diretriz (Titulo, ID_Servico) VALUES ('$titulo_dir', $id_servico)");
          $id_diretriz = $mysqli->insert_id;
          if (!empty($diretriz['itens'])) {
            foreach ($diretriz['itens'] as $item) {
              if (!empty($item)) {
                $conteudo = $mysqli->real_escape_string($item);
                $mysqli->query("INSERT INTO itemdiretriz (ID_Diretriz, Conteudo) VALUES ($id_diretriz, '$conteudo')");
              }
            }
          }
        }
      }
    }

    if (!empty($_POST['padroes'])) {
      foreach ($_POST['padroes'] as $padrao) {
        if (!empty($padrao['titulo'])) {
          $titulo_pad = $mysqli->real_escape_string($padrao['titulo']);
          $mysqli->query("INSERT INTO padrao (Titulo, ID_Servico) VALUES ('$titulo_pad', $id_servico)");
          $id_padrao = $mysqli->insert_id;
          if (!empty($padrao['itens'])) {
            foreach ($padrao['itens'] as $item) {
              if (!empty($item)) {
                $conteudo = $mysqli->real_escape_string($item);
                $mysqli->query("INSERT INTO itempadrao (ID_Padrao, Conteudo) VALUES ($id_padrao, '$conteudo')");
              }
            }
          }
        }
      }
    }

    if (!empty($_POST['checklist'])) {
      foreach ($_POST['checklist'] as $item) {
        if (!empty($item['item'])) {
          $nome = $mysqli->real_escape_string($item['item']);
          $obs_item = $mysqli->real_escape_string($item['observacao']);
          $mysqli->query("INSERT INTO checklist (ID_Servico, NomeItem, Observacao) VALUES ($id_servico, '$nome', '$obs_item')");
        }
      }
    }

    header("Location: ../list/manage_listservico.php?sucesso=1");
    exit;
  } else {
    $mensagem = "Erro ao criar serviço: " . $stmt_insert->error;
    $stmt_insert->close();
  }
}

$tipo_usuario = $_GET['tipo'] ?? 'criador'; // 'criador', 'revisor', 'po'
if (isset($_GET['forcar_status'])) {
  $dados_edicao['status_ficha'] = $_GET['forcar_status'];
}

$diretrizes = [];

if ($modo_edicao) {
  $stmt = $mysqli->prepare("
        SELECT d.ID AS diretriz_id, d.Titulo AS diretriz_titulo, i.Conteudo AS item_conteudo
        FROM diretriz d
        LEFT JOIN itemdiretriz i ON d.ID = i.ID_Diretriz
        WHERE d.ID_Servico = ?
        ORDER BY d.ID, i.ID
    ");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $result = $stmt->get_result();

  while ($row = $result->fetch_assoc()) {
    $dir_id = $row['diretriz_id'];
    if (!isset($diretrizes[$dir_id])) {
      $diretrizes[$dir_id] = [
        'titulo' => $row['diretriz_titulo'],
        'itens' => []
      ];
    }
    if (!empty($row['item_conteudo'])) {
      $diretrizes[$dir_id]['itens'][] = $row['item_conteudo'];
    }
  }
}

$padroes = [];

if ($modo_edicao) {
  $stmt = $mysqli->prepare("
        SELECT p.ID AS padrao_id, p.Titulo AS padrao_titulo, i.Conteudo AS item_conteudo
        FROM padrao p
        LEFT JOIN itempadrao i ON p.ID = i.ID_Padrao
        WHERE p.ID_Servico = ?
        ORDER BY p.ID, i.ID
    ");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $result = $stmt->get_result();

  while ($row = $result->fetch_assoc()) {
    $padrao_id = $row['padrao_id'];
    if (!isset($padroes[$padrao_id])) {
      $padroes[$padrao_id] = [
        'titulo' => $row['padrao_titulo'],
        'itens' => []
      ];
    }
    if (!empty($row['item_conteudo'])) {
      $padroes[$padrao_id]['itens'][] = $row['item_conteudo'];
    }
  }
}

$checklist = [];

if ($modo_edicao) {
  $stmt = $mysqli->prepare("
        SELECT NomeItem, Observacao FROM checklist
        WHERE ID_Servico = ?
        ORDER BY ID
    ");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $result = $stmt->get_result();

  while ($row = $result->fetch_assoc()) {
    $checklist[] = [
      'item' => $row['NomeItem'],
      'observacao' => $row['Observacao']
    ];
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['acao'] === 'enviar_revisao_novamente') {
  $id = intval($_GET['id']);
  $justificativa = $_POST['justificativa'] ?? 'Devolvido pelo PO para ajustes.';

  $stmt = $mysqli->prepare("
        UPDATE servico SET 
            status_ficha = 'em_revisao', 
            justificativa_rejeicao = ? 
        WHERE ID = ?
    ");
  $stmt->bind_param("si", $justificativa, $id);
  $stmt->execute();
  $stmt->close();

  header("Location: ../list/manage_listservico.php?sucesso=1");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['acao'] === 'publicar_ficha') {
  $id_nova_versao = intval($_GET['id']);

  $codigo_ficha = null;
  $stmt_find = $mysqli->prepare("SELECT codigo_ficha FROM servico WHERE ID = ?");
  $stmt_find->bind_param("i", $id_nova_versao);
  $stmt_find->execute();
  $result = $stmt_find->get_result();
  if ($row = $result->fetch_assoc()) {
    $codigo_ficha = $row['codigo_ficha'];
  }
  $stmt_find->close();

  $stmt_pub = $mysqli->prepare("UPDATE servico SET status_ficha = 'publicado' WHERE ID = ?");
  $stmt_pub->bind_param("i", $id_nova_versao);
  $stmt_pub->execute();
  $stmt_pub->close();

  if ($codigo_ficha) {
    $stmt_update_old = $mysqli->prepare(
      "UPDATE servico SET status_ficha = 'substituida' 
             WHERE codigo_ficha = ? AND ID != ? AND status_ficha = 'publicado'"
    );
    $stmt_update_old->bind_param("si", $codigo_ficha, $id_nova_versao);
    $stmt_update_old->execute();
    $stmt_update_old->close();
  }

  header("Location: ../list/manage_listservico.php?sucesso=1");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['acao'] === 'enviar_revisao') {
  $id = intval($_GET['id']);

  $titulo    = $_POST['nome_servico'];
  $descricao = $_POST['descricao_servico'];
  $subcategoria = $_POST['id_subcategoria'];
  $kbs       = $_POST['base_conhecimento'];
  $area      = $_POST['area_especialista'];
  $po        = $_POST['po_responsavel'];
  $alcadas   = $_POST['alcadas'];
  $excecao   = $_POST['procedimento_excecao'];
  $obs       = $_POST['observacoes_gerais'];
  $criador   = $_POST['usuario_criador'];

  $stmt = $mysqli->prepare("UPDATE servico SET 
    Titulo = ?, Descricao = ?, ID_SubCategoria = ?, KBs = ?, 
    UltimaAtualizacao = NOW(), area_especialista = ?, 
    po_responsavel = ?, alcadas = ?, procedimento_excecao = ?, 
    observacoes = ?, usuario_criador = ?, status_ficha = 'em_revisao'
    WHERE ID = ?");

  $stmt->bind_param(
    "ssisssssssi",
    $titulo,
    $descricao,
    $subcategoria,
    $kbs,
    $area,
    $po,
    $alcadas,
    $excecao,
    $obs,
    $criador,
    $id
  );

  $stmt->execute();
  $stmt->close();

  $revisores_selecionados = $_POST['revisores_ids'] ?? [];

  $stmt_delete = $mysqli->prepare("DELETE FROM servico_revisores WHERE servico_id = ?");
  $stmt_delete->bind_param("i", $id);
  $stmt_delete->execute();
  $stmt_delete->close();

  if (!empty($revisores_selecionados)) {
    $stmt_assign = $mysqli->prepare("INSERT INTO servico_revisores (servico_id, revisor_id) VALUES (?, ?)");
    foreach ($revisores_selecionados as $revisor_id) {
      $stmt_assign->bind_param("ii", $id, $revisor_id);
      $stmt_assign->execute();
    }
    $stmt_assign->close();
  }

  $res = $mysqli->query("SELECT codigo_ficha FROM servico WHERE ID = $id LIMIT 1");
  if ($row = $res->fetch_assoc()) {
    $codigo_ficha = $mysqli->real_escape_string($row['codigo_ficha']);
  }

  $mysqli->query("DELETE FROM diretriz WHERE ID_Servico = $id");
  $mysqli->query("DELETE FROM padrao WHERE ID_Servico = $id");
  $mysqli->query("DELETE FROM checklist WHERE ID_Servico = $id");

  if (!empty($_POST['diretrizes'])) {
    foreach ($_POST['diretrizes'] as $diretriz) {
      $titulo = $mysqli->real_escape_string($diretriz['titulo']);
      $mysqli->query("INSERT INTO diretriz (Titulo, ID_Servico) VALUES ('$titulo', $id)");
      $id_diretriz = $mysqli->insert_id;

      if (!empty($diretriz['itens'])) {
        foreach ($diretriz['itens'] as $item) {
          $conteudo = $mysqli->real_escape_string($item);
          $mysqli->query("INSERT INTO itemdiretriz (ID_Diretriz, Conteudo) VALUES ($id_diretriz, '$conteudo')");
        }
      }
    }
  }

  if (!empty($_POST['padroes'])) {
    foreach ($_POST['padroes'] as $padrao) {
      $titulo = $mysqli->real_escape_string($padrao['titulo']);
      $mysqli->query("INSERT INTO padrao (Titulo, ID_Servico) VALUES ('$titulo', $id)");
      $id_padrao = $mysqli->insert_id;

      if (!empty($padrao['itens'])) {
        foreach ($padrao['itens'] as $item) {
          $conteudo = $mysqli->real_escape_string($item);
          $mysqli->query("INSERT INTO itempadrao (ID_Padrao, Conteudo) VALUES ($id_padrao, '$conteudo')");
        }
      }
    }
  }

  if (!empty($_POST['checklist'])) {
    foreach ($_POST['checklist'] as $item) {
      $nome = $mysqli->real_escape_string($item['item']);
      $obs  = $mysqli->real_escape_string($item['observacao']);
      $mysqli->query("INSERT INTO checklist (ID_Servico, NomeItem, Observacao) VALUES ($id, '$nome', '$obs')");
    }
  }

  header("Location: ../list/manage_listservico.php?sucesso=1");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['acao'] === 'aprovar_revisor') {
  $id = intval($_GET['id']);

  $stmt = $mysqli->prepare("
        UPDATE servico SET 
            status_ficha = 'revisada', 
            data_revisao = NOW() 
        WHERE ID = ?
    ");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $stmt->close();

  header("Location: ../list/manage_listservico.php?sucesso=1");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['acao'] === 'enviar_para_aprovacao') {
  $id = intval($_GET['id']);

  $stmt = $mysqli->prepare("UPDATE servico SET status_ficha = 'em_aprovacao' WHERE ID = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $stmt->close();

  header("Location: ../list/manage_listservico.php?sucesso=1");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['acao'] === 'aprovar_po') {
  $id_servico_aprovado = intval($_GET['id']);

  $nome_po_responsavel = null;
  $stmt_find_resp = $mysqli->prepare("SELECT po_responsavel FROM servico WHERE ID = ?");
  $stmt_find_resp->bind_param("i", $id_servico_aprovado);
  $stmt_find_resp->execute();
  $result_resp = $stmt_find_resp->get_result();
  if ($servico_row = $result_resp->fetch_assoc()) {
    $nome_po_responsavel = $servico_row['po_responsavel'];
  }
  $stmt_find_resp->close();

  if ($nome_po_responsavel) {
    $email_po_aprovador = null;
    $stmt_find_email = $mysqli->prepare("SELECT email FROM pos WHERE nome = ?");
    $stmt_find_email->bind_param("s", $nome_po_responsavel);
    $stmt_find_email->execute();
    $result_email = $stmt_find_email->get_result();
    if ($po_row = $result_email->fetch_assoc()) {
      $email_po_aprovador = $po_row['email'];
    }
    $stmt_find_email->close();

    $stmt_update = $mysqli->prepare("
            UPDATE servico SET 
                status_ficha = 'aprovada', 
                po_aprovador_nome = ?, 
                po_aprovador_email = ?, 
                data_aprovacao = NOW() 
            WHERE ID = ?
        ");
    $stmt_update->bind_param("ssi", $nome_po_responsavel, $email_po_aprovador, $id_servico_aprovado);
    $stmt_update->execute();
    $stmt_update->close();

    header("Location: ../list/manage_listservico.php?sucesso=1");
    exit;
  } else {
    die("Erro: Não foi possível aprovar. Nenhum PO Responsável está definido para este serviço.");
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['acao'] === 'reprovar_revisor') {
  $id = intval($_GET['id']);
  $justificativa = $_POST['justificativa'] ?? 'Sem justificativa';

  $stmt = $mysqli->prepare("
        UPDATE servico SET 
            status_ficha = 'reprovado_revisor', 
            justificativa_rejeicao = ? 
        WHERE ID = ?
    ");
  $stmt->bind_param("si", $justificativa, $id);
  $stmt->execute();
  $stmt->close();
  header("Location: ../list/manage_listservico.php?sucesso=1");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['acao'] === 'reativar_para_revisao') {
  $id = intval($_GET['id']);

  $stmt = $mysqli->prepare("UPDATE servico SET status_ficha = 'rascunho' WHERE ID = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $stmt->close();

  header("Location: ../list/manage_listservico.php?sucesso=1");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['acao'] === 'cancelar_ficha') {
  $id = intval($_GET['id']);

  $stmt = $mysqli->prepare("UPDATE servico SET status_ficha = 'cancelada' WHERE ID = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $stmt->close();

  header("Location: ../list/manage_listservico.php?sucesso=1");
  exit;
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <title>Adicionar Serviço</title>
  <link rel="stylesheet" href="style_manage_add.css">
  <?php if (in_array($tipo_usuario, ['revisor', 'po'])): ?>
  <?php endif; ?>
</head>

<body class="<?= ($tipo_usuario === 'revisor') ? 'modo-visualizacao' : '' ?>">
  <?php
  $todos_status = [
    'rascunho',
    'em_revisao',
    'revisada',
    'em_aprovacao',
    'aprovada',
    'publicado',
    'cancelada',
    'reprovado_revisor',
    'reprovado_po',
    'substituida'
  ];
  ?>

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
        $todos_status = ['rascunho', 'em_revisao', 'revisada', 'em_aprovacao', 'aprovada', 'publicado', 'cancelada', 'reprovado_revisor', 'reprovado_po', 'substituida'];
        foreach ($todos_status as $status_opcao): ?>
          <option value="<?= $status_opcao ?>" <?= (($dados_edicao['status_ficha'] ?? '') === $status_opcao) ? 'selected' : '' ?>>
            <?= ucfirst(str_replace('_', ' ', $status_opcao)) ?>
          </option>
        <?php endforeach; ?>
      </select>
    <?php endif; ?>

    <button id="debug-apply-btn">Aplicar e Recarregar</button>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const applyBtn = document.getElementById('debug-apply-btn');

      if (applyBtn) {
        applyBtn.addEventListener('click', function() {
          const currentUrl = new URL(window.location.href);
          const params = currentUrl.searchParams;

          const novoTipo = document.getElementById('debug-tipo-usuario').value;
          params.set('tipo', novoTipo);

          const statusSelect = document.getElementById('debug-status-ficha');
          if (statusSelect) {
            const novoStatus = statusSelect.value;
            if (novoStatus) {
              params.set('forcar_status', novoStatus);
            } else {
              params.delete('forcar_status');
            }
          }

          window.location.href = currentUrl.pathname + '?' + params.toString();
        });
      }
    });
  </script>
  </div>
  <div class="form-wrapper">
    <h2 class="form-title">
      <?php echo $modo_edicao ? "Editar Ficha " . htmlspecialchars($dados_edicao['codigo_ficha'] ?? '') . " (v" . htmlspecialchars($dados_edicao['versao'] ?? '') . ")" : "Adicionar Serviço"; ?>
    </h2>
    <a href="../list/manage_listservico.php" class="btn-back">← Voltar para lista</a>
    <?php if ($modo_edicao): ?>
      <p><strong>Status da Ficha:</strong>
        <?php
        switch ($dados_edicao['status_ficha']) {
          case 'rascunho':
            echo "📝 Rascunho";
            break;
          case 'em_revisao':
            echo "🔍 Em revisão";
            break;
          case 'revisada':
            echo "✅ Revisada";
            break;
          case 'em_aprovacao':
            echo "🕒 Em aprovação";
            break;
          case 'aprovada':
            echo "☑️ Aprovada";
            break;
          case 'publicado':
            echo "📢 Publicado";
            break;
          case 'cancelada':
            echo "🚫 Cancelada";
            break;
          case 'reprovado_revisor':
            echo "❌ Reprovado pelo Revisor";
            break;
          case 'reprovado_po':
            echo "❌ Reprovado pelo PO";
            break;
          case 'substituida':
            echo "♻️ Substituída";
            break;
          default:
            echo "—";
            break;
        }
        ?>
      </p>
    <?php endif; ?>
    <?php if (!empty($dados_edicao['justificativa_rejeicao']) && in_array($dados_edicao['status_ficha'], ['rascunho', 'em_revisao', 'reprovado_revisor', 'reprovado_po'])): ?>
      <div style="margin-top: 15px; margin-bottom: 15px; padding: 10px; background-color: #ffe6e6; border-left: 4px solid #cc0000;">
        <strong>Justificativa da Reprovação:</strong><br>
        <em><?php echo nl2br(htmlspecialchars($dados_edicao['justificativa_rejeicao'] ?? 'Nenhuma')); ?></em>
      </div>
    <?php endif; ?>
    <?php if (!empty($mensagem)): ?>
      <div class="mensagem">
        <?php echo htmlspecialchars($mensagem); ?>
      </div>
    <?php endif; ?>
    <?php
    function campo_desabilitado($tipo_usuario)
    {
      return in_array($tipo_usuario, ['revisor', 'po']) ? 'disabled' : '';
    }
    function campo_somente_leitura($tipo_usuario)
    {
      return in_array($tipo_usuario, ['revisor', 'po']) ? 'readonly' : '';
    }
    ?>
    <form id="form-ficha" method="post" class="form-grid">
      <div class="form-column">
        <label>Nome do Serviço:
          <textarea name="nome_servico" rows="1" oninput="autoResize(this)" required><?php echo htmlspecialchars($dados_edicao['Titulo'] ?? '') ?></textarea>
        </label>

        <label>Descrição do Serviço:
          <textarea name="descricao_servico" rows="4"><?php echo htmlspecialchars($dados_edicao['Descricao'] ?? '') ?></textarea>
        </label>

        <h3>Informações Adicionais</h3>

        <label>Área Especialista:
          <textarea name="area_especialista" rows="1" oninput="autoResize(this)" required><?php echo htmlspecialchars($dados_edicao['area_especialista'] ?? '') ?></textarea>
        </label>

        <label>Alçadas:
          <textarea name="alcadas" rows="1" oninput="autoResize(this)"><?php echo htmlspecialchars($dados_edicao['alcadas'] ?? '') ?></textarea>
        </label>

        <label>Procedimento de Exceção:
          <textarea name="procedimento_excecao" rows="1" oninput="autoResize(this)"><?php echo htmlspecialchars($dados_edicao['procedimento_excecao'] ?? '') ?></textarea>
        </label>

        <label>Base de Conhecimento:
          <textarea name="base_conhecimento" rows="1" oninput="autoResize(this)"><?php echo htmlspecialchars($dados_edicao['KBs'] ?? '') ?></textarea>
        </label>

        <label>PO Responsável:
          <select name="po_responsavel" required>
            <option value="">Selecione um PO...</option>
            <?php foreach ($lista_pos as $po): ?>
              <option value="<?= htmlspecialchars($po['nome']) ?>" <?= (($dados_edicao['po_responsavel'] ?? '') === $po['nome']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($po['nome']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>

        <?php if (false): ?> <!-- Trocar para $tipo_usuario === 'super_admim' ou um cargo com as devidas permissões -->
          <label>Status da Ficha:
            <select name="status_ficha">
              <option value="rascunho" <?= ($dados_edicao['status_ficha'] ?? '') === 'rascunho' ? 'selected' : '' ?>>📝 Rascunho</option>
              <option value="em_revisao" <?= ($dados_edicao['status_ficha'] ?? '') === 'em_revisao' ? 'selected' : '' ?>>🔍 Em revisão</option>
              <option value="revisada" <?= ($dados_edicao['status_ficha'] ?? '') === 'revisada' ? 'selected' : '' ?>>✅ Revisada</option>
              <option value="em_aprovacao" <?= ($dados_edicao['status_ficha'] ?? '') === 'em_aprovacao' ? 'selected' : '' ?>>🕒 Em aprovação</option>
              <option value="aprovada" <?= ($dados_edicao['status_ficha'] ?? '') === 'aprovada' ? 'selected' : '' ?>>☑️ Aprovada</option>
              <option value="publicado" <?= ($dados_edicao['status_ficha'] ?? '') === 'publicado' ? 'selected' : '' ?>>📢 Publicado</option>
              <option value="cancelada" <?= ($dados_edicao['status_ficha'] ?? '') === 'cancelada' ? 'selected' : '' ?>>🚫 Cancelada</option>
              <option value="reprovado_revisor" <?= ($dados_edicao['status_ficha'] ?? '') === 'reprovado_revisor' ? 'selected' : '' ?>>❌ Reprovado pelo Revisor</option>
              <option value="reprovado_po" <?= ($dados_edicao['status_ficha'] ?? '') === 'reprovado_po' ? 'selected' : '' ?>>❌ Reprovado pelo PO</option>
            </select>
          </label>
        <?php endif; ?>
        <div class="form-column">
          <label>Subcategoria:
            <select name="id_subcategoria" required>
              <option value="">Selecione uma subcategoria</option>
              <?php foreach ($subcategorias as $sub): ?>
                <option value="<?php echo $sub['ID']; ?>" <?php if (($dados_edicao['ID_SubCategoria'] ?? '') == $sub['ID']) echo 'selected'; ?>>
                  <?php echo htmlspecialchars($sub['Titulo']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>
          <?php 
          if ($modo_edicao): 
          ?>
            <div class="revisores-container">
              <label>Revisores Designados</label>
              <p class="form-text">Selecione um ou mais revisores da lista.</p>

              <div class="checkbox-list">
                <?php foreach ($lista_revisores as $revisor): ?>
                  <label class="checkbox-label">
                    <input
                      type="checkbox"
                      name="revisores_ids[]"
                      value="<?= $revisor['ID'] ?>"
                      <?= in_array($revisor['ID'], $revisores_servico) ? 'checked' : '' ?>>
                    <?= htmlspecialchars($revisor['nome']) ?>
                    <span class="revisor-email">(<?= htmlspecialchars($revisor['email']) ?>)</span>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
          <h3>Diretrizes</h3>
          <div id="diretrizes">
            <?php $index = 0; ?>
            <?php foreach ($diretrizes as $diretriz): ?>
              <div class="grupo">
                <label>Diretriz <?= $index + 1 ?> - Título:</label>
                <textarea name="diretrizes[<?= $index ?>][titulo]" rows="1" oninput="autoResize(this)"><?= htmlspecialchars($diretriz['titulo']) ?></textarea>
                <div id="itens_diretriz_<?= $index ?>">
                  <?php foreach ($diretriz['itens'] as $item): ?>
                    <textarea name="diretrizes[<?= $index ?>][itens][]" rows="1" oninput="autoResize(this)" placeholder="Item da diretriz"><?= htmlspecialchars($item) ?></textarea><br>
                  <?php endforeach; ?>
                </div>
                <button type="button" class="btn-salvar" onclick="adicionarItemDiretriz(<?= $index ?>)">+ Item</button>
              </div>
              <?php $index++; ?>
            <?php endforeach; ?>
          </div>
          <button type="button" class="btn-salvar" onclick="adicionarDiretriz()">+ Adicionar Diretriz</button>

          <h3>Padrões</h3>
          <div id="padroes">
            <?php $index = 0; ?>
            <?php foreach ($padroes as $padrao): ?>
              <div class="grupo">
                <label>Padrão <?= $index + 1 ?> - Título:</label>
                <textarea name="padroes[<?= $index ?>][titulo]" rows="1" oninput="autoResize(this)"><?= htmlspecialchars($padrao['titulo']) ?></textarea>
                <div id="itens_padrao_<?= $index ?>">
                  <?php foreach ($padrao['itens'] as $item): ?>
                    <textarea name="padroes[<?= $index ?>][itens][]" rows="1" oninput="autoResize(this)" placeholder="Item do padrão"><?= htmlspecialchars($item) ?></textarea><br>
                  <?php endforeach; ?>
                </div>
                <button type="button" class="btn-salvar" onclick="adicionarItemPadrao(<?= $index ?>)">+ Item</button>
              </div>
              <?php $index++; ?>
            <?php endforeach; ?>
          </div>
          <button type="button" class="btn-salvar" onclick="adicionarPadrao()">+ Adicionar Padrão</button>

          <h3>Checklist de Verificação</h3>
          <div id="checklist">
            <?php $index = 0; ?>
            <?php foreach ($checklist as $item): ?>
              <div class="grupo">
                <label>Item <?= $index + 1 ?>:</label>
                <textarea name="checklist[<?= $index ?>][item]" rows="1" oninput="autoResize(this)"><?= htmlspecialchars($item['item']) ?></textarea>
                <label>Observação <?= $index + 1 ?>:</label>
                <textarea name="checklist[<?= $index ?>][observacao]" rows="1" oninput="autoResize(this)"><?= htmlspecialchars($item['observacao']) ?></textarea>
              </div>
              <?php $index++; ?>
            <?php endforeach; ?>
          </div>
          <button type="button" class="btn-salvar" onclick="adicionarChecklist()">+ Adicionar Item</button>

          <h3>Observações Gerais</h3>
          <textarea name="observacoes_gerais" rows="4" oninput="autoResize(this)"><?php echo htmlspecialchars($dados_edicao['observacoes'] ?? '') ?></textarea>
        </div>
        <?php if ($modo_edicao && isset($dados_edicao['codigo_ficha'])): ?>
          <div class="historico-versoes">
            <strong>Versões:</strong>
            <?php
            $codigo = $dados_edicao['codigo_ficha'];
            $res = $mysqli->query("SELECT ID, versao, status_ficha FROM servico WHERE codigo_ficha = '$codigo' ORDER BY versao ASC");
            while ($ver = $res->fetch_assoc()) {
              echo "<a href='manage_addservico.php?id={$ver['ID']}' class='versao-link'>v{$ver['versao']} ({$ver['status_ficha']})</a> ";
            }
            ?>
          </div>
        <?php endif; ?>
        <div class="form-actions-horizontal">
          <div class="group-btns">
            <input type="hidden" name="usuario_criador" value="Service-Desk/WD">

            <?php
            if (!$modo_edicao) {
              if ($tipo_usuario === 'criador') {
                echo '<button type="submit" class="btn-salvar" name="acao" value="criar_servico">Criar serviço</button>';
              }
            } else {
              $status = $dados_edicao['status_ficha'] ?? 'rascunho';

              if ($tipo_usuario === 'criador') {
                if ($status === 'rascunho' || $status === 'reprovado_revisor' || $status === 'reprovado_po') {
                  echo '<button type="submit" class="btn-salvar" name="acao" value="enviar_revisao" style="margin-right: 4px;">Enviar para Revisão</button>';
                }
                if ($status === 'revisada') {
                  echo '<button type="submit" class="btn-salvar" name="acao" value="enviar_para_aprovacao" style="margin-right: 4px;">Enviar para Aprovação do PO</button>';
                  echo '<button type="submit" class="btn-danger" name="acao" value="cancelar_ficha" onclick="return confirm(\'Tem certeza que deseja cancelar esta ficha?\');">Cancelar Ficha</button>';
                }
                if ($status === 'publicado') {
                  echo '<button type="submit" class="btn-salvar" name="acao" value="nova_versao_auto" style="margin-right: 4px;">Nova Versão</button>';
                }
                if ($status === 'cancelada') {
                  echo '<button type="submit" class="btn-salvar" name="acao" value="reativar_para_revisao">Reativar e Enviar para Revisão</button>';
                }
              }

              if ($tipo_usuario === 'revisor' && $status === 'em_revisao') {
                echo '<button type="submit" class="btn-salvar" name="acao" value="aprovar_revisor" style="margin-right: 4px;">Concluir Revisão</button>';
                echo '<button type="button" class="btn-danger" onclick="mostrarJustificativa(\'reprovar_revisor\')">Reprovar (Volta p/ Criador)</button>';
              }

              if ($tipo_usuario === 'po') {
                if ($status === 'em_aprovacao') {
                  echo '<button type="submit" class="btn-salvar" name="acao" value="aprovar_po" style="margin-right: 4px;">Aprovar Ficha</button>';
                  echo '<button type="button" class="btn-salvar" onclick="mostrarJustificativa(\'enviar_revisao_novamente\')" style="background-color: #5bc0de; border-color: #46b8da;">Devolver para Revisão</button>';
                }
                if ($status === 'aprovada') {
                  echo '<button type="submit" class="btn-salvar" name="acao" value="publicar_ficha" style="margin-right: 4px;">Publicar Ficha</button>';
                  echo '<button type="submit" class="btn-danger" name="acao" value="cancelar_ficha" onclick="return confirm(\'Tem certeza que deseja cancelar esta ficha?\');">Cancelar</button>';
                }
              }
            }
            ?>
            <?php
            $pode_excluir = false;
            if (isset($status)) {
              if ($tipo_usuario === 'po') {
                $pode_excluir = true;
              } elseif ($tipo_usuario === 'criador' && in_array($status, ['rascunho', 'reprovado_revisor', 'reprovado_po', 'em_revisao'])) {
                $pode_excluir = true;
              }
            }

            if ($modo_edicao && $pode_excluir):
            ?>
              <button type="submit" class="btn-danger" name="acao" value="excluir" onclick="return confirm('Tem certeza que deseja excluir permanentemente este serviço? Esta ação não pode ser desfeita.');">Excluir</button>
              <input type="hidden" name="delete_id" value="<?php echo intval($_GET['id']); ?>">
            <?php endif; ?>
          </div>
        </div>
    </form>
    <form method="post" id="form-reprovacao" style="display:none; margin-top: 20px;">
      <label for="justificativa">Justificativa da Reprovação:</label><br>
      <textarea name="justificativa" id="justificativa" rows="4" cols="60" required></textarea><br><br>
      <input type="hidden" name="acao" id="justificativa-submit-acao" value="">
      <button type="submit" class="btn-danger">Enviar Reprovação</button>
    </form>
    <script>
      let contDiretriz = <?= count($diretrizes) ?>;
      let contPadrao = <?= count($padroes) ?>;
      let contChecklist = <?= count($checklist) ?>;
    </script>
    <script src="addservico.js"></script>
    <input type="hidden" id="justificativa-submit-acao" value="">
</body>

</html>