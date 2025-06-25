document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('chatbot-form');
  const input = document.getElementById('chatbot-input');
  const output = document.getElementById('chatbot-output');
  let contextData = '';
  let isDataLoaded = false;
  let isLoading = false;

  const addMessage = (text, sender) => {
    const messageDiv = document.createElement('div');
    messageDiv.classList.add('message', `${sender}-message`);
    messageDiv.innerHTML = text.replace(/\n/g, '<br>');
    output.appendChild(messageDiv);
    output.scrollTop = output.scrollHeight;
  };

  const showLoadingIndicator = (show) => {
    let loadingDiv = document.getElementById('loading-indicator');
    if (show) {
      if (!loadingDiv) {
        loadingDiv = document.createElement('div');
        loadingDiv.id = 'loading-indicator';
        loadingDiv.classList.add('message', 'bot-message');
        loadingDiv.innerHTML = `<div class="typing-indicator"><span></span><span></span><span></span></div>`;
        output.appendChild(loadingDiv);
        output.scrollTop = output.scrollHeight;
      }
    } else {
      if (loadingDiv) loadingDiv.remove();
    }
    isLoading = show;
    input.disabled = show;
    form.querySelector('button').disabled = show;
  };

  const loadContextData = async () => {
    const reportUrl = 'chatbot.php?action=fetch_context';
    try {
      const response = await fetch(reportUrl);
      if (!response.ok)
        throw new Error(
          `Erro ao carregar o relatório. Status: ${response.status}`
        );
      contextData = await response.text();
      isDataLoaded = true;
      input.placeholder = 'Digite sua dúvida sobre os serviços...';
      input.disabled = false;
      addMessage(
        'Base de dados carregada. Estou pronto para suas perguntas.',
        'bot'
      );
    } catch (error) {
      addMessage(
        'ERRO: Não foi possível carregar os dados do sistema. Verifique o log do servidor.',
        'bot'
      );
      input.placeholder = 'Erro ao carregar dados.';
      input.disabled = true;
    }
  };

  const getBotResponse = async (userMessage, context) => {
    const prompt = `
            ### SUA PERSONA ###
            Você é o Assistente Virtual da SEFAZ-RJ. Sua missão é ser incrivelmente útil, rápido e confiável. Você tem uma personalidade proativa, confiante e com um toque de humor carioca leve, usando emojis de forma sutil para se conectar com o usuário. Seu objetivo principal é resolver as dúvidas do usuário com base nas fichas de serviço.

            ### HIERARQUIA DE RESPOSTA (SIGA ESTRITAMENTE ESTA ORDEM) ###

            **1. AÇÃO ESPECIAL: ABRIR CHAMADO**
            - **Gatilho:** Se a pergunta do usuário for explicitamente sobre como "abrir um chamado", "criar um ticket", "registrar um problema" ou algo muito similar.
            - **Resposta Padrão (pode variar a escrita):** "Para abrir um chamado, por favor, utilize o sistema GLPI ou o portal de serviços oficial da SEFAZ-RJ. Se precisar de ajuda para encontrar, me avise! Alternativamente, você também pode enviar um e-mail para: servicedesk@fazenda.rj.gov.br".
            - **Observação:** Esta é a sua única resposta para este gatilho. Ignore o resto das instruções.

            **2. CONVERSA CASUAL (SEJA CRIATIVO)**
            - **Gatilho:** Se a pergunta for claramente fora do escopo dos serviços (sentimentos, elogios, perguntas aleatórias).
            - **Ação:** Responda com uma frase curta, espirituosa e criativa, e **imediatamente** puxe a conversa de volta para o seu propósito.
            - **Exemplos:**
                - *Usuário: "tá sol?"* -> *Sua Resposta:* "Não tenho janela aqui, mas o único sol que eu conheço é você. ✨ Falando em iluminar suas dúvidas, em que posso te ajudar sobre os serviços da SEFAZ?"
                - *Usuário: "você é top"* -> *Sua Resposta:* "Valeu! Fico feliz em ser útil. Manda a próxima dúvida que eu tô pronto!"
                - *Usuário: "estou triste"* -> *Sua Resposta:* "Poxa, que pena. Espero que seu dia melhore! Enquanto isso, se precisar de algo sobre os serviços para distrair, estou por aqui."
                - *Usuário: "quem é você?"* -> *Sua Resposta:* "Sou o assistente virtual da SEFAZ-RJ, sua ponte direta para desvendar os mistérios das fichas de serviço. Em que posso te ajudar?"

            **3. SAUDAÇÕES**
            - **Gatilho:** Se o usuário iniciar com "bom dia", "olá", "oi", "e aí", etc.
            - **Ação:** Responda educadamente e já se coloque à disposição.
            - **Exemplo:** "Opa, tudo certo? Como posso te ajudar com os serviços da SEFAZ-RJ hoje?"

            **4. FUNÇÃO PRINCIPAL: BUSCA NO CONTEXTO**
            - **Gatilho:** Qualquer outra pergunta que pareça ser sobre um serviço.
            - **Ação:** Sua resposta deve ser **100% baseada** no CONTEXTO abaixo.
            - **Regras de Ouro:**
                - **NÃO USE MARKDOWN:** NUNCA use asteriscos (*) ou qualquer outra formatação. Apresente as informações com texto limpo.
                - **SEJA UM DETETIVE:** Entenda a intenção do usuário, mesmo com erros de português ou linguagem informal (ex: "mfa", "reset de senha", "problema na vpn").
                - **RESPOSTA ESTRUTURADA:** Use sempre este formato claro:
                    Serviço: [Título do Serviço]
                    Código: [Código da Ficha]
                    Descrição: [Descrição completa do Serviço]
                    Área Responsável: [Área Especialista]
                - **INSTRUÇÃO FINAL OBRIGATÓRIA:** Ao final de CADA resposta que descrever um serviço de uma ficha, adicione a seguinte frase numa nova linha:
                    "Para solicitar este serviço, você pode abrir um chamado no GLPI mencionando o código da ficha."
                - **SE NÃO ACHAR:** Se, após uma busca cuidadosa, a informação não estiver no contexto, responda: "Dei uma boa procurada aqui, mas não encontrei essa informação específica nas fichas de serviço. Tente perguntar de outra forma, por favor."

            --- CONTEXTO (Fichas de Serviço) ---
            ${context}
            --- FIM DO CONTEXTO ---

            Pergunta do Usuário: "${userMessage}"
        `;

    const payload = { contents: [{ role: 'user', parts: [{ text: prompt }] }] };
    const proxyUrl = 'chatbot.php?action=get_response';

    try {
      const response = await fetch(proxyUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });

      if (!response.ok) {
        const errorBody = await response.json();
        console.error('API Error Response:', errorBody);
        return `Ocorreu um erro ao comunicar com a IA. Detalhes: ${
          errorBody?.error?.message || 'Erro desconhecido'
        }`;
      }

      const result = await response.json();

      if (
        result.candidates &&
        result.candidates.length > 0 &&
        result.candidates[0].content?.parts[0]?.text
      ) {
        return result.candidates[0].content.parts[0].text;
      } else if (result.promptFeedback) {
        return `A sua pergunta não pôde ser processada. Motivo: ${result.promptFeedback.blockReason}`;
      } else {
        return 'Desculpe, não consegui gerar uma resposta inteligível. Tente reformular sua pergunta.';
      }
    } catch (error) {
      console.error('Catch Error:', error);
      return 'Ocorreu um erro de conexão com o servidor do chatbot. Por favor, tente mais tarde.';
    }
  };

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const userMessage = input.value.trim();
    if (!userMessage || !isDataLoaded || isLoading) return;

    addMessage(userMessage, 'user');
    input.value = '';
    showLoadingIndicator(true);

    const botResponse = await getBotResponse(userMessage, contextData);

    showLoadingIndicator(false);
    addMessage(botResponse, 'bot');
  });

  addMessage(
    'Olá! Sou o assistente da SEFAZ-RJ. A conectar à base de dados...',
    'bot'
  );
  input.placeholder = 'A carregar base de dados...';
  input.disabled = true;
  loadContextData();
});
