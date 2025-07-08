document.addEventListener('DOMContentLoaded', () => {
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
                html += servicos.map(serv => `
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

    // --- LÓGICA DA NAVBAR (MENU SANFONA) ---
    const accordionToggles = document.querySelectorAll('.accordion-toggle');

    accordionToggles.forEach(button => {
        button.addEventListener('click', function() {
            // Não executa a lógica sanfona para o botão "Todas Categorias"
            if (this.onclick && this.onclick.toString().includes("index.php")) {
                return;
            }
            
            this.classList.toggle('active');
            
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
});
