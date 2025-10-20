<?php
require_once "Grafo.php";
require_once "GrafoMatriz.php";
require_once "GrafoLista.php";
require_once "includes/helpers.php";
require_once "includes/state-manager.php";
require_once "Coloring.php";
require_once "Planarity.php";


session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $in = json_decode(file_get_contents('php://input'), true);
    if (!is_array($in)) {
        $in = $_POST;
    }

    $g = get_graph();
    $out = ['ok' => true];

    try {
        switch ($in['action'] ?? '') {
            case 'check_planarity':
                $g = get_graph();
                $out['result'] = Planarity::check($g);
                break;
            case 'run_coloring':
                $algo_type = $in['type'] ?? '';
                $g = get_graph();
                $labels = grafo_labels($g);
                $result = [];

                // Garante que o grafo é não-direcionado e não-ponderado para coloração
                _set_prop($g, 'direcionado', false);
                _set_prop($g, 'ponderado', false);

                switch ($algo_type) {
                    case 'arbitrary':
                        $result = Coloring::greedy_arbitrary($g);
                        break;
                    case 'welsh_powell':
                        $result = Coloring::welsh_powell($g);
                        break;
                    case 'dsatur':
                        $result = Coloring::dsatur($g);
                        break;
                    case 'brute_force':
                        $result = Coloring::brute_force($g);
                        break;
                    default:
                        throw new Exception('Algoritmo de coloração desconhecido.');
                }

                $out['result'] = $result;
                // Se o grafo for pequeno, formata o mapeamento com labels
                if (count($labels) < 10) {
                    $out['result']['details'] = [];
                    foreach ($result['mapping'] as $vertex_idx => $color_idx) {
                        $out['result']['details'][] = [
                            'label' => $labels[$vertex_idx] ?? "V{$vertex_idx}",
                            'color' => $color_idx
                        ];
                    }
                }
                break;
            case 'bfs':
                $indiceInicial = (int)($in['start'] ?? 0);
                $ordem = $g->bfs($indiceInicial);
                $labels = grafo_labels($g);
                $out['result'] = [
                    'type'         => 'bfs',
                    'start'        => $indiceInicial,
                    'orderIndices' => $ordem,
                    'orderLabels'  => array_map(fn($i) => $labels[$i] ?? (string)$i, $ordem),
                ];
                break;

            case 'dfs':
                $indiceInicial = (int)($in['start'] ?? 0);
                $ordem = $g->dfs($indiceInicial);
                $labels = grafo_labels($g);
                $out['result'] = [
                    'type'         => 'dfs',
                    'start'        => $indiceInicial,
                    'orderIndices' => $ordem,
                    'orderLabels'  => array_map(fn($i) => $labels[$i] ?? (string)$i, $ordem),
                ];
                break;

            case 'dijkstra':
                $indiceInicial = (int)($in['start'] ?? 0);
                $info = $g->dijkstra($indiceInicial);
                $labels = grafo_labels($g);

                $resultadoFormatado = [];
                foreach ($info as $indiceDestino => $dados) {
                    $caminhoIdx = $dados['caminho'] ?? [];
                    $caminhoLabels = array_map(fn($i) => $labels[$i] ?? (string)$i, $caminhoIdx);
                    $resultadoFormatado[] = [
                        'destIndex'   => $indiceDestino,
                        'destLabel'   => $labels[$indiceDestino] ?? (string)$indiceDestino,
                        'distance'    => is_infinite($dados['distancia']) ? null : $dados['distancia'],
                        'pathIndices' => $caminhoIdx,
                        'pathLabels'  => $caminhoLabels,
                    ];
                }
                $out['result'] = [
                    'type'  => 'dijkstra',
                    'start' => $indiceInicial,
                    'items' => $resultadoFormatado,
                ];
                break;

            case 'import_txt':
                $conteudo = (string)($in['content'] ?? '');
                if (trim($conteudo) === '') throw new Exception('Conteúdo TXT vazio.');

                $arquivoTemp = tempnam(sys_get_temp_dir(), 'grafo_');
                file_put_contents($arquivoTemp, $conteudo);

                $novo = new GrafoLista(false, false);
                $novo->carregarDeArquivo($arquivoTemp);
                @unlink($arquivoTemp);

                save_graph($novo);
                $out['graph'] = build_graph_arrays($novo);
                break;

            case 'get':
                $out['graph'] = build_graph_arrays($g);
                break;

            case 'reset':
                $g = create_initial_graph();
                save_graph($g);
                $out['graph'] = build_graph_arrays($g);
                break;

            case 'add_vertex':
                $label = trim($in['label'] ?? '');
                if ($label === '') $label = 'V' . count(grafo_labels($g));
                $g->inserirVertice($label);
                save_graph($g);
                $out['graph'] = build_graph_arrays($g);
                break;

            case 'rename_vertex':
                $idx = (int)($in['index'] ?? -1);
                $label = trim($in['label'] ?? '');
                if ($idx < 0 || $label === '') throw new Exception('Parâmetros inválidos');

                $labels = grafo_labels($g);
                if (!array_key_exists($idx, $labels)) throw new Exception('Vértice inexistente');
                $labels[$idx] = $label;
                _set_prop($g, 'vertices', array_values($labels));

                save_graph($g);
                $out['graph'] = build_graph_arrays($g);
                break;

            case 'remove_vertex':
                $idx = (int)($in['index'] ?? -1);
                if ($idx < 0) throw new Exception('Índice inválido');
                $g->removerVertice($idx);
                save_graph($g);
                $out['graph'] = build_graph_arrays($g);
                break;

            case 'add_edge':
                $o = (int)($in['from'] ?? -1);
                $d = (int)($in['to'] ?? -1);
                $w = isset($in['weight']) ? (float)$in['weight'] : 1.0;
                if ($o < 0 || $d < 0) throw new Exception('Origem/Destino inválidos');
                $g->inserirAresta($o, $d, $w);
                save_graph($g);
                $out['graph'] = build_graph_arrays($g);
                break;

            case 'remove_edge':
                $o = (int)($in['from'] ?? -1);
                $d = (int)($in['to'] ?? -1);
                if ($o < 0 || $d < 0) throw new Exception('Origem/Destino inválidos');
                $g->removerAresta($o, $d);
                save_graph($g);
                $out['graph'] = build_graph_arrays($g);
                break;

            case 'set_flags':
                $newDir  = (bool)($in['direcionado'] ?? false);
                $newPond = (bool)($in['ponderado'] ?? false);
                $cur    = build_graph_arrays($g);
                $labels = array_map(fn($n) => $n['label'], $cur['nodes']);
                $edges  = $cur['edges'];

                if (!$cur['flags']['direcionado'] && $newDir) {
                    $dups = [];
                    foreach ($edges as $e) $dups[] = ['from' => $e['to'], 'to' => $e['from'], 'label' => $e['label']];
                    $edges = array_merge($edges, $dups);
                }

                $g = rebuild_graph($labels, $edges, $newDir, $newPond);
                save_graph($g);
                $out['graph'] = build_graph_arrays($g);
                break;

            case 'export':
                $out['graph'] = build_graph_arrays($g);
                break;

            case 'import':
                $payload = $in['graph'] ?? null;
                if (!$payload || !isset($payload['nodes'], $payload['edges'])) throw new Exception('JSON inválido');

                $flags  = grafo_flags($g);
                $labels = [];
                foreach ($payload['nodes'] as $n) {
                    $labels[] = $n['label'] ?? ('V' . count($labels));
                }
                $g = rebuild_graph($labels, $payload['edges'], $flags['direcionado'], $flags['ponderado']);
                save_graph($g);
                $out['graph'] = build_graph_arrays($g);
                break;

            case 'random':
                $n    = max(1, (int)($in['n'] ?? 6));
                $p    = isset($in['prob']) ? (float)str_replace(',', '.', $in['prob']) : 0.3;
                if ($p < 0) $p = 0;
                if ($p > 1) $p = 1;

                $minW = isset($in['minW']) ? (float)str_replace(',', '.', $in['minW']) : 1;
                $maxW = isset($in['maxW']) ? (float)str_replace(',', '.', $in['maxW']) : 10;
                if ($minW > $maxW) list($minW, $maxW) = [$maxW, $minW];

                $flags = grafo_flags($g);
                $ng = new GrafoLista($flags['direcionado'], $flags['ponderado']);

                for ($i = 0; $i < $n; $i++) $ng->inserirVertice("V$i");

                $weight = function () use ($flags, $minW, $maxW) {
                    if (!$flags['ponderado']) return 1.0;
                    $lo = (int)round($minW * 10);
                    $hi = (int)round($maxW * 10);
                    if ($hi < $lo) list($lo, $hi) = [$hi, $lo];
                    return mt_rand($lo, $hi) / 10.0;
                };

                if ($flags['direcionado']) {
                    for ($i = 0; $i < $n; $i++) {
                        for ($j = 0; $j < $n; $j++) {
                            if ($i !== $j && (mt_rand() / mt_getrandmax() <= $p)) {
                                $ng->inserirAresta($i, $j, $weight());
                            }
                        }
                    }
                } else {
                    for ($i = 0; $i < $n; $i++) {
                        for ($j = $i + 1; $j < $n; $j++) {
                            if (mt_rand() / mt_getrandmax() <= $p) {
                                $ng->inserirAresta($i, $j, $weight());
                            }
                        }
                    }
                }

                save_graph($ng);
                $out['graph'] = build_graph_arrays($ng);
                break;

            default:
                throw new Exception('Ação inválida');
        }
    } catch (Throwable $e) {
        $out = ['ok' => false, 'error' => $e->getMessage()];
    }

    echo json_encode($out);
    exit;
}
