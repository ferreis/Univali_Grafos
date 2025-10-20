<div class="section">
    <div class="section-title">Coloração de Grafos</div>
    <div class="btn-row">
        <button class="btn secondary row-3" onclick="runColoring('arbitrary')">ARBITRÁRIA</button>
        <button class="btn secondary row-3" onclick="runColoring('welsh_powell')">Welsh-Powell</button>
        <button class="btn secondary row-3" onclick="runColoring('dsatur')">DSATUR</button>
        <button class="btn secondary row-3" onclick="runColoring('brute_force')">Força Bruta (<=10 V)</button>
    </div>

    <label class="form-label" style="margin-top:8px">Resultado da Coloração</label>
    <pre id="coloringOut" class="textarea" style="height:auto; min-height:110px; white-space:pre-wrap; background:#f9fafb;"></pre>
</div>