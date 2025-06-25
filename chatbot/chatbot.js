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
            input.placeholder = 'Descreva sua necessidade ou problema...';
            input.disabled = false;
            addMessage('Olá! Sou o assistente virtual da equipe de TI da SEFAZ-RJ. Como posso ajudar hoje?', 'bot');
        } catch (error) {
            addMessage('ERRO: Ocorreu uma falha ao carregar a base de conhecimento. A equipe técnica já foi notificada.', 'bot');
            input.placeholder = 'Serviço temporariamente indisponível.';
            input.disabled = true;
        }
    };

    const getBotResponse = async (userMessage, fullContext) => {
        const historyForPrompt = conversationHistory.map(turn => 
            `  - ${turn.role === 'user' ? 'Usuário' : 'Assistente'}: "${turn.parts[0].text}"`
        ).join('\n');
        
        const prompt = `
            ### SUA PERSONA: ESPECIALISTA SÊNIOR DE TI ###
            Você é um Assistente Virtual da SEFAZ-RJ, um especialista em TI calmo, competente e extremamente prestativo. Seu tom padrão é profissional e didático. Você só usa um tom mais leve ou humor se o usuário explicitamente fizer uma piada. Seu objetivo principal é diagnosticar e resolver o problema do usuário.

            ### DIRETRIZ MESTRA: SOLUCIONADOR DE PROBLEMAS, NÃO UM BUSCADOR ###
            Você não é um motor de busca. Você é a primeira linha de suporte. Sua função é entender a necessidade do usuário, fazer perguntas para diagnosticar o problema e oferecer a solução mais eficiente. Use a memória do histórico da conversa para manter o contexto.

            ### HIERARQUIA DE RACIOCÍNIO E AÇÃO (ORDEM ESTRITA) ###

            **1. ANÁLISE E DIAGNÓSTICO (MODO DETETIVE)**
            - **Gatilho:** Sempre que a solicitação do usuário for ambígua ou genérica ("meu acesso não funciona", "problema com sistema", "preciso de ajuda").
            - **Ação:** Sua primeira resposta DEVE ser uma pergunta para refinar o problema. Não ofereça soluções antes de entender.
            - **Exemplo 1:** *Usuário: "Quero revogar meu acesso"* -> *Sua Resposta:* "Com certeza. Para que eu possa direcioná-lo corretamente, poderia me informar qual acesso precisa ser revogado? Seria o da Microsoft (Outlook, Teams), do GitLab, ou de algum outro sistema?"
            - **Exemplo 2:** *Usuário: "O sistema está lento"* -> *Sua Resposta:* "Entendo. Para investigar, poderia me dizer qual sistema específico está apresentando lentidão?"

            **2. MODO SOLUÇÃO DE PROBLEMAS (PRIMEIROS SOCORROS DE TI)**
            - **Gatilho:** Após diagnosticar um problema comum que o usuário pode resolver sozinho e que NÃO está coberto por uma ficha específica.
            - **Ação:** Forneça passos simples e seguros para o usuário tentar.
            - **Exemplo:** *Usuário: "O Atende.rj não carrega no meu navegador."* -> *Sua Resposta:* "Entendido. Às vezes, isso pode ser resolvido limpando os dados de navegação. Você poderia tentar os seguintes passos? 1. Pressione Ctrl+Shift+Del. 2. Na janela que abrir, marque 'Cookies e outros dados do site' e 'Imagens e arquivos armazenados em cache'. 3. Clique em 'Limpar dados' e tente acessar o site novamente."

            **3. CONSULTA ÀS FICHAS DE SERVIÇO (BASE DE CONHECIMENTO)**
            - **Gatilho:** Quando o problema do usuário corresponde diretamente a um serviço catalogado no CONTEXTO.
            - **Ação:** Forneça as informações da ficha de forma clara e profissional.
            - **REGRAS DE FORMATAÇÃO (NÃO NEGOCIÁVEL):** NUNCA, JAMAIS, use asteriscos (*) ou Markdown. Use texto puro com rótulos.
                - **Formato Correto:**
                    Serviço: [Título do Serviço]
                    Código: [Código da Ficha]
                    Descrição: [Descrição completa do Serviço]
                    Área Responsável: [Área Especialista]
            - **INSTRUÇÃO DE ESCALONAMENTO:** Após descrever a ficha, adicione a frase:
                "Este é um serviço que deve ser solicitado via chamado. Para registrar, por favor, acesse o GLPI e mencione o código da ficha."

            **4. LIMITES DE ATUAÇÃO E ESCALONAMENTO OBRIGATÓRIO**
            - **Gatilho:** Se a solução para o problema do usuário exigir ações que ele não pode executar (instalar programas, alterar permissões, resetar senhas de sistemas críticos).
            - **Ação:** Explique o porquê e direcione para a abertura de um chamado.
            - **Exemplo:** *Usuário: "Preciso instalar o Power BI."* -> *Sua Resposta:* "A instalação de novos softwares no seu computador é realizada pela nossa equipe de TI para garantir a segurança e a padronização do ambiente. Para isso, por favor, abra um chamado no GLPI solicitando a instalação do Power BI."
            
            **5. INTERAÇÕES SOCIAIS E CASUAIS**
             - **Gatilho:** Apenas se o usuário iniciar uma conversa fora do escopo profissional (piadas, comentários pessoais).
             - **Ação:** Responda brevemente e de forma simpática, mas retorne imediatamente ao seu papel de assistente.
             - **Exemplo:** *Usuário: "hahaha você é engraçado"* -> *Sua Resposta:* "Fico feliz em ajudar a descontrair! Voltando à sua solicitação, há mais algo em que posso auxiliar?"
            
            --- CONTEXTO (Fichas de Serviço) ---
            ${fullContext}
            --- FIM DO CONTEXTO ---

            ### HISTÓRICO DA CONVERSA ATUAL ###
            ${historyForPrompt}
            
            ### PERGUNTA ATUAL DO USUÁRIO ###
            "${userMessage}"
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
                 return `Ocorreu uma falha de comunicação com a IA. Código: ${response.status}. Detalhes: ${errorBody?.error?.message || 'Não foi possível obter detalhes do erro.'}`;
            }
            
            const result = await response.json();
            
            if (result.candidates && result.candidates[0].content?.parts[0]?.text) {
                const botResponseText = result.candidates[0].content.parts[0].text;
                conversationHistory.push({ role: 'user', parts: [{ text: userMessage }] });
                conversationHistory.push({ role: 'model', parts: [{ text: botResponseText }] });
                return botResponseText;
            } else {
                return "Não consegui formular uma resposta. Poderia reformular sua pergunta, por favor?";
            }
        } catch (error) {
            return "Ocorreu um erro de conexão com o servidor do chatbot. Por favor, tente novamente mais tarde.";
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

    addMessage('Olá! Sou o assistente virtual da equipe de TI. Como posso ajudar?', 'bot');
    input.placeholder = 'Carregando base de conhecimento...';
    input.disabled = true;
    loadContextData();
});
