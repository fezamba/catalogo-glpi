document.addEventListener('DOMContentLoaded', function () {
    const categoriaSelect = document.getElementById('categoria');
    const subcategoriaSelect = document.getElementById('subcategoria');
    const servicoSelect = document.getElementById('servico');
    const descricaoContainer = document.getElementById('descricao-container');
    const descricaoTexto = document.getElementById('descricao-servico');
    const inputBusca = document.getElementById('busca-global');
    const resultadosBox = document.getElementById('resultados-busca');
    const form = document.querySelector('.form-wrapper form');
    const dropdownsWrapper = document.querySelector('.form-grid');

    let servicosMap = {};

    categoriaSelect.addEventListener('change', () => {
        const categoriaId = categoriaSelect.value;
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
                servicosMap = {};
                servicoSelect.innerHTML = '<option value="">Selecione...</option>';
                data.forEach(serv => {
                    servicoSelect.add(new Option(serv.Titulo, serv.ID));
                    servicosMap[serv.ID] = serv.Descricao;
                });
            });
    });

    servicoSelect.addEventListener('change', () => {
        const descricao = servicosMap[servicoSelect.value];
        if (descricao) {
            descricaoTexto.textContent = descricao;
            descricaoContainer.style.display = 'block';
        } else {
            descricaoContainer.style.display = 'none';
        }
    });

    inputBusca.addEventListener('input', () => {
        const termo = inputBusca.value.trim();
        if (termo.length < 2) {
            resultadosBox.style.display = 'none';
            return;
        }

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

    resultadosBox.addEventListener('click', function(event) {
        const itemClicado = event.target.closest('.resultado-item');
        if (!itemClicado || !itemClicado.dataset.id) return;

        const idServico = itemClicado.dataset.id;
        const tituloServico = itemClicado.dataset.titulo;
        const descricaoServico = itemClicado.dataset.descricao;

        inputBusca.value = tituloServico;
        resultadosBox.style.display = 'none';

        descricaoTexto.textContent = descricaoServico;
        descricaoContainer.style.display = 'block';

        if (dropdownsWrapper) dropdownsWrapper.style.display = 'none';

        let campoOculto = form.querySelector('input[name="servico_id_selecionado"]');
        if (!campoOculto) {
            campoOculto = document.createElement('input');
            campoOculto.type = 'hidden';
            campoOculto.name = 'servico_id_selecionado';
            form.appendChild(campoOculto);
        }
        campoOculto.value = idServico;
    });

    document.addEventListener('click', e => {
        if (!inputBusca.contains(e.target) && !resultadosBox.contains(e.target)) {
            resultadosBox.style.display = 'none';
        }
    });
});