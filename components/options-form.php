<div class="section">
    <div class="section-title">Opções do grafo</div>
    <label class="form-label">Direcionado</label>
    <input type="checkbox" id="chkDir">
    <label class="form-label">Ponderado</label>
    <input type="checkbox" id="chkPond">
    <div class="btn-row">
        <button class="btn" onclick="applyFlags()">Aplicar</button>
        <button class="btn secondary" onclick="resetGraph()">Reset</button>
    </div>
    <div>
        <input type="checkbox" id="checkbox-desativar-visualizacao" name="desativar_visualizacao">
        <label for="checkbox-desativar-visualizacao">Desativar visualização do grafo (para arquivos grandes)</label>
    </div>
</div>