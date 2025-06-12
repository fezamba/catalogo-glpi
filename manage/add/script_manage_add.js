document.addEventListener('DOMContentLoaded', () => {
  const mensagem = document.querySelector('.mensagem');

  if (mensagem && mensagem.textContent.trim() !== '') {
    // Oculta a mensagem depois de 3 segundos
    setTimeout(() => {
      mensagem.style.display = 'none';
    }, 3000);
  }
});
