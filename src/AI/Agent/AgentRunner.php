<?php

namespace SearchAgent\AI\Agent;

use Dotenv\Dotenv;
use LLPhant\Chat\OpenAIChat;
use LLPhant\OpenAIConfig;
use LLPhant\Chat\Message;
use LLPhant\Chat\FunctionInfo\FunctionInfo;
use LLPhant\Chat\FunctionInfo\FunctionBuilder;
use LLPhant\Chat\FunctionInfo\Parameter;
use SearchAgent\AI\Tools\SearchWebTool;
use SearchAgent\AI\Tools\ReadUrlTool;
use SearchAgent\AI\Tools\ReadSkillTool;

class AgentRunner
{
    private OpenAIChat $chat;
    private array $messages = [];
    private int $maxIterations = 20;

    public function __construct(?OpenAIChat $chat = null)
    {
        // Carrega .env a partir da raiz do projeto (assumindo que o AgentRunner está em src/AI/Agent)
        $dotenv = Dotenv::createMutable(dirname(__DIR__, 3));
        $dotenv->safeLoad();

        if ($chat === null) {

            $config = new OpenAIConfig();
            $config->apiKey = $_ENV['OPENAI_API_KEY'] ?? $_SERVER['OPENAI_API_KEY'] ?? '';
            $config->url = $_ENV['OPENAI_API_URL'] ?? $_SERVER['OPENAI_API_URL'] ?? 'https://api.openai.com/v1/';
            $config->model = $_ENV['OPENAI_API_MODEL'] ?? $_SERVER['OPENAI_API_MODEL'] ?? 'openai/gpt-4.1-mini';

            $config->modelOptions = [
                'stream' => false,
                'temperature' => 1.0,
            ];

            $this->chat = new OpenAIChat($config);
        } else {
            $this->chat = $chat;
        }

        $this->setupSystemPrompt();
        $this->setupTools();
    }

    private function setupSystemPrompt(): void
    {
        $skills = ReadSkillTool::listSkills();
        $date = date('d/m/Y');

        $prompt = "Você é um assistente virtual útil e inteligente.
Para completar as tarefas solicitadas, identifique a melhor abordagem usando as TOOLS e SKILLS disponíveis.

# TOOLS
- Utilise as tools sempre que possivel para otimizar as resposta ao usuário.
# SKILLS
- As skills são capacidades especializadas que podem ser utilizadas quando relevantes para a tarefa do usuário.
- Quando identificar que uma skill é apropriada:
  - chame a tool readSkill com o parâmetro folderName no formato:
    folderName: <nome-da-skill>

## Skills disponíveis:
 $skills 

## INFORMAÇÕES GERAIS
 HOJE É $date
";

        $this->chat->setSystemMessage($prompt);
    }

    private function setupTools(): void
    {
        $parameters2 = new Parameter('url', 'string', 'Retorna o conteúdo de uma URL');
        $parametersSkill = new Parameter('folderName', 'string', 'O nome da skill');

        $searchTool = FunctionBuilder::buildFunctionInfo(new SearchWebTool(), 'search');

        $readTool = new FunctionInfo(
            'read',
            new ReadUrlTool(),
            'Lê o conteúdo principal de uma URL',
            [$parameters2]
        );

        $readSkillTool = new FunctionInfo(
            'readSkill',
            new ReadSkillTool(),
            'Lê o conteúdo do arquivo skill.md de uma skill específica',
            [$parametersSkill]
        );

        $this->chat->addTool($searchTool);
        $this->chat->addTool($readTool);
        $this->chat->addTool($readSkillTool);
    }

    public function run(string $prompt): string
    {
        $this->messages[] = Message::user($prompt);

        $iteration = 0;

        while ($iteration < $this->maxIterations) {
            $iteration++;

            $result = $this->chat->generateChatOrReturnFunctionCalled($this->messages);

            if (is_string($result)) {
                return $result;
            }

            foreach ($result as $functionInfo) {
                // Resolve the tool call and collect messages to send back.
                $toolMessages = $functionInfo->callAndReturnAsOpenAIMessages();
                $this->messages = array_merge($this->messages, $toolMessages);
            }
        }

        return 'Limite de iterações atingido.';
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function getChat(): OpenAIChat
    {
        return $this->chat;
    }

    public function reset(): void
    {
        $this->messages = [];
    }
}