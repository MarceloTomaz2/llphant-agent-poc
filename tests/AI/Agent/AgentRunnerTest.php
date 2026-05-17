<?php

namespace Tests\AI\Agent;

use PHPUnit\Framework\TestCase;
use SearchAgent\AI\Agent\AgentRunner;
use LLPhant\Chat\OpenAIChat;

class AgentRunnerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Definir as variáveis de ambiente necessárias para evitar erros se o .env não for carregado
        $_ENV['OPENAI_API_KEY'] = 'test_key';
        $_ENV['OPENAI_API_URL'] = 'http://test.url';
        $_ENV['OPENAI_API_MODEL'] = 'test_model';
    }

    public function testAgentRunnerCanBeInstantiated(): void
    {
        // Ao passar o mock, não chamará a API real no construtor (embora OpenAIChat não chame no construtor)
        $chatMock = $this->createMock(OpenAIChat::class);
        $runner = new AgentRunner([], $chatMock);
        
        $this->assertInstanceOf(AgentRunner::class, $runner);
    }

    public function testRunReturnsStringWhenChatReturnsString(): void
    {
        $chatMock = $this->createMock(OpenAIChat::class);
        
        $chatMock->expects($this->once())
                 ->method('generateChatOrReturnFunctionCalled')
                 ->willReturn('Resposta final de teste');

        $runner = new AgentRunner([], $chatMock);
        $result = $runner->run('Olá, mundo');

        $this->assertEquals('Resposta final de teste', $result);
        
        // Verifica se a mensagem do usuário foi registrada no histórico
        $messages = $runner->getMessages();
        $this->assertCount(1, $messages);
        $this->assertEquals('Olá, mundo', $messages[0]->content);
    }

    public function testRunExitsAfterMaxIterations(): void
    {
        $chatMock = $this->createMock(OpenAIChat::class);
        
        // Simular chamadas infinitas de tools (retornando array vazio/não-string repetidamente)
        $chatMock->expects($this->exactly(20))
                 ->method('generateChatOrReturnFunctionCalled')
                 ->willReturn([]);

        $runner = new AgentRunner([], $chatMock);
        $result = $runner->run('Teste de loop infinito');

        $this->assertEquals('Limite de iterações atingido.', $result);
    }

    public function testResetClearsMessages(): void
    {
        $chatMock = $this->createMock(OpenAIChat::class);
        $chatMock->method('generateChatOrReturnFunctionCalled')
                 ->willReturn('Teste');

        $runner = new AgentRunner([], $chatMock);
        $runner->run('Mensagem 1');
        
        $this->assertNotEmpty($runner->getMessages());
        
        $runner->reset();
        $this->assertEmpty($runner->getMessages());
    }
}
