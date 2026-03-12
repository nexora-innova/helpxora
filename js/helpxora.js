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
    const sendBg    = config.color_send_button_bg || '#28a745';
    const sendColor = config.color_send_button || '#ffffff';

    widget.innerHTML = `
        <style>
          #glpi-helpxora-button { background-color: ${config.color_button_float} !important; width: ${config.bubble_size || 60}px !important; height: ${config.bubble_size || 60}px !important; font-size: ${Math.floor((config.bubble_size || 60) * 0.5)}px !important; }
          #glpi-helpxora-header { background-color: ${config.color_header} !important; }
          .helpxora-msg.user { background-color: ${config.color_user_bubble} !important; }
          .helpxora-options button { background-color: ${config.color_bot_buttons} !important; }
          .helpxora-options button:hover { background-color: ${config.color_hover} !important; }
          #glpi-helpxora-send { background-color: ${sendBg} !important; color: ${sendColor} !important; }
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
                <span id="glpi-helpxora-file-preview"></span>
                <div class="helpxora-input-row">
                    <input type="text" id="glpi-helpxora-text" placeholder="Escribe algo...">
                    <label for="glpi-helpxora-file" id="glpi-helpxora-file-label" title="Adjuntar archivo">📎</label>
                    <input type="file" id="glpi-helpxora-file" style="display:none;">
                    <button id="glpi-helpxora-send">${sendLabel}</button>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(widget);

    document.getElementById('glpi-helpxora-button').addEventListener('click', openChat);
    document.getElementById('glpi-helpxora-close').addEventListener('click', closeChat);
}

let isChatOpen = false;
let currentFlow = null;
let currentData = {};

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
    const inputArea = document.getElementById('glpi-helpxora-input-area');
    if (inputArea) inputArea.style.display = 'none';
    const preview = document.getElementById('glpi-helpxora-file-preview');
    if (preview) { preview.textContent = ''; preview.style.display = 'none'; }
    currentFlow = null;
    currentData = {};
    showMenu();
}

function closeChat() {
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
    if (!url) return;
    const messagesDiv = document.getElementById('glpi-helpxora-messages');
    const msg = document.createElement('div');
    msg.className = 'helpxora-msg bot img-msg';
    msg.innerHTML = `<img src="${url}" style="max-width: 100%; border-radius: 5px;">`;
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

                let msg = "Por favor, describe detalladamente el problema o solicitud.";
                if (selected.custom_response && selected.custom_response.trim() !== '') {
                    msg = selected.custom_response;
                }
                msg += "<br><small style='opacity:0.8;'>Puedes adjuntar un archivo usando el ícono 📎</small>";

                showBotMessage(msg);

                const inputArea = document.getElementById('glpi-helpxora-input-area');
                inputArea.style.display = 'block';

                const preview = document.getElementById('glpi-helpxora-file-preview');
                if (preview) { preview.textContent = ''; preview.style.display = 'none'; }

                const sendBtn   = document.getElementById('glpi-helpxora-send');
                const textInput = document.getElementById('glpi-helpxora-text');
                const fileInput = document.getElementById('glpi-helpxora-file');

                const newInput = textInput.cloneNode(true);
                textInput.parentNode.replaceChild(newInput, textInput);
                const newSendBtn = sendBtn.cloneNode(true);
                sendBtn.parentNode.replaceChild(newSendBtn, sendBtn);
                const newFileInput = fileInput.cloneNode(true);
                fileInput.parentNode.replaceChild(newFileInput, fileInput);

                newFileInput.addEventListener('change', function () {
                    const pEl = document.getElementById('glpi-helpxora-file-preview');
                    if (newFileInput.files.length > 0) {
                        pEl.innerHTML = '📎 ' + escapeHtml(newFileInput.files[0].name) + ' <span class="helpxora-preview-remove" title="Quitar archivo">✕</span>';
                        pEl.style.display = 'inline-flex';
                        pEl.querySelector('.helpxora-preview-remove').addEventListener('click', function (e) {
                            e.stopPropagation();
                            newFileInput.value = '';
                            pEl.textContent = '';
                            pEl.style.display = 'none';
                        });
                    } else {
                        pEl.textContent = '';
                        pEl.style.display = 'none';
                    }
                });

                newSendBtn.addEventListener('click', handleTicketSubmit);
                newInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') handleTicketSubmit();
                });
            });
        });
}

function handleTicketSubmit() {
    const input     = document.getElementById('glpi-helpxora-text');
    const fileInput = document.getElementById('glpi-helpxora-file');
    const sendBtn   = document.getElementById('glpi-helpxora-send');
    const text      = input.value.trim();

    if (!text) return;

    if (fileInput.files.length > 0) {
        const file = fileInput.files[0];

        if (glpiHelpxoraConfig.max_upload_size && file.size > glpiHelpxoraConfig.max_upload_size) {
            showBotMessage(`❌ ${glpiHelpxoraConfig.error_msg}`);
            fileInput.value = "";
            const pEl = document.getElementById('glpi-helpxora-file-preview');
            if (pEl) { pEl.textContent = ''; pEl.style.display = 'none'; }
            setTimeout(resetFlow, 2000);
            return;
        }

        if (glpiHelpxoraConfig.allowed_extensions) {
            try {
                let patternStr = glpiHelpxoraConfig.allowed_extensions;
                if (patternStr.startsWith('/') && patternStr.endsWith('/i')) {
                    patternStr = patternStr.substring(1, patternStr.length - 2);
                }
                const regex = new RegExp(patternStr, 'i');
                if (!regex.test(file.name)) {
                    showBotMessage(`❌ ${glpiHelpxoraConfig.error_msg}`);
                    fileInput.value = "";
                    const pEl = document.getElementById('glpi-helpxora-file-preview');
                    if (pEl) { pEl.textContent = ''; pEl.style.display = 'none'; }
                    setTimeout(resetFlow, 2000);
                    return;
                }
            } catch (e) {
                console.error("Regex parsing error", e);
            }
        }
    }

    showUserMessage(text);
    if (fileInput.files.length > 0) {
        showBotMessage(`📎 Archivo adjunto: <strong>${escapeHtml(fileInput.files[0].name)}</strong>`);
    }

    input.value = '';
    const preview = document.getElementById('glpi-helpxora-file-preview');
    if (preview) { preview.textContent = ''; preview.style.display = 'none'; }
    document.getElementById('glpi-helpxora-input-area').style.display = 'none';

    sendBtn.disabled = true;
    sendBtn.textContent = '⏳';
    showTypingIndicator();

    const formData = new FormData();
    formData.append('action', 'create_ticket');
    formData.append('req_id', currentData.req_id);
    formData.append('description', text);
    if (fileInput.files.length > 0) {
        formData.append('file', fileInput.files[0]);
    }

    fetch(CFG_GLPI.root_doc + '/plugins/helpxora/ajax/chat.php', { method: 'POST', headers: { 'X-Glpi-Csrf-Token': getAjaxCsrfToken() }, body: formData })
        .then(res => res.json())
        .then(data => {
            removeTypingIndicator();
            fileInput.value = "";
            sendBtn.disabled = false;
            sendBtn.textContent = glpiHelpxoraConfig.send_button_label || '➢';
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

(function () {
    var HELPXORA_EDITOR_IDS = ['helpxora_welcome_message', 'helpxora_intro_message', 'helpxora_reason_message', 'helpxora_close_message', 'helpxora_req_custom_response_modal', 'helpxora_consulta_answer_modal'];

    function runScriptsInElement(container) {
        if (!container || !container.querySelector) return;
        var scripts = container.querySelectorAll('script');
        for (var i = 0; i < scripts.length; i++) {
            var s = (scripts[i].textContent || scripts[i].innerText || '').trim();
            if (s) {
                if (typeof window.$ !== 'undefined' && window.$.globalEval) {
                    window.$.globalEval(s);
                } else {
                    try { (function () { eval(s); })(); } catch (e) { console.warn('Helpxora script eval:', e); }
                }
                scripts[i].parentNode && scripts[i].parentNode.removeChild(scripts[i]);
            }
        }
    }

    function runWhenTinyMCEReady(container, maxWaitMs) {
        maxWaitMs = maxWaitMs || 8000;
        var start = Date.now();
        function tryRun() {
            if (typeof window.tinyMCE !== 'undefined') {
                runScriptsInElement(container);
                return;
            }
            if (Date.now() - start < maxWaitMs) {
                setTimeout(tryRun, 120);
            }
        }
        tryRun();
    }

    function containerHasHelpxoraEditors(container) {
        if (!container || !container.querySelector) return false;
        for (var i = 0; i < HELPXORA_EDITOR_IDS.length; i++) {
            if (container.querySelector('#' + HELPXORA_EDITOR_IDS[i])) return true;
        }
        return false;
    }

    function initHelpxoraConfigEditors(container) {
        container = container || document;
        if (!containerHasHelpxoraEditors(container)) return;
        runWhenTinyMCEReady(container);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            if (document.querySelector('main')) {
                document.querySelector('main').addEventListener('glpi.tab.loaded', function () {
                    var active = document.querySelector('main .tab-content .tab-pane.active');
                    if (active) runWhenTinyMCEReady(active);
                });
            }
            var main = document.querySelector('main');
            if (main && typeof MutationObserver !== 'undefined') {
                var observer = new MutationObserver(function (mutations) {
                    for (var m = 0; m < mutations.length; m++) {
                        var nodes = mutations[m].addedNodes;
                        for (var n = 0; n < nodes.length; n++) {
                            var node = nodes[n];
                            if (node.nodeType === 1) {
                                if (containerHasHelpxoraEditors(node)) {
                                    runWhenTinyMCEReady(node);
                                    return;
                                }
                                if (node.querySelector && containerHasHelpxoraEditors(node)) {
                                    runWhenTinyMCEReady(node);
                                    return;
                                }
                            }
                        }
                    }
                });
                observer.observe(main, { childList: true, subtree: true });
            }
            setTimeout(function () { initHelpxoraConfigEditors(document.body); }, 300);
        });
    } else {
        if (document.querySelector('main')) {
            document.querySelector('main').addEventListener('glpi.tab.loaded', function () {
                var active = document.querySelector('main .tab-content .tab-pane.active');
                if (active) runWhenTinyMCEReady(active);
            });
        }
        var main = document.querySelector('main');
        if (main && typeof MutationObserver !== 'undefined') {
            var observer = new MutationObserver(function (mutations) {
                for (var m = 0; m < mutations.length; m++) {
                    var nodes = mutations[m].addedNodes;
                    for (var n = 0; n < nodes.length; n++) {
                        var node = nodes[n];
                        if (node.nodeType === 1) {
                            if (containerHasHelpxoraEditors(node)) {
                                runWhenTinyMCEReady(node);
                                return;
                            }
                            if (node.querySelector && containerHasHelpxoraEditors(node)) {
                                runWhenTinyMCEReady(node);
                                return;
                            }
                        }
                    }
                }
            });
            observer.observe(main, { childList: true, subtree: true });
        }
        setTimeout(function () { initHelpxoraConfigEditors(document.body); }, 300);
    }
})();
