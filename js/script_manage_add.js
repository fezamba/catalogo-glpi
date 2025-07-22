/**
 * Este script é executado quando o documento HTML foi completamente carregado e analisado.
 * A sua função é encontrar um elemento com a classe 'mensagem' e, se ele existir e contiver texto,
 * ocultá-lo automaticamente após um curto período de tempo.
 */
document.addEventListener('DOMContentLoaded', () => {
  // Seleciona o primeiro elemento no DOM que possui a classe CSS 'mensagem'.
  // Geralmente usado para caixas de alerta, sucesso ou erro que aparecem após uma ação.
  const mensagem = document.querySelector('.mensagem');

  // Verifica duas condições:
  // 1. Se o elemento 'mensagem' foi de fato encontrado no documento.
  // 2. Se o conteúdo de texto do elemento, após remover espaços em branco do início e do fim, não está vazio.
  // Isso evita que o temporizador seja ativado para um elemento <div class="mensagem"></div> vazio.
  if (mensagem && mensagem.textContent.trim() !== '') {
    // Se ambas as condições forem verdadeiras, define um temporizador.
    setTimeout(() => {
      // Após 3000 milissegundos (3 segundos), esta função será executada.
      // Ela altera o estilo de exibição do elemento para 'none', efetivamente ocultando-o da página.
      mensagem.style.display = 'none';
    }, 3000);
  }
});
