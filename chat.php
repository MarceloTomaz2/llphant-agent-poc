<?php
session_start();

require __DIR__ . '/vendor/autoload.php';

use SearchAgent\AI\Agent\AgentRunner;
use LLPhant\Chat\Message;

$action = $_GET['action'] ?? 'view';

// Limpa o histórico da sessão
if ($action === 'clear') {
    $_SESSION['chat_history'] = [];
    header('Location: chat.php');
    exit;
}

// Endpoint SSE
if ($action === 'stream') {
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    // Desabilita o output buffering para garantir que o flush() funcione corretamente
    while (ob_get_level() > 0) {
        ob_end_flush();
    }

    $message = $_GET['message'] ?? '';
    
    if (empty($message)) {
        echo "data: Mensagem vazia\n\n";
        flush();
        exit;
    }

    echo "event: status\n";
    echo "data: Pensando e executando ferramentas...\n\n";
    flush();

    // Recupera o histórico da sessão
    $historyData = $_SESSION['chat_history'] ?? [];
    
    // Reconstrói o array de objetos Message
    $historico = [];
    foreach ($historyData as $msgData) {
        $msg = new Message();
        $msg->role = $msgData['role'];
        $msg->content = $msgData['content'];
        $historico[] = $msg;
    }

    $runner = new AgentRunner([
        'maxIterations' => 15,
        'temperature' => 0.7,
    ]);

    // Injeta o histórico
    if (!empty($historico)) {
        $runner->setMessages($historico);
    }

    // Executa o agente (isso pode demorar alguns segundos)
    $result = $runner->run($message);

    // Salva o novo histórico na sessão
    $newHistory = [];
    foreach ($runner->getMessages() as $msg) {
        // Ignora mensagens de uso de ferramentas para evitar erros de validação no histórico futuro da OpenAI
        if ($msg->role === 'tool' || !empty($msg->toolCalls)) {
            continue;
        }
        $newHistory[] = [
            'role' => $msg->role,
            'content' => $msg->content
        ];
    }
    $_SESSION['chat_history'] = $newHistory;

    // Envia o resultado pro cliente
    // O SSE requer que cada linha seja precedida por "data: "
    $lines = explode("\n", $result);
    foreach ($lines as $line) {
        echo "data: " . $line . "\n";
    }
    echo "\n\n";
    
    // Sinaliza o fim da transmissão
    echo "event: end\n";
    echo "data: done\n\n";
    flush();
    exit;
}

