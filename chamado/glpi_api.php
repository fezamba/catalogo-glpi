<?php

/**
 * Inicia uma sessão na API do GLPI para obter um token de sessão.
 *
 * Esta função envia uma requisição para o endpoint /initSession da API do GLPI
 * para autenticar e iniciar uma nova sessão.
 *
 * @param string $api_url A URL base da sua API do GLPI (ex: http://seu-glpi.com/apirest.php).
 * @param string $app_token O token do aplicativo registrado no GLPI.
 * @param string $user_token O token de usuário para autenticação.
 * @return string|null Retorna o token da sessão ('session_token') em caso de sucesso, ou null se a autenticação falhar.
 */
function iniciarSessaoGLPI($api_url, $app_token, $user_token)
{
    // Inicializa uma nova sessão cURL para o endpoint de início de sessão.
    $ch = curl_init("$api_url/initSession");

    // Configura os cabeçalhos HTTP necessários para a requisição.
    // Content-Type: especifica que o corpo da requisição está em formato JSON.
    // App-Token: token de identificação da aplicação cliente.
    // Authorization: token de autenticação do usuário.
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "App-Token: $app_token",
        "Authorization: user_token $user_token"
    ]);

    // Configura o cURL para retornar a resposta da API como uma string em vez de imprimi-la diretamente.
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Executa a requisição cURL e armazena a resposta.
    $response = curl_exec($ch);

    // Fecha a sessão cURL para liberar os recursos.
    curl_close($ch);

    // Decodifica a resposta JSON em um array associativo do PHP.
    $data = json_decode($response, true);

    // Retorna o 'session_token' se existir na resposta, caso contrário, retorna null.
    return $data['session_token'] ?? null;
}

/**
 * Cria um novo chamado (ticket) no GLPI.
 *
 * Esta função envia uma requisição POST para o endpoint /Ticket da API do GLPI
 * para criar um novo chamado com os detalhes fornecidos.
 *
 * @param string $api_url A URL base da sua API do GLPI.
 * @param string $session_token O token de sessão obtido através da função iniciarSessaoGLPI.
 * @param string $app_token O token do aplicativo registrado no GLPI.
 * @param string $titulo O título do chamado.
 * @param string $descricao O conteúdo ou descrição detalhada do chamado.
 * @param int $requesttype_id O ID do tipo de requisição (ex: Incidente, Solicitação).
 * @param int $user_id O ID do usuário que está abrindo o chamado (requerente).
 * @param int $entity_id O ID da entidade à qual o chamado pertence. O padrão é 0 (entidade raiz).
 * @return array|null Retorna a resposta da API como um array associativo, contendo os detalhes do chamado criado, ou null em caso de erro.
 */
function criarChamadoGLPI($api_url, $session_token, $app_token, $titulo, $descricao, $requesttype_id, $user_id, $entity_id = 0)
{
    // Monta o array com os dados do chamado no formato esperado pela API do GLPI.
    $dadosChamado = [
        "input" => [
            "name" => $titulo,
            "content" => $descricao,
            "requesttypes_id" => $requesttype_id,
            "users_id_recipient" => $user_id,
            "entities_id" => $entity_id
        ]
    ];

    // Inicializa uma nova sessão cURL para o endpoint de criação de Ticket.
    $ch = curl_init("$api_url/Ticket");

    // Configura os cabeçalhos HTTP, incluindo o token de sessão para autenticar a ação.
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "App-Token: $app_token",
        "Session-Token: $session_token"
    ]);

    // Configura a requisição para ser do tipo POST.
    curl_setopt($ch, CURLOPT_POST, true);

    // Anexa os dados do chamado (em formato JSON) ao corpo da requisição.
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dadosChamado));

    // Configura o cURL para retornar a resposta da API como uma string.
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Executa a requisição cURL.
    $response = curl_exec($ch);

    // Fecha a sessão cURL.
    curl_close($ch);

    // Decodifica a resposta JSON e a retorna como um array associativo.
    return json_decode($response, true);
}