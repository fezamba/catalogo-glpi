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

// O resto da sua lógica de busca de dados continua aqui...
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
        /* Estilos que você já tinha */
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; color: #333; margin: 0; padding: 20px; }
        .wrapper { max-width: 900px; margin: 0 auto; background-color: #fff; padding: 30px 40px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        
        .section-title { font-size: 20px; color: #333; border-bottom: 3px solid #f9b000; padding-bottom: 8px; margin-top: 35px; margin-bottom: 20px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px 30px; }
        .info-item strong { display: block; font-size: 14px; color: #555; margin-bottom: 4px; }
        .info-item span, .info-item p { font-size: 16px; color: #000; margin: 0; line-height: 1.6; }
        .grupo { background-color: #f8f9fa; border-left: 4px solid #ccc; margin-bottom: 15px; padding: 15px 20px; border-radius: 0 8px 8px 0; }
        .grupo h4 { margin: 0 0 10px 0; font-size: 16px; }
        .grupo p, .grupo li { font-size: 15px; line-height: 1.6; }
        .grupo ul { padding-left: 20px; margin: 0; }
        
        /* --- MUDANÇAS E ADIÇÕES NO CSS --- */

        .header { 
            display: flex; /* <-- NOVO */
            justify-content: space-between; /* <-- NOVO */
            align-items: flex-start; /* <-- NOVO */
            border-bottom: 1px solid #eee; 
            padding-bottom: 15px; 
            margin-bottom: 25px; 
        }
        .header-info h1 { margin: 0; font-size: 28px; }
        .header-info .meta { font-size: 14px; color: #777; margin-top: 5px; }
        .header-info .meta strong { color: #000; }
        .header-info .breadcrumb a { color: #007bff; text-decoration: none; }
        .header-info .breadcrumb a:hover { text-decoration: underline; }

        .btn {
            display: inline-flex;
            align-items: center;
            padding: 10px 18px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s;
            font-size: 14px;
        }
        .btn-back { background-color: #e0e0e0; color: #333; }
        .btn-back:hover { background-color: #d1d1d1; }
        .btn-primary { background-color: #f9b000; color: white; }
        .btn-primary:hover { background-color: #d89a00; }

    </style>
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