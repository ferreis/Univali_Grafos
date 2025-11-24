<?php


class Planarity
{
    /**
     * Verifica se um grafo pode ser planar usando a condição E <= 3V - 6.
     */
    public static function check(Grafo $g): array
    {
        $vertices = _get_prop($g, 'vertices');
        $num_vertices = count($vertices);

        if ($num_vertices < 3) {
            return [
                'is_planar' => true,
                'message' => "É PLANAR (Prova definitiva).\n\n" .
                    "Grafos com menos de 3 vértices são sempre planares."
            ];
        }

        // Calcula o número de arestas (E)
        $num_edges = 0;
        $edges_counted = [];
        for ($u = 0; $u < $num_vertices; $u++) {
            foreach ($g->retornarVizinhos($u) as $v) {
                $key1 = "{$u}-{$v}";
                $key2 = "{$v}-{$u}";
                if (!isset($edges_counted[$key1]) && !isset($edges_counted[$key2])) {
                    $num_edges++;
                    $edges_counted[$key1] = true;
                }
            }
        }

        $limit = 3 * $num_vertices - 6;

        if ($num_edges > $limit) {
            return [
                'is_planar' => false,
                'message' => "NÃO É PLANAR (Prova definitiva).\n\n" .
                    "O grafo tem {$num_vertices} vértices e {$num_edges} arestas.\n" .
                    "Ele viola a regra de que um grafo planar simples não pode ter mais que 3V - 6 arestas.\n" .
                    "Limite para este grafo: {$limit} arestas. Impossível desenhar sem cruzamentos."
            ];
        }

        return [
            'is_planar' => null, // Teste inconclusivo
            'message' => "PODE SER PLANAR (Teste inconclusivo).\n\n" .
                "O grafo com {$num_vertices} vértices e {$num_edges} arestas passou na verificação inicial (E <= 3V - 6).\n" .
                "Isso é um forte indicativo, mas não uma garantia matemática. O sistema continuará com a reorganização e coloração."
        ];
    }
}
