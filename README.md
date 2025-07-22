# Catálogo de Serviços GLPI

## 📖 Sobre o Projeto

Este projeto é um **Catálogo de Serviços de TI** dinâmico e interativo, desenvolvido em PHP e MySQL. O objetivo é centralizar, padronizar e facilitar o acesso à documentação de todos os serviços oferecidos, servindo como uma fonte única de verdade para colaboradores e para a equipe de TI.

A plataforma vai além de uma simples documentação, integrando um workflow completo de criação, revisão e aprovação de fichas de serviço, um assistente virtual para suporte primário e um mecanismo para abertura de chamados diretamente para o GLPI.

## ✨ Funcionalidades Principais

* **Catálogo Público:** Interface amigável e intuitiva para que os usuários naveguem por categorias, subcategorias e encontrem os serviços de que precisam.
* **Busca Inteligente:** Ferramenta de busca global em tempo real que pesquisa títulos, descrições e categorias para encontrar serviços rapidamente.
* **Visualização Detalhada:** Páginas dedicadas para cada serviço, apresentando todas as informações de forma clara e organizada, incluindo diretrizes, padrões e checklists.
* **Integração com GLPI:** Formulário integrado na página de cada serviço para que o usuário possa abrir um chamado diretamente no GLPI, com o contexto do serviço já preenchido.
* **Assistente Virtual (Chatbot):** Um chatbot com IA (integrado com a API do Google Gemini) que utiliza o catálogo como base de conhecimento para responder dúvidas dos usuários e orientá-los na abertura de chamados.
* **Área Administrativa:** Um painel de controle completo para gerenciar:
    * **Fichas de Serviço:** Criação, edição, versionamento e exclusão.
    * **Categorias e Subcategorias:** Organização estrutural do catálogo.
    * **Product Owners (POs):** Cadastro de responsáveis pelos serviços.
    * **Revisores:** Cadastro de usuários responsáveis pela revisão técnica das fichas.
* **Workflow de Aprovação e Versionamento:**
    * Um fluxo de trabalho robusto com múltiplos estágios (`Rascunho`, `Em Revisão`, `Revisada`, `Em Aprovação`, `Aprovada`, `Publicado`, `Cancelada`, `Reprovada`).
    * Atribuição de múltiplos revisores para uma única ficha.
    * Criação de novas versões de fichas já publicadas, mantendo o histórico e substituindo as antigas automaticamente.
* **Painel de Debug (Testes):** Uma ferramenta administrativa que permite simular a visualização do sistema como diferentes tipos de usuários (Criador, Revisor, PO) e forçar o status de uma ficha para testar as regras de negócio e permissões.

## 🛠️ Tecnologias Utilizadas

* **Backend:** PHP 8+
* **Banco de Dados:** MySQL
* **Frontend:** HTML5, CSS3, JavaScript (Vanilla)
* **APIs Externas:** Google Gemini API (para o chatbot)

## 🚀 Como Executar o Projeto

### Pré-requisitos
* Servidor web com suporte a PHP (ex: Apache, Nginx)
* Banco de dados MySQL
* PHP com as extensões `mysqli` e `curl` ativadas.