<?php

abstract class Grafo
{
    protected bool $direcionado;
    protected bool $ponderado;

    /**
     * Array de rótulos (labels) por índice de vértice.
     * Índice 0..N-1
     * Ex.: $vertices[0] = "0", $vertices[1] = "1", ...
     */
    protected array $vertices;

    public function __construct(bool $direcionado = false, bool $ponderado = false)
    {
        $this->direcionado = $direcionado;
        $this->ponderado = $ponderado;
        $this->vertices = [];
    }

    // ===== Operações básicas que as classes filhas DEVEM implementar =====
    abstract public function inserirVertice(string $rotuloVertice): bool;
    abstract public function removerVertice(int $indiceVertice): bool;
    abstract public function labelVertice(int $indiceVertice): ?string;
    abstract public function imprimeGrafo(): void;

    abstract public function inserirAresta(int $indiceOrigem, int $indiceDestino, float $pesoDaAresta = 1): bool;
    abstract public function removerAresta(int $indiceOrigem, int $indiceDestino): bool;
    abstract public function existeAresta(int $indiceOrigem, int $indiceDestino): bool;

    /**
     * Deve retornar o peso da aresta se existir.
     * Retornar null se NÃO existir aresta.
     * Em grafos não ponderados, retornar 1 quando existir aresta.
     */
    abstract public function pesoAresta(int $indiceOrigem, int $indiceDestino): ?float;

    /**
     * Deve retornar um array de INT com os vizinhos do vértice (índices).
     */
    abstract public function retornarVizinhos(int $indiceVertice): array;

    // =================== Helpers ===================

    protected function obterNumeroDeVertices(): int
    {
        return count($this->vertices);
    }

    protected function definirDirecionado(bool $ehDirecionado): void
    {
        $this->direcionado = $ehDirecionado;
    }

    protected function definirPonderado(bool $ehPonderado): void
    {
        $this->ponderado = $ehPonderado;
    }

    // =================== Leitura de arquivo ===================

