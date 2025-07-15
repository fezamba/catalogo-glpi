### **Escopo do Projeto: Catálogo de Serviços Integrado ao GLPI**

#### **1. Banco de Dados e Conexão**
* **Estrutura de Dados:** Implementar as tabelas customizadas no banco de dados do GLPI. Os testes devem ser realizados em um ambiente de desenvolvimento ou container.
* **Conexão:** Configurar o arquivo de conexão da aplicação (`conexao.php`) para apontar para o banco de dados GLPI do ambiente correspondente (DEV, PROD, etc.).

#### **2. Sistema de Autenticação**
* **Integração com GLPI:** Substituir a autenticação local atual por uma que valide os usuários e seus perfis diretamente no GLPI.
* **Perfis de Acesso:** O sistema deverá reconhecer os perfis `ADMIN` e `PO` (já existentes) e um novo perfil `Revisor`, que precisa ser criado no GLPI.
* **Refatoração:** Com a nova autenticação, os recursos para listar e adicionar POs e Revisores na aplicação serão descontinuados.

#### **3. Aplicação e Workflow**
* **Painel de Debug:** O painel de debug deve ser ocultado na versão de produção.
* **Workflow das Fichas:** Sobre a lógica de workflow, presente no arquivo `manage_addservico.php`, tirar dúvidas com a Fernanda ou a Virginia.
* **Criação de Chamados via API:** Configurar os scripts (`glpi_api.php`, `processar_chamado.php`) para utilizar a API do GLPI na criação de chamados a partir do catálogo.

#### **4. Implantação e Integração Final**
* **Hospedagem:** A aplicação será hospedada em um ambiente externo ao GLPI.
* **Acesso via GLPI:** Acredito que possa existir um plugin, já instalado no GLPI, que inserirá um botão de redirecionamento no cabeçalho, direcionando para a aplicação do catálogo.

#### **5. Inteligência Artificial (Chatbot)**
* **Modelo de IA:** Substituir a API do Gemini pelo modelo PHI-2 para o chatbot, motivado por ser uma alternativa de menor custo.
* **Análise de Viabilidade:** A viabilidade da implementação do PHI-2 deve ser discutida com o Francisco.

---
**Ponto Focal para Dúvidas de Desenvolvimento:**
* **Nome:** Fernando
* **Contato:** (21) 998809049
* **Observação:** Contato preferencialmente por mensagem.