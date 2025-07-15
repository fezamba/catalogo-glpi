<?php
// Substituir pelas credenciais do banco do GLPI
// Será necessário criar novas tabelas, utilize plguin_nome_da_tabela para criar as tabelas necessárias
$host = 'yamabiko.proxy.rlwy.net';
$user = 'root';
$pass = 'UeNxoVXpTBdeuPmEafuCxNKXCzbtxbaT';
$db   = 'railway';
$port = 15683;

$mysqli = new mysqli($host, $user, $pass, $db, $port);

if ($mysqli->connect_errno) {
    error_log("Erro de conexão com o banco de dados: " . $mysqli->connect_error);
    die("Ocorreu um erro inesperado no servidor. Por favor, tente novamente mais tarde.");
}
?>