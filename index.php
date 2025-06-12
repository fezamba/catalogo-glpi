<?php
echo "<pre style='font-family: monospace; font-size: 14px; line-height: 1.6;'>";
echo "<h1>Verificando Variáveis de Ambiente do Servidor</h1>";

echo "<h2>Conteúdo da superglobal \$_ENV:</h2>";
// Imprime todas as variáveis que o Railway passou para o $_ENV
print_r($_ENV);

echo "\n<hr><h2>Teste com getenv() individualmente:</h2>";
echo "MYSQLHOST: " . getenv('MYSQLHOST') . "\n";
echo "MYSQLUSER: " . getenv('MYSQLUSER') . "\n";
// Não imprimimos a senha por segurança, apenas verificamos se ela existe
echo "MYSQLPASSWORD: " . (getenv('MYSQLPASSWORD') ? 'Encontrada (oculta)' : '*** NÃO ENCONTRADA ***') . "\n";
echo "MYSQLDATABASE: " . getenv('MYSQLDATABASE') . "\n";
echo "MYSQLPORT: " . getenv('MYSQLPORT') . "\n";

echo "</pre>";
?>
