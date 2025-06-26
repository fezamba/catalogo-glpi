function showErrorPopup(message) {
    const popup = document.getElementById('error-popup');
    const messageElement = document.getElementById('error-message');
    
    if (popup && messageElement) {
        messageElement.textContent = message;
        popup.style.display = 'flex';
    } else {
        alert(message);
    }
}

function autoResize(el) {
    el.style.height = 'auto';
    el.style.height = el.scrollHeight + 'px';
}

function adicionarDiretriz() {
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

function mostrarJustificativa(tipo) {
    const formReprovacao = document.getElementById('form-reprovacao');
    const campoAcao = document.getElementById('justificativa-submit-acao');

    formReprovacao.style.display = 'block';
    campoAcao.value = tipo;
    formReprovacao.scrollIntoView({ behavior: 'smooth' });
}

document.addEventListener('DOMContentLoaded', function() {
    contDiretriz = document.querySelectorAll('#diretrizes .grupo').length;
    contPadrao = document.querySelectorAll('#padroes .grupo').length;
    contChecklist = document.querySelectorAll('#checklist .grupo').length;

    const btnEnviarRevisao = document.getElementById('btn-enviar-revisao');
    
    if (btnEnviarRevisao) {
        btnEnviarRevisao.addEventListener('click', function(event) {
            
            const revisoresMarcados = document.querySelectorAll('input[name="revisores_ids[]"]:checked').length;
            if (revisoresMarcados === 0) {
                event.preventDefault();
                showErrorPopup('É obrigatório selecionar ao menos um revisor.');
                return;
            }

            const diretrizesTitulos = document.querySelectorAll('textarea[name^="diretrizes"][name$="[titulo]"]');
            let peloMenosUmTituloPreenchido = false;
            
            if (diretrizesTitulos.length === 0) {
                 event.preventDefault();
                 showErrorPopup('É necessário adicionar pelo menos uma Diretriz.');
                 return;
            }

            diretrizesTitulos.forEach((textarea) => {
                if (textarea.value.trim() !== '') {
                    peloMenosUmTituloPreenchido = true;
                }
            });

            if (!peloMenosUmTituloPreenchido) {
                event.preventDefault();
                showErrorPopup('Você precisa preencher o título de pelo menos uma diretriz.');
                return; 
            }
            
        });
    }
});
