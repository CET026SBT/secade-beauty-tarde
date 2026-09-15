(function () {
    const launcher = document.querySelector('[data-chatbot-launcher]');
    const panel = document.querySelector('[data-chatbot-panel]');
    const form = document.querySelector('[data-chatbot-form]');
    const input = document.querySelector('[data-chatbot-input]');
    const messages = document.querySelector('[data-chatbot-messages]');

    if (!launcher || !panel || !form || !input || !messages) {
        return;
    }

    function addMessage(text, type) {
        const item = document.createElement('div');
        item.className = 'chatbot-message chatbot-message-' + type;
        item.textContent = text;
        messages.appendChild(item);
        messages.scrollTop = messages.scrollHeight;
    }

    launcher.addEventListener('click', function () {
        panel.classList.toggle('chatbot-open');
        input.focus();
    });

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        const text = input.value.trim();
        if (!text) {
            return;
        }

        addMessage(text, 'user');
        input.value = '';
        input.disabled = true;

        try {
            const response = await fetch('chatbot.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: text })
            });
            const data = await response.json();
            addMessage(data.reply || 'Nao consegui responder agora.', 'bot');
        } catch (error) {
            addMessage('Nao consegui ligar ao assistente agora.', 'bot');
        } finally {
            input.disabled = false;
            input.focus();
        }
    });
}());
