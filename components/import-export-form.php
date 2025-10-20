<div class="section">
    <div class="section-title">Importar / Exportar</div>
    <div class="btn-row">
        <button class="btn secondary" onclick="exportGraph()">Exportar JSON</button>
        <button class="btn" onclick="downloadSVG()">Baixar SVG</button>
    </div>

    <label class="form-label">Cole aqui o JSON para importar</label>
    <textarea id="txtImport" class="textarea"></textarea>
    <div class="btn-row">
        <button class="btn" onclick="importGraph()">Importar JSON</button>
    </div>

    <label class="form-label">Importar arquivo TXT (formato V A D P)</label>

    <input type="file" id="fileImportTXT" accept=".txt" style="display: none;" />

    <div class="btn-row">
        <button class="btn" onclick="triggerFileImport()">Importar de Arquivo TXT</button>
    </div>
</div>