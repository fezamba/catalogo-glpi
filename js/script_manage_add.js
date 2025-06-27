document.addEventListener('DOMContentLoaded', () => {
  const mensagem = document.querySelector('.mensagem');

  if (mensagem && mensagem.textContent.trim() !== '') {
    setTimeout(() => {
      mensagem.style.display = 'none';
    }, 3000);
  }
});
