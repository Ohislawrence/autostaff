/**
 * AI Employee — Embeddable Chat Widget
 * 
 * Usage: <script src="https://your-domain.com/widget/chat-widget.js" data-employee="UUID"></script>
 * 
 * This script creates a chat bubble and chat window on the host page.
 * No API keys are exposed — all communication goes through the platform's public chat endpoint.
 */

(function () {
    'use strict';

    // Get configuration from data attributes
    const script = document.currentScript;
    const employeeUuid = script.getAttribute('data-employee');
    const apiBase = script.getAttribute('data-api') || window.location.origin;
    const primaryColor = script.getAttribute('data-color') || '#4F46E5';
    const greeting = script.getAttribute('data-greeting') || '👋 Hi! How can we help you today?';
    const position = script.getAttribute('data-position') || 'bottom-right';

    if (!employeeUuid) {
        console.error('AI Employee Widget: data-employee attribute is required.');
        return;
    }

    const CHAT_API = `${apiBase}/chat/${employeeUuid}/message`;

    // Persist the session so a visitor's chat continues across page refreshes
    // (until it expires). TTL in minutes via data-session-ttl (default 60).
    const storageKey = 'nomdal_chat_' + employeeUuid.replace(/[^a-zA-Z0-9_-]/g, '');
    const sessionTtlMs = (parseInt(script.getAttribute('data-session-ttl') || '60', 10) || 60) * 60 * 1000;

    const store = {
        get(key) { try { return localStorage.getItem(key); } catch (e) { return null; } },
        set(key, value) { try { localStorage.setItem(key, value); } catch (e) {} },
        remove(key) { try { localStorage.removeItem(key); } catch (e) {} },
    };

    let sessionId = store.get(storageKey + '_id');
    const lastSeen = parseInt(store.get(storageKey + '_ts') || '0', 10);
    if (!sessionId || (Date.now() - lastSeen) > sessionTtlMs) {
        sessionId = 'wc_' + Math.random().toString(36).substring(2, 15) + Date.now().toString(36);
        store.set(storageKey + '_id', sessionId);
        store.remove(storageKey + '_history');
    }
    store.set(storageKey + '_ts', String(Date.now()));

    let history = [];
    try { history = JSON.parse(store.get(storageKey + '_history') || '[]'); } catch (e) { history = []; }

    // Escape HTML first (XSS-safe), then render a small subset of Markdown:
    // bold, italic, inline code, links, numbered + bulleted lists, line breaks.
    function escapeHtml(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatMessage(text) {
        let escaped = escapeHtml(text ?? '');
        // The AI often emits bullet lists inline ("item - item - item") instead
        // of on separate lines. Convert those " - " separators to newline bullets
        // so the line-based list detection below renders them as proper <ul><li>.
        escaped = escaped.replace(/ - /g, '\n- ');
        const lines = escaped.split('\n');
        let html = '';
        let inUl = false;
        let inOl = false;

        const closeLists = () => {
            if (inOl) { html += '</ol>'; inOl = false; }
            if (inUl) { html += '</ul>'; inUl = false; }
        };

        const inline = (s) => s
            .replace(/`([^`\n]+)`/g, '<code>$1</code>')
            .replace(/\*\*([^*\n]+)\*\*/g, '<strong>$1</strong>')
            .replace(/(^|[^*])\*([^*\n]+)\*(?!\*)/g, '$1<em>$2</em>')
            .replace(/\[([^\]]+)\]\((https?:\/\/[^)\s]+)\)/g, '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>');

        for (const line of lines) {
            const ol = line.match(/^\s*\d+\.\s+(.*)$/);
            const ul = line.match(/^\s*[-*]\s+(.*)$/);

            if (ol) {
                if (inUl) { html += '</ul>'; inUl = false; }
                if (!inOl) { html += '<ol>'; inOl = true; }
                html += '<li>' + inline(ol[1]) + '</li>';
                continue;
            }
            if (ul) {
                if (inOl) { html += '</ol>'; inOl = false; }
                if (!inUl) { html += '<ul>'; inUl = true; }
                html += '<li>' + inline(ul[1]) + '</li>';
                continue;
            }

            closeLists();
            html += (line.trim() === '' ? '' : inline(line)) + '<br>';
        }
        closeLists();

        return html.replace(/(<br>)+$/, '');
    }

    // Create widget styles
    const styles = `
        .ai-widget-bubble {
            position: fixed;
            ${position === 'bottom-left' ? 'left: 20px' : 'right: 20px'};
            bottom: 20px;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: ${primaryColor};
            color: white;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 999999;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .ai-widget-bubble:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(0,0,0,0.2);
        }
        .ai-widget-window {
            position: fixed;
            ${position === 'bottom-left' ? 'left: 20px' : 'right: 20px'};
            bottom: 90px;
            width: 380px;
            max-width: calc(100vw - 40px);
            height: 520px;
            max-height: calc(100vh - 120px);
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12);
            z-index: 999998;
            display: none;
            flex-direction: column;
            overflow: hidden;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .ai-widget-window.open { display: flex; }
        .ai-widget-header {
            background: ${primaryColor};
            color: white;
            padding: 16px;
            font-weight: 600;
            font-size: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .ai-widget-close {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 20px;
            padding: 0 4px;
            line-height: 1;
            opacity: 0.8;
        }
        .ai-widget-close:hover { opacity: 1; }
        .ai-widget-messages {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            background: #f9fafb;
        }
        .ai-widget-message {
            max-width: 85%;
            padding: 10px 14px;
            border-radius: 12px;
            font-size: 14px;
            line-height: 1.5;
            word-wrap: break-word;
        }
        .ai-widget-message.bot {
            background: white;
            border: 1px solid #e5e7eb;
            align-self: flex-start;
            border-bottom-left-radius: 4px;
        }
        .ai-widget-message.user {
            background: ${primaryColor};
            color: white;
            align-self: flex-end;
            border-bottom-right-radius: 4px;
        }
        .ai-widget-message.human {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            align-self: flex-start;
            border-bottom-left-radius: 4px;
        }
        .ai-widget-message strong { font-weight: 600; }
        .ai-widget-message em { font-style: italic; }
        .ai-widget-message code { background: rgba(0,0,0,0.06); padding: 1px 4px; border-radius: 4px; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 12px; }
        .ai-widget-message a { color: ${primaryColor}; text-decoration: underline; }
        .ai-widget-message ul, .ai-widget-message ol { margin: 4px 0; padding-left: 18px; }
        .ai-widget-message li { margin: 2px 0; }
        .ai-widget-input-area {
            padding: 12px 16px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            gap: 8px;
            background: white;
        }
        .ai-widget-input {
            flex: 1;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 14px;
            outline: none;
            font-family: inherit;
        }
        .ai-widget-input:focus { border-color: ${primaryColor}; }
        .ai-widget-send {
            background: ${primaryColor};
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 16px;
            cursor: pointer;
            font-weight: 500;
            font-size: 14px;
            font-family: inherit;
        }
        .ai-widget-send:disabled { opacity: 0.5; cursor: not-allowed; }
        .ai-widget-typing {
            align-self: flex-start;
            padding: 8px 14px;
            color: #9ca3af;
            font-size: 13px;
            font-style: italic;
        }
        .ai-widget-powered {
            text-align: center;
            padding: 6px;
            font-size: 11px;
            color: #9ca3af;
            background: #f9fafb;
            border-top: 1px solid #f3f4f6;
        }
        .ai-widget-powered a { color: #6b7280; text-decoration: none; }
    `;

    // Inject styles
    const styleEl = document.createElement('style');
    styleEl.textContent = styles;
    document.head.appendChild(styleEl);

    // Create DOM elements
    const bubble = document.createElement('button');
    bubble.className = 'ai-widget-bubble';
    bubble.innerHTML = '💬';
    bubble.setAttribute('aria-label', 'Open chat');

    const widget = document.createElement('div');
    widget.className = 'ai-widget-window';
    widget.innerHTML = `
        <div class="ai-widget-header">
            <span>💬 Chat with us</span>
            <button class="ai-widget-close" aria-label="Close chat">×</button>
        </div>
        <div class="ai-widget-messages">
            <div class="ai-widget-message bot">${formatMessage(greeting)}</div>
        </div>
        <div class="ai-widget-powered">
            Powered by <a href="https://nomdal.com" target="_blank">AI Employee</a>
        </div>
        <div class="ai-widget-input-area">
            <input type="text" class="ai-widget-input" placeholder="Type your message..." />
            <button class="ai-widget-send">Send</button>
        </div>
    `;

    document.body.appendChild(bubble);
    document.body.appendChild(widget);

    const messagesEl = widget.querySelector('.ai-widget-messages');
    const inputEl = widget.querySelector('.ai-widget-input');
    const sendBtn = widget.querySelector('.ai-widget-send');
    const closeBtn = widget.querySelector('.ai-widget-close');

    // Restore any messages from the persisted session (after a page refresh).
    for (const m of history) {
        const msg = document.createElement('div');
        msg.className = `ai-widget-message ${m.type}`;
        msg.innerHTML = formatMessage(m.text);
        messagesEl.appendChild(msg);
    }
    if (history.length) {
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    let isOpen = false;
    let conversationId = store.get(storageKey + '_conversation') || null;
    let lastPolledMessageId = parseInt(store.get(storageKey + '_last_msg') || '0', 10);
    let pollTimer = null;

    function toggleWidget() {
        isOpen = !isOpen;
        widget.classList.toggle('open', isOpen);
        if (isOpen) {
            bubble.style.display = 'none';
            inputEl.focus();
        } else {
            bubble.style.display = 'flex';
        }
    }

    function addMessage(text, type) {
        const msg = document.createElement('div');
        msg.className = `ai-widget-message ${type}`;
        msg.innerHTML = formatMessage(text);
        messagesEl.appendChild(msg);
        messagesEl.scrollTop = messagesEl.scrollHeight;

        history.push({ text: text, type: type });
        if (history.length > 100) history = history.slice(-100);
        store.set(storageKey + '_history', JSON.stringify(history));
    }

    function showTyping() {
        const typing = document.createElement('div');
        typing.className = 'ai-widget-typing';
        typing.textContent = 'Typing...';
        typing.id = 'typing-indicator';
        messagesEl.appendChild(typing);
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function hideTyping() {
        const typing = document.getElementById('typing-indicator');
        if (typing) typing.remove();
    }

    async function pollConversation() {
        if (!conversationId) return;
        try {
            const res = await fetch(`${apiBase}/chat/${employeeUuid}/conversation/${conversationId}/poll?after_id=${lastPolledMessageId}`);
            const data = await res.json();
            if (!data || !data.success) return;
            for (const m of (data.messages || [])) {
                if (m.type === 'human_response') {
                    addMessage(m.content, 'human');
                    lastPolledMessageId = m.id;
                    store.set(storageKey + '_last_msg', String(lastPolledMessageId));
                }
            }
            if (data.status === 'resolved' || data.status === 'closed') {
                stopPolling();
            }
        } catch (e) {
            // ignore transient polling errors
        }
    }

    function startPolling() {
        if (pollTimer) return;
        pollTimer = setInterval(pollConversation, 3000);
        pollConversation();
    }

    function stopPolling() {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    }

    // Resume a handed-off conversation after a page refresh.
    if (conversationId) {
        startPolling();
    }

    async function sendMessage() {
        const message = inputEl.value.trim();
        if (!message) return;

        addMessage(message, 'user');
        inputEl.value = '';
        sendBtn.disabled = true;
        showTyping();

        try {
            const response = await fetch(CHAT_API, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    message: message,
                    channel: 'web_chat',
                    channel_conversation_id: sessionId,
                }),
            });

            const data = await response.json();
            hideTyping();

            if (data.success) {
                addMessage(data.response, 'bot');
                if (data.conversation_id) {
                    conversationId = data.conversation_id;
                    store.set(storageKey + '_conversation', conversationId);
                }
                if (data.escalated || data.human_handled) {
                    startPolling();
                }
            } else {
                addMessage('Sorry, I encountered an error. Please try again or contact our team directly.', 'bot');
            }
        } catch (err) {
            hideTyping();
            addMessage('Sorry, I could not connect to our servers. Please try again later.', 'bot');
        }

        sendBtn.disabled = false;
        inputEl.focus();
    }

    // Event listeners
    bubble.addEventListener('click', toggleWidget);
    closeBtn.addEventListener('click', toggleWidget);
    sendBtn.addEventListener('click', sendMessage);
    inputEl.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') sendMessage();
    });

})();