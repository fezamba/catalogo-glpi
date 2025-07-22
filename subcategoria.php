<?php
// Inclui o arquivo de conexão com o banco de dados.
require_once 'conexao.php';

// Verifica se a conexão com o banco de dados foi bem-sucedida.
if ($mysqli->connect_errno) {
    die("Erro: " . $mysqli->connect_error);
}

// Obtém o ID da subcategoria da URL. Se não for fornecido, o padrão é 0.
$id_subcategoria = $_GET['id'] ?? 0;

// --- Busca dos Detalhes da Subcategoria Ativa ---
$categoria_pai_id = 0; // Inicializa o ID da categoria pai.
// Prepara uma consulta para buscar os detalhes da subcategoria atual.
$stmt_sub_details = $mysqli->prepare("SELECT Titulo, Descricao, ID_Categoria FROM subcategoria WHERE ID = ?");
$stmt_sub_details->bind_param("i", $id_subcategoria);
$stmt_sub_details->execute();
$result_sub_details = $stmt_sub_details->get_result();
// Se a subcategoria for encontrada, armazena seus detalhes em variáveis.
if ($sub_details = $result_sub_details->fetch_assoc()) {
    $subtitulo = $sub_details['Titulo'];
    $subdescricao = $sub_details['Descricao'];
    $categoria_pai_id = $sub_details['ID_Categoria']; // Essencial para saber qual menu acordeão abrir.
}
$stmt_sub_details->close();

// --- Lógica para Montar o Menu Lateral (Sidebar) ---
$categorias = [];
// Busca todas as categorias para construir o menu lateral completo.
$result_all_cats = $mysqli->query("SELECT * FROM categoria ORDER BY Titulo ASC");
// Itera sobre cada categoria principal.
while ($cat = $result_all_cats->fetch_assoc()) {
    $cat['subcategorias'] = [];
    // NOTA DE PERFORMANCE: A execução de uma query dentro de um loop (N+1 Query Problem)
    // pode impactar o desempenho. Para um menu, o impacto pode ser aceitável.
    $subres = $mysqli->query("SELECT * FROM subcategoria WHERE ID_Categoria = {$cat['ID']} ORDER BY Titulo ASC");
    // Itera sobre as subcategorias encontradas e as adiciona ao array da categoria pai.
    while ($sub = $subres->fetch_assoc()) {
        $cat['subcategorias'][] = $sub;
    }
    $categorias[] = $cat;
}

// --- Busca dos Serviços da Subcategoria Ativa ---
$servicos = [];
// Prepara uma consulta para buscar todos os serviços publicados que pertencem à subcategoria atual.
$stmt_servicos = $mysqli->prepare("SELECT ID, Titulo, Descricao, KBs FROM servico WHERE ID_SubCategoria = ? AND status_ficha = 'publicado'");
$stmt_servicos->bind_param("i", $id_subcategoria);
$stmt_servicos->execute();
$result_servicos = $stmt_servicos->get_result();
while ($row = $result_servicos->fetch_assoc()) {
    $servicos[] = $row;
}
$stmt_servicos->close();
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="css/subcategoria.css" />
    <!-- O título da página é dinâmico, baseado no nome da subcategoria selecionada. -->
    <title><?php echo htmlspecialchars($subtitulo ?? 'Subcategoria'); ?></title>
</head>

<body>

    <div class="layout">
        <!-- A barra lateral (sidebar) exibe todas as categorias e suas subcategorias em um menu acordeão. -->
        <aside class="sidebar">
            <div class="menu-item">
                <button class="menu-button todas-categorias" onclick="window.location.href='index.php'">
                    <span>Todas Categorias</span>
                    <span class="badge"><?php echo count($categorias); ?></span>
                </button>
            </div>

            <!-- Itera sobre o array de categorias para construir o menu. -->
            <?php foreach ($categorias as $cat): ?>
                <div class="menu-item">
                    <?php
                    // Verifica se a categoria atual é a PAI da subcategoria ativa, para aplicar a classe 'active'.
                    $is_active_cat = ($cat['ID'] == $categoria_pai_id) ? 'active' : '';
                    ?>
                    <button class="menu-button accordion-toggle <?= $is_active_cat ?>">
                        <span><?php echo htmlspecialchars($cat['Titulo']); ?></span>
                        <span class="badge"><?php echo count($cat['subcategorias']); ?></span>
                    </button>
                    <!-- Se a categoria tiver subcategorias, cria um submenu. -->
                    <?php if (count($cat['subcategorias'])): ?>
                        <div class="submenu">
                            <?php foreach ($cat['subcategorias'] as $sub): ?>
                                <?php
                                // Verifica se o link da subcategoria atual é o da página ativa, para aplicar a classe 'active-link'.
                                $is_active_sub = ($sub['ID'] == $id_subcategoria) ? 'active-link' : '';
                                ?>
                                <a href="subcategoria.php?id=<?php echo $sub['ID']; ?>" class="<?= $is_active_sub ?>">
                                    <?php echo htmlspecialchars($sub['Titulo']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </aside>

        <!-- A seção de conteúdo principal exibe os detalhes da subcategoria selecionada e seus serviços. -->
        <section class="content">
            <h1><?php echo htmlspecialchars($subtitulo ?? 'Subcategoria não encontrada'); ?></h1>
            <p><?php echo htmlspecialchars($subdescricao ?? ''); ?></p>

            <!-- Lista os serviços pertencentes a esta subcategoria. -->
            <div class="cards-list-sub">
                <?php foreach ($servicos as $serv): ?>
                    <a href="view_servico.php?id=<?php echo $serv['ID']; ?>" class="service-card">
                        <h3><?php echo htmlspecialchars($serv['Titulo']); ?></h3>
                        <p class="card-description"><?php echo htmlspecialchars($serv['Descricao']); ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Seleciona todos os botões que funcionam como um 'acordeão'.
            const accordionToggles = document.querySelectorAll('.accordion-toggle');

            accordionToggles.forEach(button => {
                const submenu = button.nextElementSibling;

                // Se o botão da categoria pai já tiver a classe 'active' ao carregar a página,
                // expande o submenu correspondente para mostrar a subcategoria ativa.
                if (button.classList.contains('active') && submenu && submenu.classList.contains('submenu')) {
                    submenu.style.maxHeight = submenu.scrollHeight + "px";
                }

                // Adiciona um evento de clique a cada botão do acordeão.
                button.addEventListener('click', function() {
                    if (submenu && submenu.classList.contains('submenu')) {
                        this.classList.toggle('active');

                        // Se o submenu estiver visível, oculta-o.
                        if (submenu.style.maxHeight) {
                            submenu.style.maxHeight = null;
                        } else {
                            // Se estiver oculto, define o maxHeight para a sua altura total, revelando-o.
                            submenu.style.maxHeight = submenu.scrollHeight + "px";
                        }
                    }
                });
            });
        });
    </script>
</body>

</html>
