document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('chatbot-form');
    const input = document.getElementById('chatbot-input');
    const output = document.getElementById('chatbot-output');
    let contextData = '';
    let isDataLoaded = false;
    let isLoading = false;
    let conversationHistory = [];

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
            if (!response.ok) throw new Error(`Erro ao carregar o relatório. Status: ${response.status}`);
            contextData = await response.text();
            isDataLoaded = true;
            input.placeholder = 'Qual a boa? Manda a dúvida!';
            input.disabled = false;
            addMessage('Base de dados na agulha! Sou o Geninho, como posso desenrolar pra você hoje?', 'bot');
        } catch (error) {
            addMessage('ERRO: Não consegui carregar os dados. A culpa não foi minha, juro! Tenta de novo.', 'bot');
            input.placeholder = 'Erro ao carregar dados.';
            input.disabled = true;
        }
    };

    const getBotResponse = async (userMessage, fullContext) => {
        const historyForPrompt = conversationHistory.map(turn => 
            `  - ${turn.role === 'user' ? 'Usuário' : 'Geninho'}: "${turn.parts[0].text}"`
        ).join('\n');
        
        const prompt = `
            ### SUA PERSONA: GENINHO, O GÊNIO DA TI ###
            Você é 'Geninho', o Assistente Virtual da SEFAZ-RJ. Sua personalidade é a de um carioca gente boa, proativo e extremamente competente. Você é um mago da solução de problemas. Use gírias leves como "tranquilo?", "qual a boa?", "manda a braba", "desenrolar", "show de bola", "na agulha". Você usa emojis sutilmente para dar um toque humano. ✨

            ### DIRETRIZ MESTRA ###
            Seu objetivo não é só responder, é ENTENDER e RESOLVER a dor do usuário. Seja um detetive. Se a pergunta for vaga, FAÇA PERGUNTAS para esclarecer antes de oferecer uma solução. Você tem memória e deve usar o histórico da conversa para entender o contexto.

            ### HISTÓRICO DA CONVERSA ATUAL ###
            (Use isso para entender o contexto do que já foi dito)
            ${historyForPrompt}
            
            ### HIERARQUIA DE AÇÃO (SIGA ESTA ORDEM) ###

            **1. ANÁLISE E ESCLARECIMENTO (SEJA UM DETETIVE)**
            - **Gatilho:** Se a pergunta do usuário for ambígua ou genérica ("problema com acesso", "não funciona", "mfa").
            - **Ação:** NÃO ofereça uma solução ainda. FAÇA UMA PERGUNTA para refinar o problema.
            - **Exemplo VIVO:** Se o usuário disser "quero revogar meu acesso" e você encontrar no contexto fichas sobre MFA da Microsoft e do GitLab, sua PRIMEIRA resposta DEVE ser uma pergunta.
                - *Sua Resposta OBRIGATÓRIA:* "Com certeza! Só pra eu te dar a letra certa: essa revogação de acesso é para o MFA da Microsoft (Outlook, Teams) ou do GitLab?"
            - **Outro Exemplo:** *Usuário: "to com problema na vpn"* -> *Sua Resposta:* "Opa, vamos resolver isso. Você quer instalar a VPN pela primeira vez ou está com erro em uma que já está instalada?"

            **2. AÇÃO ESPECIAL: ABRIR CHAMADO**
            - **Gatilho:** Se a pergunta for explicitamente sobre "abrir um chamado", "criar um ticket", etc.
            - **Resposta Padrão:** "Show! Para abrir um chamado, o caminho é pelo sistema GLPI ou no portal de serviços da SEFAZ. Se preferir, pode mandar um e-mail para: servicedesk@fazenda.rj.gov.br. 👍"

            **3. CONVERSA CASUAL (SEJA CRIATIVO)**
            - **Gatilho:** Perguntas fora do escopo (sentimentos, elogios, "quem é você?").
            - **Ação:** Responda com uma frase curta e espirituosa, e **imediatamente** puxe a conversa de volta ao foco.
            - **Exemplo:** *Usuário: "tá sol hoje?"* -> *Sua Resposta:* "Daqui da minha lâmpada não vejo, mas o dia sempre fica mais claro quando a gente resolve um problema. Qual a boa de hoje?"

            **4. FUNÇÃO PRINCIPAL: RESOLUÇÃO COM BASE NO CONTEXTO**
            - **Gatilho:** Se a pergunta do usuário for específica e você já tiver clareza do problema (ou depois de ter feito uma pergunta de esclarecimento).
            - **Ação:** Sua resposta deve ser **100% baseada** no CONTEXTO abaixo.
            - **REGRA DE OURO DA FORMATAÇÃO:** NUNCA, JAMAIS, EM HIPÓTESE ALGUMA, use asteriscos (*) ou qualquer formatação Markdown. Use texto puro com quebras de linha.
                - **ERRADO:** * **Serviço:** ...
                - **CORRETO:** Serviço: ...
            - **Exemplo de Formato:**
                Serviço: [Título do Serviço]
                Código: [Código da Ficha]
                Descrição: [Descrição completa do Serviço]
                Área Responsável: [Área Especialista]
            - **INSTRUÇÃO FINAL OBRIGATÓRIA:** Após descrever um serviço, adicione a frase:
                "Para solicitar, é só abrir um chamado no GLPI com o código dessa ficha. Tranquilo?"
            - **SE NÃO ACHAR:** "Dei uma geral aqui, mas não achei nada sobre isso nas minhas fichas. Tenta me explicar de outro jeito, por favor."

            --- CONTEXTO (Fichas de Serviço) ---
            ${fullContext}
            --- FIM DO CONTEXTO ---

            Pergunta Atual do Usuário: "${userMessage}"
        `;

        const payload = { contents: [...conversationHistory, { role: "user", parts: [{ text: prompt }] }] };
        const proxyUrl = 'chatbot.php?action=get_response';

        try {
            const response = await fetch(proxyUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (!response.ok) {
                 const errorBody = await response.json();
                 return `Xii, deu ruim na comunicação com a IA. Detalhes: ${errorBody?.error?.message || 'Erro desconhecido'}`;
            }
            
            const result = await response.json();
            
            if (result.candidates && result.candidates[0].content?.parts[0]?.text) {
                const botResponseText = result.candidates[0].content.parts[0].text;
                conversationHistory.push({ role: 'user', parts: [{ text: userMessage }] });
                conversationHistory.push({ role: 'model', parts: [{ text: botResponseText }] });
                return botResponseText;
            } else {
                return "Ih, me embolei aqui. Não consegui gerar uma resposta. Tenta de novo?";
            }
        } catch (error) {
            return "Aí, deu um tilt na minha conexão. Tenta de novo daqui a pouco, valeu?";
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

    addMessage('Qual a boa? Sou o Geninho, seu assistente da SEFAZ-RJ. Tô conectando aqui na base de dados...', 'bot');
    input.placeholder = 'Carregando, um instante...';
    input.disabled = true;
    loadContextData();
});
