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

    // Generate a session ID
    const sessionId = 'wc_' + Math.random().toString(36).substring(2, 15);

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
            <div class="ai-widget-message bot">${greeting}</div>
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

    let isOpen = false;

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
        msg.textContent = text;
        messagesEl.appendChild(msg);
        messagesEl.scrollTop = messagesEl.scrollHeight;
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