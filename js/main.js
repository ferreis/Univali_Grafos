// js/main.js
document.addEventListener("DOMContentLoaded", () => {
  /* ===================== Destaque de caminhos ===================== */
  function clearHighlight() {
    state.highlight.nodes.clear();
    state.highlight.edges.clear();
    draw();
  }

  function setHighlightFromEdges(edgeList) {
    clearHighlight();
    if (!Array.isArray(edgeList) || edgeList.length === 0) {
      draw();
      return;
    }

    edgeList.forEach((aresta) => {
      const a = Number(aresta.de);
      const b = Number(aresta.para);
      state.highlight.nodes.add(a);
      state.highlight.nodes.add(b);
      state.highlight.edges.add(`${a}-${b}`);
      state.highlight.edges.add(`${b}-${a}`);
    });
    draw();
  }

  function setHighlightFromPath(pathIndices) {
    clearHighlight();
    if (!Array.isArray(pathIndices) || pathIndices.length === 0) {
      draw();
      return;
    }
    pathIndices.forEach((i) => state.highlight.nodes.add(Number(i)));
    for (let i = 0; i < pathIndices.length - 1; i++) {
      const a = Number(pathIndices[i]),
        b = Number(pathIndices[i + 1]);
      state.highlight.edges.add(`${a}-${b}`);
      state.highlight.edges.add(`${b}-${a}`);
    }
    draw();
  }

  function isEdgeHighlighted(a, b) {
    return (
      state.highlight.edges.has(`${a}-${b}`) ||
      state.highlight.edges.has(`${b}-${a}`)
    );
  }

  /* ===================== Modal ===================== */
  window.openDialog = function (title, htmlBody) {
    document.getElementById("dlgTitle").textContent = title;
    document.getElementById("dlgBody").innerHTML = htmlBody;
    document.getElementById("dlg").classList.add("show");
  };

  window.closeDialog = function () {
    document.getElementById("dlg").classList.remove("show");
  };

  /* ===================== Estado global do front ===================== */
  const R = 20; // raio
  const COLOR_PALETTE = [
    "#e6194B",
    "#3cb44b",
    "#ffe119",
    "#4363d8",
    "#f58231",
    "#911eb4",
    "#46f0f0",
    "#f032e6",
    "#bcf60c",
    "#fabebe",
    "#008080",
    "#e6beff",
    "#9A6324",
    "#fffac8",
    "#800000",
    "#aaffc3",
    "#808000",
    "#ffd8b1",
    "#000075",
    "#808080",
  ];

  const state = {
    nodes: [],
    edges: [],
    flags: window.initialGraphData.flags,
    transform: { x: 0, y: 0, k: 1 },
    highlight: { nodes: new Set(), edges: new Set() },
    coloring: {}, // <-- ADICIONE ESTA LINHA (mapeamento: {verticeId: colorId})
  };

  const boot = window.initialGraphData;
  const svg = document.getElementById("stage"),
    vp = document.getElementById("viewport");

  function applyTransform() {
    vp.setAttribute(
      "transform",
      `translate(${state.transform.x},${state.transform.y}) scale(${state.transform.k})`
    );
  }

  function clientToWorld(evt) {
    const r = svg.getBoundingClientRect();
    const x = (evt.clientX - r.left - state.transform.x) / state.transform.k;
    const y = (evt.clientY - r.top - state.transform.y) / state.transform.k;
    return { x, y };
  }

  /* ===================== UI de flags ===================== */
  function setFlagInfo() {
    document.getElementById("flagInfo").textContent = `dir=${
      state.flags.direcionado ? "on" : "off"
    } · pond=${state.flags.ponderado ? "on" : "off"}`;
    document.getElementById("chkDir").checked = !!state.flags.direcionado;
    document.getElementById("chkPond").checked = !!state.flags.ponderado;
  }

  /* ===================== Desenho (SVG) ===================== */
  function ensurePositions() {
    if (state.nodes.every((n) => n.x !== undefined)) return;
    layoutCirc();
  }

  function draw() {
    ensurePositions();
    vp.innerHTML = "";

    state.edges.forEach((e) => {
      const a = state.nodes.find((n) => n.id === e.from),
        b = state.nodes.find((n) => n.id === e.to);
      if (!a || !b) return;

      const dx = b.x - a.x,
        dy = b.y - a.y;
      const len = Math.hypot(dx, dy) || 1;
      const ux = dx / len,
        uy = dy / len;
      const x1 = a.x + ux * R,
        y1 = a.y + uy * R;
      const x2 = b.x - ux * R,
        y2 = b.y - uy * R;

      const line = document.createElementNS(
        "http://www.w3.org/2000/svg",
        "line"
      );
      line.setAttribute("x1", x1);
      line.setAttribute("y1", y1);
      line.setAttribute("x2", x2);
      line.setAttribute("y2", y2);
      line.setAttribute("class", "edge");
      if (isEdgeHighlighted(a.id, b.id)) line.classList.add("hl");
      if (state.flags.direcionado)
        line.setAttribute("marker-end", "url(#arrow)");
      vp.appendChild(line);

      if (e.label) {
        const tx = (x1 + x2) / 2,
          ty = (y1 + y2) / 2 - 6;
        const tShadow = document.createElementNS(
          "http://www.w3.org/2000/svg",
          "text"
        );
        tShadow.setAttribute("x", tx);
        tShadow.setAttribute("y", ty);
        tShadow.setAttribute("text-anchor", "middle");
        tShadow.setAttribute("class", "label shadow");
        tShadow.textContent = e.label;
        vp.appendChild(tShadow);

        const t = document.createElementNS(
          "http://www.w3.org/2000/svg",
          "text"
        );
        t.setAttribute("x", tx);
        t.setAttribute("y", ty);
        t.setAttribute("text-anchor", "middle");
        t.setAttribute("class", "label");
        t.textContent = e.label;
        vp.appendChild(t);
      }
    });

    state.nodes.forEach((n) => {
      const g = document.createElementNS("http://www.w3.org/2000/svg", "g");
      g.setAttribute("class", "node");
      g.dataset.id = n.id;
      const c = document.createElementNS(
        "http://www.w3.org/2000/svg",
        "circle"
      );
      c.setAttribute("cx", n.x);
      c.setAttribute("cy", n.y);
      c.setAttribute("r", R);

      if (state.coloring[n.id] !== undefined) {
        const colorIndex = state.coloring[n.id];
        c.setAttribute(
          "fill",
          COLOR_PALETTE[colorIndex % COLOR_PALETTE.length]
        );
      } else {
        c.setAttribute("fill", n.color || "#b7d4ea");
      }
      if (state.highlight.nodes.has(n.id)) c.classList.add("hl");

      const tShadow = document.createElementNS(
        "http://www.w3.org/2000/svg",
        "text"
      );
      tShadow.setAttribute("x", n.x);
      tShadow.setAttribute("y", n.y + 4);
      tShadow.setAttribute("text-anchor", "middle");
      tShadow.setAttribute("class", "label shadow");
      tShadow.textContent = n.label;

      const t = document.createElementNS("http://www.w3.org/2000/svg", "text");
      t.setAttribute("x", n.x);
      t.setAttribute("y", n.y + 4);
      t.setAttribute("text-anchor", "middle");
      t.setAttribute("class", "label");
      t.textContent = n.label;

      g.appendChild(c);
      g.appendChild(tShadow);
      g.appendChild(t);
      vp.appendChild(g);
      enableDrag(g, n.id);
    });
  }

  /* ===================== Drag / Pan / Zoom ===================== */
  let dragNode = null;
  function enableDrag(group, id) {
    group.addEventListener("mousedown", (e) => {
      dragNode = { id, offset: clientToWorld(e) };
      e.stopPropagation();
    });
  }
  svg.addEventListener("mousemove", (e) => {
    if (!dragNode) return;
    const p = clientToWorld(e);
    const n = state.nodes.find((n) => n.id === dragNode.id);
    n.x += p.x - dragNode.offset.x;
    n.y += p.y - dragNode.offset.y;
    dragNode.offset = p;
    draw();
  });
  svg.addEventListener("mouseup", () => (dragNode = null));
  svg.addEventListener("mouseleave", () => (dragNode = null));

  let panning = false,
    panStart = { x: 0, y: 0 };
  svg.addEventListener("mousedown", (e) => {
    if (e.target.closest(".node")) return;
    panning = true;
    panStart = {
      x: e.clientX - state.transform.x,
      y: e.clientY - state.transform.y,
    };
  });
  svg.addEventListener("mousemove", (e) => {
    if (!panning) return;
    state.transform.x = e.clientX - panStart.x;
    state.transform.y = e.clientY - panStart.y;
    applyTransform();
  });
  svg.addEventListener("mouseup", () => (panning = false));
  svg.addEventListener("mouseleave", () => (panning = false));

  svg.addEventListener(
    "wheel",
    (e) => {
      e.preventDefault();
      const factor = 1 + (e.deltaY > 0 ? -0.1 : 0.1);
      const p = clientToWorld(e);
      state.transform.x -= p.x * (factor - 1) * state.transform.k;
      state.transform.y -= p.y * (factor - 1) * state.transform.k;
      state.transform.k *= factor;
      applyTransform();
    },
    { passive: false }
  );

  /* ===================== UI dinâmica ===================== */
  function refreshSelects() {
    const selectIds = [
      "selOrigem",
      "selDestino",
      "selRemover",
      "selRenIdx",
      "selAlgoStart",
    ];
    const lists = selectIds
      .map((id) => document.getElementById(id))
      .filter(Boolean);
    lists.forEach((sel) => {
      sel.innerHTML = "";
      state.nodes
        .slice()
        .sort((a, b) => a.id - b.id)
        .forEach((o) => {
          const op = document.createElement("option");
          op.value = o.id;
          op.textContent = `${o.id} (${o.label})`;
          sel.appendChild(op);
        });
    });
    updateButtonStates();
  }

  function edgeExists(o, d) {
    return state.edges.some(
      (e) =>
        (e.from === o && e.to === d) ||
        (!state.flags.direcionado && e.from === d && e.to === o)
    );
  }

  function updateButtonStates() {
    const hasNodes = state.nodes.length > 0;
    document.getElementById("btnDelVertex").disabled = !hasNodes;
    const o = +document.getElementById("selOrigem").value || 0;
    const d = +document.getElementById("selDestino").value || 0;
    document.getElementById("btnAddEdge").disabled = !hasNodes || o === d;
    document.getElementById("btnDelEdge").disabled =
      !hasNodes || o === d || !edgeExists(o, d);
    const renLabel = document.getElementById("inRenLabel").value.trim();
    document.getElementById("btnRename").disabled =
      !hasNodes || renLabel === "";
    document.getElementById("btnAddVertex").disabled = false;
  }

  [
    "selOrigem",
    "selDestino",
    "selRemover",
    "selRenIdx",
    "inRenLabel",
    "inPeso",
  ].forEach((id) => {
    const el = document.getElementById(id);
    if (el) {
      el.addEventListener("input", updateButtonStates);
      el.addEventListener("change", updateButtonStates);
    }
  });

  /* ===================== API Helper ===================== */
  async function api(action, payload = {}) {
    const r = await fetch("api.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ action, ...payload }),
    });
    return r.json();
  }

  function applyGraph(g) {
    const pos = Object.fromEntries(
      state.nodes.map((n) => [n.id, { x: n.x, y: n.y }])
    );
    state.nodes = g.nodes.map((n) => ({ ...n, ...(pos[n.id] || {}) }));
    state.edges = g.edges.map((e) => ({ ...e }));
    state.flags = g.flags;
    setFlagInfo();
    refreshSelects();
    draw();
  }

  /* ===================== Ações (UI → API) ===================== */
  window.resetGraph = async function () {
    const r = await api("reset");
    if (r.ok) applyGraph(r.graph);
  };
  window.addVertex = async function () {
    const label = document.getElementById("inVertice").value.trim();
    const r = await api("add_vertex", { label });
    if (r.ok) {
      applyGraph(r.graph);
      document.getElementById("inVertice").value = "";
    }
  };
  window.renameVertex = async function () {
    const idx = +document.getElementById("selRenIdx").value;
    const label = document.getElementById("inRenLabel").value.trim();
    if (!label) return;
    const r = await api("rename_vertex", { index: idx, label });
    if (r.ok) {
      applyGraph(r.graph);
      document.getElementById("inRenLabel").value = "";
    }
  };
  window.removeVertex = async function () {
    const idx = +document.getElementById("selRemover").value;
    const r = await api("remove_vertex", { index: idx });
    if (r.ok) applyGraph(r.graph);
  };
  window.addEdge = async function () {
    const o = +document.getElementById("selOrigem").value;
    const d = +document.getElementById("selDestino").value;
    const w = parseFloat(document.getElementById("inPeso").value || "1");
    const r = await api("add_edge", { from: o, to: d, weight: w });
    if (r.ok) applyGraph(r.graph);
  };
  window.removeEdge = async function () {
    const o = +document.getElementById("selOrigem").value;
    const d = +document.getElementById("selDestino").value;
    const r = await api("remove_edge", { from: o, to: d });
    if (r.ok) applyGraph(r.graph);
  };
  window.applyFlags = async function () {
    const direc = document.getElementById("chkDir").checked;
    const pond = document.getElementById("chkPond").checked;
    const r = await api("set_flags", { direcionado: direc, ponderado: pond });
    if (r.ok) applyGraph(r.graph);
  };
  window.randomGraph = async function () {
    const toNum = (v, def = 0) => {
      if (typeof v === "string") v = v.replace(",", ".");
      const n = Number(v);
      return Number.isFinite(n) ? n : def;
    };
    let n = Math.floor(toNum(document.getElementById("rndN").value, 6));
    let prob = toNum(document.getElementById("rndProb").value, 0.3);
    let minW = toNum(document.getElementById("rndMinW").value, 1);
    let maxW = toNum(document.getElementById("rndMaxW").value, 10);
    if (n < 1) n = 1;
    if (prob < 0) prob = 0;
    if (prob > 1) prob = 1;
    if (minW > maxW) [minW, maxW] = [maxW, minW];
    const r = await api("random", { n, prob, minW, maxW });
    if (r.ok) applyGraph(r.graph);
    else alert(r.error || "Falha ao gerar grafo aleatório");
  };
  window.exportGraph = async function () {
    const r = await api("export");
    if (!r.ok) return alert(r.error || "Falha export");
    const blob = new Blob([JSON.stringify(r.graph, null, 2)], {
      type: "application/json",
    });
    const a = document.createElement("a");
    a.href = URL.createObjectURL(blob);
    a.download = "grafo.json";
    a.click();
  };
  window.importGraph = async function () {
    const txt = document.getElementById("txtImport").value.trim();
    if (!txt) return alert("Cole o JSON.");
    try {
      const j = JSON.parse(txt);
      const r = await api("import", { graph: j });
      if (r.ok) applyGraph(r.graph);
    } catch (e) {
      alert("JSON inválido.");
    }
  };
  /**
   * Aciona o clique no input de arquivo escondido.
   */
  window.triggerFileImport = function () {
    document.getElementById("fileImportTXT").click();
  };

  /**
   * Lê o arquivo TXT selecionado e envia seu conteúdo para a API.
   * Esta função é acionada pelo evento 'change' do input de arquivo.
   */
  async function handleFileImport(event) {
    const file = event.target.files[0];
    if (!file) {
      return; // Nenhum arquivo selecionado
    }

    const reader = new FileReader();

    reader.onload = async (e) => {
      const content = e.target.result;
      if (!content.trim()) {
        return alert("O arquivo TXT está vazio.");
      }

      const r = await api("import_txt", { content: content });

      if (r.ok) {
        applyGraph(r.graph);
        document.getElementById("algoOut").textContent =
          "Grafo importado com sucesso do arquivo TXT.";
      } else {
        alert(r.error || "Falha ao importar o arquivo TXT.");
      }
    };

    reader.onerror = () => {
      alert("Ocorreu um erro ao ler o arquivo.");
    };

    reader.readAsText(file);

    // Limpa o valor do input para permitir o reenvio do mesmo arquivo
    event.target.value = "";
  }

  /* ===================== Algoritmos ===================== */
  window.runBFS = async function () {
    const startNode = +document.getElementById("selAlgoStart").value || 0;
    const res = await api("bfs", { start: startNode });
    if (!res.ok) return alert(res.error || "Falha BFS");
    const { orderIndices, orderLabels } = res.result;
    const out = `BFS a partir de ${startNode}:\nÍndices: ${orderIndices.join(
      " "
    )}\nLabels: ${orderLabels.join(" -> ")}`;
    document.getElementById("algoOut").textContent = out;
  };
  window.runDFS = async function () {
    const startNode = +document.getElementById("selAlgoStart").value || 0;
    const res = await api("dfs", { start: startNode });
    if (!res.ok) return alert(res.error || "Falha DFS");
    const { orderIndices, orderLabels } = res.result;
    const out = `DFS a partir de ${startNode}:\nÍndices: ${orderIndices.join(
      " "
    )}\nLabels: ${orderLabels.join(" -> ")}`;
    document.getElementById("algoOut").textContent = out;
  };
  window.runDijkstra = async function () {
    const startNode = +document.getElementById("selAlgoStart").value || 0;
    const res = await api("dijkstra", { start: startNode });
    if (!res.ok) return alert(res.error || "Falha Dijkstra");
    const items = (res.result.items || []).slice();
    const startLabel =
      state.nodes.find((n) => n.id === res.result.start)?.label ??
      String(res.result.start);
    items.sort((a, b) =>
      String(a.destLabel).localeCompare(String(b.destLabel), "pt-BR")
    );
    let html = `Começa em ${startLabel} (índice ${res.result.start})\n`;
    html += `Tabela de DIJKSTRA:\n\n`;
    html +=
      '<table class="table-like" style="width:100%; border-collapse:collapse">';
    html +=
      '<thead><tr style="text-align:left;border-bottom:1px solid #e5e7eb"><th style="padding:6px 4px">Até (label)</th><th style="padding:6px 4px">Até (idx)</th><th style="padding:6px 4px">Distância</th><th style="padding:6px 4px">Caminho</th></tr></thead><tbody>';
    for (const item of items) {
      const dist = item.distance == null ? "INF" : item.distance;
      const pathLbl =
        item.pathLabels && item.pathLabels.length
          ? item.pathLabels.join(" → ")
          : "(sem caminho)";
      const pathIdxCsv = (item.pathIndices || []).join(",");
      html += `<tr style="border-bottom:1px solid #f3f4f6" data-path="${pathIdxCsv}" onclick="onDijkstraRowClick(this)" title="Clique para destacar">`;
      html += `<td style="padding:6px 4px">${item.destLabel}</td>`;
      html += `<td style="padding:6px 4px">${item.destIndex}</td>`;
      html += `<td style="padding:6px 4px">${dist}</td>`;
      html += `<td style="padding:6px 4px">${pathLbl}</td>`;
      html += `</tr>`;
    }
    html += "</tbody></table>";
    openDialog("Tabela de DIJKSTRA", html);
  };

  window.runPrim = async function () {
    const areaSaida = document.getElementById("algoOut");
    areaSaida.textContent = "Executando Prim...";

    const res = await api("run_prim");
    if (!res.ok) {
      areaSaida.textContent = `Erro: ${res.error || "Falha ao executar Prim"}`;
      return alert(res.error || "Falha ao executar Prim");
    }

    const { result } = res;
    let textoSaida = `--- Algoritmo de Prim ---\n\n`;
    textoSaida += `Tempo de execução: ${result.time.toFixed(4)} ms\n`;
    textoSaida += `Peso total da AGM: ${result.total_weight}\n`;
    textoSaida += `Arestas na AGM: ${result.edges.length}\n\n`;

    // Mapeia labels para as arestas (opcional, mas útil)
    const labels = Object.fromEntries(state.nodes.map((n) => [n.id, n.label]));
    textoSaida += "Arestas (de -> para | peso):\n";
    result.edges.forEach((aresta) => {
      textoSaida += `- ${labels[aresta.de]} -> ${labels[aresta.para]} | ${
        aresta.peso
      }\n`;
    });

    areaSaida.textContent = textoSaida;

    // Destaca as arestas da AGM no visualizador
    setHighlightFromEdges(result.edges);
  };

  window.runKruskal = async function () {
    const areaSaida = document.getElementById("algoOut");
    areaSaida.textContent = "Executando Kruskal...";

    const res = await api("run_kruskal");
    if (!res.ok) {
      areaSaida.textContent = `Erro: ${
        res.error || "Falha ao executar Kruskal"
      }`;
      return alert(res.error || "Falha ao executar Kruskal");
    }

    const { result } = res;
    let textoSaida = `--- Algoritmo de Kruskal ---\n\n`;
    textoSaida += `Tempo de execução: ${result.time.toFixed(4)} ms\n`;
    textoSaida += `Peso total da AGM: ${result.total_weight}\n`;
    textoSaida += `Arestas na AGM: ${result.edges.length}\n\n`;

    // Mapeia labels para as arestas (opcional, mas útil)
    const labels = Object.fromEntries(state.nodes.map((n) => [n.id, n.label]));
    textoSaida += "Arestas (de -> para | peso):\n";
    result.edges.forEach((aresta) => {
      textoSaida += `- ${labels[aresta.de]} -> ${labels[aresta.para]} | ${
        aresta.peso
      }\n`;
    });

    areaSaida.textContent = textoSaida;

    // Destaca as arestas da AGM no visualizador
    setHighlightFromEdges(result.edges);
  };
  window.onDijkstraRowClick = function (tr) {
    const pathCsv = tr.getAttribute("data-path") || "";
    const path = pathCsv
      ? pathCsv
          .split(",")
          .map((s) => Number(s.trim()))
          .filter(Number.isFinite)
      : [];
    setHighlightFromPath(path);
  };

  /* ===================== Coloração ===================== */

  /**
   * Anima a coloração do grafo, pintando um vértice de cada vez.
   * @param {Object} mapping - Mapeamento de { verticeId: colorId }
   */
  function animateColoring(mapping) {
    // Limpa a coloração anterior e reseta o estado
    state.coloring = {};
    draw(); // Redesenha o grafo com as cores padrão

    const verticesToColor = Object.keys(mapping);
    if (verticesToColor.length === 0) return;

    let i = 0;
    const intervalId = setInterval(() => {
      if (i >= verticesToColor.length) {
        clearInterval(intervalId);
        return;
      }

      const vertexId = verticesToColor[i];
      const colorId = mapping[vertexId];

      // Aplica a cor ao estado e redesenha
      state.coloring[vertexId] = colorId;
      draw();

      i++;
    }, 100); // Intervalo de 100ms entre cada pintura
  }

  /**
   * Ponto de entrada para executar os algoritmos de coloração.
   * Chama a API e, em caso de sucesso, inicia a animação.
   */
  window.runColoring = async function (type) {
    const outEl = document.getElementById("coloringOut");
    outEl.textContent = "Executando...";

    // Limpa a coloração e destaques visuais antes de começar
    state.coloring = {};
    clearHighlight();
    draw();

    try {
      const res = await api("run_coloring", { type: type });
      if (!res.ok) {
        outEl.textContent = `Erro: ${res.error}`;
        return;
      }

      const { result } = res;
      let output = `--- Algoritmo: ${type.replace("_", " ")} ---\n\n`;
      output += `Tempo de execução: ${result.time.toFixed(4)} ms\n`;
      output += `Número de cores: ${result.num_colors}\n\n`;

      if (result.details) {
        output += "Mapeamento (Vértice: Cor):\n";
        result.details.forEach((item) => {
          output += `- ${item.label}: Cor ${item.color + 1}\n`;
        });
      }

      outEl.textContent = output;

      // Inicia a animação de pintura com o resultado
      animateColoring(result.mapping);
    } catch (e) {
      outEl.textContent = `Erro na requisição: ${e.message}`;
    }
  };

  /* ===================== Layouts ===================== */
  window.layoutCirc = function () {
    const w = 1100,
      h = 600,
      cx = w / 2,
      cy = h / 2,
      r = Math.min(w, h) / 2 - 60;
    const n = state.nodes.length,
      step = (2 * Math.PI) / Math.max(n, 1);
    state.nodes.forEach((nd, i) => {
      nd.x = cx + r * Math.cos(i * step);
      nd.y = cy + r * Math.sin(i * step);
    });
    draw();
  };
  window.layoutRand = function () {
    const w = 1100,
      h = 600;
    state.nodes.forEach((nd) => {
      nd.x = 80 + Math.random() * (w - 160);
      nd.y = 60 + Math.random() * (h - 120);
    });
    draw();
  };
  window.layoutHier = function () {
    const n = state.nodes.length;
    const adj = Array.from({ length: n }, () => []);
    state.edges.forEach((e) => {
      adj[e.from].push(e.to);
      if (!state.flags.direcionado) adj[e.to].push(e.from);
    });
    const INF = 1e9,
      level = Array(n).fill(INF),
      q = [0];
    if (n > 0) level[0] = 0;

    let head = 0;
    while (head < q.length) {
      const u = q[head++];
      adj[u].forEach((v) => {
        if (level[v] === INF) {
          level[v] = level[u] + 1;
          q.push(v);
        }
      });
    }

    const maxL = Math.max(...level.map((v) => (v === INF ? 0 : v)));
    for (let i = 0; i < n; i++) if (level[i] === INF) level[i] = maxL + 1;

    const groups = {};
    level.forEach((lv, i) => (groups[lv] ??= []).push(i));

    const w = 1100,
      h = 600;
    const layerGap = Math.max(120, h / (Object.keys(groups).length + 1));
    const xPad = 80,
      baseY = 60;

    Object.values(groups).forEach((verts, idx) => {
      const gap = (w - 2 * xPad) / Math.max(verts.length, 1);
      verts.forEach((id, j) => {
        const node = state.nodes.find((node) => node.id === id);
        if (node) {
          node.x = xPad + gap / 2 + j * gap;
          node.y = baseY + idx * layerGap;
        }
      });
    });
    draw();
  };

  /* ===================== Export SVG ===================== */
  window.downloadSVG = function () {
    const s = new XMLSerializer().serializeToString(svg);
    const blob = new Blob([s], { type: "image/svg+xml" });
    const a = document.createElement("a");
    a.href = URL.createObjectURL(blob);
    a.download = "grafo.svg";
    a.click();
  };

  /* ===================== Inicialização ===================== */
  state.nodes = boot.nodes.map((n) => ({ ...n }));
  state.edges = boot.edges.map((e) => ({ ...e }));
  document
    .getElementById("fileImportTXT")
    .addEventListener("change", handleFileImport);

  setFlagInfo();
  refreshSelects();
  layoutCirc();
  applyTransform();
});
