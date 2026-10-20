/* AdPilot progressive runtime: one observer, bounded batches, no React. */
(() => {
  'use strict';
  const config = window.adpilotRuntime;
  if (!config || window.__adpilotRuntime) return;
  try { if (new URL(config.restUrl, location.href).origin !== location.origin) return; } catch { return; }
  window.__adpilotRuntime = true;
  const queued = [], seen = new WeakSet();
  let running = 0;
  const id = () => 'r_' + (crypto.randomUUID ? crypto.randomUUID().replaceAll('-', '') : Math.random().toString(36).slice(2) + Date.now());
  async function request(path, body) {
    const controller = new AbortController(), timer = setTimeout(() => controller.abort(), 8000);
    try {
      const response = await fetch(config.restUrl + path, {method:'POST', credentials:'same-origin', keepalive:true, headers:{'Content-Type':'application/json'}, body:JSON.stringify(body), signal:controller.signal});
      if (!response.ok) throw new Error('AdPilot unavailable');
      return await response.json();
    } finally { clearTimeout(timer); }
  }
  function paint(node, decision, measured) {
    if (!node.isConnected || decision.status !== 'selected' || !decision.creative) return;
    const c = decision.creative, a = document.createElement('a'), label = document.createElement('span'), title = document.createElement('strong'), body = document.createElement('span');
    try { const url = new URL(c.destination_url); if (!['https:', 'http:'].includes(url.protocol)) return; a.href = url.href; } catch { return; }
    a.className = 'adp-native'; a.rel = 'sponsored noopener noreferrer';
    label.className = 'adp-ad-label'; label.textContent = config.advertisementLabel || 'Advertisement';
    title.textContent = c.title; body.textContent = c.body; a.append(label, title, body); node.replaceChildren(a);
    if (measured) {
      request('measurement/events', {events:[{type:'rendered', decision_ref:decision.decision_ref}]}).catch(() => {});
      a.addEventListener('click', event => {
        if (event.button || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        // Normal navigation remains the fallback if telemetry is unavailable.
        request('delivery/click', {decision_ref:decision.decision_ref}).catch(() => {});
      }, {passive:true});
    }
  }
  function drain() {
    refill();
    if (document.hidden || running >= 2 || !queued.length) return;
    const nodes = queued.splice(0, 20).filter(n => n.isConnected);
    if (!nodes.length) return;
    const opportunities = nodes.map(node => ({slot_id:node.dataset.adpilotSlot, render_instance_id:(node.dataset.adpilotRender ||= id())}));
    running++;
    request('delivery/decisions', {opportunities}).then(result => {
      const byId = new Map(nodes.map(n => [n.dataset.adpilotRender, n]));
      for (const decision of result.decisions || []) { const node = byId.get(decision.render_instance_id); if (node) paint(node, decision, result.measurement_enabled); }
    }).catch(() => {}).finally(() => { running--; setTimeout(drain, 40); });
    if (running < 2) setTimeout(drain, 40);
  }
  const observer = 'IntersectionObserver' in window ? new IntersectionObserver(entries => {
    for (const entry of entries) if (entry.isIntersecting && queued.length < 80 && !entry.target.dataset.adpilotAttempted) { entry.target.dataset.adpilotAttempted = '1'; observer.unobserve(entry.target); queued.push(entry.target); }
    drain();
  }, {rootMargin:'300px'}) : null;
  function refill() {
    if (queued.length >= 40) return;
    for (const node of document.querySelectorAll('[data-adpilot-slot]:not([data-adpilot-attempted])')) {
      if (queued.length >= 80) break;
      const rect = node.getBoundingClientRect();
      if (rect.top < innerHeight + 300 && rect.bottom > -300) { node.dataset.adpilotAttempted = '1'; observer?.unobserve(node); queued.push(node); }
    }
  }
  function mount(root) {
    for (const node of root.querySelectorAll('[data-adpilot-slot]')) {
      if (seen.has(node)) continue; seen.add(node);
      // Replace server IDs because full-page caches repeat HTML across visits.
      node.id = 'adp-' + id(); node.dataset.adpilotRender = id();
      if (observer) observer.observe(node);
      else if (node.getBoundingClientRect().top < innerHeight + 300 && queued.length < 20) { node.dataset.adpilotAttempted = '1'; queued.push(node); }
    }
    drain();
  }
  mount(document);
  document.addEventListener('adpilot:mount', event => mount(event.target));
  document.addEventListener('visibilitychange', drain);
  if (!observer) window.addEventListener('scroll', drain, {passive:true});
  window.addEventListener('pagehide', () => { queued.length = 0; observer?.disconnect(); });
})();
