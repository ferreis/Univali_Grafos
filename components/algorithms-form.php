<div class="section">
    <div class="section-title">Algoritmos</div>
    <label class="form-label">Vértice inicial (Usado por BFS, DFS, Dijkstra)</label>
    <select id="selAlgoStart" class="select"></select>

    <div class="btn-row">
        <button class="btn secondary" onclick="runBFS()">BFS</button>
        <button class="btn secondary" onclick="runDFS()">DFS</button>
        <button class="btn" onclick="runDijkstra()">Dijkstra</button>
        <button class="btn" onclick="runPrim()">Prim</button>
        <button class="btn" onclick="runKruskal()">Kruskal</button>
    </div>

    <label class="form-label" style="margin-top:8px">Resultado</label>
    <pre id="algoOut" class="textarea" style="height:auto; min-height:110px; white-space:pre-wrap"></pre>
</div>