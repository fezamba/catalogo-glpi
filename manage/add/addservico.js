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
    // Verifica se o campo já existe para não duplicar
    if (!form.querySelector("input[name='usuario_criador']")) {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'usuario_criador';
      input.value = 'Service-Desk/WD'; // Substituir futuramente por uma variável de sessão
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

  // Scroll automático até o formulário
  formReprovacao.scrollIntoView({ behavior: 'smooth' });
}

function toggleSoftware(ativo) {
  document.getElementById('campo-versao-software').style.display = ativo ? 'block' : 'none';
}

function toggleSistema(ativo) {
  const campoSistema = document.getElementById('campo-sistema');
  campoSistema.style.display = ativo ? 'block' : 'none';

  const areaEspecialista = document.querySelector('[name="area_especialista"]');
  if (areaEspecialista) {
    areaEspecialista.disabled = ativo;
  }

  // Se for PO ou Revisor, bloqueia inputs do campo sistema também
  const tipoUsuario = '<?= $tipo_usuario ?>';
  if (tipoUsuario === 'po' || tipoUsuario === 'revisor') {
    const inputs = campoSistema.querySelectorAll('input');
    inputs.forEach(input => {
      input.readOnly = true;
      input.style.backgroundColor = '#f5f5f5';
      input.style.cursor = 'not-allowed';
    });
  }
}

// Exclusão cruzada + toggle visual + seleção do "não"
document.getElementById('radio_software').addEventListener('change', function () {
  if (this.checked) {
    document.getElementById('radio_sistema').checked = false;
    document.querySelector('input[name="eh_sistema"][value="nao"]').checked = true;
    toggleSistema(false);
  }
});

document.getElementById('radio_sistema').addEventListener('change', function () {
  if (this.checked) {
    document.getElementById('radio_software').checked = false;
    document.querySelector('input[name="eh_software"][value="nao"]').checked = true;
    toggleSoftware(false);
  }
});

// Mostra os campos extras conforme o radio já está marcado (ao abrir a página)
document.addEventListener('DOMContentLoaded', function () {
  // Exibe campo de versão do software se o radio "sim" estiver marcado
  if (document.querySelector('input[name="eh_software"][value="sim"]').checked) {
    toggleSoftware(true);
  } else {
    toggleSoftware(false);
  }

  // Exibe campos de sistema/portal se o radio "sim" estiver marcado
  if (document.querySelector('input[name="eh_sistema"][value="sim"]').checked) {
    toggleSistema(true);
  } else {
    toggleSistema(false);
  }
});

document.getElementById('debug-apply-btn').addEventListener('click', function() {
    // Pega a URL atual sem os parâmetros de busca
    let baseUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;

    // Pega os parâmetros existentes (como o 'id')
    const params = new URLSearchParams(window.location.search);

    // Pega os novos valores dos dropdowns
    const novoTipo = document.getElementById('debug-tipo-usuario').value;
    params.set('tipo', novoTipo); // Define o novo tipo

    // Se o dropdown de status existir, pega o valor dele
    const statusSelect = document.getElementById('debug-status-ficha');
    if (statusSelect) {
        const novoStatus = statusSelect.value;
        if (novoStatus) {
            params.set('forcar_status', novoStatus); // Define o novo status
        } else {
            params.delete('forcar_status'); // Remove se a opção for a padrão
        }
    }

    // Reconstrói a URL e recarrega a página
    window.location.href = baseUrl + '?' + params.toString();
});