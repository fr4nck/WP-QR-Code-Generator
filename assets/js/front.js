(function () {
  'use strict';

  const qs = (root, selector) => root.querySelector(selector);
  const qsa = (root, selector) => Array.from(root.querySelectorAll(selector));

  function escapeWifiValue(value) {
    return String(value || '').replace(/([\\;,:\"])/g, '\\$1');
  }

  function escapeVCard(value) {
    return String(value || '')
      .replace(/\\/g, '\\\\')
      .replace(/\r?\n/g, '\\n')
      .replace(/;/g, '\\;')
      .replace(/,/g, '\\,');
  }

  function escapeICal(value) {
    return String(value || '')
      .replace(/\\/g, '\\\\')
      .replace(/\r?\n/g, '\\n')
      .replace(/;/g, '\\;')
      .replace(/,/g, '\\,');
  }

  function escapeXml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&apos;');
  }

  function normalizeUrl(value) {
    const raw = String(value || '').trim();
    if (!raw) return '';
    if (/^https?:\/\//i.test(raw)) return raw;
    return 'https://' + raw;
  }

  function buildContentUrl(root) {
    const selectedId = qs(root, '[name="content_id"]').value.trim();
    if (!selectedId || !/^[1-9][0-9]*$/.test(selectedId)) return '';
    const config = root.wpqrConfig || {};
    const selectedUrl = qs(root, '[name="content_id"]').dataset.url || '';
    return selectedUrl || (config.siteUrl || window.location.origin).replace(/\/+$/, '') + '/qr/' + selectedId + '/';
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
    const lat = qs(root, '[name="gps_lat"]').value.trim().replace(',', '.');
    const lng = qs(root, '[name="gps_lng"]').value.trim().replace(',', '.');
    const label = qs(root, '[name="gps_label"]').value.trim();
    if (!lat || !lng) return '';
    if (!label) return 'geo:' + lat + ',' + lng;
    return 'geo:' + lat + ',' + lng + '?q=' + encodeURIComponent(lat + ',' + lng + '(' + label + ')');
  }

  function buildVCard(root) {
    const name = qs(root, '[name="contact_name"]').value.trim();
    const title = qs(root, '[name="contact_title"]').value.trim();
    const org = qs(root, '[name="contact_org"]').value.trim();
    const department = qs(root, '[name="contact_department"]').value.trim();
    const phone = qs(root, '[name="contact_phone"]').value.trim();
    const email = qs(root, '[name="contact_email"]').value.trim();
    const url = normalizeUrl(qs(root, '[name="contact_url"]').value);
    const address = qs(root, '[name="contact_address"]').value.trim();
    const postcode = qs(root, '[name="contact_postcode"]').value.trim();
    const city = qs(root, '[name="contact_city"]').value.trim();
    const country = qs(root, '[name="contact_country"]').value.trim();
    const note = qs(root, '[name="contact_note"]').value.trim();

    if (!name && !title && !org && !department && !phone && !email && !url && !address && !postcode && !city && !country && !note) {
      return '';
    }

    const lines = ['BEGIN:VCARD', 'VERSION:3.0'];
    if (name) lines.push('FN:' + escapeVCard(name));
    if (org || department) {
      lines.push('ORG:' + [org, department].map(escapeVCard).join(';'));
    }
    if (title) lines.push('TITLE:' + escapeVCard(title));
    if (phone) lines.push('TEL;TYPE=WORK,VOICE:' + escapeVCard(phone));
    if (email) lines.push('EMAIL;TYPE=WORK:' + escapeVCard(email));
    if (url) lines.push('URL:' + escapeVCard(url));
    if (address || postcode || city || country) {
      lines.push('ADR;TYPE=WORK:;;' + [address, city, '', postcode, country].map(escapeVCard).join(';'));
    }
    if (note) lines.push('NOTE:' + escapeVCard(note));
    lines.push('END:VCARD');

    return lines.join('\r\n');
  }

  function parseLocalDate(dateValue, timeValue) {
    const dateMatch = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(dateValue || ''));
    const timeMatch = /^(\d{2}):(\d{2})$/.exec(String(timeValue || ''));
    if (!dateMatch || !timeMatch) return null;

    const date = new Date(
      Number(dateMatch[1]),
      Number(dateMatch[2]) - 1,
      Number(dateMatch[3]),
      Number(timeMatch[1]),
      Number(timeMatch[2]),
      0,
      0
    );

    return Number.isNaN(date.getTime()) ? null : date;
  }

  function pad2(value) {
    return String(value).padStart(2, '0');
  }

  function formatLocalDateTime(date) {
    return String(date.getFullYear())
      + pad2(date.getMonth() + 1)
      + pad2(date.getDate())
      + 'T'
      + pad2(date.getHours())
      + pad2(date.getMinutes())
      + pad2(date.getSeconds());
  }

  function formatUtcDateTime(date) {
    return String(date.getUTCFullYear())
      + pad2(date.getUTCMonth() + 1)
      + pad2(date.getUTCDate())
      + 'T'
      + pad2(date.getUTCHours())
      + pad2(date.getUTCMinutes())
      + pad2(date.getUTCSeconds())
      + 'Z';
  }

  function formatDateOnly(date) {
    return String(date.getFullYear()) + pad2(date.getMonth() + 1) + pad2(date.getDate());
  }

  function parseDateOnly(value) {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(value || ''));
    if (!match) return null;
    const date = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]), 12, 0, 0, 0);
    return Number.isNaN(date.getTime()) ? null : date;
  }

  function buildEvent(root) {
    const title = qs(root, '[name="event_title"]').value.trim();
    const allDay = qs(root, '[name="event_all_day"]').checked;
    const startDateValue = qs(root, '[name="event_start_date"]').value;
    const startTimeValue = qs(root, '[name="event_start_time"]').value;
    const endDateValue = qs(root, '[name="event_end_date"]').value;
    const endTimeValue = qs(root, '[name="event_end_time"]').value;
    const location = qs(root, '[name="event_location"]').value.trim();
    const description = qs(root, '[name="event_description"]').value.trim();

    if (!title || !startDateValue) return '';

    const lines = [
      'BEGIN:VCALENDAR',
      'VERSION:2.0',
      'PRODID:-//WP QR Code Generator//FR',
      'CALSCALE:GREGORIAN',
      'BEGIN:VEVENT',
      'UID:' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 10) + '@wpqr',
      'DTSTAMP:' + formatUtcDateTime(new Date())
    ];

    if (allDay) {
      const startDate = parseDateOnly(startDateValue);
      if (!startDate) return '';

      let endDate = parseDateOnly(endDateValue);
      if (!endDate || endDate.getTime() <= startDate.getTime()) {
        endDate = new Date(startDate.getTime());
        endDate.setDate(endDate.getDate() + 1);
      }

      lines.push('DTSTART;VALUE=DATE:' + formatDateOnly(startDate));
      lines.push('DTEND;VALUE=DATE:' + formatDateOnly(endDate));
    } else {
      const startDate = parseLocalDate(startDateValue, startTimeValue);
      if (!startDate) return '';

      let endDate = null;
      if (endDateValue) {
        endDate = parseLocalDate(endDateValue, endTimeValue || startTimeValue);
      } else if (endTimeValue) {
        endDate = parseLocalDate(startDateValue, endTimeValue);
      }

      if (!endDate || endDate.getTime() <= startDate.getTime()) {
        endDate = new Date(startDate.getTime() + 60 * 60 * 1000);
      }

      lines.push('DTSTART:' + formatLocalDateTime(startDate));
      lines.push('DTEND:' + formatLocalDateTime(endDate));
    }

    lines.push('SUMMARY:' + escapeICal(title));
    if (location) lines.push('LOCATION:' + escapeICal(location));
    if (description) lines.push('DESCRIPTION:' + escapeICal(description));
    lines.push('END:VEVENT', 'END:VCALENDAR');

    return lines.join('\r\n');
  }

  function buildPayload(root, mode) {
    if (mode === 'wifi') {
      const ssid = qs(root, '[name="wifi_ssid"]').value.trim();
      const password = qs(root, '[name="wifi_password"]').value;
      const security = qs(root, '[name="wifi_security"]').value;
      const hidden = qs(root, '[name="wifi_hidden"]').checked;

      if (!ssid) return { payload: '', error: 'Renseignez le nom du réseau Wi-Fi.' };

      const parts = ['WIFI:'];
      parts.push('T:' + (security || 'WPA') + ';');
      parts.push('S:' + escapeWifiValue(ssid) + ';');
      if (security !== 'nopass' && password) {
        parts.push('P:' + escapeWifiValue(password) + ';');
      }
      if (hidden) parts.push('H:true;');
      parts.push(';');
      return { payload: parts.join(''), error: '' };
    }

    if (mode === 'url') {
      const payload = normalizeUrl(qs(root, '[name="url_value"]').value);
      return { payload, error: payload ? '' : 'Renseignez une adresse web.' };
    }

    if (mode === 'content') {
      const payload = buildContentUrl(root);
      return { payload, error: payload ? '' : 'Recherchez puis sélectionnez un contenu WordPress publié.' };
    }

    if (mode === 'phone') {
      const number = qs(root, '[name="phone_number"]').value.trim();
      return { payload: number ? 'tel:' + number : '', error: number ? '' : 'Renseignez un numéro de téléphone.' };
    }

    if (mode === 'email') {
      const payload = buildMailto(root);
      return { payload, error: payload ? '' : 'Renseignez une adresse e-mail.' };
    }

    if (mode === 'sms') {
      const payload = buildSms(root);
      return { payload, error: payload ? '' : 'Renseignez un numéro ou un message SMS.' };
    }

    if (mode === 'gps') {
      const payload = buildGps(root);
      return { payload, error: payload ? '' : 'Renseignez la latitude et la longitude.' };
    }

    if (mode === 'event') {
      const payload = buildEvent(root);
      const allDay = qs(root, '[name="event_all_day"]').checked;
      const hasTime = allDay || qs(root, '[name="event_start_time"]').value;
      const error = !qs(root, '[name="event_title"]').value.trim()
        ? 'Renseignez le titre de l’événement.'
        : !qs(root, '[name="event_start_date"]').value
          ? 'Renseignez la date de début.'
          : !hasTime
            ? 'Renseignez l’heure de début ou cochez « Journée entière ». '
            : payload ? '' : 'Les informations de l’événement sont incomplètes.';
      return { payload, error };
    }

    if (mode === 'contact') {
      const payload = buildVCard(root);
      return { payload, error: payload ? '' : 'Renseignez au moins une information de contact.' };
    }

    const payload = qs(root, '[name="text_payload"]').value.trim();
    return { payload, error: payload ? '' : 'Saisissez le texte à encoder.' };
  }

  function setStatus(root, message, type) {
    const status = qs(root, '[data-role="status"]');
    status.textContent = message || '';
    status.classList.remove('is-error', 'is-success', 'is-warning');
    if (type) status.classList.add('is-' + type);
  }

  function activateTab(root, mode, focusTab) {
    qsa(root, '.wpqr-tab').forEach((tab) => {
      const active = tab.dataset.tab === mode;
      tab.classList.toggle('is-active', active);
      tab.setAttribute('aria-selected', active ? 'true' : 'false');
      tab.tabIndex = active ? 0 : -1;
      if (active && focusTab) tab.focus();
    });

    qsa(root, '.wpqr-panel').forEach((panel) => {
      const active = panel.dataset.panel === mode;
      panel.classList.toggle('is-active', active);
      panel.hidden = !active;
    });

    root.dataset.mode = mode;
  }

  function changeMode(root, mode, focusTab) {
    activateTab(root, mode, focusTab);
    qs(root, '.wpqr-output').hidden = true;
    setStatus(root, '', '');
  }

  function configureKeyboardTabs(root) {
    const tabs = qsa(root, '.wpqr-tab');
    tabs.forEach((tab, index) => {
      tab.addEventListener('keydown', (event) => {
        let targetIndex = null;
        if (event.key === 'ArrowRight' || event.key === 'ArrowDown') targetIndex = (index + 1) % tabs.length;
        if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') targetIndex = (index - 1 + tabs.length) % tabs.length;
        if (event.key === 'Home') targetIndex = 0;
        if (event.key === 'End') targetIndex = tabs.length - 1;
        if (targetIndex === null) return;

        event.preventDefault();
        changeMode(root, tabs[targetIndex].dataset.tab, true);
      });
    });
  }

  function resolveQuietZone(size, requestedMargin, moduleCount) {
    const requested = Math.max(0, Math.min(80, parseInt(requestedMargin, 10) || 0));
    const safeRequested = Math.min(requested, Math.floor(size * 0.25));
    const minimumForFourModules = Math.ceil((4 * size) / (moduleCount + 8));
    return Math.max(safeRequested, minimumForFourModules);
  }

  function drawQrOnCanvas(canvas, payload, options) {
    const size = Math.max(150, Math.min(1200, parseInt(options.size, 10) || 320));
    const ecLevel = ['L', 'M', 'Q', 'H'].includes(options.ecLevel) ? options.ecLevel : 'M';
    const dark = options.dark || '#000000';
    const light = options.light || '#ffffff';
    const dpr = window.devicePixelRatio || 1;

    if (qrcode.stringToBytesFuncs && qrcode.stringToBytesFuncs['UTF-8']) {
      qrcode.stringToBytes = qrcode.stringToBytesFuncs['UTF-8'];
    }

    const qr = qrcode(0, ecLevel);
    qr.addData(payload, 'Byte');
    qr.make();

    const moduleCount = qr.getModuleCount();
    const margin = resolveQuietZone(size, options.margin, moduleCount);
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
        const nextX = margin + (col + 1) * cellSize;
        const nextY = margin + (row + 1) * cellSize;
        ctx.fillRect(Math.floor(x), Math.floor(y), Math.ceil(nextX) - Math.floor(x), Math.ceil(nextY) - Math.floor(y));
      }
    }

    return { qr, moduleCount, size, margin, cellSize, dark, light };
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
      img.onerror = () => reject(new Error('Impossible de charger le logo central.'));
      img.src = src;
    });
  }

  function imageToDataUri(img) {
    const maxDimension = 512;
    const naturalWidth = img.naturalWidth || img.width || 1;
    const naturalHeight = img.naturalHeight || img.height || 1;
    const scale = Math.min(1, maxDimension / Math.max(naturalWidth, naturalHeight));
    const canvas = document.createElement('canvas');
    canvas.width = Math.max(1, Math.round(naturalWidth * scale));
    canvas.height = Math.max(1, Math.round(naturalHeight * scale));
    canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
    return canvas.toDataURL('image/png');
  }

  async function getLogoAsset(config) {
    if (!config.logoEnabled || !config.centerImageUrl) return null;
    const image = await loadImage(config.centerImageUrl);
    let dataUri = config.centerImageUrl;
    try {
      dataUri = imageToDataUri(image);
    } catch (error) {
      console.warn(error);
    }
    return { image, dataUri };
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

  function getLogoGeometry(size, config) {
    const maximum = Math.max(0.08, Math.min(0.22, parseFloat(config.maxLogoRatio) || 0.22));
    const ratio = Math.max(0.08, Math.min(maximum, parseFloat(config.centerImageSizeRatio) || 0.18));
    const logoSize = size * ratio;
    const padding = Math.max(8, logoSize * 0.18);
    const backgroundSize = logoSize + padding * 2;
    return {
      logoSize,
      backgroundSize,
      backgroundX: (size - backgroundSize) / 2,
      backgroundY: (size - backgroundSize) / 2,
      logoX: (size - logoSize) / 2,
      logoY: (size - logoSize) / 2
    };
  }

  function drawContainedImage(ctx, img, x, y, width, height) {
    const sourceWidth = img.naturalWidth || img.width || width;
    const sourceHeight = img.naturalHeight || img.height || height;
    const scale = Math.min(width / sourceWidth, height / sourceHeight);
    const drawWidth = sourceWidth * scale;
    const drawHeight = sourceHeight * scale;
    ctx.drawImage(img, x + (width - drawWidth) / 2, y + (height - drawHeight) / 2, drawWidth, drawHeight);
  }

  function overlayLogo(canvas, config, lightColor, logoAsset) {
    if (!logoAsset) return;

    const ctx = canvas.getContext('2d');
    const size = parseInt(canvas.style.width, 10) || 320;
    const geometry = getLogoGeometry(size, config);

    ctx.save();
    drawRoundedRect(
      ctx,
      geometry.backgroundX,
      geometry.backgroundY,
      geometry.backgroundSize,
      geometry.backgroundSize,
      geometry.backgroundSize * 0.18
    );
    ctx.fillStyle = lightColor || '#ffffff';
    ctx.fill();
    drawContainedImage(
      ctx,
      logoAsset.image,
      geometry.logoX,
      geometry.logoY,
      geometry.logoSize,
      geometry.logoSize
    );
    ctx.restore();
  }

  function numberForSvg(value) {
    return Number(value.toFixed(3));
  }

  function buildSvg(rendered, config, logoAsset) {
    const path = [];
    for (let row = 0; row < rendered.moduleCount; row += 1) {
      for (let col = 0; col < rendered.moduleCount; col += 1) {
        if (!rendered.qr.isDark(row, col)) continue;
        const x = numberForSvg(rendered.margin + col * rendered.cellSize);
        const y = numberForSvg(rendered.margin + row * rendered.cellSize);
        const width = numberForSvg(rendered.cellSize);
        const height = numberForSvg(rendered.cellSize);
        path.push('M' + x + ' ' + y + 'h' + width + 'v' + height + 'h-' + width + 'z');
      }
    }

    const parts = [
      '<?xml version="1.0" encoding="UTF-8"?>',
      '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="' + rendered.size + '" height="' + rendered.size + '" viewBox="0 0 ' + rendered.size + ' ' + rendered.size + '" role="img" aria-label="QR code">',
      '<rect width="100%" height="100%" fill="' + escapeXml(rendered.light) + '"/>',
      '<path d="' + path.join('') + '" fill="' + escapeXml(rendered.dark) + '" shape-rendering="crispEdges"/>'
    ];

    if (logoAsset) {
      const geometry = getLogoGeometry(rendered.size, config);
      const radius = geometry.backgroundSize * 0.18;
      parts.push(
        '<rect x="' + numberForSvg(geometry.backgroundX) + '" y="' + numberForSvg(geometry.backgroundY) + '" width="' + numberForSvg(geometry.backgroundSize) + '" height="' + numberForSvg(geometry.backgroundSize) + '" rx="' + numberForSvg(radius) + '" fill="' + escapeXml(rendered.light) + '"/>'
      );
      parts.push(
        '<image x="' + numberForSvg(geometry.logoX) + '" y="' + numberForSvg(geometry.logoY) + '" width="' + numberForSvg(geometry.logoSize) + '" height="' + numberForSvg(geometry.logoSize) + '" href="' + escapeXml(logoAsset.dataUri) + '" xlink:href="' + escapeXml(logoAsset.dataUri) + '" preserveAspectRatio="xMidYMid meet"/>'
      );
    }

    parts.push('</svg>');
    return parts.join('');
  }

  function updateLinks(root, canvas, rendered, payload, mode, config, logoAsset) {
    const openLink = qs(root, '[data-role="open"]');
    const downloadLink = qs(root, '[data-role="download"]');
    const svgLink = qs(root, '[data-role="download-svg"]');
    const raw = qs(root, '[data-role="payload"]');
    const slug = mode ? 'wp-qr-code-generator-' + mode : 'wp-qr-code-generator';

    const pngDataUrl = canvas.toDataURL('image/png');
    openLink.href = pngDataUrl;
    downloadLink.href = pngDataUrl;
    downloadLink.download = slug + '.png';

    if (root.wpqrSvgUrl) URL.revokeObjectURL(root.wpqrSvgUrl);
    const svg = buildSvg(rendered, config, logoAsset);
    root.wpqrSvgUrl = URL.createObjectURL(new Blob([svg], { type: 'image/svg+xml;charset=utf-8' }));
    svgLink.href = root.wpqrSvgUrl;
    svgLink.download = slug + '.svg';

    raw.textContent = payload;
  }

  async function generate(root, config) {
    const mode = root.dataset.mode || 'text';
    const built = buildPayload(root, mode);
    const output = qs(root, '.wpqr-output');
    const canvas = qs(root, '.wpqr-canvas');

    if (!built.payload) {
      output.hidden = true;
      setStatus(root, built.error || 'Complétez les champs nécessaires.', 'error');
      qs(root, '[data-role="status"]').focus();
      return;
    }

    const options = {
      size: qs(root, '[name="size"]').value || config.size || '320',
      margin: qs(root, '[name="margin"]').value || config.margin || '16',
      ecLevel: config.logoEnabled ? 'H' : (qs(root, '[name="ecLevel"]').value || 'M'),
      dark: config.dark,
      light: config.light
    };

    let rendered;
    try {
      rendered = drawQrOnCanvas(canvas, built.payload, options);
    } catch (error) {
      console.warn(error);
      output.hidden = true;
      setStatus(root, 'Le contenu est trop long ou ne peut pas être encodé avec ces paramètres.', 'error');
      qs(root, '[data-role="status"]').focus();
      return;
    }

    let logoAsset = null;
    let logoWarning = '';
    if (config.logoEnabled) {
      try {
        logoAsset = await getLogoAsset(config);
        overlayLogo(canvas, config, rendered.light, logoAsset);
      } catch (error) {
        console.warn(error);
        logoWarning = ' Le logo central n’a pas pu être chargé ; le QR a été généré sans logo.';
      }
    }

    try {
      updateLinks(root, canvas, rendered, built.payload, mode, config, logoAsset);
    } catch (error) {
      console.warn(error);
      output.hidden = true;
      setStatus(root, 'Le QR a été calculé, mais les fichiers de téléchargement n’ont pas pu être préparés.', 'error');
      qs(root, '[data-role="status"]').focus();
      return;
    }

    output.hidden = false;
    setStatus(
      root,
      'QR code généré. Téléchargement disponible en PNG et SVG.' + logoWarning,
      logoWarning ? 'warning' : 'success'
    );
    output.focus();
  }

  function syncEventAllDay(root) {
    const checkbox = qs(root, '[name="event_all_day"]');
    if (!checkbox) return;
    const allDay = checkbox.checked;
    qsa(root, '.wpqr-event-time-field').forEach((field) => {
      field.hidden = allDay;
      const input = qs(field, 'input');
      if (input) input.disabled = allDay;
    });
  }


  function loadOrganization(root, config) {
    const organization = config && config.organization ? config.organization : {};
    const mapping = {
      contact_org: organization.name && organization.acronym
        ? organization.name + ' (' + organization.acronym + ')'
        : (organization.name || organization.acronym || ''),
      contact_department: organization.department || '',
      contact_phone: organization.phone || '',
      contact_email: organization.email || '',
      contact_url: organization.website || '',
      contact_address: organization.address || '',
      contact_postcode: organization.postcode || '',
      contact_city: organization.city || '',
      contact_country: organization.country || ''
    };

    let loaded = 0;
    Object.keys(mapping).forEach((fieldName) => {
      const field = qs(root, '[name="' + fieldName + '"]');
      const value = String(mapping[fieldName] || '').trim();
      if (!field || !value || field.value.trim()) return;
      field.value = value;
      loaded += 1;
    });

    if (loaded > 0) {
      setStatus(root, 'Coordonnées de l’organisme chargées. Vous pouvez les compléter ou les modifier.', 'success');
    } else {
      setStatus(root, 'Aucun champ vide n’a pu être complété avec les coordonnées enregistrées.', 'warning');
    }
  }

  function renderContentResults(root, items) {
    const results = qs(root, '[data-role="content-results"]');
    results.innerHTML = '';

    if (!items.length) {
      results.hidden = false;
      results.innerHTML = '<p class="wpqr-field-note">Aucun contenu publié trouvé.</p>';
      return;
    }

    const list = document.createElement('div');
    list.className = 'wpqr-content-result-list';
    items.forEach((item) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'wpqr-content-result';
      button.dataset.id = String(item.id || '');
      button.dataset.url = String(item.url || '');
      button.textContent = (item.title || 'Sans titre') + ' — ' + (item.type || 'Contenu') + ' (#' + item.id + ')';
      list.appendChild(button);
    });

    results.appendChild(list);
    results.hidden = false;
  }

  function configureContentSearch(root, config) {
    const search = qs(root, '[data-role="content-search"]');
    const contentId = qs(root, '[name="content_id"]');
    const results = qs(root, '[data-role="content-results"]');
    if (!search || !contentId || !results || !config.ajaxUrl) return;

    let controller = null;
    let timer = null;

    search.addEventListener('input', function () {
      contentId.value = '';
      contentId.dataset.url = '';
      clearTimeout(timer);

      const term = search.value.trim();
      if (term.length < 2) {
        results.hidden = true;
        results.innerHTML = '';
        return;
      }

      timer = setTimeout(function () {
        if (controller) controller.abort();
        controller = new AbortController();
        const url = new URL(config.ajaxUrl);
        url.searchParams.set('action', 'wpqr_search_content');
        url.searchParams.set('term', term);

        fetch(url.toString(), { credentials: 'same-origin', signal: controller.signal })
          .then((response) => response.json())
          .then((json) => renderContentResults(root, Array.isArray(json.data) ? json.data : []))
          .catch((error) => {
            if (error.name === 'AbortError') return;
            console.warn(error);
            results.hidden = false;
            results.innerHTML = '<p class="wpqr-field-note">La recherche est momentanément indisponible.</p>';
          });
      }, 250);
    });

    results.addEventListener('click', function (event) {
      const button = event.target.closest('.wpqr-content-result');
      if (!button) return;
      contentId.value = button.dataset.id || '';
      contentId.dataset.url = button.dataset.url || '';
      search.value = button.textContent;
      results.hidden = true;
      setStatus(root, 'Contenu sélectionné. Le QR pointera vers ' + contentId.dataset.url, 'success');
    });
  }

  function clearCanvas(canvas) {
    const ctx = canvas.getContext('2d');
    ctx.setTransform(1, 0, 0, 1, 0, 0);
    ctx.clearRect(0, 0, canvas.width, canvas.height);
  }

  function initInstance(root) {
    let config = {};
    try {
      config = JSON.parse(root.dataset.config || '{}');
    } catch (error) {
      console.warn(error);
    }
    root.wpqrConfig = config;

    activateTab(root, root.dataset.mode || 'text', false);
    configureKeyboardTabs(root);
    syncEventAllDay(root);
    configureContentSearch(root, config);

    qsa(root, '.wpqr-tab').forEach((tab) => {
      tab.addEventListener('click', () => changeMode(root, tab.dataset.tab, false));
    });

    const form = qs(root, '.wpqr-form');
    const resetButton = qs(root, '[data-action="reset"]');
    const output = qs(root, '.wpqr-output');
    const canvas = qs(root, '.wpqr-canvas');
    const errorCorrection = qs(root, '[name="ecLevel"]');
    const allDay = qs(root, '[name="event_all_day"]');
    const organizationButton = qs(root, '[data-action="load-organization"]');

    if (config.logoEnabled) {
      errorCorrection.value = 'H';
      errorCorrection.disabled = true;
    }

    if (allDay) allDay.addEventListener('change', () => syncEventAllDay(root));
    if (organizationButton) {
      organizationButton.addEventListener('click', function () {
        loadOrganization(root, config);
      });
    }

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      generate(root, config);
    });

    resetButton.addEventListener('click', function () {
      form.reset();
      activateTab(root, 'text', true);
      output.hidden = true;
      clearCanvas(canvas);
      qs(root, '[data-role="payload"]').textContent = '';
      qs(root, '[name="size"]').value = config.size || '320';
      qs(root, '[name="margin"]').value = config.margin || '16';
      errorCorrection.value = config.logoEnabled ? 'H' : 'M';
      errorCorrection.disabled = Boolean(config.logoEnabled);
      syncEventAllDay(root);
      setStatus(root, '', '');
      if (root.wpqrSvgUrl) {
        URL.revokeObjectURL(root.wpqrSvgUrl);
        root.wpqrSvgUrl = null;
      }
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.wpqr-app').forEach(initInstance);
  });
})();
