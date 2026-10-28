/**
 * Llama Segura — hero de captación
 * - Guarda la atribución de campaña (UTM, gclid, gbraid, wbraid, fbclid) en una cookie first-party.
 * - Genera un lead_id único por visita y lo reutiliza en formulario, WhatsApp y dataLayer.
 * - Empuja eventos de negocio al dataLayer: lp_view, contact_intent, form_start, form_error, generate_lead.
 * - Envía el lead al endpoint REST de WordPress si existe; si no (demo estática), simula el envío.
 */
(function () {
  'use strict';

  var root = document.getElementById('ls-hero');
  if (!root) return;

  var cfg = {
    endpoint: root.dataset.endpoint || '',
    nonce: root.dataset.nonce || '',
    wa: root.dataset.wa || '',
    did: root.dataset.did || '',
    landing: root.dataset.landing || 'demo',
    product: root.dataset.product || 'revision_gas'
  };

  var ATTR_KEYS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'gclid', 'gbraid', 'wbraid', 'fbclid'];
  var COOKIE = 'ls_attr';
  var COOKIE_DAYS = 90;

  window.dataLayer = window.dataLayer || [];

  /* ---------- utilidades ---------- */
  function readCookie(name) {
    var m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
    if (!m) return null;
    try { return JSON.parse(decodeURIComponent(m[1])); } catch (e) { return null; }
  }

  function writeCookie(name, value, days) {
    var exp = new Date(Date.now() + days * 864e5).toUTCString();
    var secure = location.protocol === 'https:' ? '; Secure' : '';
    document.cookie = name + '=' + encodeURIComponent(JSON.stringify(value)) + '; expires=' + exp + '; path=/; SameSite=Lax' + secure;
  }

  function session(key, value) {
    try {
      if (value === undefined) return sessionStorage.getItem(key);
      sessionStorage.setItem(key, value);
    } catch (e) { return null; }
    return value;
  }

  function uuid() {
    if (window.crypto && crypto.randomUUID) return crypto.randomUUID();
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
      var r = Math.random() * 16 | 0;
      return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
    });
  }

  function push(event, params) {
    var payload = { event: event };
    for (var k in params) if (Object.prototype.hasOwnProperty.call(params, k)) payload[k] = params[k];
    window.dataLayer.push(payload);
  }

  async function sha256(text) {
    if (!window.crypto || !crypto.subtle) return '';
    var buf = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(text));
    return Array.from(new Uint8Array(buf)).map(function (b) { return b.toString(16).padStart(2, '0'); }).join('');
  }

  /* ---------- atribución ---------- */
  function captureAttribution() {
    var params = new URLSearchParams(location.search);
    var incoming = {};
    var hasIncoming = false;
    ATTR_KEYS.forEach(function (k) {
      var v = params.get(k);
      if (v) { incoming[k] = v.slice(0, 150); hasIncoming = true; }
    });

    var stored = readCookie(COOKIE) || {};
    if (hasIncoming) {
      incoming.ts = new Date().toISOString();
      incoming.landing_url = location.origin + location.pathname;
      if (!stored.first) stored.first = incoming; // primer toque: no se sobrescribe
      stored.last = incoming;                      // último toque: siempre el más reciente
      writeCookie(COOKIE, stored, COOKIE_DAYS);
    }
    return stored;
  }

  var attribution = captureAttribution();
  var last = attribution.last || {};

  var leadId = session('ls_lead_id') || session('ls_lead_id', uuid());
  var ref = 'LS-' + leadId.replace(/-/g, '').slice(0, 6).toUpperCase();

  /* ---------- campos ocultos ---------- */
  var form = root.querySelector('#ls-form');
  ATTR_KEYS.forEach(function (k) {
    var input = form.querySelector('[name="' + k + '"]');
    if (input) input.value = last[k] || '';
  });
  form.querySelector('[name="lead_id"]').value = leadId;
  form.querySelector('[name="landing_id"]').value = cfg.landing;

  /* ---------- enlaces de WhatsApp y llamada ---------- */
  var waText = 'Hola, quiero agendar una revisión de gasodomésticos. Ref: ' + ref;
  root.querySelectorAll('[data-channel="whatsapp"]').forEach(function (a) {
    a.href = 'https://wa.me/' + cfg.wa + '?text=' + encodeURIComponent(waText);
  });
  root.querySelectorAll('[data-channel="did"]').forEach(function (a) {
    a.href = 'tel:+' + cfg.did;
  });

  /* ---------- eventos ---------- */
  var base = {
    landing_id: cfg.landing,
    product: cfg.product,
    lead_id: leadId,
    campaign: last.utm_campaign || '(not set)',
    source: last.utm_source || '(direct)'
  };

  push('lp_view', base);

  root.addEventListener('click', function (e) {
    var el = e.target.closest('[data-channel]');
    if (!el) return;
    push('contact_intent', Object.assign({}, base, {
      channel: el.dataset.channel,
      cta_position: el.dataset.position || 'hero'
    }));
  });

  var started = false;
  form.addEventListener('focusin', function () {
    if (started) return;
    started = true;
    push('form_start', base);
  });

  /* ---------- validación ---------- */
  var nameField = form.querySelector('#ls-name');
  var phoneField = form.querySelector('#ls-phone');
  var consentField = form.querySelector('#ls-consent');

  function normalizePhone(raw) {
    var d = String(raw).replace(/\D/g, '');
    if (d.length === 12 && d.indexOf('57') === 0) d = d.slice(2);
    return d;
  }

  function setError(field, msg) {
    var wrap = field.closest('.ls-field') || field.closest('.ls-consent');
    var out = wrap.querySelector('.ls-error') || form.querySelector('#ls-consent-error');
    if (wrap.classList.contains('ls-field')) wrap.dataset.invalid = msg ? 'true' : 'false';
    field.setAttribute('aria-invalid', msg ? 'true' : 'false');
    out.textContent = msg;
  }

  function validate() {
    var errors = [];
    var name = nameField.value.trim();
    var phone = normalizePhone(phoneField.value);

    if (name.length < 2) { setError(nameField, 'Escribe tu nombre para saber a quién llamar.'); errors.push('name'); }
    else setError(nameField, '');

    // Celular (3xx) o fijo con indicativo nacional (60x), 10 dígitos
    if (!/^(3\d{9}|60\d{8})$/.test(phone)) { setError(phoneField, 'Escribe un número de 10 dígitos, por ejemplo 3001234567.'); errors.push('phone'); }
    else setError(phoneField, '');

    if (!consentField.checked) { setError(consentField, 'Necesitamos tu autorización para llamarte.'); errors.push('consent'); }
    else setError(consentField, '');

    return { ok: errors.length === 0, errors: errors, name: name, phone: phone };
  }

  /* ---------- envío ---------- */
  var submitBtn = form.querySelector('[type="submit"]');
  var successBox = root.querySelector('#ls-success');

  async function send(data) {
    if (!cfg.endpoint) {
      // Demo estática: no hay backend, se simula la respuesta del servidor.
      await new Promise(function (r) { setTimeout(r, 600); });
      return { status: 'created', lead_id: leadId };
    }
    var res = await fetch(cfg.endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-LS-Nonce': cfg.nonce },
      body: JSON.stringify(data),
      credentials: 'same-origin'
    });
    var json = await res.json().catch(function () { return {}; });
    if (!res.ok) throw new Error(json.message || 'Error ' + res.status);
    return json;
  }

  form.addEventListener('submit', async function (e) {
    e.preventDefault();
    var v = validate();
    if (!v.ok) {
      push('form_error', Object.assign({}, base, { fields: v.errors.join(',') }));
      form.querySelector('[aria-invalid="true"]').focus();
      return;
    }

    var data = {};
    new FormData(form).forEach(function (val, key) { data[key] = val; });
    data.phone = v.phone;
    data.channel = 'form';
    data.page_url = location.href.split('?')[0];
    data.first_touch = attribution.first || null;

    submitBtn.disabled = true;
    submitBtn.textContent = 'Enviando…';

    try {
      var result = await send(data);

      // Un solo generate_lead por lead_id, aunque el usuario recargue o reenvíe.
      if (result.status === 'created' && !session('ls_sent_' + leadId)) {
        session('ls_sent_' + leadId, '1');
        push('generate_lead', Object.assign({}, base, {
          channel: 'form',
          event_id: leadId,             // deduplicación Pixel + CAPI
          transaction_id: leadId,       // deduplicación Google Ads
          value: 39900,
          currency: 'COP',
          user_data: { sha256_phone_number: await sha256('+57' + v.phone) }
        }));
      }

      form.hidden = true;
      successBox.querySelector('[data-name]').textContent = v.name.split(' ')[0];
      successBox.querySelector('[data-ref]').textContent = ref;
      successBox.hidden = false;
      successBox.focus();
    } catch (err) {
      setError(phoneField, 'No pudimos enviar tus datos. Intenta de nuevo o escríbenos por WhatsApp.');
      push('form_error', Object.assign({}, base, { fields: 'server', message: String(err.message).slice(0, 100) }));
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Quiero que me llamen';
    }
  });

  /* ---------- panel de medición (solo en la demo) ---------- */
  var debug = document.getElementById('ls-debug-log');
  if (debug) {
    var render = function () {
      debug.textContent = window.dataLayer
        .filter(function (ev) { return ev && ev.event && ev.event.indexOf('gtm.') !== 0; })
        .map(function (ev) { return JSON.stringify(ev, null, 2); })
        .join('\n\n') || 'Sin eventos todavía.';
    };
    var originalPush = window.dataLayer.push.bind(window.dataLayer);
    window.dataLayer.push = function () { var r = originalPush.apply(null, arguments); render(); return r; };
    render();
  }
})();
