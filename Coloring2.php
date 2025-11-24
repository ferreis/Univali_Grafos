<?php
class Coloring
{
    /**
     * Helper para medir o tempo de execução de uma função.
     */
    private static function time_execution(callable $callback): array
    {
        $start_time = microtime(true);
        $result = $callback();
        $end_time = microtime(true);

        $result['time'] = ($end_time - $start_time) * 1000;
        return $result;
    }

    /**
     * Verifica se uma dada coloração é válida para um grafo.
     * Uma coloração é válida se nenhum par de vértices adjacentes tem a mesma cor.
     */
    private static function is_valid_coloring(Grafo $g, array $colors, int $num_vertices): bool
    {
        for ($u = 0; $u < $num_vertices; $u++) {
            foreach ($g->retornarVizinhos($u) as $v) {
                if ($colors[$u] === $colors[$v]) {
                    return false;
                }
            }
        }
        return true;
    }

    // ==================================================================
    // HEURÍSTICA TESTE: GULOSA COM ORDEM ARBITRÁRIA
    // ==================================================================
    public static function greedy_arbitrary(Grafo $g): array
    {
        return self::time_execution(function () use ($g) {
            $num_vertices = count(_get_prop($g, 'vertices'));
            $colors = array_fill(0, $num_vertices, -1);
            $num_colors = 0;

            for ($u = 0; $u < $num_vertices; $u++) {
                $neighbor_colors = [];
                foreach ($g->retornarVizinhos($u) as $v) {
                    if ($colors[$v] !== -1) {
                        $neighbor_colors[$colors[$v]] = true;
                    }
                }

                $color = 0;
                while (isset($neighbor_colors[$color])) {
                    $color++;
                }
                $colors[$u] = $color;
                $num_colors = max($num_colors, $color + 1);
            }

            return [
                'num_colors' => $num_colors,
                'mapping' => $colors
            ];
        });
    }

    // ==================================================================
    // HEURÍSTICA 1: WELSH-POWELL (ORDEM DE GRAU DECRESCENTE)
    // ==================================================================
    public static function welsh_powell(Grafo $g): array
    {
        return self::time_execution(function () use ($g) {
            $num_vertices = count(_get_prop($g, 'vertices'));
            $degrees = [];
            for ($i = 0; $i < $num_vertices; $i++) {
                $degrees[$i] = count($g->retornarVizinhos($i));
            }
            arsort($degrees);

            $sorted_vertices = array_keys($degrees);
            $colors = array_fill(0, $num_vertices, -1);
            $num_colors = 0;

            foreach ($sorted_vertices as $u) {
                $neighbor_colors = [];
                foreach ($g->retornarVizinhos($u) as $v) {
                    if ($colors[$v] !== -1) {
                        $neighbor_colors[$colors[$v]] = true;
                    }
                }

                $color = 0;
                while (isset($neighbor_colors[$color])) {
                    $color++;
                }
                $colors[$u] = $color;
                $num_colors = max($num_colors, $color + 1);
            }

            // Reordena o mapping para a ordem original dos vértices
            ksort($colors);

            return [
                'num_colors' => $num_colors,
                'mapping' => $colors
            ];
        });
    }

