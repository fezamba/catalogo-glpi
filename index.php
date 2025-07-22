<?php
// Inclui o arquivo de conexão com o banco de dados.
require_once 'conexao.php';

// Verifica se a conexão com o banco de dados foi bem-sucedida.
if ($mysqli->connect_errno) {
    die("Erro: " . $mysqli->connect_error);
}

// --- Busca de Dados para o Menu Lateral e Conteúdo ---
$categorias = [];
// Busca todas as categorias principais, ordenadas por título.
$result = $mysqli->query("SELECT * FROM categoria ORDER BY Titulo ASC");

// Itera sobre cada categoria encontrada.
while ($cat = $result->fetch_assoc()) {
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
    // Adiciona a categoria completa (com suas subcategorias) ao array final.
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
</head>

<body>

    <div class="layout">
        <!-- A barra lateral (sidebar) exibe todas as categorias e suas subcategorias em um menu acordeão. -->
        <aside class="sidebar">
            <div class="menu-item todas-categorias">
                <button class="menu-button" onclick="window.location.href='index.php'">
                    <span>Todas Categorias</span>
                    <!-- Badge (selo) mostrando o número total de categorias. -->
                    <span class="badge"><?php echo count($categorias); ?></span>
                </button>
            </div>

            <!-- Itera sobre o array de categorias para construir o menu dinamicamente. -->
            <?php foreach ($categorias as $cat): ?>
                <div class="menu-item">
                    <button class="menu-button accordion-toggle">
                        <span><?php echo htmlspecialchars($cat['Titulo']); ?></span>
                        <span class="badge"><?php echo count($cat['subcategorias']); ?></span>
                    </button>
                    <!-- Se a categoria tiver subcategorias, cria um submenu oculto. -->
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
            <!-- Botões fixos na parte inferior do menu. -->
            <div class="menu-item fixed-bottom">
                <button class="menu-button" style="margin-bottom: 10px;" onclick="window.location.href='chatbot.php'">
                    Assistente Virtual <img src="img/chat.png" alt="Assistente-virtual" class="icon-left2">
                </button>
                <button class="menu-button" onclick="window.location.href='/manage/manage.php'">
                    Edição <img src="img/edit.png" alt="Editar-catalogo" class="icon-left">
                </button>
            </div>
        </aside>

        <!-- A seção de conteúdo principal onde os cartões de gerenciamento são exibidos. -->
        <section class="content">
            <h1>Catálogo de Serviços - Todas as Categorias</h1>
            <p style="margin-bottom: 15px">Para te ajudar com mais agilidade e praticidade, disponibilizamos este catálogo com os principais serviços de TI oferecidos:</p>
            <!-- Campo de busca global -->
            <div style="margin-bottom: 20px;">
                <input type="text" id="busca-global" placeholder="Que serviço você está procurando?"
                    style="display: block; width: 100%; max-width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc; font-size: 14px; box-sizing: border-box;">
                <!-- Contêiner para exibir os resultados da busca dinâmica. -->
                <div id="resultados-busca" style="background: white; border: 1px solid #ddd; border-radius: 6px; text-align: left; display: none;"></div>
            </div>

            <!-- Grid para organizar os cartões das categorias. -->
            <div class="cards-grid">
                <?php foreach ($categorias as $cat): ?>
                    <div class="card-button" onclick="window.location.href='categoria.php?id=<?php echo $cat['ID']; ?>'">
                        <div class="card-header">
                            <h3><?php echo htmlspecialchars($cat['Titulo']); ?></h3>
                            <!-- O nome da imagem é gerado dinamicamente a partir do título da categoria. -->
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
            // --- Lógica do Menu Acordeão ---
            const accordionToggles = document.querySelectorAll('.accordion-toggle');

            accordionToggles.forEach(button => {
                button.addEventListener('click', function() {
                    const submenu = this.nextElementSibling;
                    if (submenu && submenu.classList.contains('submenu')) {
                        // Se o submenu já estiver aberto (tem maxHeight), fecha-o.
                        if (submenu.style.maxHeight) {
                            submenu.style.maxHeight = null;
                        } else {
                            // Se estiver fechado, abre-o definindo o maxHeight para sua altura total.
                            // Isso permite a animação via transição CSS.
                            submenu.style.maxHeight = submenu.scrollHeight + "px";
                        }
                    }
                });
            });

            // --- Lógica da Busca Global ---
            const inputBusca = document.getElementById('busca-global');
            const resultadosBox = document.getElementById('resultados-busca');
            let debounceTimer; // Variável para controlar o debounce.

            if (inputBusca && resultadosBox) {
                const handleSearch = () => {
                    const termo = inputBusca.value.trim();
                    // Só executa a busca se o termo tiver 2 ou mais caracteres.
                    if (termo.length < 2) {
                        resultadosBox.style.display = 'none';
                        return;
                    }
                    // Faz a requisição para o script de busca no backend.
                    fetch('../buscar_servicos.php?termo=' + encodeURIComponent(termo))
                        .then(response => response.json())
                        .then(servicos => {
                            displayResults(servicos); // Chama a função para exibir os resultados.
                        })
                        .catch(error => {
                            console.error('Erro na busca:', error);
                            resultadosBox.innerHTML = '<div class="resultado-item">Ocorreu um erro na busca.</div>';
                            resultadosBox.style.display = 'block';
                        });
                };

                // Função para montar e exibir os resultados da busca.
                const displayResults = (servicos) => {
                    let html = '';
                    if (servicos && servicos.length > 0) {
                        // Mapeia cada serviço para um elemento <a> e os junta em uma única string HTML.
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

                // Adiciona um ouvinte ao evento 'keyup' para acionar a busca.
                inputBusca.addEventListener('keyup', () => {
                    clearTimeout(debounceTimer); // Cancela o timer anterior.
                    // Inicia um novo timer. A busca só será executada 300ms após o usuário parar de digitar.
                    // Isso evita requisições excessivas ao servidor.
                    debounceTimer = setTimeout(handleSearch, 300);
                });

                // Adiciona um ouvinte de clique no documento para fechar a caixa de resultados
                // se o usuário clicar fora dela.
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
