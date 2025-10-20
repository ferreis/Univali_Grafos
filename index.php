<?php
// index.php
require_once "Grafo.php";
require_once "GrafoMatriz.php";
require_once "GrafoLista.php";
require_once "includes/helpers.php";
require_once "includes/state-manager.php";

session_start();

// Garante que o grafo exista na sessão e obtém o estado inicial para o frontend
$grafo_inicial = build_graph_arrays(get_graph());
?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <title>Visualização do Grafo</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <h2>Visualização do Grafo <span class="tag" id="flagInfo"></span></h2>

    <div class="wrap">
        <div class="card panel">
            <div class="hd">Controles</div>
            <div class="bd">
                <?php include 'components/options-form.php'; ?>
                <?php include 'components/algorithms-form.php'; ?>
                <?php include 'components/coloring-form.php'; ?>


                <?php include 'components/vertex-form.php'; ?>
                <?php include 'components/edge-form.php'; ?>
                <?php include 'components/random-graph-form.php'; ?>
                <?php include 'components/import-export-form.php'; ?>
                <?php include 'components/layout-form.php'; ?>
            </div>
        </div>

        <div class="card">
            <div class="bd">
                <svg id="stage" width="100%" viewBox="0 0 1200 650">
                    <defs>
                        <marker id="arrow" markerWidth="10" markerHeight="10" refX="10" refY="5" orient="auto" markerUnits="strokeWidth">
                            <path d="M0,0 L10,5 L0,10 z" fill="#555"></path>
                        </marker>
                    </defs>
                    <g id="viewport"></g>
                </svg>
            </div>
        </div>
    </div>

    <?php include 'components/modal.php'; ?>

    <script>
        // Passa os dados iniciais do PHP para o JavaScript
        window.initialGraphData = <?php echo json_encode($grafo_inicial); ?>;
    </script>
    <script src="js/main.js"></script>
</body>

</html>