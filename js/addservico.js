function adicionarDiretriz() {
    const index = document.querySelectorAll('#diretrizes .grupo').length;
    const container = document.createElement('div');
    container.classList.add('grupo');
    container.innerHTML = `<label>Diretriz ${index + 1} - Título:</label><textarea name="diretrizes[${index}][titulo]" rows="1" maxlength="255" oninput="autoResize(this)"></textarea><div id="itens_diretriz_${index}"></div><button type="button" class="btn-salvar" onclick="adicionarItemDiretriz(${index})">+ Item</button>`;
    document.getElementById('diretrizes').appendChild(container);
}

function adicionarItemDiretriz(index) {
    document.getElementById(`itens_diretriz_${index}`).insertAdjacentHTML('beforeend', `<textarea name="diretrizes[${index}][itens][]" rows="1" maxlength="1000" oninput="autoResize(this)" placeholder="Item da diretriz"></textarea><br>`);
}

function adicionarPadrao() {
    const index = document.querySelectorAll('#padroes .grupo').length;
    const container = document.createElement('div');
    container.classList.add('grupo');
    container.innerHTML = `<label>Padrão ${index + 1} - Título:</label><textarea name="padroes[${index}][titulo]" rows="1" maxlength="255" oninput="autoResize(this)"></textarea><div id="itens_padrao_${index}"></div><button type="button" class="btn-salvar" onclick="adicionarItemPadrao(${index})">+ Item</button>`;
    document.getElementById('padroes').appendChild(container);
}

function adicionarItemPadrao(index) {
    document.getElementById(`itens_padrao_${index}`).insertAdjacentHTML('beforeend', `<textarea name="padroes[${index}][itens][]" rows="1" maxlength="1000" oninput="autoResize(this)" placeholder="Item do padrão"></textarea><br>`);
}

function adicionarChecklist() {
    const index = document.querySelectorAll('#checklist .grupo').length;
    const container = document.createElement('div');
    container.classList.add('grupo');
    container.innerHTML = `<label>Item ${index + 1}:</label><textarea name="checklist[${index}][item]" rows="1" maxlength="255" oninput="autoResize(this)"></textarea><label>Observação ${index + 1}:</label><textarea name="checklist[${index}][observacao]" rows="1" maxlength="1000" oninput="autoResize(this)"></textarea>`;
    document.getElementById('checklist').appendChild(container);
}

function autoResize(el) {
    el.style.height = 'auto';
    el.style.height = el.scrollHeight + 'px';
}

function mostrarJustificativa(acao) {
    const modal = document.getElementById('justificativa-modal');
    modal.style.display = 'block';
    document.getElementById('justificativa-submit').onclick = function() {
        const justificativa = document.getElementById('justificativa-texto').value;
        if (!justificativa.trim()) {
            alert('A justificativa é obrigatória.');
            return;
        }
        const form = document.getElementById('form-ficha');
        form.insertAdjacentHTML('beforeend', `<input type="hidden" name="acao" value="${acao}">`);
        form.insertAdjacentHTML('beforeend', `<input type="hidden" name="justificativa" value="${justificativa}">`);
        form.submit();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('debug-apply-btn')?.addEventListener('click', function() {
        const url = new URL(window.location.href);
        const params = url.searchParams;
        params.set('forcar_status', document.getElementById('debug-status-ficha').value);
        params.set('simular_usuario', document.getElementById('debug-simular-usuario').value);
        window.location.href = url.toString();
    });

    const btnEnviarRevisao = document.getElementById('btn-enviar-revisao');
    if (btnEnviarRevisao) {
        btnEnviarRevisao.addEventListener('click', function(event) {
            const form = document.getElementById('form-ficha');
            
            const revisoresMarcados = document.querySelectorAll('input[name="revisores_ids[]"]:checked').length;
            if (document.querySelector('input[name="revisores_ids[]"]') && revisoresMarcados === 0) {
                event.preventDefault();
                alert('Erro: Por favor, selecione ao menos um revisor para continuar.');
                return;
            }

            const validarTitulos = (selector, nomeDoCampo) => {
                const campos = form.querySelectorAll(selector);
                for (const campo of campos) {
                    if (campo.value.trim() === '') {
                        event.preventDefault();
                        alert(`Erro: O campo "${nomeDoCampo}" não pode estar vazio.`);
                        campo.focus();
                        campo.style.border = '2px solid red';
                        campo.addEventListener('input', () => {
                            campo.style.border = '';
                        }, { once: true });
                        return false;
                    }
                }
                return true;
            };

            if (!validarTitulos('textarea[name^="diretrizes"][name$="[titulo]"]', 'Título da Diretriz')) {
                return;
            }
        });
    }
});
