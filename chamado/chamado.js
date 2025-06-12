document.addEventListener('DOMContentLoaded', function () {
    // --- ELEMENTOS DO DOM ---
    const categoriaSelect = document.getElementById('categoria');
    const subcategoriaSelect = document.getElementById('subcategoria');
    const servicoSelect = document.getElementById('servico');
    const descricaoContainer = document.getElementById('descricao-container');
    const descricaoTexto = document.getElementById('descricao-servico');
    const inputBusca = document.getElementById('busca-global');
    const resultadosBox = document.getElementById('resultados-busca');
    const form = document.querySelector('.form-wrapper form');
    const dropdownsWrapper = document.querySelector('.form-grid');

    // Mapeamento para guardar descrições (usado pelos dropdowns)
    let servicosMap = {};

    // --- LÓGICA DOS DROPDOWNS EM CASCATA ---

    // Quando o usuário MUDA A CATEGORIA
    categoriaSelect.addEventListener('change', () => {
        const categoriaId = categoriaSelect.value;
        // Reseta e esconde os campos seguintes
        subcategoriaSelect.innerHTML = '<option value="">Carregando...</option>';
        servicoSelect.innerHTML = '<option value="">Selecione uma subcategoria</option>';
        document.getElementById('servico-wrapper').style.display = 'none';
        descricaoContainer.style.display = 'none';

        if (!categoriaId) {
            document.getElementById('subcategoria-wrapper').style.display = 'none';
            return;
        }

        document.getElementById('subcategoria-wrapper').style.display = 'block';
        fetch(`carregar_subcategorias.php?categoria_id=${categoriaId}`)
            .then(res => res.json())
            .then(data => {
                subcategoriaSelect.innerHTML = '<option value="">Selecione...</option>';
                data.forEach(sub => {
                    subcategoriaSelect.add(new Option(sub.Titulo, sub.ID));
                });
            });
    });

    // Quando o usuário MUDA A SUBCATEGORIA
    subcategoriaSelect.addEventListener('change', () => {
        const subId = subcategoriaSelect.value;
        servicoSelect.innerHTML = '<option value="">Carregando...</option>';
        descricaoContainer.style.display = 'none';

        if (!subId) {
            document.getElementById('servico-wrapper').style.display = 'none';
            return;
        }

        document.getElementById('servico-wrapper').style.display = 'block';
        fetch(`carregar_servicos.php?subcategoria_id=${subId}`)
            .then(res => res.json())
            .then(data => {
                servicosMap = {}; // Limpa o mapa antigo
                servicoSelect.innerHTML = '<option value="">Selecione...</option>';
                data.forEach(serv => {
                    servicoSelect.add(new Option(serv.Titulo, serv.ID));
                    servicosMap[serv.ID] = serv.Descricao; // Armazena a descrição
                });
            });
    });

    // Quando o usuário SELECIONA O SERVIÇO (pelo dropdown)
    servicoSelect.addEventListener('change', () => {
        const descricao = servicosMap[servicoSelect.value];
        if (descricao) {
            descricaoTexto.textContent = descricao;
            descricaoContainer.style.display = 'block';
        } else {
            descricaoContainer.style.display = 'none';
        }
    });

    // --- LÓGICA DA BUSCA OTIMIZADA ---

    // Quando o usuário DIGITA NA BUSCA
    inputBusca.addEventListener('input', () => {
        const termo = inputBusca.value.trim();
        if (termo.length < 2) {
            resultadosBox.style.display = 'none';
            return;
        }

        // Usa o seu script PHP original de busca
        fetch('buscar_servicos.php?termo=' + encodeURIComponent(termo))
            .then(res => res.json())
            .then(data => {
                resultadosBox.innerHTML = '';
                if (data.length > 0) {
                    resultadosBox.innerHTML = data.map(serv => `
                        <div class="resultado-item" 
                             data-id="${serv.id}" 
                             data-titulo="${serv.titulo}" 
                             data-descricao="${serv.descricao}">
                            <strong class="resultado-titulo">${serv.titulo}</strong>
                            <span class="resultado-contexto">em ${serv.categoria} > ${serv.subcategoria}</span>
                            <small class="resultado-desc">${serv.descricao.substring(0, 80)}...</small>
                        </div>
                    `).join('');
                    resultadosBox.style.display = 'block';
                } else {
                    resultadosBox.innerHTML = '<div class="resultado-item">Nenhum serviço encontrado.</div>';
                    resultadosBox.style.display = 'block';
                }
            });
    });

    // Quando o usuário CLICA EM UM RESULTADO DA BUSCA
    resultadosBox.addEventListener('click', function(event) {
        const itemClicado = event.target.closest('.resultado-item');
        if (!itemClicado || !itemClicado.dataset.id) return; // Sai se não for um item válido

        // AÇÃO OTIMIZADA: pega os dados direto do elemento, sem nova busca
        const idServico = itemClicado.dataset.id;
        const tituloServico = itemClicado.dataset.titulo;
        const descricaoServico = itemClicado.dataset.descricao;

        // 1. Preenche o campo de busca e esconde os resultados
        inputBusca.value = tituloServico;
        resultadosBox.style.display = 'none';

        // 2. Mostra a descrição do serviço
        descricaoTexto.textContent = descricaoServico;
        descricaoContainer.style.display = 'block';
        
        // 3. Esconde os dropdowns manuais para evitar confusão
        if (dropdownsWrapper) dropdownsWrapper.style.display = 'none';

        // 4. Adiciona um campo oculto com o ID do serviço para o formulário
        let campoOculto = form.querySelector('input[name="servico_id_selecionado"]');
        if (!campoOculto) {
            campoOculto = document.createElement('input');
            campoOculto.type = 'hidden';
            campoOculto.name = 'servico_id_selecionado';
            form.appendChild(campoOculto);
        }
        campoOculto.value = idServico;
    });

    // Fecha os resultados se clicar fora
    document.addEventListener('click', e => {
        if (!inputBusca.contains(e.target) && !resultadosBox.contains(e.target)) {
            resultadosBox.style.display = 'none';
        }
    });
});