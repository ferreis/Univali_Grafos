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

    // Operações básicas que as classes filhas DEVEM implementar
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

    // Helpers

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

    // Leitura de arquivo

    /**
     * Formato:
     * Linha 1: V A D P
     * Próximas A linhas:
     * - Se P=0: origem destino
     * - Se P=1: origem destino peso
     *
     * Índices no arquivo podem ser baseados em 0 ou 1. O método detecta:
     * - Se maior índice aparecer == V  => considera base 1 (subtrai 1)
     * - Caso contrário                 => considera base 0
     */
    public function carregarDeArquivo(string $caminhoArquivo): void
    {
        $linhas = @file($caminhoArquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($linhas === false || count($linhas) === 0) {
            throw new RuntimeException("Falha ao ler o arquivo '{$caminhoArquivo}'.");
        }

        
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

        
        $this->vertices = [];

        if ($usarLabels) {
            // Validar quantidade
            if (count($conjuntoLabels) !== $quantidadeVertices) {
                throw new InvalidArgumentException(
                    "Cabeçalho V={$quantidadeVertices}, mas o arquivo usa labels para " . count($conjuntoLabels) . " vértices."
                );
            }
            
            foreach ($conjuntoLabels as $rotulo) {
                $this->inserirVertice((string)$rotulo);
            }
        } else {
            
            $arquivoEhBase1 = ($maiorIndiceVisto === $quantidadeVertices);
            for ($i = 0; $i < $quantidadeVertices; $i++) {
                
                $this->inserirVertice((string)$i);
            }
        }

        
        foreach ($linhasArestas as $linha) {
            $p = preg_split('/\s+/', trim($linha));
            $a = $p[0];
            $b = $p[1];

            
            $peso = 1.0;
            if ($this->ponderado) {
                if (count($p) < 3) throw new InvalidArgumentException("Aresta ponderada sem peso: '{$linha}'.");
                $peso = floatval($p[2]);
                if ($peso < 0) throw new InvalidArgumentException("Peso negativo não suportado no Dijkstra. Linha: '{$linha}'.");
            }

            
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
     * 'distancia' => float|INF,
     * 'caminho'   => int[] (índices do caminho da origem até ele; vazio se inalcançável)
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
        }

        $caminho = [];
        for ($v = $indiceDestino; $v !== null; $v = $precedentePorIndice[$v]) {
            array_unshift($caminho, $v);
            if ($v === $indiceOrigem) {
                break;
            }
        }
        if (empty($caminho) || $caminho[0] !== $indiceOrigem) {
            return [];
        }

        return $caminho;
    }

    // =================== Prim ===================

    /**
     * Algoritmo de Prim para encontrar a Árvore Geradora Mínima (AGM).
     * Funciona em grafos CONEXOS, não-direcionados e ponderados.
     *
     * @param int $indiceVerticeInicial Vértice para iniciar a busca.
     * @return array ['peso_total' => float, 'arestas' => array]
     */
    public function prim(int $indiceVerticeInicial = 0): array
    {
        $num_vertices = $this->obterNumeroDeVertices();
        if ($num_vertices === 0) {
            return ['peso_total' => 0.0, 'arestas' => []];
        }

        // Custo mínimo para conectar o vértice [i] à AGM
        $custo_minimo_aresta = array_fill(0, $num_vertices, INF);
        // Aresta que conecta o vértice [i] (ex: ['de' => 0, 'para' => 1, 'peso' => 5])
        $aresta_precedente_na_agm = array_fill(0, $num_vertices, null);
        // Vértice [i] já está na AGM?
        $vertice_esta_na_agm = array_fill(0, $num_vertices, false);

        $fila_prioridade = new SplPriorityQueue();
        $fila_prioridade->insert($indiceVerticeInicial, 0.0);
        $custo_minimo_aresta[$indiceVerticeInicial] = 0.0;

        $agm_arestas = [];
        $agm_peso_total = 0.0;

        while (!$fila_prioridade->isEmpty()) {
            $vertice_atual = $fila_prioridade->extract();

            if ($vertice_esta_na_agm[$vertice_atual]) {
                continue;
            }

            $vertice_esta_na_agm[$vertice_atual] = true;

            // Se este vértice não é a raiz, adiciona a aresta que o conectou
            if ($aresta_precedente_na_agm[$vertice_atual] !== null) {
                $aresta = $aresta_precedente_na_agm[$vertice_atual];
                $agm_arestas[] = $aresta;
                $agm_peso_total += $aresta['peso'];
            }

            foreach ($this->retornarVizinhos($vertice_atual) as $vizinho) {
                $peso_aresta = $this->pesoAresta($vertice_atual, $vizinho);

                if ($peso_aresta === null) continue; // Sanidade

                // Se o vizinho ainda não está na AGM e esta aresta é mais barata...
                if (!$vertice_esta_na_agm[$vizinho] && $peso_aresta < $custo_minimo_aresta[$vizinho]) {
                    $custo_minimo_aresta[$vizinho] = $peso_aresta;
                    $aresta_precedente_na_agm[$vizinho] = [
                        'de' => $vertice_atual,
                        'para' => $vizinho,
                        'peso' => $peso_aresta
                    ];
                    $fila_prioridade->insert($vizinho, -$peso_aresta);
                }
            }
        }

        return ['peso_total' => $agm_peso_total, 'arestas' => $agm_arestas];
    }

    // =================== Kruskal ===================

    /**
     * Algoritmo de Kruskal para encontrar a Árvore Geradora Mínima (AGM).
     * Funciona em grafos não-direcionados e ponderados.
     *
     * @return array ['peso_total' => float, 'arestas' => array]
     */
    public function kruskal(): array
    {
        $num_vertices = $this->obterNumeroDeVertices();
        if ($num_vertices === 0) {
            return ['peso_total' => 0.0, 'arestas' => []];
        }

        $arestas_grafo = [];
        for ($origem = 0; $origem < $num_vertices; $origem++) {
            foreach ($this->retornarVizinhos($origem) as $destino) {
                // Como é não-direcionado, só adicionamos se (origem < destino) para evitar duplicatas
                if ($origem < $destino) {
                    $peso = $this->pesoAresta($origem, $destino);
                    if ($peso !== null) {
                        $arestas_grafo[] = [
                            'de' => $origem,
                            'para' => $destino,
                            'peso' => $peso
                        ];
                    }
                }
            }
        }

        // Ordena todas as arestas do grafo pelo peso (menor para maior)
        usort($arestas_grafo, fn($a, $b) => $a['peso'] <=> $b['peso']);

        $conjuntos_disjuntos = new UniaoBusca($num_vertices);
        $agm_arestas = [];
        $agm_peso_total = 0.0;
        $num_arestas_na_agm = 0;

        foreach ($arestas_grafo as $aresta) {
            // Se a união for bem-sucedida (não forma ciclo)
            if ($conjuntos_disjuntos->unir($aresta['de'], $aresta['para'])) {
                $agm_arestas[] = $aresta;
                $agm_peso_total += $aresta['peso'];
                $num_arestas_na_agm++;

                // Otimização: se já temos V-1 arestas, a AGM está completa
                if ($num_arestas_na_agm === $num_vertices - 1) {
                    break;
                }
            }
        }

        return ['peso_total' => $agm_peso_total, 'arestas' => $agm_arestas];
    }
}


