<?php

require __DIR__ . '/vendor/autoload.php';

use SearchAgent\AI\Agent\AgentRunner;

$runner = new AgentRunner();

$prompt = "Qual o valor do dolar hoje ?";

echo "Iniciando o agente...\n";
$result = $runner->run($prompt);

echo "\nResultado Final:\n";
echo "================\n";
echo $result . "\n";