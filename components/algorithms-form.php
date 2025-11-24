<div class="section">
    <div class="section-title">Algoritmos</div>
    <!-- Coloração / Heurísticas -->
    <div class="subsection">
        <div class="section-title" style="font-size:14px">Coloração / Heurísticas</div>
        <div class="btn-row">
            <button class="btn secondary row-3" onclick="runColoring('arbitrary')">ARBITRÁRIA</button>
            <button class="btn secondary row-3" onclick="runColoring('welsh_powell')">Welsh-Powell</button>
            <button class="btn secondary row-3" onclick="runColoring('dsatur')">DSATUR</button>
            <button class="btn secondary row-3" onclick="runColoring('brute_force')">Força Bruta (<=10 V)</button>
        </div>
        <label class="form-label" style="margin-top:8px">Resultado da Coloração</label>
        <pre id="coloringOut" class="textarea" style="height:auto; min-height:110px; white-space:pre-wrap; background:#f9fafb;"></pre>
    </div>

    <!-- Busca (BFS / DFS / Dijkstra / Prim / Kruskal) -->
    <div class="subsection" style="margin-top:10px">
        <div class="section-title" style="font-size:14px">Busca</div>
        <label class="form-label">Vértice inicial (Usado por BFS, DFS, Dijkstra)</label>
        <select id="selAlgoStart" class="select"></select>

        <div class="btn-row" style="margin-top:6px">
            <button class="btn secondary" onclick="runBFS()">BFS</button>
            <button class="btn secondary" onclick="runDFS()">DFS</button>
            <button class="btn" onclick="runDijkstra()">Dijkstra</button>
            <button class="btn" onclick="runPrim()">Prim</button>
            <button class="btn" onclick="runKruskal()">Kruskal</button>
        </div>
    </div>

    <!-- Fluxo -->
    <div class="subsection" style="margin-top:10px">
        <div class="section-title" style="font-size:14px">Fluxo Máximo</div>
        <label class="form-label" style="margin-top:4px">Fonte / Sorvedouro</label>
        <div style="display:flex;gap:6px;align-items:center">
            <select id="selOrigem" class="select" style="flex:1"></select>
            <select id="selDestino" class="select" style="flex:1"></select>
        </div>
        <div class="btn-row" style="margin-top:6px">
            <button class="btn" onclick="runMaxFlow()">Ford-Fulkerson</button>
            <button class="btn" onclick="runOptimizeFlow()">Otimizar Fluxo</button>
        </div>
    </div>

    <label class="form-label" style="margin-top:8px">Resultado</label>
    <pre id="algoOut" class="textarea" style="height:auto; min-height:110px; white-space:pre-wrap"></pre>
</div>