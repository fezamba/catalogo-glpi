<?php
// Nota do desenvolvedor original:
// Substituir pelas credenciais do banco do GLPI
// Será necessário criar novas tabelas, utilize catalogo_nome_da_tabela para criar as tabelas necessárias
// Estou usando o banco dev do GLPI

// --- Credenciais de Conexão com o Banco de Dados ---
// NOTA DE SEGURANÇA: É uma má prática armazenar credenciais diretamente no código.
// O ideal é usar variáveis de ambiente ou um arquivo de configuração seguro fora do diretório público.

// Endereço do servidor do banco de dados.
$host = '10.8.75.87';
// Nome de usuário para a conexão com o banco.
$user = 'glpi';
// Senha para a conexão com o banco.
$pass = 'Glpi123#';
// Nome do banco de dados a ser utilizado.
$db   = 'glpi';
// Porta de conexão com o banco de dados.
$port = 3306;

// --- Estabelecimento da Conexão ---
// Cria uma nova instância do objeto mysqli, tentando estabelecer a conexão com o banco de dados.
$mysqli = new mysqli($host, $user, $pass, $db, $port);

// --- Verificação de Erro na Conexão ---
// Verifica se ocorreu algum erro durante a tentativa de conexão.
// A propriedade 'connect_errno' retorna um código de erro (diferente de zero) se a conexão falhar.
if ($mysqli->connect_errno) {
    // Registra o erro detalhado no log de erros do servidor para análise posterior do desenvolvedor.
    // Isso evita expor detalhes técnicos sensíveis ao usuário final.
    error_log("Erro de conexão com o banco de dados: " . $mysqli->connect_error);
    
    // Encerra a execução de todo o script (die) e exibe uma mensagem genérica para o usuário.
    // É uma boa prática de segurança não mostrar o erro real do banco de dados na tela.
    die("Ocorreu um erro inesperado no servidor. Por favor, tente novamente mais tarde.");
}
?>
