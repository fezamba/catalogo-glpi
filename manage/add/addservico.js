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
    `
        <textarea name="diretrizes[${index}][itens][]" rows="1" oninput="autoResize(this)" placeholder="Item da diretriz"></textarea><br>
      `
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
    `
        <textarea name="padroes[${index}][itens][]" rows="1" oninput="autoResize(this)" placeholder="Item do padrão"></textarea><br>
      `
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

window.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('form-ficha');

  form.addEventListener('submit', function (e) {
    if (!form.querySelector("input[name='usuario_criador']")) {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'usuario_criador';
      input.value = 'Service-Desk/WD';
      form.appendChild(input);
    }
  });
});

function autoResize(el) {
  el.style.height = 'auto';
  el.style.height = el.scrollHeight + 'px';
}

function mostrarJustificativa(tipo) {
  const formReprovacao = document.getElementById('form-reprovacao');
  const campoAcao = document.getElementById('justificativa-submit-acao');

  formReprovacao.style.display = 'block';
  campoAcao.value = tipo;

  formReprovacao.scrollIntoView({ behavior: 'smooth' });
}