/**
 * Classe auxiliar para o algoritmo de Kruskal (Disjoint Set Union / Union-Find).
 * Usada para detectar ciclos de forma eficiente.
 */
class UniaoBusca
{
    /** @var int[] */
    protected array $parente;
    /** @var int[] */
    protected array $ranking;

    public function __construct(int $numeroDeElementos)
    {
        $this->parente = array_fill(0, $numeroDeElementos, 0);
        $this->ranking = array_fill(0, $numeroDeElementos, 0);
        for ($i = 0; $i < $numeroDeElementos; $i++) {
            $this->parente[$i] = $i; // Cada um é seu próprio parente inicialmente
        }
    }

    /**
     * Encontra o representante (raiz) do conjunto ao qual $i pertence.
     */
    public function encontrar(int $i): int
    {
        if ($this->parente[$i] == $i) {
            return $i;
        }
        // Compressão de caminho: aponta $i diretamente para a raiz
        $this->parente[$i] = $this->encontrar($this->parente[$i]);
        return $this->parente[$i];
    }

    /**
     * Une os conjuntos que contêm $x$ e $y$.
     * Retorna true se a união foi feita, false se $x$ e $y$ já estavam no mesmo conjunto.
     */
    public function unir(int $x, int $y): bool
    {
        $raizDeX = $this->encontrar($x);
        $raizDeY = $this->encontrar($y);

        if ($raizDeX === $raizDeY) {
            return false;
        }

        // União por ranking: anexa a árvore menor à maior
        if ($this->ranking[$raizDeX] < $this->ranking[$raizDeY]) {
            $this->parente[$raizDeX] = $raizDeY;
        } elseif ($this->ranking[$raizDeX] > $this->ranking[$raizDeY]) {
            $this->parente[$raizDeY] = $raizDeX;
        } else {
            $this->parente[$raizDeY] = $raizDeX;
            $this->ranking[$raizDeX]++;
        }
        return true;
    }
}
