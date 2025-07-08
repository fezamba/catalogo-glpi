<?php
require_once 'conexao.php';

if ($mysqli->connect_errno) {
    die("Erro: " . $mysqli->connect_error);
}

$id_categoria = $_GET['id'] ?? 0;

// Busca todas as categorias para a barra lateral
$categorias = [];
$result_all_cats = $mysqli->query("SELECT * FROM categoria ORDER BY Titulo ASC");
while ($cat = $result_all_cats->fetch_assoc()) {
    $cat['subcategorias'] = [];
    $subres = $mysqli->query("SELECT * FROM subcategoria WHERE ID_Categoria = {$cat['ID']} ORDER BY Titulo ASC");
    while ($sub = $subres->fetch_assoc()) {
        $cat['subcategorias'][] = $sub;
    }
    $categorias[] = $cat;
}

// Busca os detalhes da categoria atual
$stmt = $mysqli->prepare("SELECT Titulo, Descricao FROM categoria WHERE ID = ?");
$stmt->bind_param("i", $id_categoria);
$stmt->execute();
$stmt->bind_result($tituloCategoria, $descricaoCategoria);
$stmt->fetch();
$stmt->close();

// Busca as subcategorias da categoria atual para exibir no conteúdo
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
    <title><?php echo htmlspecialchars($tituloCategoria); ?></title>
</head>

<body>

    <div class="layout">
        <aside class="sidebar">
            <div class="menu-item">
                <button class="menu-button todas-categorias" onclick="window.location.href='index.php'">
                    <span>Todas Categorias</span>
                </button>
            </div>

            <?php foreach ($categorias as $cat): ?>
                <div class="menu-item">
                    <?php 
                        // Adiciona a classe 'active' se esta for a categoria atual
                        $is_active_cat = ($cat['ID'] == $id_categoria) ? 'active' : '';
                    ?>
                    <button class="menu-button accordion-toggle <?= $is_active_cat ?>">
                        <span><?php echo htmlspecialchars($cat['Titulo']); ?></span>
                        <span class="badge"><?php echo count($cat['subcategorias']); ?></span>
                    </button>
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

        <section class="content">
            <h1><?php echo htmlspecialchars($tituloCategoria); ?></h1>
            <p><?php echo htmlspecialchars($descricaoCategoria); ?></p>

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
        document.addEventListener('DOMContentLoaded', () => {
            const accordionToggles = document.querySelectorAll('.accordion-toggle');

            accordionToggles.forEach(button => {
                const submenu = button.nextElementSibling;

                // Se a categoria estiver ativa, expande o submenu ao carregar a página
                if (button.classList.contains('active') && submenu && submenu.classList.contains('submenu')) {
                    submenu.style.maxHeight = submenu.scrollHeight + "px";
                }

                button.addEventListener('click', function() {
                    if (submenu && submenu.classList.contains('submenu')) {
                        // Alterna a classe 'active' para o estilo visual
                        this.classList.toggle('active');
                        
                        if (submenu.style.maxHeight) {
                            submenu.style.maxHeight = null;
                        } else {
                            submenu.style.maxHeight = submenu.scrollHeight + "px";
                        }
                    }
                });
            });
        });
    </script>
</body>

</html>
