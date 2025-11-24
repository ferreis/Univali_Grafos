<?php

require_once 'Fluxo.php';
require_once 'GrafoMatriz.php';

if ($argc < 4) {
    echo "Uso: php run_maxflow.php <arquivo_grafo> <fonte_indice> <sorvedouro_indice>\n";
    echo "Exemplo: php run_maxflow.php " . __DIR__ . "/inputs testes/50vertices25Arestas.txt 0 49\n";
    exit(1);
}

$arquivo = $argv[1];
$fonte = intval($argv[2]);
$sorvedouro = intval($argv[3]);

if (!file_exists($arquivo)) {
    echo "Arquivo não encontrado: $arquivo\n";
    exit(1);
}

$grafo = new GrafoMatriz(true, true);
try {
    $grafo->carregarDeArquivo($arquivo);
} catch (Exception $e) {
    echo "Erro ao carregar grafo: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

echo "Executando Ford-Fulkerson...\n";
$fluxoInicial = Fluxo::fordFulkerson($grafo, $fonte, $sorvedouro);
echo "Fluxo Máximo (Ford-Fulkerson): " . $fluxoInicial . PHP_EOL;

echo "\nExecutando Busca Local para otimização...\n";
$resultado = Fluxo::otimizarFluxo($grafo, $fonte, $sorvedouro);

if (is_array($resultado)) {
    echo "Fluxo Máximo Inicial: " . ($resultado['initial_flow'] ?? $fluxoInicial) . PHP_EOL;
    echo "Fluxo Máximo Otimizado: " . ($resultado['optimized_flow'] ?? $fluxoInicial) . PHP_EOL;
    echo "Total de Passos: " . ($resultado['steps'] ?? 0) . PHP_EOL;
} else {
    echo "Busca local retornou formato inesperado." . PHP_EOL;
}

echo "Concluído.\n";
