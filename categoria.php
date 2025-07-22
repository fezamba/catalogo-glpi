<?php
// Inclui o arquivo de conexão com o banco de dados.
require_once 'conexao.php';

// Verifica se a conexão com o banco de dados foi bem-sucedida.
if ($mysqli->connect_errno) {
    die("Erro: " . $mysqli->connect_error);
}

// Obtém o ID da categoria da URL. Se não for fornecido, o padrão é 0.
$id_categoria = $_GET['id'] ?? 0;

// --- Lógica para Montar o Menu Lateral (Sidebar) ---
$categorias = [];
// Busca todas as categorias para construir o menu lateral completo.
$result_all_cats = $mysqli->query("SELECT * FROM categoria ORDER BY Titulo ASC");
// Itera sobre cada categoria principal.
while ($cat = $result_all_cats->fetch_assoc()) {
    $cat['subcategorias'] = [];
    // NOTA DE PERFORMANCE: A execução de uma query dentro de um loop (N+1 Query Problem)
    // pode impactar o desempenho se houver muitas categorias.
    // Para um menu, o impacto pode ser aceitável, mas para grandes volumes de dados,
    // seria mais eficiente buscar todas as subcategorias de uma vez e agrupá-las em PHP.
    $subres = $mysqli->query("SELECT * FROM subcategoria WHERE ID_Categoria = {$cat['ID']} ORDER BY Titulo ASC");
    // Itera sobre as subcategorias encontradas e as adiciona ao array da categoria pai.
    while ($sub = $subres->fetch_assoc()) {
        $cat['subcategorias'][] = $sub;
    }
    $categorias[] = $cat;
}

// --- Lógica para o Conteúdo Principal da Página ---
// Prepara uma consulta para buscar o título e a descrição da categoria ativa (selecionada).
$stmt = $mysqli->prepare("SELECT Titulo, Descricao FROM categoria WHERE ID = ?");
$stmt->bind_param("i", $id_categoria);
$stmt->execute();
$stmt->bind_result($tituloCategoria, $descricaoCategoria);
$stmt->fetch();
$stmt->close();

// Busca as subcategorias que pertencem à categoria ativa para exibi-las como cartões no conteúdo.
$subcategorias_pagina = [];
$stmt_sub = $mysqli->prepare("SELECT * FROM subcategoria WHERE ID_Categoria = ? ORDER BY Titulo ASC");
$stmt_sub->bind_param("i", $id_categoria);
$stmt_sub->execute();
$result_sub = $stmt_sub->get_result();
while ($sub_page = $result_sub->fetch_assoc()) {
    $subcategorias_pagina[] = $sub_page;
}
$stmt_sub->close();
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="css/categoria.css" />
    <!-- O título da página é dinâmico, baseado no nome da categoria selecionada. -->
    <title><?php echo htmlspecialchars($tituloCategoria); ?></title>
</head>

<body>

    <div class="layout">
        <!-- A barra lateral (sidebar) exibe todas as categorias e suas subcategorias em um menu acordeão. -->
        <aside class="sidebar">
            <div class="menu-item">
                <button class="menu-button todas-categorias" onclick="window.location.href='index.php'">
                    <span>Todas Categorias</span>
                    <!-- Badge (selo) mostrando o número total de categorias. -->
                    <span class="badge"><?php echo count($categorias); ?></span>
                </button>
            </div>

            <!-- Itera sobre o array de categorias para construir o menu. -->
            <?php foreach ($categorias as $cat): ?>
                <div class="menu-item">
                    <?php
                    // Verifica se a categoria atual é a que está ativa (selecionada na URL) para aplicar a classe 'active'.
                    $is_active_cat = ($cat['ID'] == $id_categoria) ? 'active' : '';
                    ?>
                    <button class="menu-button accordion-toggle <?= $is_active_cat ?>">
                        <span><?php echo htmlspecialchars($cat['Titulo']); ?></span>
                        <span class="badge"><?php echo count($cat['subcategorias']); ?></span>
                    </button>
                    <!-- Se a categoria tiver subcategorias, cria um submenu. -->
                    <?php if (count($cat['subcategorias'])): ?>
                        <div class="submenu">
                            <?php foreach ($cat['subcategorias'] as $sub): ?>
                                <a href="subcategoria.php?id=<?php echo $sub['ID']; ?>">
                                    <?php echo htmlspecialchars($sub['Titulo']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </aside>

        <!-- A seção de conteúdo principal exibe os detalhes da categoria selecionada. -->
        <section class="content">
            <h1><?php echo htmlspecialchars($tituloCategoria); ?></h1>
            <p><?php echo htmlspecialchars($descricaoCategoria); ?></p>

            <!-- Grid para exibir as subcategorias da categoria ativa como cartões clicáveis. -->
            <div class="subcards-grid">
                <?php foreach ($subcategorias_pagina as $sub): ?>
                    <a href="subcategoria.php?id=<?php echo $sub['ID']; ?>" class="subcard">
                        <h3><?php echo htmlspecialchars($sub['Titulo']); ?></h3>
                        <p><?php echo htmlspecialchars($sub['Descricao']); ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
    <script>
        // Adiciona um ouvinte de eventos que executa o código quando o DOM estiver carregado.
        document.addEventListener('DOMContentLoaded', () => {
            // Seleciona todos os botões que funcionam como um 'acordeão'.
            const accordionToggles = document.querySelectorAll('.accordion-toggle');

            accordionToggles.forEach(button => {
                // Encontra o submenu que é o próximo irmão do botão.
                const submenu = button.nextElementSibling;

                // Se o botão já tiver a classe 'active' ao carregar a página,
                // expande o submenu correspondente.
                if (button.classList.contains('active') && submenu && submenu.classList.contains('submenu')) {
                    submenu.style.maxHeight = submenu.scrollHeight + "px";
                }

                // Adiciona um evento de clique a cada botão do acordeão.
                button.addEventListener('click', function() {
                    // Verifica se existe um submenu para este botão.
                    if (submenu && submenu.classList.contains('submenu')) {
                        // Alterna a classe 'active' no botão.
                        this.classList.toggle('active');

                        // Se o submenu estiver visível (tem um maxHeight), oculta-o.
                        if (submenu.style.maxHeight) {
                            submenu.style.maxHeight = null;
                        } else {
                            // Se estiver oculto, define o maxHeight para a sua altura total, revelando-o com uma animação CSS.
                            submenu.style.maxHeight = submenu.scrollHeight + "px";
                        }
                    }
                });
            });
        });
    </script>
</body>

</html>