    // ==================================================================
    // HEURÍSTICA 2: DSATUR (Implementação Correta)
    // ==================================================================
    public static function dsatur(Grafo $g): array
    {
        return self::time_execution(function () use ($g) {
            $num_vertices = count(_get_prop($g, 'vertices'));
            if ($num_vertices === 0) {
                return ['num_colors' => 0, 'mapping' => []];
            }

            $colors = array_fill(0, $num_vertices, -1);
            $degrees = [];
            $saturation = array_fill(0, $num_vertices, 0);
            $uncolored_vertices_map = array_flip(range(0, $num_vertices - 1));

            // Pré-calcula os graus de todos os vértices
            for ($i = 0; $i < $num_vertices; $i++) {
                $degrees[$i] = count($g->retornarVizinhos($i));
            }

            $num_colors = 0;

            for ($i = 0; $i < $num_vertices; $i++) {
                $best_vertex = -1;
                $max_sat = -1;
                $max_deg = -1;

                // 1. Encontra o próximo vértice a ser colorido
                // Procura o vértice não colorido com maior saturação. Em caso de empate, o de maior grau.
                foreach ($uncolored_vertices_map as $u => $_) {
                    $current_sat = $saturation[$u];
                    $current_deg = $degrees[$u];

                    if ($current_sat > $max_sat) {
                        $max_sat = $current_sat;
                        $max_deg = $current_deg; // ATUALIZA O GRAU JUNTAMENTE
                        $best_vertex = $u;
                    } elseif ($current_sat === $max_sat) {
                        if ($current_deg > $max_deg) {
                            $max_deg = $current_deg;
                            $best_vertex = $u;
                        }
                    }
                }

                // Se best_vertex não foi escolhido (primeira iteração ou grafo vazio),
                // pegamos o primeiro da lista de não coloridos.
                if ($best_vertex === -1) {
                    $best_vertex = key($uncolored_vertices_map);
                }


                // 2. Colore o vértice escolhido com a menor cor possível
                $used_neighbor_colors = [];
                foreach ($g->retornarVizinhos($best_vertex) as $v) {
                    if ($colors[$v] !== -1) {
                        $used_neighbor_colors[$colors[$v]] = true;
                    }
                }

                $color = 0;
                while (isset($used_neighbor_colors[$color])) {
                    $color++;
                }

                $colors[$best_vertex] = $color;
                $num_colors = max($num_colors, $color + 1);
                unset($uncolored_vertices_map[$best_vertex]);

                // 3. ATUALIZA A SATURAÇÃO DOS VIZINHOS (A PARTE CRUCIAL)
                // Para cada vizinho não colorido do vértice que acabamos de pintar...
                foreach ($g->retornarVizinhos($best_vertex) as $neighbor) {
                    if (isset($uncolored_vertices_map[$neighbor])) {
                        // ...verifica se a cor que usamos é nova para ele.
                        $is_new_color_for_neighbor = true;

                        foreach ($g->retornarVizinhos($neighbor) as $neighbor_of_neighbor) {

                            // ================== A CORREÇÃO ESTÁ AQUI ==================
                            // Precisamos ignorar o $best_vertex, senão a cor
                            // nunca será considerada "nova".
                            if ($neighbor_of_neighbor !== $best_vertex && $colors[$neighbor_of_neighbor] === $color) {
                                // ==========================================================
                                $is_new_color_for_neighbor = false;
                                break;
                            }
                        }

                        // Se for uma cor nova na vizinhança dele, incrementamos sua saturação.
                        if ($is_new_color_for_neighbor) {
                            $saturation[$neighbor]++;
                        }
                    }
                }
            }

            return [
                'num_colors' => $num_colors,
                'mapping' => $colors
            ];
        });
    }


    // ==================================================================
    // FORÇA BRUTA
    // ==================================================================
    public static function brute_force(Grafo $g): array
    {
        return self::time_execution(function () use ($g) {
            $num_vertices = count(_get_prop($g, 'vertices'));
            if ($num_vertices === 0) return ['num_colors' => 0, 'mapping' => []];
            if ($num_vertices > 10) { // Limite de segurança
                throw new Exception("Força bruta não é viável para grafos com mais de 10 vértices.");
            }

            for ($k = 1; $k <= $num_vertices; $k++) {
                $colors = array_fill(0, $num_vertices, 0);
                if (self::solve_brute_force($g, $k, $colors, 0, $num_vertices)) {
                    return [
                        'num_colors' => $k,
                        'mapping' => $colors
                    ];
                }
            }
            return ['num_colors' => $num_vertices, 'mapping' => range(0, $num_vertices - 1)]; // Fallback
        });
    }

    private static function solve_brute_force(Grafo $g, int $k, array &$colors, int $vertex_idx, int $num_vertices): bool
    {
        if ($vertex_idx === $num_vertices) {
            return self::is_valid_coloring($g, $colors, $num_vertices);
        }

        for ($c = 0; $c < $k; $c++) {
            $colors[$vertex_idx] = $c;

            // Verificação parcial: a cor é válida para os vizinhos já coloridos?
            $is_safe = true;
            foreach ($g->retornarVizinhos($vertex_idx) as $v) {
                if ($v < $vertex_idx && $colors[$v] === $c) {
                    $is_safe = false;
                    break;
                }
            }

            if ($is_safe && self::solve_brute_force($g, $k, $colors, $vertex_idx + 1, $num_vertices)) {
                return true;
            }
        }
        return false;
    }
}
