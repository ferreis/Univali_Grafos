<div class="section">
    <div class="section-title">Opções do grafo</div>
    <label class="form-label">Direcionado</label>
    <input type="checkbox" id="chkDir">
    <label class="form-label">Ponderado</label>
    <input type="checkbox" id="chkPond">
    <div style="padding: 5px 10px; background: #f0f0f0; border-radius: 4px;">
        <input type="checkbox" id="chkVisualizacao" checked>
        <label for="chkVisualizacao">Habilitar visualização do grafo</label>
        <span id="aviso-visualizacao" style="color: #c00; font-size: 0.9em; margin-left: 10px;"></span>
    </div>
    <div class="btn-row">
        <button class="btn" onclick="applyFlags()">Aplicar</button>
        <button class="btn secondary" onclick="resetGraph()">Reset</button>
    </div>
</div>