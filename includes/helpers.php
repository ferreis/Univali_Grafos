<?php

/**
 * Acessa uma propriedade privada/protegida de um objeto.
 */
function _get_prop($obj, $prop)
{
    $rp = new ReflectionProperty('Grafo', $prop);
    $rp->setAccessible(true);
    return $rp->getValue($obj);
}

/**
 * Define o valor de uma propriedade privada/protegida.
 */
function _set_prop($obj, $prop, $val)
{
    $rp = new ReflectionProperty('Grafo', $prop);
    $rp->setAccessible(true);
    $rp->setValue($obj, $val);
}

/**
 * Retorna os rótulos dos vértices.
 */
function grafo_labels(Grafo $g): array
{
    return _get_prop($g, 'vertices');
}

/**
 * Retorna as flags (direcionado, ponderado).
 */
function grafo_flags(Grafo $g): array
{
    return [
        'direcionado' => (bool)_get_prop($g, 'direcionado'),
        'ponderado'   => (bool)_get_prop($g, 'ponderado')
    ];
}

/**
 * Constrói os arrays (nodes, edges) para o frontend.
 */
function build_graph_arrays(Grafo $g): array
{
    $labels = grafo_labels($g);
    $flags = grafo_flags($g);
    $n = count($labels);
    $nodes = [];
    $edges = [];

    for ($i = 0; $i < $n; $i++) {
        $nodes[] = [
            'id'    => $i,
            'label' => $labels[$i],
            'color' => ($i == 0 ? '#98f59a' : ($i == $n - 1 ? '#ffa39e' : '#b7d4ea'))
        ];
    }

    for ($i = 0; $i < $n; $i++) {
        foreach ($g->retornarVizinhos($i) as $v) {
            if ($flags['direcionado'] || $i < $v) {
                $peso = $g->pesoAresta($i, $v);
                $edges[] = [
                    'from'  => $i,
                    'to'    => $v,
                    'label' => ($flags['ponderado'] && $peso !== null) ? (string)$peso : ''
                ];
            }
        }
    }

    return ['nodes' => $nodes, 'edges' => $edges, 'flags' => $flags];
}

/**
 * Recria um grafo a partir de dados estruturados.
 */
function rebuild_graph(array $labels, array $edges, bool $direc, bool $pond): GrafoLista
{
    $ng = new GrafoLista($direc, $pond);
    foreach ($labels as $lb) {
        $ng->inserirVertice($lb);
    }
    foreach ($edges as $e) {
        $w = $pond ? ((isset($e['label']) && $e['label'] !== '') ? (float)$e['label'] : 1.0) : 1.0;
        $ng->inserirAresta((int)$e['from'], (int)$e['to'], $w);
    }
    return $ng;
}
