<?php
session_start();

// Lista de palavras inúteis (stopwords)
$stopwords = ['o', 'a', 'as', 'os', 'é', 'de', 'do', 'da', 'e', 'ou', 'pra', 'com', 'no', 'na', 'um', 'uma', 'que', 'qual', 'meu', 'minha', 'eu', 'você'];

// Dicionário de sinônimos
$sinonimos = [
    'senha' => ['senha', 'password', 'acesso', 'login', 'redefinir', 'trocar'],
    'outlook' => ['outlook', 'email', 'e-mail', 'mensagem'],
    'excel' => ['excel', 'planilha', 'licença', 'ativar'],
    'sefaznet' => ['sefaznet', 'sefaz', 'acesso', 'sistema'],
    'erro' => ['erro', 'bug', 'problema', 'falha', 'travando'],
];

// Recebe a pergunta
$perguntaUsuario = isset($_POST['pergunta']) ? strtolower(trim($_POST['pergunta'])) : '';
$_SESSION['ultima_pergunta'] = $perguntaUsuario;

// Quebra e limpa as palavras da pergunta
$palavras = preg_split('/[\s,]+/', $perguntaUsuario);
$palavrasFiltradas = array_diff($palavras, $stopwords);

// Expande com sinônimos
$palavrasExpandida = $palavrasFiltradas;
foreach ($palavrasFiltradas as $palavra) {
    foreach ($sinonimos as $grupo) {
        if (in_array($palavra, $grupo)) {
            $palavrasExpandida = array_merge($palavrasExpandida, $grupo);
        }
    }
}
$palavrasExpandida = array_unique($palavrasExpandida);

// Carrega o CSV
$csv = array_map(fn($linha) => str_getcsv($linha, ',', '"', '\\'), file('base_conhecimento.csv'));
array_shift($csv); // Remove cabeçalho

// Inicia busca
$maiorPontuacao = 0;
$melhorResposta = '';
$idSub = null;

foreach ($csv as $linha) {
    [$pergunta, $resposta, $id, $chaves] = $linha;
    $palavrasChave = explode(' ', strtolower($chaves));
    $pontos = 0;

    foreach ($palavrasExpandida as $palavraUser) {
        foreach ($palavrasChave as $palavraBase) {
            if ($palavraUser === $palavraBase) $pontos += 3;
            elseif (strpos($palavraBase, $palavraUser) !== false) $pontos += 2;
        }
    }

    if (str_starts_with($perguntaUsuario, strtolower($pergunta))) $pontos += 5;

    if ($pontos > $maiorPontuacao) {
        $maiorPontuacao = $pontos;
        $melhorResposta = $resposta;
        $idSub = $id;
    }
}

// Gera resposta
if ($maiorPontuacao === 0) {
    $falhas = [
        "🤔 Não entendi muito bem... tenta reformular aí!",
        "😅 Ihhh... essa passou direto. Manda de novo de outro jeito?",
        "🧐 Hmmm... isso não me é familiar. Pode repetir de forma diferente?"
    ];
    $respostaFinal = $falhas[array_rand($falhas)];
    $idSub = null;
} else {
    $respostaFinal = $melhorResposta;
}
// Retorna JSON
echo json_encode([
    'resposta' => nl2br($respostaFinal),
    'id' => $idSub
]);
