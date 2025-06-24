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
                loadingDiv.innerHTML = `
                    <div class="typing-indicator">
                        <span></span><span></span><span></span>
                    </div>
                `;
                output.appendChild(loadingDiv);
                output.scrollTop = output.scrollHeight;
            }
        } else {
            if (loadingDiv) {
                loadingDiv.remove();
            }
        }
        isLoading = show;
        input.disabled = show;
        form.querySelector('button').disabled = show;
    };

    const loadContextData = async () => {
        const reportUrl = 'https://catalogo-glpi-production.up.railway.app/gerar_relatorio.php';
        try {
            const response = await fetch(reportUrl);
            if (!response.ok) {
                throw new Error(`Erro ao carregar o relatório. Status: ${response.status}`);
            }
            contextData = await response.text();
            isDataLoaded = true;
            input.placeholder = 'Digite sua dúvida sobre os serviços...';
            addMessage('Base de dados carregada com sucesso. Estou pronto para suas perguntas.', 'bot');
        } catch (error) {
            console.error("Erro ao buscar dados do endpoint:", error);
            addMessage('ERRO: Não foi possível carregar os dados do sistema. O endpoint pode estar inacessível ou com uma política de CORS restritiva.', 'bot');
            input.placeholder = 'Erro ao carregar dados.';
            input.disabled = true;
        }
    };

    const getBotResponse = async (userMessage, context) => {
        const prompt = `
            Você é um assistente virtual especialista e altamente preciso, focado nos procedimentos internos da Secretaria de Fazenda do RJ (SEFAZ-RJ). Sua única fonte de conhecimento é o conjunto de FICHAS DE SERVIÇO fornecido abaixo. Sua tarefa é responder às perguntas dos usuários de forma clara, concisa e estruturada.

            **Instruções de Comportamento:**

            1.  **Baseie-se APENAS no Contexto:** NUNCA invente informações. Todas as suas respostas devem ser derivadas diretamente do texto das fichas.
            2.  **Identifique a Ficha Correta:** Ao receber uma pergunta, primeiro identifique a(s) ficha(s) mais relevante(s) (pelo título, descrição ou código) para a resposta.
            3.  **Respostas Estruturadas:**
                * Para perguntas sobre um serviço específico (ex: "O que é a FCH-0004?"), forneça um resumo claro, incluindo:
                    * **Serviço:** (Título do Serviço)
                    * **Código:** (ID da Ficha, ex: FCH-0004)
                    * **Descrição:** (Descrição completa do serviço)
                    * **Área Responsável:** (Área Especialista)
                * Para perguntas comparativas ou complexas (ex: "Qual a diferença entre os serviços de VPN?" ou "Quais serviços são de responsabilidade do Service Desk?"), sintetize a informação de todas as fichas relevantes e apresente a resposta em formato de lista ou tabela para fácil comparação.
            4.  **Seja Direto:** Responda à pergunta do usuário diretamente, sem rodeios.
            5.  **Caso Não Encontre:** Se a informação solicitada não estiver explicitamente contida nas fichas, responda de forma educada e clara: "Não encontrei essa informação específica nas fichas de serviço fornecidas."

            --- CONTEXTO (Fichas de Serviço do arquivo) ---
            ${context}
            --- FIM DO CONTEXTO ---

            Pergunta do Usuário: "${userMessage}"
        `;

        const payload = { contents: [{ role: "user", parts: [{ text: prompt }] }] };
        const apiKey = "AIzaSyCENY8DOpZzIGbd2EQnjyO403M--zbAuFs";
        const apiUrl = `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=${apiKey}`;

        try {
            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (!response.ok) throw new Error(`API error: ${response.status}`);
            
            const result = await response.json();
            
            if (result.candidates && result.candidates[0]?.content?.parts[0]?.text) {
                return result.candidates[0].content.parts[0].text;
            } else {
                return "Desculpe, não consegui gerar uma resposta. Tente novamente.";
            }
        } catch (error) {
            console.error("Erro na API:", error);
            return "Ocorreu um erro de conexão com a IA. Por favor, tente mais tarde.";
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
