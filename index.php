<?php

require __DIR__ . '/vendor/autoload.php';

use SearchAgent\AI\Agent\AgentRunner;
use LLPhant\Chat\Message;

$options = [
    'maxIterations' => 10,
    'temperature' => 0.7,
    // 'apiKey' => 'sua-chave', // Pode injetar direto aqui ignorando o .env se quiser
];

$runner = new AgentRunner($options);

// Injetando histórico de mensagens para dar contexto
$historico = [
    Message::user('Gostaria de falar sobre investimentos. O que você acha do Bitcoin e do Dólar?'),
    Message::assistant('O Bitcoin é a principal criptomoeda do mercado, conhecida por sua alta volatilidade. Já o Dólar americano é uma moeda fiduciária tradicional e costuma ser visto como um porto seguro. O que mais você deseja saber?'),
];
$runner->setMessages($historico);

$prompt = "Seguindo nossa conversa, qual o valor da cotação do dolar hoje?";

echo "Iniciando o agente...\n";
$result = $runner->run($prompt);

echo "\nResultado Final:\n";
echo "================\n";
echo $result . "\n";