// Renderização do HTML Interface
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Agent Chat SSE</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #eef2f5; display: flex; flex-direction: column; align-items: center; margin: 0; padding: 20px; height: 100vh; box-sizing: border-box;}
        .chat-container { width: 100%; max-width: 800px; background: white; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.1); display: flex; flex-direction: column; flex: 1; overflow: hidden; }
        .chat-header { background: #2563eb; color: white; padding: 20px; text-align: center; position: relative; font-size: 1.2rem; font-weight: bold;}
        .clear-btn { position: absolute; right: 20px; top: 20px; color: white; text-decoration: none; font-size: 0.9rem; background: rgba(255,255,255,0.2); padding: 6px 12px; border-radius: 6px; transition: background 0.2s;}
        .clear-btn:hover { background: rgba(255,255,255,0.3); }
        .chat-messages { flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 15px; }
        .message { padding: 12px 16px; border-radius: 12px; max-width: 85%; line-height: 1.5; word-wrap: break-word; font-size: 0.95rem;}
        .user { background: #2563eb; color: white; align-self: flex-end; border-bottom-right-radius: 2px; }
        .assistant { background: #f1f5f9; color: #1e293b; align-self: flex-start; border-bottom-left-radius: 2px; border: 1px solid #e2e8f0; }
        .status { align-self: center; font-size: 0.85rem; color: #64748b; font-style: italic; background: #f8fafc; padding: 4px 12px; border-radius: 12px;}
        .chat-input { display: flex; padding: 20px; border-top: 1px solid #e2e8f0; background: #f8fafc; }
        .chat-input input { flex: 1; padding: 12px 16px; border: 1px solid #cbd5e1; border-radius: 8px; outline: none; font-size: 1rem; transition: border-color 0.2s;}
        .chat-input input:focus { border-color: #2563eb; }
        .chat-input button { background: #2563eb; color: white; border: none; padding: 12px 24px; margin-left: 12px; border-radius: 8px; cursor: pointer; font-size: 1rem; font-weight: 500; transition: background 0.2s;}
        .chat-input button:hover { background: #1d4ed8; }
        .chat-input button:disabled { background: #94a3b8; cursor: not-allowed; }
    </style>
</head>
<body>

<div class="chat-container">
    <div class="chat-header">
        Agent Chat (Protocolo SSE)
        <a href="?action=clear" class="clear-btn">Limpar Histórico</a>
    </div>
    <div class="chat-messages" id="messages">
        <?php
            $historyData = $_SESSION['chat_history'] ?? [];
            foreach ($historyData as $msg) {
                if ($msg['role'] === 'system' || $msg['role'] === 'tool') continue;
                $class = $msg['role'] === 'user' ? 'user' : 'assistant';
                // Preserva quebras de linha no HTML
                $content = nl2br(htmlspecialchars($msg['content'] ?? ''));
                echo "<div class='message {$class}'>{$content}</div>";
            }
        ?>
    </div>
    <div class="chat-input">
        <input type="text" id="prompt" placeholder="Digite sua mensagem para o agente..." onkeypress="if(event.key === 'Enter') sendMessage()">
        <button id="sendBtn" onclick="sendMessage()">Enviar</button>
    </div>
</div>

<script>
    const messagesDiv = document.getElementById('messages');
    const promptInput = document.getElementById('prompt');
    const sendBtn = document.getElementById('sendBtn');

    // Desce a barra de rolagem ao carregar
    messagesDiv.scrollTop = messagesDiv.scrollHeight;

    function appendMessage(text, sender) {
        const div = document.createElement('div');
        div.className = 'message ' + sender;
        div.innerHTML = text.replace(/\n/g, '<br>');
        messagesDiv.appendChild(div);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
        return div;
    }

    function appendStatus(text) {
        const div = document.createElement('div');
        div.className = 'status';
        div.id = 'current-status';
        div.innerText = text;
        messagesDiv.appendChild(div);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }

    function removeStatus() {
        const statusDiv = document.getElementById('current-status');
        if (statusDiv) statusDiv.remove();
    }

    function sendMessage() {
        const text = promptInput.value.trim();
        if (!text) return;

        appendMessage(text, 'user');
        promptInput.value = '';
        promptInput.disabled = true;
        sendBtn.disabled = true;

        appendStatus('Iniciando conexão SSE...');

        // Usamos EventSource para consumir o endpoint via Server-Sent Events
        const source = new EventSource('chat.php?action=stream&message=' + encodeURIComponent(text));
        
        let assistantDiv = null;
        let responseText = '';

        // Escuta eventos customizados de status (ex: "Pensando...")
        source.addEventListener('status', function(e) {
            removeStatus();
            appendStatus(e.data);
        });

        // Escuta a resposta principal do agente
        source.onmessage = function(e) {
            removeStatus();
            if (!assistantDiv) {
                assistantDiv = appendMessage('', 'assistant');
            }
            
            // SSE pode enviar a string vazia como um chunk válido
            if (responseText !== '') responseText += '\n';
            responseText += e.data;
            
            assistantDiv.innerHTML = responseText.replace(/\n/g, '<br>');
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        };

        // Escuta o fim do processo
        source.addEventListener('end', function(e) {
            removeStatus();
            source.close();
            promptInput.disabled = false;
            sendBtn.disabled = false;
            promptInput.focus();
        });

        // Tratamento de erros
        source.onerror = function(e) {
            removeStatus();
            appendStatus('Conexão encerrada ou ocorreu um erro de timeout.');
            source.close();
            promptInput.disabled = false;
            sendBtn.disabled = false;
        };
    }
</script>
</body>
</html>
