<?php

function iniciarSessaoGLPI($api_url, $app_token, $user_token, &$error_details)
{
    $ch = curl_init("$api_url/initSession");

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "App-Token: $app_token",
        "Authorization: user_token $user_token"
    ]);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // Adiciona um timeout
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error_details = 'Erro de cURL: ' . curl_error($ch);
        curl_close($ch);
        return null;
    }

    curl_close($ch);
    $data = json_decode($response, true);

    if (isset($data['session_token'])) {
        return $data['session_token'];
    } else {
        // Captura a mensagem de erro retornada pela API do GLPI
        $error_details = 'Erro da API do GLPI: ' . ($response ?: 'Nenhuma resposta recebida.');
        return null;
    }
}

function criarChamadoGLPI($api_url, $session_token, $app_token, $titulo, $descricao, $requesttype_id, $user_id, $entity_id = 0)
{
    $dadosChamado = [
        "input" => [
            "name" => $titulo,
            "content" => $descricao,
            "requesttypes_id" => $requesttype_id,
            "users_id_recipient" => $user_id,
            "entities_id" => $entity_id
        ]
    ];

    $ch = curl_init("$api_url/Ticket");

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "App-Token: $app_token",
        "Session-Token: $session_token"
    ]);

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dadosChamado));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}
