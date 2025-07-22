<?php
// Define o cabeçalho da resposta como texto puro com codificação UTF-8.
// Isso garante que o output seja um texto simples, ideal para ser usado como base de conhecimento.
header('Content-Type: text/plain; charset=utf-8');

// Inclui o arquivo de conexão com o banco de dados.
require_once 'conexao.php';

// --- Busca Inicial de IDs de Serviços ---
// Busca os IDs de todos os serviços que não estão em um estado final como 'cancelada' ou 'substituida'.
$result_servicos = $mysqli->query("
    SELECT ID FROM servico 
    WHERE status_ficha NOT IN ('cancelada', 'substituida')
    ORDER BY ID
");

// Se a consulta falhar, encerra o script com uma mensagem de erro.
if (!$result_servicos) {
    die("Erro ao buscar a lista de serviços.");
}

// Pega todos os IDs de serviços e os armazena em um array.
$todos_servicos_ids = $result_servicos->fetch_all(MYSQLI_ASSOC);

// --- Loop Principal para Gerar a Ficha de Cada Serviço ---
// Itera sobre cada ID de serviço encontrado.
foreach ($todos_servicos_ids as $servico_data) {
    $id_servico = $servico_data['ID'];

    // --- Busca dos Dados Detalhados do Serviço ---
    // Prepara uma consulta para buscar os detalhes completos do serviço atual, incluindo nomes de categoria e subcategoria.
    $stmt = $mysqli->prepare("
        SELECT 
            s.*, 
            sub.Titulo as subcategoria_titulo, 
            cat.Titulo as categoria_titulo
        FROM servico s
        LEFT JOIN subcategoria sub ON s.ID_SubCategoria = sub.ID
        LEFT JOIN categoria cat ON sub.ID_Categoria = cat.ID
        WHERE s.ID = ?
    ");
    $stmt->bind_param("i", $id_servico);
    $stmt->execute();
    $servico = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Se, por algum motivo, o serviço não for encontrado, pula para o próximo no loop.
    if (!$servico) {
        continue;
    }

    // --- Impressão dos Dados da Ficha ---
    // Imprime um cabeçalho para a ficha.
    echo "### INÍCIO DA FICHA: " . htmlspecialchars($servico['codigo_ficha'] ?? 'N/A') . " ###\n\n";
    // Imprime os campos principais do serviço.
    echo "Título do Serviço: " . htmlspecialchars($servico['Titulo'] ?? 'N/A') . "\n";
    // strip_tags() remove quaisquer tags HTML da descrição antes de imprimir.
    echo "Descrição: " . htmlspecialchars(strip_tags($servico['Descricao'] ?? 'N/A')) . "\n";
    echo "Área Especialista: " . htmlspecialchars($servico['area_especialista'] ?? 'N/A') . "\n";
    echo "PO Responsável: " . htmlspecialchars($servico['po_responsavel'] ?? 'N/A') . "\n\n";
    echo "Categoria: " . htmlspecialchars($servico['categoria_titulo'] ?? 'N/A') . "\n";
    echo "Subcategoria: " . htmlspecialchars($servico['subcategoria_titulo'] ?? 'N/A') . "\n\n";

    // --- Busca e Impressão de Diretrizes ---
    // NOTA DE PERFORMANCE: As queries dentro do loop podem impactar o desempenho.
    // Uma abordagem mais otimizada seria buscar todos os dados relacionados de uma vez e agrupá-los em PHP.
    $res_dir = $mysqli->query("SELECT ID, Titulo FROM diretriz WHERE ID_Servico = $id_servico ORDER BY ID");
    if ($res_dir && $res_dir->num_rows > 0) {
        echo "# DIRETRIZES\n";
        while ($dir = $res_dir->fetch_assoc()) {
            echo "## " . htmlspecialchars($dir['Titulo']) . "\n";
            $res_item_dir = $mysqli->query("SELECT Conteudo FROM itemdiretriz WHERE ID_Diretriz = {$dir['ID']} ORDER BY ID");
            while ($item = $res_item_dir->fetch_assoc()) {
                echo "- " . htmlspecialchars($item['Conteudo']) . "\n";
            }
        }
        echo "\n";
    }

    // --- Busca e Impressão de Padrões ---
    $res_pad = $mysqli->query("SELECT ID, Titulo FROM padrao WHERE ID_Servico = $id_servico ORDER BY ID");
    if ($res_pad && $res_pad->num_rows > 0) {
        echo "# PADRÕES\n";
        while ($pad = $res_pad->fetch_assoc()) {
            echo "## " . htmlspecialchars($pad['Titulo']) . "\n";
            $res_item_pad = $mysqli->query("SELECT Conteudo FROM itempadrao WHERE ID_Padrao = {$pad['ID']} ORDER BY ID");
            while ($item = $res_item_pad->fetch_assoc()) {
                echo "- " . htmlspecialchars($item['Conteudo']) . "\n";
            }
        }
        echo "\n";
    }

    // --- Busca e Impressão do Checklist ---
    $checklist = $mysqli->query("SELECT NomeItem, Observacao FROM checklist WHERE ID_Servico = $id_servico ORDER BY ID")->fetch_all(MYSQLI_ASSOC);
    if (!empty($checklist)) {
        echo "# CHECKLIST DE VERIFICAÇÃO\n";
        foreach ($checklist as $item) {
            echo "- " . htmlspecialchars($item['NomeItem']) . " (Observação: " . htmlspecialchars($item['Observacao']) . ")\n";
        }
        echo "\n";
    }
    
    // --- Impressão de Outras Informações ---
    echo "# OUTRAS INFORMAÇÕES\n";
    echo "Alçadas: " . htmlspecialchars($servico['alcadas'] ?? 'Não informado.') . "\n";
    echo "Procedimento de Exceção: " . htmlspecialchars($servico['procedimento_excecao'] ?? 'Não informado.') . "\n";
    echo "Observações Gerais: " . htmlspecialchars($servico['observacoes'] ?? 'Não informado.') . "\n";

    // Imprime um rodapé para a ficha.
    echo "### FIM DA FICHA: " . htmlspecialchars($servico['codigo_ficha'] ?? 'N/A') . " ###\n\n";
    // Imprime uma linha separadora para facilitar a leitura do relatório.
    echo str_repeat("=", 60) . "\n\n";
}

// Fecha a conexão com o banco de dados para liberar recursos.
$mysqli->close();
?>
