// Lógica de abrir/fechar submenus (mantida)
document.querySelectorAll('.accordion-toggle').forEach((button) => {
  button.addEventListener('click', () => {
    const submenu = button.nextElementSibling;
    const isOpen = submenu.style.display === 'block';

    document
      .querySelectorAll('.submenu')
      .forEach((menu) => (menu.style.display = 'none'));
    document
      .querySelectorAll('.accordion-toggle')
      .forEach((btn) => btn.classList.remove('active'));

    if (!isOpen) {
      submenu.style.display = 'block';
      button.classList.add('active');
    }
  });
});

// NOVO: Atualiza automaticamente os números das badges
document.querySelectorAll('.menu-item').forEach((item) => {
  const submenu = item.querySelector('.submenu');
  const badge = item.querySelector('.badge');

  if (submenu && badge) {
    const count = submenu.querySelectorAll('a').length;
    badge.textContent = count;
  }
});

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.subcat').forEach((link) => {
    link.addEventListener('click', function (e) {
      e.preventDefault();
      const url = this.getAttribute('href');
      window.location.href = url; // Redireciona a página para a URL
    });
  });
});

// O ideal é ter apenas um listener de DOMContentLoaded para organizar o código
document.addEventListener('DOMContentLoaded', () => {
  // --- Lógica para o menu sanfona e badges (você pode colocar aqui dentro) ---
  // ... seu código de accordion e badges ...

  // --- LÓGICA DA BUSCA (CORRIGIDA) ---
  const inputBusca = document.getElementById('busca-global');
  const resultadosBox = document.getElementById('resultados-busca');

  if (inputBusca && resultadosBox) {
    inputBusca.addEventListener('input', () => {
      const termo = inputBusca.value.trim();

      if (termo.length < 2) {
        resultadosBox.style.display = 'none';
        resultadosBox.innerHTML = '';
        return;
      }

      // Lembre de usar o nome correto do seu arquivo PHP aqui
      fetch('buscar_servicos.php?termo=' + encodeURIComponent(termo))
        .then((res) => res.json())
        .then((data) => {
          if (data.length === 0) {
            resultadosBox.innerHTML =
              '<div class="resultado-item">Nenhum serviço encontrado.</div>';
          } else {
            // Constrói o HTML usando <a> para cada resultado
            resultadosBox.innerHTML = data
              .map(
                (serv) => `
                            <a href="view_servico.php?id=${
                              serv.id
                            }" class="resultado-item">
                                <strong class="resultado-titulo">${
                                  serv.titulo
                                }</strong>
                                <span class="resultado-contexto">em ${
                                  serv.categoria
                                } > ${serv.subcategoria}</span>
                                <small class="resultado-desc">${serv.descricao.substring(
                                  0,
                                  80
                                )}...</small>
                            </a>
                        `
              )
              .join('');
          }
          resultadosBox.style.display = 'block';
        })
        .catch((error) => console.error('Erro na busca:', error));
    });

    // Fecha os resultados se clicar fora
    document.addEventListener('click', function (event) {
      if (
        !inputBusca.contains(event.target) &&
        !resultadosBox.contains(event.target)
      ) {
        resultadosBox.style.display = 'none';
      }
    });
  }
});
