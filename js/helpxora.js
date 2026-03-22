document.addEventListener('DOMContentLoaded', function () {
    fetch(CFG_GLPI.root_doc + '/plugins/helpxora/ajax/chat.php?action=init')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'ok') {
                initHelpxora(data.config);
            }
        });
});

let glpiHelpxoraConfig = {};

function initHelpxora(config) {
    glpiHelpxoraConfig = config;

    const widget = document.createElement('div');
    widget.id = 'glpi-helpxora-widget';

    const avatarImg = config.avatar
        ? (config.avatar.match(/^https?:\/\//) || config.avatar.startsWith('/')
            ? `<img src="${config.avatar}" alt="Avatar">`
            : `<div class="def-avatar">${config.avatar}</div>`)
        : `<div class="def-avatar">🤖</div>`;

    const bubbleIcon = config.bubble_icon ? (config.bubble_icon.match(/^https?:\/\//) || config.bubble_icon.startsWith('/') ? `<img src="${config.bubble_icon}" alt="Bubble Icon" style="width: 100%; height: 100%; object-fit: cover;">` : config.bubble_icon) : '💬';

    const sendLabel = config.send_button_label || '➢';

    widget.innerHTML = `
        <style>
          #glpi-helpxora-button { background-color: ${config.color_button_float} !important; width: ${config.bubble_size || 60}px !important; height: ${config.bubble_size || 60}px !important; font-size: ${Math.floor((config.bubble_size || 60) * 0.5)}px !important; }
          #glpi-helpxora-header { background-color: ${config.color_header} !important; }
          .helpxora-msg.user { background-color: ${config.color_user_bubble} !important; }
          .helpxora-options button { background-color: ${config.color_bot_buttons} !important; }
          .helpxora-options button:hover { background-color: ${config.color_hover} !important; }
        </style>
        <div id="glpi-helpxora-button">
            ${bubbleIcon}
        </div>
        <div id="glpi-helpxora-window" style="display: none;">
            <div id="glpi-helpxora-header">
                <div class="header-info">
                   <div class="avatar">${avatarImg}</div>
                   <span>${config.name}</span>
                </div>
                <button id="glpi-helpxora-close">✕</button>
            </div>
            <div id="glpi-helpxora-messages"></div>
            <div id="glpi-helpxora-input-area" style="display: none;">
                <div id="glpi-helpxora-live-errors" class="helpxora-live-errors" role="alert" aria-live="polite"></div>
                <div id="glpi-helpxora-file-preview" class="helpxora-file-preview-wrap"></div>
                <div class="helpxora-composer-main-row">
                    <label for="glpi-helpxora-file" id="glpi-helpxora-file-label" title="Adjuntar archivo">📎</label>
                    <input type="file" id="glpi-helpxora-file" style="display:none;" multiple>
                    <div class="helpxora-desc-send-flex">
                        <div id="glpi-helpxora-desc-field-wrap" class="helpxora-desc-field-wrap">
                            <input type="text" id="glpi-helpxora-text" maxlength="65535" placeholder="Escribe algo..." autocomplete="off">
                        </div>
                        <button type="button" id="glpi-helpxora-send" class="helpxora-send-btn">${sendLabel}</button>
                    </div>
                </div>
                <div class="helpxora-composer-footer">
                    <div id="glpi-helpxora-footer-status" class="helpxora-footer-status" role="status" aria-live="polite"></div>
                    <div id="glpi-helpxora-char-count" class="helpxora-char-count"></div>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(widget);

    document.getElementById('glpi-helpxora-button').addEventListener('click', openChat);
    document.getElementById('glpi-helpxora-close').addEventListener('click', closeChat);
    applyHelpxoraSendButtonState(false);
}

let isChatOpen = false;
let currentFlow = null;
let currentData = {};
let activeZoomModal = null;

function openChat() {
    document.getElementById('glpi-helpxora-window').style.display = 'flex';
    document.getElementById('glpi-helpxora-button').style.display = 'none';

    if (!isChatOpen) {
        isChatOpen = true;
        document.getElementById('glpi-helpxora-messages').innerHTML = '';
        showBotMessage(glpiHelpxoraConfig.welcome);
        showMenu();
    } else {
        resetFlow();
    }
}

function resetFlow() {
    resetComposerState();
    currentFlow = null;
    currentData = {};
    showMenu();
}

function closeChat() {
    resetChatSession();
    document.getElementById('glpi-helpxora-window').style.display = 'none';
    document.getElementById('glpi-helpxora-button').style.display = 'flex';
    isChatOpen = false;
}

function showBotMessage(text, isHtml = true) {
    const messagesDiv = document.getElementById('glpi-helpxora-messages');
    const msg = document.createElement('div');
    msg.className = 'helpxora-msg bot';
    msg.innerHTML = isHtml ? text : escapeHtml(text);
    messagesDiv.appendChild(msg);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

function showUserMessage(text) {
    const messagesDiv = document.getElementById('glpi-helpxora-messages');
    const msg = document.createElement('div');
    msg.className = 'helpxora-msg user';
    msg.innerText = text;
    messagesDiv.appendChild(msg);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

function showImage(url) {
    const safeUrl = sanitizeImageUrl(url);
    if (!safeUrl) return;
    const messagesDiv = document.getElementById('glpi-helpxora-messages');
    const msg = document.createElement('div');
    msg.className = 'helpxora-msg bot img-msg';
    msg.innerHTML = `<img src="${safeUrl}" class="helpxora-image-preview" alt="Imagen de consulta">`;
    const img = msg.querySelector('img');
    if (img) {
        img.addEventListener('click', () => openImageZoom(safeUrl));
    }
    messagesDiv.appendChild(msg);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

function showTypingIndicator() {
    const messagesDiv = document.getElementById('glpi-helpxora-messages');
    const indicator = document.createElement('div');
    indicator.id = 'glpi-helpxora-typing';
    indicator.className = 'helpxora-msg bot';
    indicator.innerHTML = '<div class="helpxora-typing-indicator"><span></span><span></span><span></span></div>';
    messagesDiv.appendChild(indicator);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

function removeTypingIndicator() {
    const indicator = document.getElementById('glpi-helpxora-typing');
    if (indicator) indicator.remove();
}

function showBotOptions(options, callback) {
    const messagesDiv = document.getElementById('glpi-helpxora-messages');
    const optionsContainer = document.createElement('div');
    optionsContainer.className = 'helpxora-options';

    options.forEach(opt => {
        const btn = document.createElement('button');
        btn.innerText = opt.text;
        btn.onclick = () => {
            optionsContainer.remove();
            showUserMessage(opt.text);
            callback(opt);
        };
        optionsContainer.appendChild(btn);
    });

    messagesDiv.appendChild(optionsContainer);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

function showMenu() {
    showBotMessage(glpiHelpxoraConfig.intro);

    fetch(CFG_GLPI.root_doc + '/plugins/helpxora/ajax/chat.php?action=get_menu')
        .then(res => res.json())
        .then(data => {
            showBotOptions(data.options, (selected) => {
                if (selected.id === 'menu_consultas') {
                    showConsultas();
                } else if (selected.id === 'menu_reqs') {
                    showRequerimientos();
                } else if (selected.id === 'login_required') {
                    showBotMessage("Por favor inicie sesión para generar requerimientos.");
                    setTimeout(showMenu, 1500);
                }
            });
        });
}

function showConsultas() {
    showBotMessage("¿Sobre qué tema tienes dudas?");
    fetch(CFG_GLPI.root_doc + '/plugins/helpxora/ajax/chat.php?action=get_consultas')
        .then(res => res.json())
        .then(data => {
            if (data.options.length === 0) {
                showBotMessage("No hay consultas configuradas en este momento.");
                setTimeout(resetFlow, 1500);
                return;
            }
            showBotOptions(data.options, (selected) => {
                showTypingIndicator();
                const formData = new FormData();
                formData.append('action', 'get_answer');
                formData.append('id', selected.id);
                fetch(CFG_GLPI.root_doc + '/plugins/helpxora/ajax/chat.php', { method: 'POST', headers: { 'X-Glpi-Csrf-Token': getAjaxCsrfToken() }, body: formData })
                    .then(res => res.json())
                    .then(ans => {
                        removeTypingIndicator();
                        if (ans && ans.answer) {
                            showBotMessage(ans.answer);
                            if (ans.images && ans.images.length) {
                                ans.images.forEach(url => showImage(url));
                            }
                        } else {
                            showBotMessage("No se encontró respuesta.");
                        }
                        setTimeout(resetFlow, 3000);
                    });
            });
        });
}

function showRequerimientos() {
    showBotMessage(glpiHelpxoraConfig.reason);
    fetch(CFG_GLPI.root_doc + '/plugins/helpxora/ajax/chat.php?action=get_reqs')
        .then(res => res.json())
        .then(data => {
            if (data.options.length === 0) {
                showBotMessage("No hay tipos de requerimientos configurados.");
                setTimeout(resetFlow, 1500);
                return;
            }
            showBotOptions(data.options, (selected) => {
                currentFlow = 'create_ticket';
                currentData.req_id = selected.id;
                currentData.helpxoraFileQueue = [];
                currentData.helpxoraAttachmentBanner = '';
                const attachMode = normalizeHelpxoraMode(selected.attachments_mode, 0);
                const descMode = normalizeHelpxoraMode(selected.description_mode, 1);
                currentData.req_rules = {
                    attachments_mode: attachMode,
                    max_files: Number(selected.max_files || 1),
                    allowed_extensions: String(selected.allowed_extensions || ""),
                    min_chars: Number(selected.min_chars ?? 10),
                    max_chars: Number(selected.max_chars || 500),
                    validation_regex: String(selected.validation_regex || ""),
                    restrict_gibberish: Number(selected.restrict_gibberish || 0),
                    description_mode: descMode
                };

                const rules = currentData.req_rules;
                const maxF = Math.min(10, Math.max(1, Number(rules.max_files || 1)));

                let msg = "";
                if (descMode === 0) {
                    msg = "No hace falta escribir descripción; pulse enviar cuando esté listo.";
                } else if (descMode === 1) {
                    msg = "Por favor, describe detalladamente el problema o solicitud.";
                } else {
                    msg = "Puede añadir una descripción o enviar solo con adjuntos; pulse enviar cuando esté listo.";
                }
                if (selected.custom_response && selected.custom_response.trim() !== '') {
                    msg = selected.custom_response;
                }
                if (attachMode === 1) {
                    msg += "<br><small class=\"helpxora-hint\">Debe adjuntar exactamente " + maxF + " archivo(s). Usa 📎.</small>";
                } else if (attachMode === 2) {
                    msg += "<br><small class=\"helpxora-hint\">Hasta " + maxF + " archivo(s) opcional(es). Usa 📎.</small>";
                }

                showBotMessage(msg);

                const inputArea = document.getElementById('glpi-helpxora-input-area');
                inputArea.style.display = 'block';

                const sendBtn = document.getElementById('glpi-helpxora-send');
                const textInput = document.getElementById('glpi-helpxora-text');
                const fileInput = document.getElementById('glpi-helpxora-file');
                const fileLabel = document.getElementById('glpi-helpxora-file-label');

                const newTa = textInput.cloneNode(true);
                textInput.parentNode.replaceChild(newTa, textInput);
                const newSendBtn = sendBtn.cloneNode(true);
                sendBtn.parentNode.replaceChild(newSendBtn, sendBtn);
                const newFileInput = fileInput.cloneNode(true);
                fileInput.parentNode.replaceChild(newFileInput, fileInput);

                const descFieldWrap = document.getElementById('glpi-helpxora-desc-field-wrap');
                if (descFieldWrap) {
                    descFieldWrap.style.display = descMode === 0 ? 'none' : '';
                }
                if (descMode === 0) {
                    newTa.value = '';
                }
                const mc = Number(rules.max_chars || 500);
                newTa.setAttribute('maxlength', mc > 0 ? String(mc) : '65535');

                if (attachMode === 1 || attachMode === 2) {
                    fileLabel.style.display = '';
                    newFileInput.multiple = maxF > 1;
                    const acceptVal = buildHelpxoraFileAcceptAttribute(rules.allowed_extensions);
                    if (acceptVal) {
                        newFileInput.setAttribute('accept', acceptVal);
                    } else {
                        newFileInput.removeAttribute('accept');
                    }
                } else {
                    fileLabel.style.display = 'none';
                    newFileInput.value = '';
                    newFileInput.removeAttribute('accept');
                }

                const preview = document.getElementById('glpi-helpxora-file-preview');
                if (preview) {
                    preview.textContent = '';
                    preview.style.display = 'none';
                }

                function runLiveValidation() {
                    updateTicketComposerUi(newTa, newFileInput, rules);
                }

                newTa.addEventListener('input', runLiveValidation);
                newFileInput.addEventListener('change', function () {
                    helpxoraAppendFilesFromInput(newFileInput, preview, rules);
                    runLiveValidation();
                });

                newSendBtn.addEventListener('click', handleTicketSubmit);
                newTa.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        handleTicketSubmit();
                    }
                });

                runLiveValidation();
            });
        });
}

function normalizeHelpxoraMode(value, defaultMode) {
    if (value === undefined || value === null || value === '') {
        return defaultMode;
    }
    const n = Number(value);
    if (Number.isNaN(n) || n < 0 || n > 2) {
        return defaultMode;
    }
    return n;
}

function buildHelpxoraFileAcceptAttribute(allowedCsv) {
    const raw = String(allowedCsv || "").trim();
    if (!raw) {
        return "";
    }
    const parts = raw.split(",").map(function (v) {
        return v.trim().toLowerCase();
    }).filter(Boolean);
    if (!parts.length) {
        return "";
    }
    return parts.map(function (ext) {
        const e = ext.startsWith(".") ? ext.slice(1) : ext;
        return e ? "." + e : "";
    }).filter(Boolean).join(",");
}

function helpxoraGetTicketFileQueue() {
    if (currentData && Array.isArray(currentData.helpxoraFileQueue)) {
        return currentData.helpxoraFileQueue;
    }
    return [];
}

function helpxoraRenderFileQueuePreview(previewEl, rules, rejectMsg) {
    if (!previewEl) {
        return;
    }
    if (currentData && arguments.length >= 3) {
        currentData.helpxoraAttachmentBanner = rejectMsg ? String(rejectMsg) : '';
    }
    const queue = helpxoraGetTicketFileQueue();
    previewEl.innerHTML = '';
    if (!queue.length) {
        previewEl.style.display = 'none';
        previewEl.classList.remove('helpxora-file-preview-wrap--visible');
        return;
    }
    previewEl.style.display = 'flex';
    previewEl.classList.add('helpxora-file-preview-wrap--visible');
    const n = queue.length;
    for (let idx = 0; idx < n; idx++) {
        const file = queue[idx];
        const span = document.createElement('span');
        span.className = 'helpxora-file-chip';
        span.appendChild(document.createTextNode('📎 ' + file.name + ' '));
        const rm = document.createElement('button');
        rm.type = 'button';
        rm.className = 'helpxora-preview-remove helpxora-chip-remove';
        rm.setAttribute('aria-label', 'Quitar archivo');
        rm.textContent = '\u00D7';
        rm.setAttribute('data-helpxora-q', String(idx));
        rm.addEventListener('click', function (ev) {
            const i = parseInt(ev.currentTarget.getAttribute('data-helpxora-q'), 10);
            const q = helpxoraGetTicketFileQueue();
            if (!Number.isNaN(i) && i >= 0 && i < q.length) {
                q.splice(i, 1);
            }
            helpxoraRenderFileQueuePreview(previewEl, rules, '');
            const ti = document.getElementById('glpi-helpxora-text');
            const fi = document.getElementById('glpi-helpxora-file');
            if (currentData && currentData.req_rules && ti && fi) {
                updateTicketComposerUi(ti, fi, currentData.req_rules);
            }
        });
        span.appendChild(rm);
        previewEl.appendChild(span);
    }
}

function helpxoraAppendFilesFromInput(fileInput, previewEl, rules) {
    if (!fileInput || !currentData) {
        return;
    }
    const maxF = Math.min(10, Math.max(1, Number(rules.max_files || 1)));
    if (!Array.isArray(currentData.helpxoraFileQueue)) {
        currentData.helpxoraFileQueue = [];
    }
    const queue = currentData.helpxoraFileQueue;
    const dt = fileInput.files;
    let rejectMsg = '';
    if (dt && dt.length) {
        for (let i = 0; i < dt.length; i++) {
            const f = dt[i];
            if (!extensionAllowedForRequirement(f.name, rules.allowed_extensions)) {
                rejectMsg = 'Extensión no permitida: ' + f.name;
                continue;
            }
            if (queue.length >= maxF) {
                rejectMsg = 'Máximo ' + maxF + ' archivo(s).';
                break;
            }
            const dup = queue.some(function (q) {
                return q.name === f.name && q.size === f.size;
            });
            if (!dup) {
                queue.push(f);
            }
        }
    }
    fileInput.value = '';
    helpxoraRenderFileQueuePreview(previewEl, rules, rejectMsg);
}

function applyHelpxoraSendButtonState(hasErrors) {
    const sendBtn = document.getElementById('glpi-helpxora-send');
    if (!sendBtn) {
        return;
    }
    const okBg = glpiHelpxoraConfig.color_send_button_bg || '#28a745';
    const okColor = glpiHelpxoraConfig.color_send_button || '#ffffff';
    const blockedBg = '#9e9e9e';
    const blockedColor = '#f8f9fa';
    if (hasErrors) {
        sendBtn.style.backgroundColor = blockedBg;
        sendBtn.style.color = blockedColor;
        sendBtn.classList.add('helpxora-send-blocked');
        sendBtn.classList.remove('helpxora-send-ready');
    } else {
        sendBtn.style.backgroundColor = okBg;
        sendBtn.style.color = okColor;
        sendBtn.classList.add('helpxora-send-ready');
        sendBtn.classList.remove('helpxora-send-blocked');
    }
}

function extensionAllowedForRequirement(filename, allowedCsv) {
    const raw = String(allowedCsv || "").trim();
    if (!raw) {
        return true;
    }
    const ext = filename.includes(".") ? filename.split(".").pop().toLowerCase() : "";
    const allowed = raw.split(",").map(v => v.trim().toLowerCase()).filter(Boolean);
    return ext !== "" && allowed.includes(ext);
}

function updateTicketComposerUi(textInput, fileInput, rules) {
    const errEl = document.getElementById('glpi-helpxora-live-errors');
    const cntEl = document.getElementById('glpi-helpxora-char-count');
    const text = textInput.value;
    const len = text.length;
    const trimmed = text.trim();
    const descMode = normalizeHelpxoraMode(rules.description_mode, 1);
    const attachMode = normalizeHelpxoraMode(rules.attachments_mode, 0);
    const minC = Number(rules.min_chars ?? 0);
    const maxC = Number(rules.max_chars || 500);
    const maxF = Math.min(10, Math.max(1, Number(rules.max_files || 1)));
    const fileCount = helpxoraGetTicketFileQueue().length;
    const invalidTextMsg = 'El texto no parece válido.';
    const errs = [];

    if (attachMode === 0 && descMode === 0) {
        errs.push('Este requerimiento no permite enviar respuesta (configure adjuntos o descripción).');
    }
    if (descMode === 1 && trimmed === '') {
        errs.push('La descripción es obligatoria.');
    }
    if (descMode === 0) {
        if (trimmed !== '' && containsSuspiciousSqlPattern(text)) {
            errs.push('Patrón no permitido.');
        }
    } else {
        if (len > maxC) {
            errs.push('Máximo ' + maxC + ' caracteres.');
        }
        if (rules.validation_regex && trimmed !== '' && !matchesCustomRegex(trimmed, rules.validation_regex)) {
            errs.push(invalidTextMsg);
        }
        if (Number(rules.restrict_gibberish) === 1 && trimmed !== '' && isLikelyGibberish(text)) {
            errs.push(invalidTextMsg);
        }
        if (trimmed !== '' && containsSuspiciousSqlPattern(text)) {
            errs.push('Patrón no permitido.');
        }
    }
    const footerStatusParts = [];
    let hasFooterStatusError = false;
    if (descMode !== 0 && trimmed !== '' && len < minC) {
        footerStatusParts.push('Mínimo ' + minC + ' caracteres.');
        hasFooterStatusError = true;
    }
    if (attachMode === 1 && fileCount !== maxF) {
        footerStatusParts.push('Debe adjuntar exactamente ' + maxF + ' archivo(s).');
        hasFooterStatusError = true;
    }
    const banner = (currentData && currentData.helpxoraAttachmentBanner) ? String(currentData.helpxoraAttachmentBanner).trim() : '';
    if (banner !== '') {
        footerStatusParts.push(banner);
        hasFooterStatusError = true;
    }
    const footerStatusEl = document.getElementById('glpi-helpxora-footer-status');
    if (footerStatusEl) {
        footerStatusEl.textContent = footerStatusParts.join(' ');
    }

    if (errEl) {
        errEl.textContent = errs.join(' ');
    }
    if (cntEl) {
        if (descMode === 0) {
            cntEl.textContent = '';
            cntEl.classList.remove('helpxora-char-count--invalid');
        } else {
            cntEl.textContent = len + ' / ' + maxC;
            const badLen = len > maxC || (trimmed !== '' && len < minC) || (descMode === 1 && trimmed === '') ||
                (rules.validation_regex && trimmed !== '' && !matchesCustomRegex(trimmed, rules.validation_regex)) ||
                (Number(rules.restrict_gibberish) === 1 && trimmed !== '' && isLikelyGibberish(text));
            cntEl.classList.toggle('helpxora-char-count--invalid', badLen);
        }
    }
    applyHelpxoraSendButtonState(errs.length > 0 || hasFooterStatusError);
}

function handleTicketSubmit() {
    const input     = document.getElementById('glpi-helpxora-text');
    const fileInput = document.getElementById('glpi-helpxora-file');
    const sendBtn   = document.getElementById('glpi-helpxora-send');
    const text      = input.value.trim();
    const rawText   = input.value;

    if (currentData.req_rules) {
        updateTicketComposerUi(input, fileInput, currentData.req_rules);
    }

    const liveErrEl = document.getElementById('glpi-helpxora-live-errors');
    const footerStatEl = document.getElementById('glpi-helpxora-footer-status');
    if ((liveErrEl && liveErrEl.textContent.trim() !== '') || (footerStatEl && footerStatEl.textContent.trim() !== '')) {
        return;
    }

    if (!validateTicketPayload(currentData, rawText, fileInput)) {
        return;
    }

    const rules = currentData.req_rules || {};
    const fileQueue = helpxoraGetTicketFileQueue();
    const nFiles = fileQueue.length;
    for (let i = 0; i < nFiles; i++) {
        const f = fileQueue[i];
        if (glpiHelpxoraConfig.max_upload_size && f.size > glpiHelpxoraConfig.max_upload_size) {
            showBotMessage(`❌ ${glpiHelpxoraConfig.error_msg}`);
            fileInput.value = "";
            const pEl = document.getElementById('glpi-helpxora-file-preview');
            if (pEl && currentData && currentData.req_rules) {
                currentData.helpxoraFileQueue = [];
                helpxoraRenderFileQueuePreview(pEl, currentData.req_rules, '');
            }
            setTimeout(resetFlow, 2000);
            return;
        }
        if (glpiHelpxoraConfig.allowed_extensions && normalizeHelpxoraMode(rules.attachments_mode, 0) === 0) {
            try {
                let patternStr = glpiHelpxoraConfig.allowed_extensions;
                if (patternStr.startsWith('/') && patternStr.endsWith('/i')) {
                    patternStr = patternStr.substring(1, patternStr.length - 2);
                }
                const regex = new RegExp(patternStr, 'i');
                if (!regex.test(f.name)) {
                    showBotMessage(`❌ ${glpiHelpxoraConfig.error_msg}`);
                    fileInput.value = "";
                    const pEl = document.getElementById('glpi-helpxora-file-preview');
                    if (pEl && currentData && currentData.req_rules) {
                        currentData.helpxoraFileQueue = [];
                        helpxoraRenderFileQueuePreview(pEl, currentData.req_rules, '');
                    }
                    setTimeout(resetFlow, 2000);
                    return;
                }
            } catch (e) {
                console.error("Regex parsing error", e);
            }
        }
    }

    showUserMessage(text || '(sin texto)');
    if (nFiles > 0) {
        let names = [];
        for (let j = 0; j < nFiles; j++) {
            names.push(escapeHtml(fileQueue[j].name));
        }
        showBotMessage(`📎 Archivo(s): <strong>${names.join(', ')}</strong>`);
    }

    input.value = '';
    const preview = document.getElementById('glpi-helpxora-file-preview');
    if (preview && currentData && currentData.req_rules) {
        currentData.helpxoraFileQueue = [];
        helpxoraRenderFileQueuePreview(preview, currentData.req_rules, '');
    }
    document.getElementById('glpi-helpxora-input-area').style.display = 'none';

    sendBtn.disabled = true;
    sendBtn.textContent = '⏳';
    showTypingIndicator();

    const formData = new FormData();
    formData.append('action', 'create_ticket');
    formData.append('req_id', currentData.req_id);
    formData.append('description', rawText.trim());
    for (let k = 0; k < nFiles; k++) {
        formData.append('files[]', fileQueue[k]);
    }

    fetch(CFG_GLPI.root_doc + '/plugins/helpxora/ajax/chat.php', { method: 'POST', headers: { 'X-Glpi-Csrf-Token': getAjaxCsrfToken() }, body: formData })
        .then(res => res.json())
        .then(data => {
            removeTypingIndicator();
            fileInput.value = "";
            sendBtn.disabled = false;
            sendBtn.textContent = glpiHelpxoraConfig.send_button_label || '➢';
            applyHelpxoraSendButtonState(false);
            if (data.status === 'success') {
                showBotMessage(`✅ ${glpiHelpxoraConfig.close} <br><small>Ticket ID: <strong>${data.ticket_id}</strong></small>`);
            } else if (data.status === 'error') {
                if (data.message) {
                    showBotMessage(`❌ ${data.message}`);
                } else {
                    showBotMessage(`❌ ${glpiHelpxoraConfig.error_msg}`);
                }
            } else {
                showBotMessage("❌ Ocurrió un error al crear el ticket.");
            }
            setTimeout(resetFlow, 4000);
        })
        .catch(() => {
            removeTypingIndicator();
            fileInput.value = "";
            sendBtn.disabled = false;
            sendBtn.textContent = glpiHelpxoraConfig.send_button_label || '➢';
            applyHelpxoraSendButtonState(false);
            showBotMessage("❌ Error de comunicación con el servidor.");
            setTimeout(resetFlow, 3000);
        });
}

function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function validateTicketPayload(data, rawText, fileInput) {
    if (!data || !Number.isInteger(Number(data.req_id)) || Number(data.req_id) <= 0) {
        showBotMessage("❌ El tipo de requerimiento seleccionado no es válido.");
        setTimeout(resetFlow, 2000);
        return false;
    }
    const rules = (data && data.req_rules) ? data.req_rules : {};
    const text = (rawText || "").trim();
    const len = (rawText || "").length;
    const descMode = normalizeHelpxoraMode(rules.description_mode, 1);
    const attachMode = normalizeHelpxoraMode(rules.attachments_mode, 0);
    const minChars = Number(rules.min_chars ?? 0);
    const maxChars = Number(rules.max_chars || 500);
    const maxF = Math.min(10, Math.max(1, Number(rules.max_files || 1)));

    if (descMode === 1 && text === '') {
        showBotMessage("❌ La descripción es obligatoria para este requerimiento.");
        return false;
    }
    if (descMode === 0) {
        if (text !== '' && containsSuspiciousSqlPattern(text)) {
            showBotMessage("❌ El texto contiene un patrón no permitido.");
            return false;
        }
    } else {
        if (text !== '' && len < minChars) {
            showBotMessage(`❌ La descripción debe tener al menos ${minChars} caracteres.`);
            return false;
        }
        if (len > maxChars) {
            showBotMessage(`❌ La descripción excede el máximo permitido (${maxChars} caracteres).`);
            return false;
        }
        if (rules.validation_regex && text !== '' && !matchesCustomRegex(text, rules.validation_regex)) {
            showBotMessage("❌ El texto no parece válido.");
            return false;
        }
        if (Number(rules.restrict_gibberish || 0) === 1 && text !== '' && isLikelyGibberish(text)) {
            showBotMessage("❌ El texto no parece válido.");
            return false;
        }
        if (text !== '' && containsSuspiciousSqlPattern(text)) {
            showBotMessage("❌ El texto contiene un patrón no permitido.");
            return false;
        }
    }

    const queue = (data && Array.isArray(data.helpxoraFileQueue)) ? data.helpxoraFileQueue : [];
    const n = queue.length;

    if (n > 0 && attachMode === 0) {
        showBotMessage("❌ Este requerimiento no permite adjuntar archivos.");
        return false;
    }
    if ((attachMode === 1 || attachMode === 2) && n > maxF) {
        showBotMessage(`❌ Demasiados archivos (máximo ${maxF}).`);
        return false;
    }
    if (attachMode === 1 && n !== maxF) {
        showBotMessage(`❌ Debe adjuntar exactamente ${maxF} archivo(s).`);
        return false;
    }
    for (let i = 0; i < n; i++) {
        if (rules.allowed_extensions && !isAllowedExtension(queue[i].name, rules.allowed_extensions)) {
            showBotMessage("❌ La extensión del archivo no está permitida para este requerimiento.");
            return false;
        }
    }

    return true;
}

function matchesCustomRegex(text, regexPattern) {
    const pattern = (regexPattern || "").trim();
    if (!pattern) {
        return true;
    }
    try {
        let regex;
        if (pattern.startsWith("/") && pattern.lastIndexOf("/") > 0) {
            const lastSlash = pattern.lastIndexOf("/");
            const body = pattern.slice(1, lastSlash);
            const flags = pattern.slice(lastSlash + 1);
            regex = new RegExp(body, flags);
        } else {
            regex = new RegExp(pattern);
        }
        return regex.test(text);
    } catch (e) {
        return true;
    }
}

function isLikelyGibberish(text) {
    const t = (text || "").trim();
    if (t === "") {
        return false;
    }
    const letters = t.replace(/[^a-zA-ZáéíóúüñÁÉÍÓÚÜÑ]/g, "");
    if (letters.length < 4) {
        return false;
    }
    const words = t.split(/[^\p{L}\p{N}]+/u).filter(Boolean);
    if (words.length < 2) {
        return true;
    }
    if (/asdf|qwer|zxcv|1234/i.test(t)) {
        return true;
    }
    if (/(.)\1{4,}/u.test(t)) {
        return true;
    }
    if (!/[aeiouáéíóúü]/i.test(letters)) {
        return true;
    }
    return false;
}

function isAllowedExtension(filename, allowedExtensions) {
    const raw = String(allowedExtensions || "").trim();
    if (!raw) {
        return true;
    }
    const fileExt = filename.includes(".") ? filename.split(".").pop().toLowerCase() : "";
    if (!fileExt) {
        return false;
    }
    const allowed = raw.split(",").map(v => v.trim().toLowerCase()).filter(Boolean);
    return allowed.includes(fileExt);
}

function containsSuspiciousSqlPattern(text) {
    if (!text || typeof text !== "string") {
        return false;
    }
    const patterns = [
        /\bunion\b\s+\bselect\b/i,
        /\bselect\b.+\bfrom\b/i,
        /\binsert\b\s+\binto\b/i,
        /\bupdate\b.+\bset\b/i,
        /\bdelete\b\s+\bfrom\b/i,
        /\bdrop\b\s+\btable\b/i,
        /\btruncate\b\s+\btable\b/i,
        /\bor\b\s+1\s*=\s*1\b/i,
        /--/,
        /\/\*/,
        /;\s*(select|insert|update|delete|drop|truncate)\b/i
    ];
    for (const pattern of patterns) {
        if (pattern.test(text)) {
            return true;
        }
    }
    return false;
}

function sanitizeImageUrl(url) {
    if (!url || typeof url !== "string") {
        return "";
    }
    const trimmed = url.trim();
    if (!trimmed) {
        return "";
    }
    if (trimmed.startsWith("http://") || trimmed.startsWith("https://") || trimmed.startsWith("/") || trimmed.startsWith("./") || trimmed.startsWith("../")) {
        return trimmed;
    }
    return "";
}

function openImageZoom(url) {
    closeImageZoom();
    const overlay = document.createElement("div");
    overlay.id = "glpi-helpxora-image-modal";
    overlay.innerHTML = `
        <div class="helpxora-image-modal-backdrop"></div>
        <button type="button" class="helpxora-image-modal-close" aria-label="Cerrar zoom">✕</button>
        <img src="${url}" alt="Imagen ampliada" class="helpxora-image-modal-content">
    `;
    overlay.addEventListener("click", function (e) {
        if (e.target === overlay || e.target.classList.contains("helpxora-image-modal-backdrop")) {
            closeImageZoom();
        }
    });
    const closeBtn = overlay.querySelector(".helpxora-image-modal-close");
    if (closeBtn) {
        closeBtn.addEventListener("click", closeImageZoom);
    }
    document.addEventListener("keydown", handleImageZoomEscape);
    document.body.appendChild(overlay);
    activeZoomModal = overlay;
}

function closeImageZoom() {
    if (activeZoomModal && activeZoomModal.parentNode) {
        activeZoomModal.parentNode.removeChild(activeZoomModal);
    }
    activeZoomModal = null;
    document.removeEventListener("keydown", handleImageZoomEscape);
}

function handleImageZoomEscape(event) {
    if (event.key === "Escape") {
        closeImageZoom();
    }
}

function resetComposerState() {
    const inputArea = document.getElementById('glpi-helpxora-input-area');
    if (inputArea) {
        inputArea.style.display = 'none';
    }
    const input = document.getElementById('glpi-helpxora-text');
    if (input) {
        input.value = '';
    }
    const fileInput = document.getElementById('glpi-helpxora-file');
    if (fileInput) {
        fileInput.value = '';
    }
    const preview = document.getElementById('glpi-helpxora-file-preview');
    if (preview) {
        preview.textContent = '';
        preview.style.display = 'none';
    }
    const errEl = document.getElementById('glpi-helpxora-live-errors');
    if (errEl) {
        errEl.textContent = '';
    }
    const footerStat = document.getElementById('glpi-helpxora-footer-status');
    if (footerStat) {
        footerStat.textContent = '';
    }
    const cntEl = document.getElementById('glpi-helpxora-char-count');
    if (cntEl) {
        cntEl.textContent = '';
        cntEl.classList.remove('helpxora-char-count--invalid');
    }
    const sendBtn = document.getElementById('glpi-helpxora-send');
    if (sendBtn) {
        sendBtn.disabled = false;
        sendBtn.textContent = glpiHelpxoraConfig.send_button_label || '➢';
    }
    if (typeof currentData !== 'undefined' && currentData) {
        currentData.helpxoraFileQueue = [];
        currentData.helpxoraAttachmentBanner = '';
    }
}

function resetChatSession() {
    const messagesDiv = document.getElementById('glpi-helpxora-messages');
    if (messagesDiv) {
        messagesDiv.innerHTML = '';
    }
    removeTypingIndicator();
    closeImageZoom();
    resetComposerState();
    currentFlow = null;
    currentData = {};
}
