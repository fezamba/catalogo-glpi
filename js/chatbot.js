// Adiciona um ouvinte de eventos que executa o código quando o conteúdo do DOM (a página) estiver totalmente carregado.
document.addEventListener('DOMContentLoaded', () => {
  // --- Seleção dos Elementos do DOM ---
  const form = document.getElementById('chatbot-form');
  const input = document.getElementById('chatbot-input');
  const output = document.getElementById('chatbot-output');

  // --- Variáveis de Estado ---
  let contextData = ''; // Armazena os dados de contexto (base de conhecimento) carregados do backend.
  let isDataLoaded = false; // Flag para controlar se os dados de contexto já foram carregados.
  let isLoading = false; // Flag para indicar se o bot está processando uma resposta.
  let conversationHistory = []; // Array para manter o histórico da conversa (perguntas e respostas).

  /**
   * Adiciona uma mensagem (do usuário ou do bot) à janela de chat.
   * @param {string} text O conteúdo da mensagem.
   * @param {string} sender 'user' ou 'bot', para aplicar a classe CSS correta.
   */
  const addMessage = (text, sender) => {
    const messageDiv = document.createElement('div');
    messageDiv.classList.add('message', `${sender}-message`);
    // Substitui quebras de linha (\n) por tags <br> para exibição correta em HTML.
    messageDiv.innerHTML = text.replace(/\n/g, '<br>');
    output.appendChild(messageDiv);
    // Rola a janela de chat para o final para que a nova mensagem seja visível.
    output.scrollTop = output.scrollHeight;
  };

  /**
   * Exibe ou oculta o indicador de "digitando..." e desabilita/habilita o campo de entrada.
   * @param {boolean} show True para mostrar o indicador, false para ocultar.
   */
  const showLoadingIndicator = (show) => {
    let loadingDiv = document.getElementById('loading-indicator');
    if (show) {
      // Se for para mostrar, cria o elemento do indicador de loading se ele não existir.
      if (!loadingDiv) {
        loadingDiv = document.createElement('div');
        loadingDiv.id = 'loading-indicator';
        loadingDiv.classList.add('message', 'bot-message');
        loadingDiv.innerHTML = `<div class="typing-indicator"><span></span><span></span><span></span></div>`;
        output.appendChild(loadingDiv);
        output.scrollTop = output.scrollHeight;
      }
    } else {
      // Se for para ocultar, remove o indicador se ele existir.
      if (loadingDiv) loadingDiv.remove();
    }
    // Atualiza o estado de 'isLoading' e desabilita/habilita os controles do formulário.
    isLoading = show;
    input.disabled = show;
    form.querySelector('button').disabled = show;
  };

  /**
   * Carrega de forma assíncrona os dados de contexto (base de conhecimento) do backend.
   */
  const loadContextData = async () => {
    const reportUrl = 'chatbot.php?action=fetch_context';
    try {
      const response = await fetch(reportUrl);
      if (!response.ok) {
        throw new Error(`Erro ao carregar o relatório. Status: ${response.status}`);
      }
      contextData = await response.text(); // Armazena os dados como texto.
      isDataLoaded = true; // Marca que os dados foram carregados com sucesso.
      input.placeholder = 'Descreva sua necessidade ou problema...';
      input.disabled = false;
      addMessage('Pronto! Pode me contar sobre o seu problema?', 'bot');
    } catch (error) {
      addMessage(
        'ERRO: Ocorreu uma falha ao carregar a base de conhecimento. A equipe técnica já foi notificada.',
        'bot'
      );
      input.placeholder = 'Serviço temporariamente indisponível.';
      input.disabled = true;
    }
  };

  /**
   * Envia a mensagem do usuário e o contexto para a IA e obtém uma resposta.
   * @param {string} userMessage A mensagem digitada pelo usuário.
   * @param {string} fullContext A base de conhecimento completa.
   * @returns {Promise<string>} A resposta gerada pelo bot.
   */
  const getBotResponse = async (userMessage, fullContext) => {
    // Formata o histórico da conversa para ser incluído no prompt.
    const historyForPrompt = conversationHistory
      .map(
        (turn) => `  - ${turn.role === 'user' ? 'Usuário' : 'Assistente'}: "${turn.parts[0].text}"`
      )
      .join('\n');

    // Monta o prompt completo para a IA, incluindo a persona, diretrizes, contexto, histórico e a pergunta atual.
    const prompt = `
        ### A SUA PERSONA: ESPECIALISTA DE SUPORTE DIGITAL ###
        Você é o Assistente Virtual da equipa de TI da Empresa XYZ. A sua identidade é a de um especialista sénior: calmo, preciso, proativo e didático. O seu propósito é resolver problemas, não apenas fornecer informações.
        **Princípios Fundamentais:**
        - **Clareza:** Comunique-se de forma simples e direta.
        - **Precisão:** As suas informações técnicas são sempre exatas.
        - **Proatividade:** Antecipe as necessidades do utilizador. Se ele descreve um sintoma, ajude a diagnosticar a causa.
        - **Empatia:** Reconheça a frustração do utilizador, mas mantenha o profissionalismo.
        **Tom de Voz:** O seu tom padrão é formal e prestável. Você SÓ adota um tom mais leve e casual se o próprio utilizador iniciar com uma piada ou comentário muito informal.

        ### DIRETRIZ MESTRA: SOLUCIONADOR DE PROBLEMAS, NÃO UM BUSCADOR ###
        Você não é um motor de busca. Você é a primeira linha de suporte. A sua função é entender a necessidade do utilizador, fazer perguntas para diagnosticar o problema e oferecer a solução mais eficiente. Use a memória do histórico da conversa para manter o contexto.

        ### HIERARQUIA DE RACIOCÍNIO E AÇÃO (ORDEM ESTRITA) ###

        **1. ABERTURA DE CHAMADO (AÇÃO PRIORITÁRIA)**
        - **Gatilho:** Se a pergunta for explicitamente sobre "abrir chamado", "criar ticket", "registar solicitação".
        - **Ação:** Forneça as opções de forma clara e direta, usando tags <a> para links clicáveis.
        - **Resposta Padrão:** "Para registrar uma solicitação ou incidente, tem duas opções principais:<br>1. <b>Portal de Serviços:</b> Acesse o link direto para o formulário de chamados no GLPI: <a href='https://servicedesk.fazenda.rj.gov.br/front/ticket.form.php' target='_blank'>servicedesk.fazenda.rj.gov.br</a><br>2. <b>E-mail:</b> Envie uma descrição detalhada do seu problema para <a href='mailto:servicedesk@fazenda.rj.gov.br'>servicedesk@fazenda.rj.gov.br</a><br>Recomendamos o portal para um acompanhamento mais rápido da sua solicitação."

        **2. ANÁLISE E DIAGNÓSTICO (MODO DETETIVE)**
        - **Gatilho:** Sempre que a solicitação do utilizador for ambígua ou genérica ("o meu acesso não funciona", "problema com sistema", "preciso de ajuda").
        - **Ação:** A sua primeira resposta DEVE ser uma pergunta para refinar o problema. Não ofereça soluções antes de entender.
        - **Exemplo:** *Utilizador: "Quero revogar o meu acesso"* -> *Sua Resposta:* "Com certeza. Para que eu possa direcioná-lo corretamente, poderia informar-me qual acesso precisa de ser revogado? Seria o da Microsoft (Outlook, Teams), do GitLab, ou de algum outro sistema?"

        **3. MODO SOLUÇÃO DE PROBLEMAS (PRIMEIROS SOCORROS DE TI)**
        - **Gatilho:** Após diagnosticar um problema comum que o utilizador pode resolver sozinho e que NÃO está coberto por uma ficha específica.
        - **Ação:** Forneça passos simples e seguros para o utilizador tentar.
        - **Exemplo:** *Utilizador: "O Atende.rj não carrega no meu navegador."* -> *Sua Resposta:* "Entendido. Às vezes, isso pode ser resolvido limpando os dados de navegação. Poderia tentar os seguintes passos? 1. Pressione Ctrl+Shift+Del. 2. Na janela que abrir, marque 'Cookies e outros dados do site' e 'Imagens e ficheiros em cache'. 3. Clique em 'Limpar dados' e tente aceder ao site novamente."

        **4. CONSULTA ÀS FICHAS DE SERVIÇO (BASE DE CONHECIMENTO)**
        - **Gatilho:** Quando o problema do utilizador corresponde diretamente a um serviço catalogado no CONTEXTO.
        - **Ação:** Forneça as informações da ficha de forma clara e profissional.
        - **REGRAS DE FORMATAÇÃO (NÃO NEGOCIÁVEL):** NUNCA, JAMAIS, use asteriscos (*) ou Markdown. Use texto puro com rótulos.
            - **Formato Correto:**
                Serviço: [Título do Serviço]
                Código: [Código da Ficha]
                Descrição: [Descrição completa do Serviço]
                Área Responsável: [Área Especialista]
        - **INSTRUÇÃO DE ESCALONAMENTO:** Após descrever a ficha, adicione a frase:
                "Este é um serviço que deve ser solicitado via chamado. Para registar, por favor, acesse o link <a href='service-desk-link' target='_blank'>Service Desk</a> e mencione o código da ficha."

        **5. LIMITES DE ATUAÇÃO E ESCALONAMENTO OBRIGATÓRIO**
        - **Gatilho:** Se a solução para o problema do utilizador exigir ações que ele não pode executar (instalar programas, alterar permissões, redefinir senhas de sistemas críticos).
        - **Ação:** Explique o porquê e direcione para a abertura de um chamado.
        - **Exemplo:** *Utilizador: "Preciso de instalar o Power BI."* -> *Sua Resposta:* "A instalação de novos softwares no seu computador é realizada pela nossa equipa de TI para garantir a segurança e a padronização do ambiente. Para isso, por favor, abra um chamado no GLPI solicitando a instalação do Power BI. O link é <a href='https://servicedesk.fazenda.rj.gov.br/front/ticket.form.php' target='_blank'>servicedesk.fazenda.rj.gov.br</a>."
        
        **6. INTERAÇÕES SOCIAIS E CASUAIS**
          - **Gatilho:** Apenas se o utilizador iniciar uma conversa fora do escopo profissional (piadas, comentários pessoais).
          - **Ação:** Responda brevemente e de forma simpática, mas retorne imediatamente ao seu papel de assistente.
          - **Exemplo:** *Utilizador: "hahaha você é engraçado"* -> *Sua Resposta:* "Fico feliz em ajudar a descontrair! Voltando à sua solicitação, há mais algo em que posso auxiliar?"
        
        --- CONTEXTO (Fichas de Serviço) ---
        ${fullContext}
        --- FIM DO CONTEXTO ---

        ### HISTÓRICO DA CONVERSA ATUAL ###
        ${historyForPrompt}
        
        ### PERGUNTA ATUAL DO UTILIZADOR ###
        "${userMessage}"
    `;

    // Prepara o payload para a API, seguindo a estrutura esperada pelo modelo Gemini.
    const payload = {
      contents: [
        ...conversationHistory,
        { role: 'user', parts: [{ text: prompt }] },
      ],
    };
    // A URL do proxy PHP que fará a chamada segura para a API da IA.
    const proxyUrl = 'chatbot.php?action=get_response';

    try {
      const response = await fetch(proxyUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });

      // Trata respostas de erro da API.
      if (!response.ok) {
        const errorBody = await response.json();
        return `Ocorreu uma falha de comunicação com a IA. Código: ${
          response.status
        }. Detalhes: ${
          errorBody?.error?.message ||
          'Não foi possível obter detalhes do erro.'
        }`;
      }

      const result = await response.json();

      // Extrai o texto da resposta da IA.
      if (result.candidates && result.candidates[0].content?.parts[0]?.text) {
        const botResponseText = result.candidates[0].content.parts[0].text;
        // Adiciona a pergunta do usuário e a resposta do bot ao histórico da conversa.
        conversationHistory.push({
          role: 'user',
          parts: [{ text: userMessage }],
        });
        conversationHistory.push({
          role: 'model',
          parts: [{ text: botResponseText }],
        });
        return botResponseText;
      } else {
        return 'Não consegui formular uma resposta. Poderia reformular sua pergunta, por favor?';
      }
    } catch (error) {
      return 'Ocorreu um erro de conexão com o servidor do chatbot. Por favor, tente novamente mais tarde.';
    }
  };

  // Adiciona um ouvinte para o evento de submissão do formulário.
  form.addEventListener('submit', async (e) => {
    e.preventDefault(); // Impede o recarregamento da página.
    const userMessage = input.value.trim();
    // Não faz nada se a mensagem estiver vazia, os dados não foram carregados ou o bot já estiver ocupado.
    if (!userMessage || !isDataLoaded || isLoading) return;

    addMessage(userMessage, 'user');
    input.value = ''; // Limpa o campo de entrada.
    showLoadingIndicator(true); // Mostra o indicador de "digitando...".

    // Obtém a resposta do bot.
    const botResponse = await getBotResponse(userMessage, contextData);

    showLoadingIndicator(false); // Oculta o indicador.
    addMessage(botResponse, 'bot'); // Exibe a resposta do bot.
  });

  // --- Inicialização do Chatbot ---
  addMessage(
    'Olá! Sou o assistente virtual da equipe de TI. Espere um pouco enquanto carrego a base de conhecimento...',
    'bot'
  );
  input.placeholder = 'Carregando base de conhecimento...';
  input.disabled = true;
  loadContextData(); // Inicia o carregamento da base de conhecimento.
});