    /**
     * Formato:
     * Linha 1: V A D P
     * Próximas A linhas:
     *   - Se P=0: origem destino
     *   - Se P=1: origem destino peso
     *
     * Índices no arquivo podem ser baseados em 0 ou 1. O método detecta:
     *   - Se maior índice aparecer == V  => considera base 1 (subtrai 1)
     *   - Caso contrário                 => considera base 0
     */
    public function carregarDeArquivo(string $caminhoArquivo): void
    {
        $linhas = @file($caminhoArquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($linhas === false || count($linhas) === 0) {
            throw new RuntimeException("Falha ao ler o arquivo '{$caminhoArquivo}'.");
        }

        // Cabeçalho: V A D P
        $cabecalho = preg_split('/\s+/', trim($linhas[0]));
        if (count($cabecalho) !== 4) {
            throw new InvalidArgumentException("Cabeçalho inválido. Esperado: 'V A D P'.");
        }
        [$quantidadeVertices, $quantidadeArestas, $flagDirecionado, $flagPonderado] = array_map('intval', $cabecalho);
        $this->definirDirecionado($flagDirecionado === 1);
        $this->definirPonderado($flagPonderado === 1);

        $linhasArestas = array_slice($linhas, 1, $quantidadeArestas);
        if (count($linhasArestas) !== $quantidadeArestas) {
            throw new InvalidArgumentException("Arquivo informa {$quantidadeArestas} arestas, mas foram lidas " . count($linhasArestas) . ".");
        }

        // ===== Detectar modo: índices numéricos (base 0/1) OU labels (A,B,C,...) =====
        $usarLabels = false;
        $maiorIndiceVisto = -1;
        $conjuntoLabels = [];              // ordem de 1ª aparição
        $mapaLabelParaIndice = [];

        foreach ($linhasArestas as $linha) {
            $p = preg_split('/\s+/', trim($linha));
            if (count($p) < 2) {
                throw new InvalidArgumentException("Linha de aresta inválida: '{$linha}'.");
            }
            $a = $p[0];
            $b = $p[1];

            $aEhNum = preg_match('/^-?\d+$/', $a) === 1;
            $bEhNum = preg_match('/^-?\d+$/', $b) === 1;

            if (!($aEhNum && $bEhNum)) {
                $usarLabels = true;
            }

            if ($aEhNum) $maiorIndiceVisto = max($maiorIndiceVisto, intval($a));
            if ($bEhNum) $maiorIndiceVisto = max($maiorIndiceVisto, intval($b));

            if ($usarLabels) {
                if (!$aEhNum && !isset($mapaLabelParaIndice[$a])) {
                    $mapaLabelParaIndice[$a] = count($conjuntoLabels);
                    $conjuntoLabels[] = $a;
                }
                if (!$bEhNum && !isset($mapaLabelParaIndice[$b])) {
                    $mapaLabelParaIndice[$b] = count($conjuntoLabels);
                    $conjuntoLabels[] = $b;
                }
            }
        }

        // ===== Criar vértices =====
        $this->vertices = [];

        if ($usarLabels) {
            // Validar quantidade
            if (count($conjuntoLabels) !== $quantidadeVertices) {
                throw new InvalidArgumentException(
                    "Cabeçalho V={$quantidadeVertices}, mas o arquivo usa labels para " . count($conjuntoLabels) . " vértices."
                );
            }
            // Inserir com labels do arquivo (ordem de 1ª aparição)
            foreach ($conjuntoLabels as $rotulo) {
                $this->inserirVertice((string)$rotulo);
            }
        } else {
            // Modo numérico: detectar base (0 ou 1)
            $arquivoEhBase1 = ($maiorIndiceVisto === $quantidadeVertices);
            for ($i = 0; $i < $quantidadeVertices; $i++) {
                // rótulo padrão = índice em string
                $this->inserirVertice((string)$i);
            }
        }

        // ===== Inserir arestas =====
        foreach ($linhasArestas as $linha) {
            $p = preg_split('/\s+/', trim($linha));
            $a = $p[0];
            $b = $p[1];

            // Peso
            $peso = 1.0;
            if ($this->ponderado) {
                if (count($p) < 3) throw new InvalidArgumentException("Aresta ponderada sem peso: '{$linha}'.");
                $peso = floatval($p[2]);
                if ($peso < 0) throw new InvalidArgumentException("Peso negativo não suportado no Dijkstra. Linha: '{$linha}'.");
            }

            // Resolver índices
            if ($usarLabels) {
                if (!isset($mapaLabelParaIndice[$a]) || !isset($mapaLabelParaIndice[$b])) {
                    throw new InvalidArgumentException("Label desconhecido na linha: '{$linha}'.");
                }
                $origem  = $mapaLabelParaIndice[$a];
                $destino = $mapaLabelParaIndice[$b];
            } else {
                $arquivoEhBase1 = ($maiorIndiceVisto === $quantidadeVertices);
                $origem  = intval($a);
                $destino = intval($b);
                if ($arquivoEhBase1) {
                    $origem--;
                    $destino--;
                }
                if ($origem < 0 || $origem >= $quantidadeVertices || $destino < 0 || $destino >= $quantidadeVertices) {
                    throw new OutOfBoundsException("Índices fora de 0.." . ($quantidadeVertices - 1) . ": {$origem} -> {$destino}");
                }
            }

            // Inserir uma via
            $this->inserirAresta($origem, $destino, $peso);
            // Duplicar se não direcionado
            if (!$this->direcionado) {
                $this->inserirAresta($destino, $origem, $peso);
            }
        }
    }
    //Breadth-First Search - Busca em Largura
    // =================== BFS ===================

    public function bfs(int $indiceVerticeInicial): array
    {
        $quantidadeVertices = $this->obterNumeroDeVertices();
        if ($indiceVerticeInicial < 0 || $indiceVerticeInicial >= $quantidadeVertices) {
            throw new OutOfBoundsException("Vértice inicial fora do intervalo.");
        }

        $foiVisitadoPorIndice = array_fill(0, $quantidadeVertices, false);
        $filaDeVisitaPorIndice = new SplQueue();
        $ordemDeVisitaPorIndice = [];

        $foiVisitadoPorIndice[$indiceVerticeInicial] = true;
        $filaDeVisitaPorIndice->enqueue($indiceVerticeInicial);

        while (!$filaDeVisitaPorIndice->isEmpty()) {
            $indiceAtual = $filaDeVisitaPorIndice->dequeue();
            $ordemDeVisitaPorIndice[] = $indiceAtual;

            foreach ($this->retornarVizinhos($indiceAtual) as $indiceVizinho) {
                if ($indiceVizinho < 0 || $indiceVizinho >= $quantidadeVertices) {
                    continue; // ignora lixo
                }
                if (!$foiVisitadoPorIndice[$indiceVizinho]) {
                    $foiVisitadoPorIndice[$indiceVizinho] = true;
                    $filaDeVisitaPorIndice->enqueue($indiceVizinho);
                }
            }
        }

        return $ordemDeVisitaPorIndice;
    }
    // Depth-First Search - Busca em Profundidade
    // =================== DFS ===================

    public function dfs(int $indiceVerticeInicial): array
    {
        $quantidadeVertices = $this->obterNumeroDeVertices();
        if ($indiceVerticeInicial < 0 || $indiceVerticeInicial >= $quantidadeVertices) {
            throw new OutOfBoundsException("Vértice inicial fora do intervalo.");
        }

        $foiVisitadoPorIndice = array_fill(0, $quantidadeVertices, false);
        $ordemDeVisitaPorIndice = [];

        $this->dfsRecursivo($indiceVerticeInicial, $foiVisitadoPorIndice, $ordemDeVisitaPorIndice);

        return $ordemDeVisitaPorIndice;
    }

    private function dfsRecursivo(int $indiceAtual, array &$foiVisitadoPorIndice, array &$ordemDeVisitaPorIndice): void
    {
        $foiVisitadoPorIndice[$indiceAtual] = true;
        $ordemDeVisitaPorIndice[] = $indiceAtual;

        foreach ($this->retornarVizinhos($indiceAtual) as $indiceVizinho) {
            if (!$foiVisitadoPorIndice[$indiceVizinho]) {
                $this->dfsRecursivo($indiceVizinho, $foiVisitadoPorIndice, $ordemDeVisitaPorIndice);
            }
        }
    }

    // =================== Dijkstra ===================

    /**
     * Retorna para cada vértice:
     * [
     *   'distancia' => float|INF,
     *   'caminho'   => int[] (índices do caminho da origem até ele; vazio se inalcançável)
     * ]
     */
    public function dijkstra(int $indiceOrigem): array
    {
        $quantidadeVertices = $this->obterNumeroDeVertices();
        if ($indiceOrigem < 0 || $indiceOrigem >= $quantidadeVertices) {
            throw new OutOfBoundsException("Vértice de origem fora do intervalo.");
        }

        $distanciaMinimaPorIndice = array_fill(0, $quantidadeVertices, INF);
        $precedentePorIndice = array_fill(0, $quantidadeVertices, null);
        $processadoPorIndice = array_fill(0, $quantidadeVertices, false);

        $distanciaMinimaPorIndice[$indiceOrigem] = 0.0;

        // SplPriorityQueue prioriza MAIOR prioridade,
        // então usamos prioridade negativa da distância para simular min-heap.
        $filaPrioridade = new SplPriorityQueue();
        $filaPrioridade->setExtractFlags(SplPriorityQueue::EXTR_DATA); // extrai o vértice
        $filaPrioridade->insert($indiceOrigem, 0.0);

        while (!$filaPrioridade->isEmpty()) {
            $indiceAtual = $filaPrioridade->extract();

            if ($processadoPorIndice[$indiceAtual]) {
                continue;
            }
            $processadoPorIndice[$indiceAtual] = true;

            foreach ($this->retornarVizinhos($indiceAtual) as $indiceVizinho) {
                $peso = $this->pesoAresta($indiceAtual, $indiceVizinho);
                if ($peso === null) {
                    continue; // sem aresta
                }
                if ($peso < 0) {
                    throw new InvalidArgumentException("Dijkstra não suporta pesos negativos ({$indiceAtual} -> {$indiceVizinho} = {$peso}).");
                }

                $novaDistancia = $distanciaMinimaPorIndice[$indiceAtual] + $peso;
                if ($novaDistancia < $distanciaMinimaPorIndice[$indiceVizinho]) {
                    $distanciaMinimaPorIndice[$indiceVizinho] = $novaDistancia;
                    $precedentePorIndice[$indiceVizinho] = $indiceAtual;
                    $filaPrioridade->insert($indiceVizinho, -$novaDistancia);
                }
            }
        }

        // Montar resultado
        $resultadoPorVertice = [];
        for ($indiceDestino = 0; $indiceDestino < $quantidadeVertices; $indiceDestino++) {
            $caminhoReconstruido = $this->reconstruirCaminho($indiceOrigem, $indiceDestino, $precedentePorIndice);
            $resultadoPorVertice[$indiceDestino] = [
                'distancia' => $distanciaMinimaPorIndice[$indiceDestino],
                'caminho'   => $caminhoReconstruido,
            ];
        }

        return $resultadoPorVertice;
    }

    /**
     * Reconstrói caminho da origem até destino usando vetor de precedentes.
     * Se inalcançável, retorna array vazio.
     */
    protected function reconstruirCaminho(int $indiceOrigem, int $indiceDestino, array $precedentePorIndice): array
    {
        if (!is_finite($precedentePorIndice[$indiceDestino] ?? INF) && $indiceDestino !== $indiceOrigem) {
            // Checagem rápida: se destino não tem precedente e não é a origem,
            // pode estar inalcançável. Ainda assim, tentaremos montar abaixo.
        }

        $caminho = [];
        for ($v = $indiceDestino; $v !== null; $v = $precedentePorIndice[$v]) {
            array_unshift($caminho, $v);
            if ($v === $indiceOrigem) {
                break;
            }
        }

        // Se não começou na origem, é que não há caminho.
        if (empty($caminho) || $caminho[0] !== $indiceOrigem) {
            return [];
        }

        return $caminho;
    }
}
