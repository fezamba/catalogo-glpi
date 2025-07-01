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
              .then(data => {
                  displayResults(data.servicos, data.subcategorias);
              })
              .catch(error => {
                  console.error('Erro na busca:', error);
                  resultadosBox.innerHTML = '<div class="resultado-item">Ocorreu um erro na busca.</div>';
                  resultadosBox.style.display = 'block';
              });
      };

      const displayResults = (servicos, subcategorias) => {
          let html = '';

          if (servicos && servicos.length > 0) {
              html += '<div class="resultado-header">Serviços</div>';
              html += servicos.map(serv => `
                  <a href="../view_servico.php?id=${serv.id}" class="resultado-item">
                      <strong class="resultado-titulo">${serv.titulo}</strong>
                      <span class="resultado-contexto">em ${serv.categoria} > ${serv.subcategoria}</span>
                      <small class="resultado-desc">${serv.descricao.substring(0, 80)}...</small>
                  </a>
              `).join('');
          }

          if (subcategorias && subcategorias.length > 0) {
              html += '<div class="resultado-header">Subcategorias</div>';
              html += subcategorias.map(sub => `
                  <a href="../list/manage_listservico.php?subcategoria_id=${sub.id}" class="resultado-item">
                      <strong class="resultado-titulo">${sub.titulo}</strong>
                  </a>
              `).join('');
          }

          if (html === '') {
              resultadosBox.innerHTML = '<div class="resultado-item">Nenhum resultado encontrado.</div>';
          } else {
              resultadosBox.innerHTML = html;
          }
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
});
