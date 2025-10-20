<div class="section">
    <div class="section-title">Aresta</div>
    <label class="form-label">Origem</label>
    <select id="selOrigem" class="select"></select>
    <label class="form-label">Destino</label>
    <select id="selDestino" class="select"></select>
    <label class="form-label">Peso</label>
    <input id="inPeso" class="input" type="number" step="0.1" value="1">
    <div class="btn-row">
        <button id="btnAddEdge" class="btn" onclick="addEdge()">+ Aresta</button>
        <button id="btnDelEdge" class="btn danger" onclick="removeEdge()">- Aresta</button>
    </div>
</div>