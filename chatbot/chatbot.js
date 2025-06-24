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
        const reportUrl = 'chatbot.php?fetch_data=true';
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
            Você é um assistente virtual especialista da SEFAZ-RJ.

            **Regra Principal:** Sua única fonte de conhecimento é o CONTEXTO das fichas de serviço abaixo.

            **Instruções de Conversa:**
            1.  **Saudações:** Se o usuário iniciar com uma saudação (como "bom dia", "olá"), responda de forma educada e pergunte como pode ajudar. Ex: "Bom dia! Como posso ajudar você com os serviços da SEFAZ-RJ?".
            2.  **Análise de Perguntas:** Para qualquer outra pergunta, siga estritamente as regras abaixo.
            
            **Regras de Resposta Baseada em Contexto:**
            - **Base Exclusiva:** NUNCA invente informações. Baseie-se APENAS no CONTEXTO.
            - **Identificação:** Identifique a(s) ficha(s) relevante(s) para a pergunta.
            - **Resposta Direta:** Para perguntas sobre uma ficha (ex: "o que é FCH-0010?"), resuma o serviço, código, descrição e área responsável.
            - **Resposta Comparativa:** Para perguntas complexas (ex: "diferença entre MFA para Microsoft e Gitlab"), sintetize e compare as informações das fichas relevantes.
            - **Não Encontrado:** Se a resposta não estiver no CONTEXTO, diga: "Não encontrei essa informação específica nas fichas de serviço fornecidas."

            --- CONTEXTO (Fichas de Serviço) ---
            ${context}
            --- FIM DO CONTEXTO ---

            Pergunta do Usuário: "${userMessage}"
        `;

        const payload = { contents: [{ role: "user", parts: [{ text: prompt }] }] };
        const apiKey = "AIzaSyDicYZKS_GmaidTbQSV5GftYUG0SzKnxJ0";
        const apiUrl = `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=${apiKey}`;

        try {
            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (!response.ok) throw new Error(`API error: ${response.status}`);
            
            const result = await response.json();
            
            if (result.candidates && result.candidates.length > 0) {
                return result.candidates[0].content.parts[0].text;
            } else if (result.promptFeedback) {
                return `A sua pergunta não pôde ser processada. Motivo: ${result.promptFeedback.blockReason}`;
            } else {
                return "Desculpe, não consegui gerar uma resposta inteligível. Tente reformular sua pergunta.";
            }
        } catch (error) {
            return "Ocorreu um erro de conexão com a IA. Verifique se a chave de API é válida e tente novamente mais tarde.";
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

    addMessage('Olá! Sou o assistente da SEFAZ-RJ. Conectando à base de dados...', 'bot');
    input.placeholder = 'Carregando base de dados...';
    input.disabled = true;
    loadContextData();
});
