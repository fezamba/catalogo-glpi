document.addEventListener('DOMContentLoaded', function () {
  const input = document.getElementById('chatbot-input');
  const output = document.getElementById('chatbot-output');
  const form = document.getElementById('chatbot-form');

  // Saudação inicial como mensagem do bot
  const saudacao = `
    <div class="mensagem bot saudacao">
      👋 <strong>Olá! Eu sou o Junin, seu assistente virtual.</strong><br>
      <em>Pode me perguntar sobre senhas, sistemas, acesso, Outlook... o que precisar! 😄</em>
    </div>
  `;
  output.innerHTML += saudacao;

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    const pergunta = input.value;

    // Exibe a pergunta do usuário
    output.innerHTML += `
      <div class="mensagem usuario">
        ${pergunta}
      </div>
    `;

    // Cria o "Junin está digitando..."
    const typingDiv = document.createElement('div');
    typingDiv.className = 'mensagem bot typing';
    typingDiv.innerText = 'Junin está digitando...';
    output.appendChild(typingDiv);
    output.scrollTop = output.scrollHeight;

    // Aguarda 1 segundos antes de buscar a resposta
    setTimeout(() => {
      fetch('responder.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'pergunta=' + encodeURIComponent(pergunta),
      })
        .then((res) => res.json())
        .then((data) => {
          // Remove o "digitando..."
          output.removeChild(typingDiv);

          // Exibe a resposta real
          let respostaFinal = `
          <div class="mensagem bot">
            ${data.resposta}
        `;

          if (data.id) {
            respostaFinal += `<br><a href="../subcategoria.php?id=${data.id}">Ir para o serviço relacionado</a>`;
          }

          respostaFinal += `</div>`;
          output.innerHTML += respostaFinal;
          input.value = ''; // Limpa o campo de entrada
          output.scrollTop = output.scrollHeight;
        });
    }, 1000); // ⏱️ 1 segundos de delay
  });
});
