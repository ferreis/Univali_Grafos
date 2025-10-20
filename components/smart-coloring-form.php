<div class="section">
    <div class="section-title">Coloração Planar Inteligente</div>
    <p style="font-size: 12px; color: #6b7280; margin: 0 0 8px;">
        Esta função primeiro verifica se o grafo pode ser planar. Se puder, reorganiza o layout para minimizar cruzamentos e aplica a coloração.
    </p>
    <div class="btn-row">
        <button class="btn" onclick="runSmartColoring()">Analisar, Reorganizar e Colorir</button>
    </div>

    <label class="form-label" style="margin-top:8px">Resultado da Análise</label>
    <pre id="smartColoringOut" class="textarea" style="height:auto; min-height:160px; background:#f9fafb;"></pre>
</div>