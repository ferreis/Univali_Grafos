<?php

require_once 'Grafo.php';
require_once 'GrafoMatriz.php';
require_once 'GrafoLista.php';
require_once 'includes/helpers.php';

class Fluxo
{
    /**
     * Algoritmo de Ford-Fulkerson para Fluxo Máximo.
     * * @param Grafo $grafo Grafo original (ponderado e direcionado).
     * @param int $fonte Índice do vértice de origem.
     * @param int $sorvedouro Índice do vértice de destino.
     * @return float O valor do fluxo máximo encontrado.
     */
    public static function fordFulkerson(Grafo $grafo, int $fonte, int $sorvedouro): float
    {
        $grafoResidual = self::copiarParaMatriz($grafo);

        $fluxoMaximo = 0.0;

        while (true) {
            $visitados = array_fill(0, self::contarVertices($grafoResidual), false);
            $caminho = [];

            $encontrouCaminho = self::dfsBuscaCaminho(
                $grafoResidual,
                $fonte,
                $sorvedouro,
                $visitados,
                $caminho
            );

            if (!$encontrouCaminho) {
                break;
            }

            $gargalo = INF;
            for ($i = 0; $i < count($caminho) - 1; $i++) {
                $u = $caminho[$i];
                $v = $caminho[$i + 1];
                $peso = $grafoResidual->pesoAresta($u, $v);
                if ($peso < $gargalo) {
                    $gargalo = $peso;
                }
            }

            for ($i = 0; $i < count($caminho) - 1; $i++) {
                $u = $caminho[$i];
                $v = $caminho[$i + 1];

                $pesoAtual = $grafoResidual->pesoAresta($u, $v);
                $grafoResidual->inserirAresta($u, $v, $pesoAtual - $gargalo);

                $pesoOposto = $grafoResidual->pesoAresta($v, $u);
                if ($pesoOposto === null) {
                    $pesoOposto = 0.0;
                }
                $grafoResidual->inserirAresta($v, $u, $pesoOposto + $gargalo);
            }

            $fluxoMaximo += $gargalo;
        }

        return $fluxoMaximo;
    }

    /**
     * Busca em Profundidade (DFS) para encontrar caminho aumentante.
     * Baseado na lógica recursiva da M1, mas para ao encontrar o destino.
     */
    private static function dfsBuscaCaminho(
        Grafo $grafo,
        int $atual,
        int $destino,
        array &$visitados,
        array &$caminho
    ): bool {
        $visitados[$atual] = true;
        $caminho[] = $atual;

        if ($atual === $destino) {
            return true;
        }

        foreach ($grafo->retornarVizinhos($atual) as $vizinho) {
            $capacidade = $grafo->pesoAresta($atual, $vizinho);
            if (!$visitados[$vizinho] && $capacidade > 0) {
                if (self::dfsBuscaCaminho($grafo, $vizinho, $destino, $visitados, $caminho)) {
                    return true;
                }
            }
        }

        array_pop($caminho);
        return false;
    }

    /**
     * Algoritmo de Busca Local para otimização do fluxo.
     * Tenta inverter arestas para encontrar uma estrutura que permita maior fluxo.
     */
    public static function otimizarFluxo(Grafo $grafoOriginal, int $fonte, int $sorvedouro): array
    {
        $grafoMelhor = self::copiarParaMatriz($grafoOriginal);
        $fluxoMelhor = self::fordFulkerson($grafoMelhor, $fonte, $sorvedouro);

        $passos = 0;
        $melhoriaEncontrada = true;

        while ($melhoriaEncontrada) {
            $melhoriaEncontrada = false;
            $melhorVizinhoGrafico = null;
            $fluxoDoMelhorVizinho = -1.0;

            $numVertices = self::contarVertices($grafoMelhor);
            
            for ($u = 0; $u < $numVertices; $u++) {
                foreach ($grafoMelhor->retornarVizinhos($u) as $v) {
                    $grafoVizinho = self::copiarParaMatriz($grafoMelhor);
                    $pesoOriginal = $grafoVizinho->pesoAresta($u, $v);
                    $grafoVizinho->removerAresta($u, $v);
                    $grafoVizinho->inserirAresta($v, $u, $pesoOriginal);

                    $fluxoVizinho = self::fordFulkerson($grafoVizinho, $fonte, $sorvedouro);

                    if ($fluxoVizinho > $fluxoMelhor && $fluxoVizinho > $fluxoDoMelhorVizinho) {
                        $fluxoDoMelhorVizinho = $fluxoVizinho;
                        $melhorVizinhoGrafico = $grafoVizinho;
                        $melhoriaEncontrada = true;
                    }
                }
            }

            if ($melhoriaEncontrada) {
                $grafoMelhor = $melhorVizinhoGrafico;
                $fluxoMelhor = $fluxoDoMelhorVizinho;
                $passos++;
            }
        }

        return [
            'initial_flow' => self::fordFulkerson(self::copiarParaMatriz($grafoOriginal), $fonte, $sorvedouro),
            'optimized_flow' => $fluxoMelhor,
            'steps' => $passos,
            'final_graph' => $grafoMelhor
        ];
    }

    // =================== Helpers ===================

    /**
     * Cria uma cópia profunda do grafo transformando-o em GrafoMatriz.
     * Isso evita problemas de referência e facilita operações de aresta (O(1)).
     */
    private static function copiarParaMatriz(Grafo $origem): GrafoMatriz
    {
        $numVertices = self::contarVertices($origem);

        $copia = new GrafoMatriz(true, true);

        for ($i = 0; $i < $numVertices; $i++) {
            $label = $origem->labelVertice($i) ?? (string)$i;
            $copia->inserirVertice($label);
        }

        for ($u = 0; $u < $numVertices; $u++) {
            foreach ($origem->retornarVizinhos($u) as $v) {
                $peso = $origem->pesoAresta($u, $v);
                if ($peso !== null) {
                    $copia->inserirAresta($u, $v, $peso);
                }
            }
        }

        return $copia;
    }

    /**
     * Helper para contar vértices acessando a propriedade protegida via Reflection,
     * já que o método obterNumeroDeVertices() é protected em Grafo.php.
     */
    private static function contarVertices(Grafo $grafo): int
    {
        $reflection = new ReflectionClass('Grafo');
        $propriedadeVertices = $reflection->getProperty('vertices');
        $propriedadeVertices->setAccessible(true);
        $arrayVertices = $propriedadeVertices->getValue($grafo);
        
        return count($arrayVertices);
    }
}