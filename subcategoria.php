<?php
require_once 'conexao.php';

if ($mysqli->connect_errno) {
    die("Erro: " . $mysqli->connect_error);
}

$id_subcategoria = $_GET['id'] ?? 0;

// Busca os detalhes da subcategoria atual e da sua categoria pai
$categoria_pai_id = 0;
$stmt_sub_details = $mysqli->prepare("SELECT Titulo, Descricao, ID_Categoria FROM subcategoria WHERE ID = ?");
$stmt_sub_details->bind_param("i", $id_subcategoria);
$stmt_sub_details->execute();
$result_sub_details = $stmt_sub_details->get_result();
if ($sub_details = $result_sub_details->fetch_assoc()) {
    $subtitulo = $sub_details['Titulo'];
    $subdescricao = $sub_details['Descricao'];
    $categoria_pai_id = $sub_details['ID_Categoria'];
}
$stmt_sub_details->close();

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

// Busca os serviços desta subcategoria
$servicos = [];
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
    <title><?php echo htmlspecialchars($subtitulo ?? 'Subcategoria'); ?></title>
</head>

<body>

    <div class="layout">
        <aside class="sidebar">
            <div class="menu-item">
                <button class="menu-button" onclick="window.location.href='index.php'">
                    <span>Todas Categorias</span>
                </button>
            </div>

            <?php foreach ($categorias as $cat): ?>
                <div class="menu-item">
                    <?php 
                        $is_active_cat = ($cat['ID'] == $categoria_pai_id) ? 'active' : '';
                    ?>
                    <button class="menu-button accordion-toggle <?= $is_active_cat ?>">
                        <span><?php echo htmlspecialchars($cat['Titulo']); ?></span>
                        <span class="badge"><?php echo count($cat['subcategorias']); ?></span>
                    </button>
                    <?php if (count($cat['subcategorias'])): ?>
                        <div class="submenu">
                            <?php foreach ($cat['subcategorias'] as $sub): ?>
                                <?php
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

        <section class="content">
            <h1><?php echo htmlspecialchars($subtitulo ?? 'Subcategoria não encontrada'); ?></h1>
            <p><?php echo htmlspecialchars($subdescricao ?? ''); ?></p>

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
            const accordionToggles = document.querySelectorAll('.accordion-toggle');

            accordionToggles.forEach(button => {
                const submenu = button.nextElementSibling;

                if (button.classList.contains('active') && submenu && submenu.classList.contains('submenu')) {
                    submenu.style.maxHeight = submenu.scrollHeight + "px";
                }

                button.addEventListener('click', function() {
                    if (submenu && submenu.classList.contains('submenu')) {
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
