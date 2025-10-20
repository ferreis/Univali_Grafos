<?php

/**
 * Cria o grafo de exemplo inicial.
 */
function create_initial_graph(): GrafoLista
{
    $list = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L'];
    $g = new GrafoLista(false, true); // não-direcionado, ponderado
    foreach ($list as $label) $g->inserirVertice($label);

    $g->inserirAresta(0, 1, 1);
    $g->inserirAresta(0, 2, 1);
    $g->inserirAresta(1, 3, 1);
    $g->inserirAresta(2, 4, 1);
    $g->inserirAresta(4, 5, 1);
    $g->inserirAresta(3, 4, 1);
    $g->inserirAresta(5, 0, 1);
    $g->inserirAresta(5, 6, 1);
    $g->inserirAresta(7, 8, 1);
    $g->inserirAresta(8, 9, 1);
    $g->inserirAresta(9, 10, 1);
    $g->inserirAresta(10, 11, 1);

    return $g;
}

/**
 * Obtém o grafo da sessão ou cria um novo.
 */
function get_graph(): Grafo
{
    if (!isset($_SESSION['grafo']) || !($_SESSION['grafo'] instanceof Grafo)) {
        $_SESSION['grafo'] = create_initial_graph();
    }
    return $_SESSION['grafo'];
}

/**
 * Salva o grafo na sessão.
 */
function save_graph(Grafo $g): void
{
    $_SESSION['grafo'] = $g;
}

/**
 * Limpa a sessão do grafo.
 */
if (isset($_GET['clear'])) {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
    }
    session_destroy();
    header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}
