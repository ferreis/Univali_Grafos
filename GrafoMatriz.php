<?php

/**
 * Implementação de Grafo usando MATRIZ DE ADJACÊNCIA.
 *
 * Como eu (autor) pensei:
 * - Para cada par (origem, destino) guardo um número na matriz:
 *      0   => não existe aresta
 *      >0  => existe aresta; se for grafo ponderado, esse número é o peso
 *             (em grafo não ponderado, uso 1 por convenção)
 * - A matriz é quadrada (N x N), onde N = quantidade de vértices.
 * - Quando adiciono um vértice, eu:
 *      1) aumento cada linha existente com uma nova coluna 0
 *      2) adiciono uma nova linha cheia de 0 (do tamanho novo N)
 * - Quando removo um vértice, eu:
 *      1) removo o rótulo do vértice
 *      2) removo a linha correspondente
 *      3) removo a coluna correspondente em todas as outras linhas
 *
 * Flags herdadas da classe base Grafo:
 * - $direcionado = true  → arestas (u->v) e (v->u) são independentes
 * - $ponderado   = true  → o número armazenado é de fato um peso (float)
 *
 * Observações:
 * - Todas as assinaturas respeitam a classe base (Grafo.php).
 * - Em não-direcionado, sempre espelho a operação para (destino, origem).
 */

require_once "Grafo.php";

class GrafoMatriz extends Grafo
{
    /**
     * @var array<int, array<int, float|int>>
     * Matriz de adjacência:
     * - $matriz[i][j] = 0          → não há aresta i→j
     * - $matriz[i][j] = peso (>0)  → existe aresta i→j com esse peso
     */
    private $matriz;

    public function __construct(bool $direcionado = false, bool $ponderado = false)
    {
        parent::__construct($direcionado, $ponderado);
        $this->matriz = [];
    }

    public function inserirVertice(string $label): bool
    {
        $this->vertices[] = $label;

        $quantidadeDeVertices = count($this->vertices);

        
        foreach ($this->matriz as &$linhaDaMatriz) {
            $linhaDaMatriz[] = 0;
        }
        unset($linhaDaMatriz);

        
        $this->matriz[] = array_fill(0, $quantidadeDeVertices, 0);

        return true;
    }

    public function removerVertice(int $indice): bool
    {
        if (!isset($this->vertices[$indice])) {
            return false; // índice inválido
        }

        
        unset($this->vertices[$indice]);
        $this->vertices = array_values($this->vertices);

        
        unset($this->matriz[$indice]);
        $this->matriz = array_values($this->matriz);

        
        foreach ($this->matriz as &$linhaDaMatriz) {
            unset($linhaDaMatriz[$indice]);
            $linhaDaMatriz = array_values($linhaDaMatriz);
        }
        unset($linhaDaMatriz);

        return true;
    }

    public function labelVertice(int $indice): ?string
    {
        return $this->vertices[$indice] ?? null;
    }

    public function imprimeGrafo(): void
    {
        foreach ($this->matriz as $linhaDaMatriz) {
            echo implode(" ", $linhaDaMatriz) . PHP_EOL;
        }
    }

    public function inserirAresta(int $origem, int $destino, float $peso = 1): bool
    {
        
        if (!isset($this->vertices[$origem]) || !isset($this->vertices[$destino])) {
            return false;
        }

        
        $this->matriz[$origem][$destino] = $this->ponderado ? $peso : 1;

        
        if (!$this->direcionado) {
            $this->matriz[$destino][$origem] = $this->ponderado ? $peso : 1;
        }

        return true;
    }

    public function removerAresta(int $origem, int $destino): bool
    {
        if (!isset($this->vertices[$origem]) || !isset($this->vertices[$destino])) {
            return false;
        }

        
        $this->matriz[$origem][$destino] = 0;

        
        if (!$this->direcionado) {
            $this->matriz[$destino][$origem] = 0;
        }

        return true;
    }

    public function existeAresta(int $origem, int $destino): bool
    {
        return ($this->matriz[$origem][$destino] ?? 0) != 0;
    }

    public function pesoAresta(int $origem, int $destino): ?float
    {
        return $this->matriz[$origem][$destino] ?? null;
    }

    public function retornarVizinhos(int $vertice): array
    {
        // array_filter sem callback elimina valores "falsy" (0), mantendo >0
        return array_keys(array_filter($this->matriz[$vertice]));
    }
}
