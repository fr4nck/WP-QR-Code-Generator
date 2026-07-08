(function () {
  const qs = (root, selector) => root.querySelector(selector);
  const qsa = (root, selector) => Array.from(root.querySelectorAll(selector));

  function escapeWifiValue(value) {
    return String(value || '').replace(/([\\;,:\"])/g, '\\$1');
  }

  function escVCard(value) {
    return String(value || '')
      .replace(/\\/g, '\\\\')
      .replace(/\n/g, '\\n')
      .replace(/;/g, '\\;')
      .replace(/,/g, '\\,');
  }

  function normalizeUrl(value) {
    const raw = String(value || '').trim();
    if (!raw) return '';
    if (/^https?:\/\//i.test(raw)) return raw;
    return 'https://' + raw;
  }

  function buildMailto(root) {
    const to = qs(root, '[name="email_to"]').value.trim();
    const subject = qs(root, '[name="email_subject"]').value.trim();
    const body = qs(root, '[name="email_body"]').value.trim();
    if (!to) return '';
    const params = new URLSearchParams();
    if (subject) params.set('subject', subject);
    if (body) params.set('body', body);
    const query = params.toString();
    return 'mailto:' + to + (query ? '?' + query : '');
  }

  function buildSms(root) {
    const number = qs(root, '[name="sms_number"]').value.trim();
    const body = qs(root, '[name="sms_body"]').value.trim();
    if (!number && !body) return '';
    if (!body) return 'sms:' + number;
    return 'sms:' + number + '?body=' + encodeURIComponent(body);
  }

  function buildGps(root) {
    const lat = qs(root, '[name="gps_lat"]').value.trim();
    const lng = qs(root, '[name="gps_lng"]').value.trim();
    const label = qs(root, '[name="gps_label"]').value.trim();
    if (!lat || !lng) return '';
    if (!label) return 'geo:' + lat + ',' + lng;
    return 'geo:' + lat + ',' + lng + '?q=' + encodeURIComponent(lat + ',' + lng + '(' + label + ')');
  }

  function buildVCard(root) {
    const name = qs(root, '[name="contact_name"]').value.trim();
    const org = qs(root, '[name="contact_org"]').value.trim();
    const phone = qs(root, '[name="contact_phone"]').value.trim();
    const email = qs(root, '[name="contact_email"]').value.trim();
    const url = normalizeUrl(qs(root, '[name="contact_url"]').value);
    const address = qs(root, '[name="contact_address"]').value.trim();
    const note = qs(root, '[name="contact_note"]').value.trim();

    if (!name && !org && !phone && !email && !url && !address && !note) {
      return '';
    }

    const lines = [
      'BEGIN:VCARD',
      'VERSION:3.0'
    ];

    if (name) lines.push('FN:' + escVCard(name));
    if (org) lines.push('ORG:' + escVCard(org));
    if (phone) lines.push('TEL:' + escVCard(phone));
    if (email) lines.push('EMAIL:' + escVCard(email));
    if (url) lines.push('URL:' + escVCard(url));
    if (address) lines.push('ADR:;;' + escVCard(address) + ';;;;');
    if (note) lines.push('NOTE:' + escVCard(note));
    lines.push('END:VCARD');

    return lines.join('\n');
  }

  function buildPayload(root, mode) {
    if (mode === 'wifi') {
      const ssid = qs(root, '[name="wifi_ssid"]').value.trim();
      const password = qs(root, '[name="wifi_password"]').value;
      const security = qs(root, '[name="wifi_security"]').value;
      const hidden = qs(root, '[name="wifi_hidden"]').checked;

      if (!ssid) return '';

      const parts = ['WIFI:'];
      parts.push('T:' + (security || 'WPA') + ';');
      parts.push('S:' + escapeWifiValue(ssid) + ';');
      if (security !== 'nopass' && password) {
        parts.push('P:' + escapeWifiValue(password) + ';');
      }
      if (hidden) {
        parts.push('H:true;');
      }
      parts.push(';');
      return parts.join('');
    }

    if (mode === 'url') {
      return normalizeUrl(qs(root, '[name="url_value"]').value);
    }

    if (mode === 'phone') {
      const number = qs(root, '[name="phone_number"]').value.trim();
      return number ? 'tel:' + number : '';
    }

    if (mode === 'email') {
      return buildMailto(root);
    }

    if (mode === 'sms') {
      return buildSms(root);
    }

    if (mode === 'gps') {
      return buildGps(root);
    }

    if (mode === 'contact') {
      return buildVCard(root);
    }

    return qs(root, '[name="text_payload"]').value.trim();
  }

  function activateTab(root, mode) {
    qsa(root, '.wpqr-tab').forEach((tab) => {
      tab.classList.toggle('is-active', tab.dataset.tab === mode);
    });
    qsa(root, '.wpqr-panel').forEach((panel) => {
      panel.classList.toggle('is-active', panel.dataset.panel === mode);
    });
    root.dataset.mode = mode;
  }

  function drawQrOnCanvas(canvas, payload, options) {
    const size = Math.max(150, Math.min(1200, parseInt(options.size, 10) || 320));
    const margin = Math.max(0, Math.min(80, parseInt(options.margin, 10) || 16));
    const ecLevel = ['L', 'M', 'Q', 'H'].includes(options.ecLevel) ? options.ecLevel : 'H';
    const dark = options.dark || '#000000';
    const light = options.light || '#ffffff';
    const dpr = window.devicePixelRatio || 1;

    const qr = qrcode(0, ecLevel);
    qr.addData(payload, 'Byte');
    qr.make();

    const moduleCount = qr.getModuleCount();
    const innerSize = Math.max(1, size - margin * 2);
    const cellSize = innerSize / moduleCount;

    canvas.width = Math.round(size * dpr);
    canvas.height = Math.round(size * dpr);
    canvas.style.width = size + 'px';
    canvas.style.height = size + 'px';

    const ctx = canvas.getContext('2d');
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    ctx.clearRect(0, 0, size, size);
    ctx.fillStyle = light;
    ctx.fillRect(0, 0, size, size);
    ctx.fillStyle = dark;

    for (let row = 0; row < moduleCount; row += 1) {
      for (let col = 0; col < moduleCount; col += 1) {
        if (!qr.isDark(row, col)) continue;
        const x = margin + col * cellSize;
        const y = margin + row * cellSize;
        const w = Math.ceil((col + 1) * cellSize + margin) - Math.floor(x);
        const h = Math.ceil((row + 1) * cellSize + margin) - Math.floor(y);
        ctx.fillRect(Math.floor(x), Math.floor(y), w, h);
      }
    }

    return { size, light };
  }

  function loadImage(src) {
    return new Promise((resolve, reject) => {
      if (!src) {
        reject(new Error('Image manquante'));
        return;
      }
      const img = new Image();
      img.crossOrigin = 'anonymous';
      img.onload = () => resolve(img);
      img.onerror = () => reject(new Error('Impossible de charger l’image du logo'));
      img.src = src;
    });
  }

  function drawRoundedRect(ctx, x, y, width, height, radius) {
    const r = Math.min(radius, width / 2, height / 2);
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.arcTo(x + width, y, x + width, y + height, r);
    ctx.arcTo(x + width, y + height, x, y + height, r);
    ctx.arcTo(x, y + height, x, y, r);
    ctx.arcTo(x, y, x + width, y, r);
    ctx.closePath();
  }

  async function overlayLogo(canvas, config, lightColor) {
    if (!config.logoEnabled || !config.centerImageUrl) return;

    const img = await loadImage(config.centerImageUrl);
    const ctx = canvas.getContext('2d');
    const size = parseInt(canvas.style.width, 10) || 320;
    const ratio = Math.max(0.08, Math.min(0.30, parseFloat(config.centerImageSizeRatio) || 0.18));
    const logoSize = size * ratio;
    const padding = Math.max(8, logoSize * 0.18);
    const bgSize = logoSize + padding * 2;
    const x = (size - bgSize) / 2;
    const y = (size - bgSize) / 2;

    ctx.save();
    drawRoundedRect(ctx, x, y, bgSize, bgSize, bgSize * 0.18);
    ctx.fillStyle = lightColor || '#ffffff';
    ctx.fill();
    ctx.restore();

    ctx.drawImage(img, (size - logoSize) / 2, (size - logoSize) / 2, logoSize, logoSize);
  }

  function updateLinks(root, canvas, payload, mode) {
    const openLink = qs(root, '[data-role="open"]');
    const downloadLink = qs(root, '[data-role="download"]');
    const raw = qs(root, '[data-role="payload"]');

    const dataUrl = canvas.toDataURL('image/png');
    const slug = mode ? 'wp-qr-code-generator-' + mode : 'wp-qr-code-generator';
    openLink.href = dataUrl;
    downloadLink.href = dataUrl;
    downloadLink.download = slug + '.png';
    raw.textContent = payload;
  }

  async function generate(root, config) {
    const mode = root.dataset.mode || 'text';
    const payload = buildPayload(root, mode);
    const output = qs(root, '.wpqr-output');
    const canvas = qs(root, '.wpqr-canvas');

    if (!payload) {
      output.hidden = true;
      return;
    }

    const options = {
      size: qs(root, '[name="size"]').value || config.size || '320',
      margin: qs(root, '[name="margin"]').value || config.margin || '16',
      ecLevel: qs(root, '[name="ecLevel"]').value || (config.logoEnabled ? 'H' : 'M'),
      dark: config.dark,
      light: config.light,
    };

    const rendered = drawQrOnCanvas(canvas, payload, options);

    try {
      await overlayLogo(canvas, config, rendered.light);
    } catch (error) {
      console.warn(error);
    }

    try {
      updateLinks(root, canvas, payload, mode);
    } catch (error) {
      console.warn(error);
      qs(root, '[data-role="payload"]').textContent = payload;
    }

    output.hidden = false;
  }

  function initInstance(root) {
    let config = {};
    try {
      config = JSON.parse(root.dataset.config || '{}');
    } catch (e) {
      config = {};
    }

    activateTab(root, root.dataset.mode || 'text');

    qsa(root, '.wpqr-tab').forEach((tab) => {
      tab.addEventListener('click', () => activateTab(root, tab.dataset.tab));
    });

    const form = qs(root, '.wpqr-form');
    const resetButton = qs(root, '[data-action="reset"]');
    const output = qs(root, '.wpqr-output');
    const canvas = qs(root, '.wpqr-canvas');

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      generate(root, config);
    });

    resetButton.addEventListener('click', function () {
      form.reset();
      activateTab(root, 'text');
      output.hidden = true;
      const ctx = canvas.getContext('2d');
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      qs(root, '[data-role="payload"]').textContent = '';
      qs(root, '[name="size"]').value = config.size || '320';
      qs(root, '[name="margin"]').value = config.margin || '16';
      qs(root, '[name="ecLevel"]').value = config.logoEnabled ? 'H' : 'M';
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.wpqr-app').forEach(initInstance);
  });
})();
