<div class="section">
    <div class="section-title">Grafo Aleatório</div>
    <label class="form-label">Quantidade de vértices (N)</label>
    <input id="rndN" class="input" type="number" value="10" min="1" step="1">
    <label class="form-label">Probabilidade de aresta (0–1)</label>
    <input id="rndProb" class="input" type="number" value="0.3" step="0.05" min="0" max="1">
    <label class="form-label">Peso mínimo</label>
    <input id="rndMinW" class="input" type="number" value="1" step="0.1">
    <label class="form-label">Peso máximo</label>
    <input id="rndMaxW" class="input" type="number" value="10" step="0.1">
    <div class="btn-row">
        <button class="btn" onclick="randomGraph()">Gerar Grafo Aleatório</button>
    </div>
</div>