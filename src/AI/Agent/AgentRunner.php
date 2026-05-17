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
    private MessageCollection $messages;
    private int $maxIterations;
    private array $options;

    public function __construct(array $options = [], ?OpenAIChat $chat = null)
    {
        $this->messages = new MessageCollection();
        // Verifica se o pacote está rodando de dentro da pasta "vendor" de outro projeto (ex: Adianti)
        $isVendor = strpos(__DIR__, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) !== false;
        
        // Se estiver no vendor, a raiz do projeto hospedeiro está 5 níveis acima. Senão, está a 3.
        $rootPath = $isVendor ? dirname(__DIR__, 5) : dirname(__DIR__, 3);
        
        if (file_exists($rootPath . DIRECTORY_SEPARATOR . '.env')) {
            $dotenv = Dotenv::createMutable($rootPath);
            $dotenv->safeLoad();
        }

        $this->options = array_merge([
            'maxIterations' => 20,
            'temperature' => 1.0,
            'apiKey' => $_ENV['OPENAI_API_KEY'] ?? $_SERVER['OPENAI_API_KEY'] ?? '',
            'url' => $_ENV['OPENAI_API_URL'] ?? $_SERVER['OPENAI_API_URL'] ?? 'https://api.openai.com/v1/',
            'model' => $_ENV['OPENAI_API_MODEL'] ?? $_SERVER['OPENAI_API_MODEL'] ?? 'openai/gpt-4.1-mini',
            'systemPrompt' => "Você é um assistente virtual útil e inteligente."
        ], $options);

        $this->maxIterations = $this->options['maxIterations'];

        if ($chat === null) {

            $config = new OpenAIConfig();
            $config->apiKey = $this->options['apiKey'];
            $config->url = $this->options['url'];
            $config->model = $this->options['model'];

            $config->modelOptions = [
                'stream' => false,
                'temperature' => $this->options['temperature'],
            ];

            $this->chat = new OpenAIChat($config);
        } else {
            $this->chat = $chat;
        }

        $this->setupSystemPrompt($this->options['systemPrompt']);
        $this->setupTools();
    }

    public function setSystemPrompt(string $basePrompt): void
    {
        $this->options['systemPrompt'] = $basePrompt;
        $this->setupSystemPrompt($basePrompt);
    }

    private function setupSystemPrompt(string $basePrompt): void
    {
        $skills = ReadSkillTool::listSkills();
        $date = date('d/m/Y');

        $prompt = $basePrompt . "\n
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

    public function addTool(FunctionInfo $tool): void
    {
        $this->chat->addTool($tool);
    }

    public function run(string $prompt): string
    {
        $this->messages->add(Message::user($prompt));

        $iteration = 0;

        while ($iteration < $this->maxIterations) {
            $iteration++;

            $result = $this->chat->generateChatOrReturnFunctionCalled($this->messages->toArray());

            if (is_string($result)) {
                return $result;
            }

            foreach ($result as $functionInfo) {
                // Resolve the tool call and collect messages to send back.
                $toolMessages = $functionInfo->callAndReturnAsOpenAIMessages();
                $this->messages->addMany($toolMessages);
            }
        }

        return 'Limite de iterações atingido.';
    }

    public function getMessages(): array
    {
        return $this->messages->toArray();
    }

    /**
     * @param Message[]|MessageCollection $messages
     */
    public function setMessages(array|MessageCollection $messages): void
    {
        if ($messages instanceof MessageCollection) {
            $this->messages = $messages;
        } else {
            $this->messages = new MessageCollection($messages);
        }
    }

    public function getChat(): OpenAIChat
    {
        return $this->chat;
    }

    public function reset(): void
    {
        $this->messages->reset();
    }
}