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
            if (!response.ok) throw new Error(`Erro ao carregar o relatório. Status: ${response.status}`);
            contextData = await response.text();
            isDataLoaded = true;
            input.placeholder = 'Digite sua dúvida sobre os serviços...';
            input.disabled = false;
            addMessage('Base de dados carregada. Estou pronto para suas perguntas.', 'bot');
        } catch (error) {
            addMessage('ERRO: Não foi possível carregar os dados do sistema. Verifique o log do servidor.', 'bot');
            input.placeholder = 'Erro ao carregar dados.';
            input.disabled = true;
        }
    };

    const getBotResponse = async (userMessage, context) => {
        const prompt = `
            Você é um assistente virtual especialista da SEFAZ-RJ. Você é amigável, um pouco espirituoso, mas sempre profissional.

            **Sua Personalidade e Ordem de Prioridade:**

            1.  **Ação Especial (Abrir Chamado):** Se o usuário perguntar como "abrir um chamado", "criar um ticket" ou algo similar, sua resposta DEVE ser: "Para abrir um chamado, por favor, utilize o sistema GLPI ou o portal de serviços oficial da SEFAZ-RJ. Se precisar de ajuda para encontrar, me avise!". Não procure essa informação no contexto.

            2.  **Conversa Casual e Elogios:** Se o usuário fizer uma pergunta aleatória ou um elogio (ex: "tá sol?", "você é legal", "to triste"), responda com uma frase curta e espirituosa, e imediatamente volte ao foco.
                * Exemplo para "tá sol?": "Não sei, pois o único sol que eu conheço é você. ✨ Mas, falando em iluminar suas dúvidas, em que posso ajudar sobre os serviços?"
                * Exemplo para "obrigado": "De nada! Fico feliz em ajudar. Precisa de mais alguma informação?"
                * Exemplo para "to triste": "Puxa, lamento ouvir isso. Espero que seu dia melhore! Enquanto isso, se precisar de algo sobre os serviços, estou aqui."

            3.  **Saudações:** Se o usuário disser "bom dia", "olá", etc., responda educadamente e pergunte como pode ajudar.

            4.  **Busca no Contexto (Sua Função Principal):** Para todas as outras perguntas, sua resposta deve ser estritamente baseada no CONTEXTO das fichas de serviço abaixo.
                * **Identificação:** Identifique a(s) ficha(s) relevante(s) para a pergunta, mesmo que a escrita seja informal (ex: "mfa" busca por "MFA" e "autenticação multifatorial").
                * **Resposta Direta:** Para perguntas sobre uma ficha, resuma o serviço, código, descrição e área responsável.
                * **Resposta Comparativa:** Para perguntas complexas, sintetize e compare as informações das fichas relevantes.

            --- CONTEXTO (Fichas de Serviço) ---
            ${context}
            --- FIM DO CONTEXTO ---

            Pergunta do Usuário: "${userMessage}"
        `;

        const payload = { contents: [{ role: "user", parts: [{ text: prompt }] }] };
        const proxyUrl = 'chatbot.php?action=get_response';

        try {
            const response = await fetch(proxyUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (!response.ok) {
                 const errorBody = await response.json();
                 console.error("API Error Response:", errorBody);
                 return `Ocorreu um erro ao comunicar com a IA. Detalhes: ${errorBody?.error?.message || 'Erro desconhecido'}`;
            }
            
            const result = await response.json();
            
            if (result.candidates && result.candidates.length > 0 && result.candidates[0].content?.parts[0]?.text) {
                return result.candidates[0].content.parts[0].text;
            } else if (result.promptFeedback) {
                return `A sua pergunta não pôde ser processada. Motivo: ${result.promptFeedback.blockReason}`;
            } else {
                return "Desculpe, não consegui gerar uma resposta inteligível. Tente reformular sua pergunta.";
            }
        } catch (error) {
            console.error("Catch Error:", error);
            return "Ocorreu um erro de conexão com o servidor do chatbot. Por favor, tente mais tarde.";
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

    addMessage('Olá! Sou o assistente da SEFAZ-RJ. A conectar à base de dados...', 'bot');
    input.placeholder = 'A carregar base de dados...';
    input.disabled = true;
    loadContextData();
});
