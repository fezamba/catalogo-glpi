<?php
// Inicia ou resume uma sessão PHP existente. Essencial para usar a superglobal $_SESSION.
session_start();

// Inclui os arquivos necessários:
// 'glpi_api.php' contém as funções para interagir com a API do GLPI.
// '../conexao.php' deve conter a lógica para estabelecer a conexão com o banco de dados ($mysqli).
require_once 'glpi_api.php';
require_once '../conexao.php';

// Verifica se houve um erro na conexão com o banco de dados.
// Se houver, encerra a execução do script e exibe a mensagem de erro.
if ($mysqli->connect_errno) {
    die("Erro ao conectar: " . $mysqli->connect_error);
}

// Verifica se a requisição HTTP foi feita usando o método POST.
// O script só deve processar dados enviados de um formulário.
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Valida se os campos essenciais ('servico_id' e 'descricao_chamado') foram enviados no POST.
    if (!isset($_POST['servico_id']) || !isset($_POST['descricao_chamado'])) {
        // Se os dados estiverem faltando, redireciona o usuário de volta com um erro.
        header("Location: index.php?erro=DadosInvalidos");
        exit;
    }

    // Obtém e sanitiza os dados do formulário.
    $servico_id = intval($_POST['servico_id']); // Garante que o ID do serviço seja um inteiro.
    $descricao_usuario = trim($_POST['descricao_chamado']); // Remove espaços em branco do início e fim.

    // Prepara uma consulta SQL para buscar o título do serviço no banco de dados com base no ID.
    // Usar prepared statements previne injeção de SQL.
    $stmt = $mysqli->prepare("SELECT Titulo FROM servico WHERE ID = ?");
    $stmt->bind_param("i", $servico_id); // Associa o ID do serviço à consulta.
    $stmt->execute(); // Executa a consulta.
    $result = $stmt->get_result(); // Obtém o resultado.
    $servico = $result->fetch_assoc(); // Pega a linha como um array associativo.
    $stmt->close(); // Fecha o statement.

    // Verifica se o serviço foi encontrado no banco de dados.
    if (!$servico) {
        // Se não foi encontrado, redireciona com um erro específico.
        header("Location: index.php?erro=ServicoNaoEncontrado");
        exit;
    }

    // Monta o título e a descrição para o chamado no GLPI.
    $titulo = "Solicitação de Serviço: " . $servico['Titulo'];
    $descricao_completa = "Descrição do usuário: \n\n" . $descricao_usuario;

    // --- Configurações da API do GLPI ---
    // NOTA: É uma má prática armazenar credenciais diretamente no código.
    // O ideal é usar variáveis de ambiente ou um arquivo de configuração seguro.
    $glpi_api_url = "http://prhel8glpi002v.sefnet.rj/glpi/apirest.php/";
    $app_token = "D50oijbJcf6RRgv5k7MHlPsAtm7HdjArqdFR0Nie"; // Substituir pelo token real da aplicação.
    $user_token = "0HzCAomqUDw6c9AGMGtgjax5ffmfooVmWvEJOQ2b"; // Substituir pelo token do usuário "master" do GLPI.
    
    // Define IDs fixos para o tipo de requisição e entidade.
    $requesttype_id = 1; // ID para "Solicitação".
    $entity_id = 0; // ID para a entidade raiz.
    
    // Obtém o ID do usuário do GLPI da sessão.
    // Se não estiver na sessão, usa um valor padrão (fallback) para testes.
    $user_id = $_SESSION['glpi_user_id'] ?? 2; // O ID 2 geralmente é o do usuário 'glpi'.

    // Chama a função para iniciar a sessão na API do GLPI e obter o token.
    $session_token = iniciarSessaoGLPI($glpi_api_url, $app_token, $user_token);

    // Verifica se o token de sessão foi obtido com sucesso.
    if (!$session_token) {
        // Se a autenticação falhar, redireciona com um erro.
        header("Location: index.php?erro=FalhaSessaoGLPI");
        exit;
    }

    // Com o token de sessão em mãos, chama a função para criar o chamado no GLPI.
    $resposta = criarChamadoGLPI($glpi_api_url, $session_token, $app_token, $titulo, $descricao_completa, $requesttype_id, $user_id, $entity_id);

    // Verifica a resposta da API para saber se o chamado foi criado.
    if (isset($resposta['id'])) {
        // Se a resposta contiver um 'id', o chamado foi criado com sucesso.
        // Redireciona para a página inicial com uma mensagem de sucesso e o ID do chamado.
        header("Location: index.php?sucesso=1&chamado_id={$resposta['id']}");
        exit;
    } else {
        // Se houve um erro na criação, a API geralmente retorna uma mensagem de erro.
        // Codifica a mensagem de erro para ser passada via URL.
        $mensagem_erro = urlencode($resposta['message'] ?? 'CriacaoFalhou');
        header("Location: index.php?erro=$mensagem_erro");
        exit;
    }
} else {
    // Se a página for acessada por um método diferente de POST (ex: GET),
    // simplesmente redireciona o usuário para a página inicial.
    header("Location: index.php");
    exit;
}
?>
