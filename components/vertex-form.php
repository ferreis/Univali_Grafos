<div class="section">
    <div class="section-title">Vértice</div>
    <label class="form-label">Label</label>
    <input id="inVertice" class="input" type="text" placeholder="Nome do novo vértice">
    <div class="btn-row">
        <button id="btnAddVertex" class="btn" onclick="addVertex()">+ Vértice</button>
    </div>
</div>

<div class="section">
    <div class="section-title">Remover Vértice</div>
    <label class="form-label">Vértice</label>
    <select id="selRemover" class="select"></select>
    <div class="btn-row">
        <button id="btnDelVertex" class="btn danger" onclick="removeVertex()">- Vértice</button>
    </div>
</div>

<div class="section">
    <div class="section-title">Renomear</div>
    <label class="form-label">Vértice</label>
    <select id="selRenIdx" class="select"></select>
    <label class="form-label">Novo label</label>
    <input id="inRenLabel" class="input" type="text" placeholder="Novo nome">
    <div class="btn-row">
        <button id="btnRename" class="btn secondary" onclick="renameVertex()">Renomear</button>
    </div>
</div>