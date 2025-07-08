<?php
require_once 'conexao.php';

if ($mysqli->connect_errno) {
    die("Erro: " . $mysqli->connect_error);
}

$categorias = [];
$result = $mysqli->query("SELECT * FROM categoria ORDER BY Titulo ASC");

while ($cat = $result->fetch_assoc()) {
    $cat['subcategorias'] = [];
    $subres = $mysqli->query("SELECT * FROM subcategoria WHERE ID_Categoria = {$cat['ID']} ORDER BY Titulo ASC");
    while ($sub = $subres->fetch_assoc()) {
        $cat['subcategorias'][] = $sub;
    }
    $categorias[] = $cat;
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="css/index.css" />
    <title>Catálogo de Serviços</title>
    <style>
        .submenu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
            background-color: #f8f9fa;
            border-left: 3px solid #f0ad4e;
            margin-left: 10px;
            padding-left: 5px;
        }

        .menu-button {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }
    </style>
</head>

<body>

    <div class="layout">
        <aside class="sidebar">
            <div class="menu-item">
                <button class="menu-button" onclick="window.location.href='index.php'">
                    <span>Todas Categorias</span>
                    <span class="badge"><?php echo count($categorias); ?></span>
                </button>
            </div>

            <?php foreach ($categorias as $cat): ?>
                <div class="menu-item">
                    <button class="menu-button accordion-toggle">
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
            <div class="menu-item fixed-bottom">
                <button class="menu-button" style="margin-bottom: 10px;" onclick="window.location.href='chatbot.php'">
                    Assistente Virtual <img src="img/chat.png" alt="Assistente-virtual" class="icon-left2">
                </button>
                <button class="menu-button" onclick="window.location.href='/manage/manage.php'">
                    Edição <img src="img/edit.png" alt="Editar-catalogo" class="icon-left">
                </button>
            </div>
        </aside>

        <section class="content">
            <h1>Catálogo de Serviços - Todas as Categorias</h1>
            <p style="margin-bottom: 15px">Para te ajudar com mais agilidade e praticidade, disponibilizamos este catálogo com os principais serviços de TI oferecidos:</p>
            <div style="margin-bottom: 20px;">
                <input type="text" id="busca-global" placeholder="Que serviço você está procurando?"
                    style="display: block; width: 100%; max-width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc; font-size: 14px; box-sizing: border-box;">
                <div id="resultados-busca" style="background: white; border: 1px solid #ddd; border-radius: 6px; text-align: left; display: none;"></div>
            </div>

            <div class="cards-grid">
                <?php foreach ($categorias as $cat): ?>
                    <div class="card-button" onclick="window.location.href='categoria.php?id=<?php echo $cat['ID']; ?>'">
                        <div class="card-header">
                            <h3><?php echo htmlspecialchars($cat['Titulo']); ?></h3>
                            <img src="img/<?php echo strtolower(str_replace(' ', '_', $cat['Titulo'])); ?>.png" alt="<?php echo htmlspecialchars($cat['Titulo']); ?>">
                        </div>
                        <p><?php echo htmlspecialchars($cat['Descricao']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const accordionToggles = document.querySelectorAll('.accordion-toggle');
            accordionToggles.forEach(button => {
                button.addEventListener('click', function() {
                    const submenu = this.nextElementSibling;
                    if (submenu && submenu.classList.contains('submenu')) {
                        if (submenu.style.maxHeight) {
                            submenu.style.maxHeight = null;
                        } else {
                            submenu.style.maxHeight = submenu.scrollHeight + "px";
                        }
                    }
                });
            });

            const inputBusca = document.getElementById('busca-global');
            const resultadosBox = document.getElementById('resultados-busca');
            let debounceTimer;

            if (inputBusca && resultadosBox) {
                const handleSearch = () => {
                    const termo = inputBusca.value.trim();
                    if (termo.length < 2) {
                        resultadosBox.style.display = 'none';
                        return;
                    }
                    fetch('../buscar_servicos.php?termo=' + encodeURIComponent(termo))
                        .then(response => response.json())
                        .then(servicos => {
                            displayResults(servicos);
                        })
                        .catch(error => {
                            console.error('Erro na busca:', error);
                            resultadosBox.innerHTML = '<div class="resultado-item">Ocorreu um erro na busca.</div>';
                            resultadosBox.style.display = 'block';
                        });
                };

                const displayResults = (servicos) => {
                    let html = '';
                    if (servicos && servicos.length > 0) {
                        html = servicos.map(serv => `
                            <a href="../view_servico.php?id=${serv.id}" class="resultado-item">
                                <strong class="resultado-titulo">${serv.titulo}</strong>
                                <span class="resultado-contexto">em ${serv.categoria} > ${serv.subcategoria}</span>
                                <small class="resultado-desc">${serv.descricao.substring(0, 80)}...</small>
                            </a>
                        `).join('');
                    } else {
                        html = '<div class="resultado-item">Nenhum resultado encontrado.</div>';
                    }
                    resultadosBox.innerHTML = html;
                    resultadosBox.style.display = 'block';
                };

                inputBusca.addEventListener('keyup', () => {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(handleSearch, 300);
                });

                document.addEventListener('click', function (event) {
                    if (!inputBusca.contains(event.target) && !resultadosBox.contains(event.target)) {
                        resultadosBox.style.display = 'none';
                    }
                });
            }
        });
    </script>
</body>

</html>
