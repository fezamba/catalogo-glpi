/**
 * Redimensiona automaticamente a altura de um elemento <textarea> para se ajustar ao seu conteúdo.
 * Isso evita barras de rolagem desnecessárias dentro do campo de texto.
 * @param {HTMLElement} el O elemento <textarea> a ser redimensionado.
 */
function autoResize(el) {
  // Reseta a altura para que o scrollHeight possa ser calculado corretamente com base no conteúdo.
  el.style.height = 'auto';
  // Define a altura do elemento para ser igual à sua altura de rolagem (a altura total do conteúdo).
  el.style.height = el.scrollHeight + 'px';
}

// Aplica a função autoResize a todas as textareas existentes assim que a página carrega.
document.querySelectorAll('textarea').forEach(el => autoResize(el));

/**
 * Adiciona dinamicamente um novo grupo de campos para uma "Diretriz" no formulário.
 * Cada diretriz consiste em um título e um ou mais itens.
 */
function adicionarDiretriz() {
  // Calcula o próximo índice para a nova diretriz com base nos grupos já existentes.
  const index = document.querySelectorAll('#diretrizes .grupo').length;
  const container = document.createElement('div');
  container.classList.add('grupo');
  // Define o HTML interno do novo grupo, incluindo um textarea para o título e um botão para adicionar itens.
  // Os nomes dos campos são estruturados como um array (ex: diretrizes[0][titulo]) para facilitar o processamento no backend.
  container.innerHTML = `<label>Diretriz ${index + 1} - Título:</label><textarea name="diretrizes[${index}][titulo]" rows="1" maxlength="255" oninput="autoResize(this)"></textarea><div id="itens_diretriz_${index}"></div><button type="button" class="btn-salvar" onclick="adicionarItemDiretriz(${index})">+ Item</button>`;
  document.getElementById('diretrizes').appendChild(container);
}

/**
 * Adiciona um novo campo de item a uma diretriz específica.
 * @param {number} index O índice da diretriz à qual o item será adicionado.
 */
function adicionarItemDiretriz(index) {
  // Insere um novo textarea para um item dentro do contêiner da diretriz correspondente.
  // O nome do campo (diretrizes[${index}][itens][]) cria um array de itens para essa diretriz no backend.
  document.getElementById(`itens_diretriz_${index}`).insertAdjacentHTML('beforeend', `<textarea name="diretrizes[${index}][itens][]" rows="1" maxlength="1000" oninput="autoResize(this)" placeholder="Item da diretriz"></textarea><br>`);
}

/**
 * Adiciona dinamicamente um novo grupo de campos para um "Padrão" no formulário.
 * A lógica é idêntica à de adicionar uma diretriz.
 */
function adicionarPadrao() {
  const index = document.querySelectorAll('#padroes .grupo').length;
  const container = document.createElement('div');
  container.classList.add('grupo');
  container.innerHTML = `<label>Padrão ${index + 1} - Título:</label><textarea name="padroes[${index}][titulo]" rows="1" maxlength="255" oninput="autoResize(this)"></textarea><div id="itens_padrao_${index}"></div><button type="button" class="btn-salvar" onclick="adicionarItemPadrao(${index})">+ Item</button>`;
  document.getElementById('padroes').appendChild(container);
}

/**
 * Adiciona um novo campo de item a um padrão específico.
 * @param {number} index O índice do padrão ao qual o item será adicionado.
 */
function adicionarItemPadrao(index) {
  document.getElementById(`itens_padrao_${index}`).insertAdjacentHTML('beforeend', `<textarea name="padroes[${index}][itens][]" rows="1" maxlength="1000" oninput="autoResize(this)" placeholder="Item do padrão"></textarea><br>`);
}

/**
 * Adiciona dinamicamente um novo grupo de campos para um item de "Checklist".
 * Cada item de checklist tem um campo para o item em si e um para observações.
 */
function adicionarChecklist() {
  const index = document.querySelectorAll('#checklist .grupo').length;
  const container = document.createElement('div');
  container.classList.add('grupo');
  container.innerHTML = `<label>Item ${index + 1}:</label><textarea name="checklist[${index}][item]" rows="1" maxlength="255" oninput="autoResize(this)"></textarea><label>Observação ${index + 1}:</label><textarea name="checklist[${index}][observacao]" rows="1" maxlength="1000" oninput="autoResize(this)"></textarea>`;
  document.getElementById('checklist').appendChild(container);
}

/**
 * Exibe um modal para que o usuário insira uma justificativa antes de executar uma ação (ex: aprovar, reprovar).
 * @param {string} acao A string que identifica a ação a ser executada (ex: 'aprovar_revisao').
 */
function mostrarJustificativa(acao) {
  const modal = document.getElementById('justificativa-modal');
  modal.style.display = 'block'; // Torna o modal visível.

  // Define a ação do botão de envio do modal.
  document.getElementById('justificativa-submit').onclick = function() {
    const justificativa = document.getElementById('justificativa-texto').value;
    // Valida se a justificativa foi preenchida.
    if (!justificativa.trim()) {
      alert('A justificativa é obrigatória.');
      return;
    }
    const form = document.getElementById('form-ficha');
    // Adiciona a ação e a justificativa como campos ocultos ao formulário principal.
    form.insertAdjacentHTML('beforeend', `<input type="hidden" name="acao" value="${acao}">`);
    form.insertAdjacentHTML('beforeend', `<input type="hidden" name="justificativa" value="${justificativa}">`);
    // Envia o formulário principal.
    form.submit();
  }
}

// Executa o código abaixo quando o DOM (a estrutura da página) estiver completamente carregado.
document.addEventListener('DOMContentLoaded', function() {
  
  // Lógica para o painel de depuração (debug).
  // Permite forçar um status ou simular um usuário através de parâmetros na URL.
  document.getElementById('debug-apply-btn')?.addEventListener('click', function() {
    const url = new URL(window.location.href);
    const params = url.searchParams;
    params.set('forcar_status', document.getElementById('debug-status-ficha').value);
    params.set('simular_usuario', document.getElementById('debug-simular-usuario').value);
    window.location.href = url.toString(); // Recarrega a página com os novos parâmetros.
  });

  // Validações do formulário antes do envio para revisão.
  const btnEnviarRevisao = document.getElementById('btn-enviar-revisao');
  if (btnEnviarRevisao) {
    btnEnviarRevisao.addEventListener('click', function(event) {
      // 1. Validação de Revisores: Verifica se pelo menos um revisor foi selecionado.
      const revisoresMarcados = document.querySelectorAll('input[name="revisores_ids[]"]:checked').length;
      // A verificação só ocorre se o campo de revisores existir na página.
      if (document.querySelector('input[name="revisores_ids[]"]') && revisoresMarcados === 0) {
        event.preventDefault(); // Impede o envio do formulário.
        alert('Erro: Por favor, selecione ao menos um revisor para continuar.');
        return;
      }
      
      // 2. Validação de Diretrizes: Se houver campos de diretriz, pelo menos um título deve ser preenchido.
      const diretrizesTitulos = document.querySelectorAll('textarea[name^="diretrizes"][name$="[titulo]"]');
      const algumTituloPreenchido = Array.from(diretrizesTitulos).some(t => t.value.trim() !== '');
      if (diretrizesTitulos.length > 0 && !algumTituloPreenchido) {
        event.preventDefault(); // Impede o envio do formulário.
        alert('Erro: Você precisa preencher o título de pelo menos uma diretriz.');
        return;
      }
    });
  }
});
