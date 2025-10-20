<?php

/**
 * Implementação de grafo usando LISTA DE ADJACÊNCIA.
 * - Mantém, para cada vértice (índice), um array de arestas de saída.
 * - Cada aresta é um array com:
 *      [
 *          'destino' => (int) índice do vizinho,
 *          'peso'    => (float) peso da aresta (1.0 se não ponderado)
 *      ]
 *
 * Observações:
 * - Respeita os flags herdados de Grafo: $direcionado e $ponderado.
 * - Nos métodos, uso nomes de variáveis descritivos para ficar claro o que é cada coisa.
 */

require_once "Grafo.php";

class GrafoLista extends Grafo
{
    /**
     * @var array<int, array<int, array{destino:int, peso:float}>> 
     * Lista de adjacência:
     * - Índice do array = índice do vértice de origem.
     * - Valor = lista de arestas (cada uma com destino e peso).
     */
    private array $lista;

    /**
     * Construtor padrão: define flags e inicia estruturas vazias.
     */
    public function __construct(bool $direcionado = false, bool $ponderado = false)
    {
        parent::__construct($direcionado, $ponderado);
        $this->lista = [];
    }

    /**
     * Insere um novo vértice com o rótulo informado.
     * - Adiciona o rótulo em $this->vertices.
     * - Cria uma lista de adjacência vazia para ele.
     */
    public function inserirVertice(string $label): bool
    {
        $this->vertices[] = $label;
        $this->lista[] = []; // lista de arestas de saída vazia para o novo vértice
        return true;
    }

    /**
     * Remove o vértice pelo índice e limpa todas as arestas incidentes.
     * Passos:
     * 1) Remove o rótulo do vértice e reindexa $this->vertices.
     * 2) Remove a lista de adjacência do vértice e reindexa $this->lista.
     * 3) Em todas as listas restantes, remove qualquer aresta que aponte para o índice removido.
     */
    public function removerVertice(int $indice): bool
    {
        if (!isset($this->vertices[$indice])) {
            return false; // índice inválido
        }

        // 1) Remove o rótulo do vértice e compacta
        unset($this->vertices[$indice]);
        $this->vertices = array_values($this->vertices);

        // 2) Remove a lista de adjacência do vértice e compacta
        unset($this->lista[$indice]);
        $this->lista = array_values($this->lista);

        // 3) Remove arestas que apontavam para o vértice removido
        foreach ($this->lista as &$listaDeArestasPorOrigem) {
            foreach ($listaDeArestasPorOrigem as $indiceAresta => $aresta) {
                if ($aresta['destino'] == $indice) {
                    unset($listaDeArestasPorOrigem[$indiceAresta]);
                }
            }
            // Reindexa a lista de arestas desse vértice
            $listaDeArestasPorOrigem = array_values($listaDeArestasPorOrigem);
        }
        unset($listaDeArestasPorOrigem); // boa prática ao usar referência (&)

        return true;
    }

    /**
     * Retorna o rótulo do vértice pelo índice ou null se não existir.
     */
    public function labelVertice(int $indice): ?string
    {
        return $this->vertices[$indice] ?? null;
    }

    /**
     * Imprime o grafo no formato:
     *   origem -> destino1(peso1),destino2(peso2),...
     * Útil para depuração no terminal.
     */
    public function imprimeGrafo(): void
    {
        foreach ($this->lista as $indiceOrigem => $listaDeAdjacencia) {
            $arestasFormatadas = array_map(
                fn(array $aresta) => $aresta['destino'] . "(" . $aresta['peso'] . ")",
                $listaDeAdjacencia
            );
            echo $indiceOrigem . " -> " . implode(",", $arestasFormatadas) . PHP_EOL;
        }
    }

    /**
     * Insere aresta (origem -> destino) com peso.
     * Regras:
     * - Se grafo não é ponderado, força peso = 1.
     * - Se grafo NÃO é direcionado, duplica a aresta no sentido contrário.
     */
    public function inserirAresta(int $origem, int $destino, float $peso = 1): bool
    {
        // Validação básica de índices
        if (!isset($this->vertices[$origem]) || !isset($this->vertices[$destino])) {
            return false;
        }

        // Aresta de origem -> destino
        $this->lista[$origem][] = [
            "destino" => $destino,
            "peso"    => $this->ponderado ? $peso : 1.0,
        ];

        // Se não-direcionado, cria a aresta espelhada (destino -> origem)
        if (!$this->direcionado) {
            $this->lista[$destino][] = [
                "destino" => $origem,
                "peso"    => $this->ponderado ? $peso : 1.0,
            ];
        }

        return true;
    }

    /**
     * Remove aresta (origem -> destino).
     * Se grafo NÃO é direcionado, remove também (destino -> origem).
     */
    public function removerAresta(int $origem, int $destino): bool
    {
        // Remove da lista de saída de $origem todas as arestas que chegam a $destino
        $this->lista[$origem] = array_values(
            array_filter(
                $this->lista[$origem] ?? [],
                fn(array $aresta) => $aresta['destino'] != $destino
            )
        );

        // Em não-direcionado, remove também a simétrica
        if (!$this->direcionado) {
            $this->lista[$destino] = array_values(
                array_filter(
                    $this->lista[$destino] ?? [],
                    fn(array $aresta) => $aresta['destino'] != $origem
                )
            );
        }

        return true;
    }

    /**
     * Verifica existência de aresta (origem -> destino).
     * Retorna true no primeiro match, senão false.
     */
    public function existeAresta(int $origem, int $destino): bool
    {
        foreach ($this->lista[$origem] ?? [] as $aresta) {
            if ($aresta['destino'] == $destino) {
                return true;
            }
        }
        return false;
    }

    /**
     * Retorna o peso da aresta (origem -> destino) se existir; null caso contrário.
     * Em grafo não ponderado, o peso armazenado é sempre 1.0.
     */
    public function pesoAresta(int $origem, int $destino): ?float
    {
        foreach ($this->lista[$origem] ?? [] as $aresta) {
            if ($aresta['destino'] == $destino) {
                return $aresta['peso'];
            }
        }
        return null;
    }

    /**
     * Retorna apenas os índices dos vizinhos alcançáveis a partir de $vertice.
     * Ex.: [2, 5, 7] significa arestas (vertice->2), (vertice->5), (vertice->7).
     */
    public function retornarVizinhos(int $vertice): array
    {
        return array_map(
            fn(array $aresta) => $aresta['destino'],
            $this->lista[$vertice] ?? []
        );
    }
}
