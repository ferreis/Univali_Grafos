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

    /**
     * Construtor: só inicia flags e a matriz vazia.
     */
    public function __construct(bool $direcionado = false, bool $ponderado = false)
    {
        parent::__construct($direcionado, $ponderado);
        $this->matriz = [];
    }

    /**
     * Inserir um novo vértice com rótulo ($label).
     * Passos:
     * 1) adiciono o label na lista de vértices
     * 2) para cada linha existente, acrescento uma coluna 0
     * 3) crio a nova linha (tamanho N) cheia de 0
     */
    public function inserirVertice(string $label): bool
    {
        $this->vertices[] = $label;

        $quantidadeDeVertices = count($this->vertices);

        // 2) expandir cada linha existente com uma nova coluna 0
        foreach ($this->matriz as &$linhaDaMatriz) {
            $linhaDaMatriz[] = 0;
        }
        unset($linhaDaMatriz);

        // 3) nova linha (N colunas), toda zerada
        $this->matriz[] = array_fill(0, $quantidadeDeVertices, 0);

        return true;
    }

    /**
     * Remover um vértice pelo índice.
     * Passos:
     * 1) remover o rótulo do vértice e reindexar
     * 2) remover a linha correspondente da matriz e reindexar
     * 3) remover a coluna correspondente em todas as outras linhas (e reindexar)
     */
    public function removerVertice(int $indice): bool
    {
        if (!isset($this->vertices[$indice])) {
            return false; // índice inválido
        }

        // 1) tira o rótulo e compacta os índices (0..N-2)
        unset($this->vertices[$indice]);
        $this->vertices = array_values($this->vertices);

        // 2) remove a linha da matriz e compacta
        unset($this->matriz[$indice]);
        $this->matriz = array_values($this->matriz);

        // 3) remove a coluna "indice" de cada linha restante e compacta
        foreach ($this->matriz as &$linhaDaMatriz) {
            unset($linhaDaMatriz[$indice]);
            $linhaDaMatriz = array_values($linhaDaMatriz);
        }
        unset($linhaDaMatriz);

        return true;
    }

    /**
     * Retorna o rótulo (label) do vértice se existir; null caso contrário.
     */
    public function labelVertice(int $indice): ?string
    {
        return $this->vertices[$indice] ?? null;
    }

    /**
     * Imprime a matriz de adjacência, uma linha por vez.
     * Útil para depuração rápida no terminal.
     * Exemplo de saída para N=3:
     * 0 1 0
     * 1 0 1
     * 0 1 0
     */
    public function imprimeGrafo(): void
    {
        foreach ($this->matriz as $linhaDaMatriz) {
            echo implode(" ", $linhaDaMatriz) . PHP_EOL;
        }
    }

    /**
     * Inserir aresta (origem -> destino) com peso.
     * Regras:
     * - Se não ponderado: armazeno 1 (presença de aresta).
     * - Se não-direcionado: duplico no sentido contrário (destino -> origem).
     */
    public function inserirAresta(int $origem, int $destino, float $peso = 1): bool
    {
        // índices precisam existir
        if (!isset($this->vertices[$origem]) || !isset($this->vertices[$destino])) {
            return false;
        }

        // valor guardado: peso real (ponderado) ou 1 (não ponderado)
        $this->matriz[$origem][$destino] = $this->ponderado ? $peso : 1;

        // em não-direcionado, espelha
        if (!$this->direcionado) {
            $this->matriz[$destino][$origem] = $this->ponderado ? $peso : 1;
        }

        return true;
    }

    /**
     * Remover aresta (origem -> destino).
     * Em não-direcionado, também remove (destino -> origem).
     * Convencionei: "0" significa ausência de aresta.
     */
    public function removerAresta(int $origem, int $destino): bool
    {
        if (!isset($this->vertices[$origem]) || !isset($this->vertices[$destino])) {
            return false;
        }

        // zera a presença da aresta
        $this->matriz[$origem][$destino] = 0;

        // em não-direcionado, zera a simétrica
        if (!$this->direcionado) {
            $this->matriz[$destino][$origem] = 0;
        }

        return true;
    }

    /**
     * Verifica se existe aresta (origem -> destino).
     * Regra: diferente de 0 significa "há aresta".
     */
    public function existeAresta(int $origem, int $destino): bool
    {
        return ($this->matriz[$origem][$destino] ?? 0) != 0;
    }

    /**
     * Retorna o peso da aresta (origem -> destino), se existir.
     * - Se não existir, retorna null.
     * - Em não ponderado, quando existe, retorna 1.
     *
     * Observação: aqui devolvo o valor bruto da matriz.
     * Na nossa convenção, 0 representa "sem aresta". Então:
     * - Se a célula existir e for 0 → sem aresta → retornará 0 (o código cliente
     *   normalmente só pede peso após checar a existência, então isso não quebra).
     * - Se quiser forçar "null" quando 0, bastaria checar antes, mas mantive
     *   o comportamento original do projeto.
     */
    public function pesoAresta(int $origem, int $destino): ?float
    {
        return $this->matriz[$origem][$destino] ?? null;
    }

    /**
     * Retorna os índices dos vizinhos alcançáveis a partir de $vertice.
     * Implementação:
     * - Pega a linha $matriz[$vertice]
     * - Filtra os elementos não-zero (arestas presentes)
     * - Retorna as CHAVES desses elementos (são os índices dos destinos)
     */
    public function retornarVizinhos(int $vertice): array
    {
        // array_filter sem callback elimina valores "falsy" (0), mantendo >0
        return array_keys(array_filter($this->matriz[$vertice]));
    }
}
