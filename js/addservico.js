function adicionarDiretriz() {
    let contDiretriz = document.querySelectorAll('#diretrizes .grupo').length;
    const index = contDiretriz++;
    const container = document.createElement('div');
    container.classList.add('grupo');
    container.innerHTML = `
        <label>Diretriz ${index + 1} - Título:</label>
        <textarea name="diretrizes[${index}][titulo]" rows="1" oninput="autoResize(this)"></textarea>
        <div id="itens_diretriz_${index}"></div>
        <button type="button" class="btn-salvar" onclick="adicionarItemDiretriz(${index})">+ Item</button>
    `;
    document.getElementById('diretrizes').appendChild(container);
}

function adicionarItemDiretriz(index) {
    document.getElementById(`itens_diretriz_${index}`).insertAdjacentHTML(
        'beforeend',
        `<textarea name="diretrizes[${index}][itens][]" rows="1" oninput="autoResize(this)" placeholder="Item da diretriz"></textarea><br>`
    );
}

function adicionarPadrao() {
    let contPadrao = document.querySelectorAll('#padroes .grupo').length;
    const index = contPadrao++;
    const container = document.createElement('div');
    container.classList.add('grupo');
    container.innerHTML = `
        <label>Padrão ${index + 1} - Título:</label>
        <textarea name="padroes[${index}][titulo]" rows="1" oninput="autoResize(this)"></textarea>
        <div id="itens_padrao_${index}"></div>
        <button type="button" class="btn-salvar" onclick="adicionarItemPadrao(${index})">+ Item</button>
    `;
    document.getElementById('padroes').appendChild(container);
}

function adicionarItemPadrao(index) {
    document.getElementById(`itens_padrao_${index}`).insertAdjacentHTML(
        'beforeend',
        `<textarea name="padroes[${index}][itens][]" rows="1" oninput="autoResize(this)" placeholder="Item do padrão"></textarea><br>`
    );
}

function adicionarChecklist() {
    let contChecklist = document.querySelectorAll('#checklist .grupo').length;
    const index = contChecklist++;
    const container = document.createElement('div');
    container.classList.add('grupo');
    container.innerHTML = `
        <label>Item ${index + 1}:</label>
        <textarea name="checklist[${index}][item]" rows="1" oninput="autoResize(this)"></textarea>
        <label>Observação ${index + 1}:</label>
        <textarea name="checklist[${index}][observacao]" rows="1" oninput="autoResize(this)"></textarea>
    `;
    document.getElementById('checklist').appendChild(container);
}

function autoResize(el) {
    el.style.height = 'auto';
    el.style.height = el.scrollHeight + 'px';
}

function mostrarJustificativa(acao) {
    const formPrincipal = document.getElementById('form-ficha');
    if (!formPrincipal) {
        console.error('Formulário principal #form-ficha não encontrado.');
        return;
    }

    const justificativaTexto = prompt("Por favor, digite a justificativa para esta ação:", "");

    if (justificativaTexto === null || justificativaTexto.trim() === "") {
        alert("Ação cancelada. A justificativa é obrigatória.");
        return;
    }

    const inputAcao = document.createElement('input');
    inputAcao.type = 'hidden';
    inputAcao.name = 'acao';
    inputAcao.value = acao;
    formPrincipal.appendChild(inputAcao);

    const inputJustificativa = document.createElement('input');
    inputJustificativa.type = 'hidden';
    inputJustificativa.name = 'justificativa';
    inputJustificativa.value = justificativaTexto;
    formPrincipal.appendChild(inputJustificativa);

    formPrincipal.submit();
}

document.addEventListener('DOMContentLoaded', function() {
    const btnEnviarRevisao = document.getElementById('btn-enviar-revisao');
    const firstRevisorCheckbox = document.querySelector('input[name="revisores_ids[]"]');

    if (btnEnviarRevisao) {
        btnEnviarRevisao.addEventListener('click', function(event) {
            
            const revisoresMarcados = document.querySelectorAll('input[name="revisores_ids[]"]:checked').length;
            if (revisoresMarcados === 0 && firstRevisorCheckbox) {
                event.preventDefault();
                firstRevisorCheckbox.setCustomValidity('Por favor, selecione ao menos um revisor.');
                firstRevisorCheckbox.reportValidity();
                return;
            }

            const diretrizesTitulos = document.querySelectorAll('textarea[name^="diretrizes"][name$="[titulo]"]');
            let peloMenosUmTituloPreenchido = false;
            diretrizesTitulos.forEach((textarea) => {
                if (textarea.value.trim() !== '') {
                    peloMenosUmTituloPreenchido = true;
                }
            });

            if (diretrizesTitulos.length > 0 && !peloMenosUmTituloPreenchido) {
                event.preventDefault();
                alert('Erro: Você precisa preencher o título de pelo menos uma diretriz.');
                return;
            }
        });
    }

    if (firstRevisorCheckbox) {
        const allRevisorCheckboxes = document.querySelectorAll('input[name="revisores_ids[]"]');
        allRevisorCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('input', () => {
                firstRevisorCheckbox.setCustomValidity('');
            });
        });
    }
});
