# Documentação do Projeto para Agentes de IA (llm.md)

Este documento foi criado para fornecer contexto a Agentes de Codificação de IA (como Gemini, Claude, ChatGPT) ou desenvolvedores que precisem entender, manter ou replicar o funcionamento deste projeto.

## 1. Visão Geral do Projeto
O projeto `search-agent` (ou LLPhant Agent) é uma aplicação em PHP construída utilizando a biblioteca **[LLPhant](https://github.com/theodo-group/llphant)**. O seu principal objetivo é fornecer um Agente conversacional que pode executar ações dinâmicas ("Tools" / Ferramentas) e ler capacidades especializadas ("Skills") para cumprir tarefas dadas pelo usuário.

## 2. Estrutura do Projeto

* `src/AI/Agent/AgentRunner.php`: **O coração da execução do agente.** É uma classe orientada a objetos (OOP) que encapsula a lógica de interação com a API do LLM. Recebe parâmetros de configuração flexíveis via array no construtor (como chaves da API, lidando inteligentemente com a raiz do projeto mesmo se instalado via *vendor*), gerencia o histórico e define o prompt. Registra as tools e processa o laço (*loop*) de iterações em que o modelo invoca as funções até retornar uma string final.
* `src/AI/Agent/MessageCollection.php`: Classe de abstração para armazenar, validar e exportar o histórico de mensagens consumido pelo LLM.
* `chat.php`: Uma interface visual web (HTML/JS) que comunica com o agente em tempo real utilizando o protocolo SSE (Server-Sent Events) e persiste contexto usando `$_SESSION`.
* `src/AI/Tools/`: Contém as ferramentas (Tools) PHP que o agente pode chamar.
  * `SearchWebTool.php`: Ferramenta para buscar conteúdo na internet.
  * `ReadUrlTool.php`: Ferramenta que lê o conteúdo principal (scraping) de um site.
  * `ReadSkillTool.php`: Ferramenta essencial que permite ao agente consultar "Skills" armazenadas em arquivos no disco.
* `.SKILLS/`: Pasta raiz que contém as habilidades especializadas do sistema. Cada pasta dentro de `.SKILLS` representa uma habilidade e deve conter um arquivo `skill.md` detalhando as instruções daquela habilidade.
  * Exemplo: `.SKILLS/analise-noticias-fiscais/skill.md`.
* `tests/AI/Agent/`: Arquivos de testes (PHPUnit). Exemplo: `AgentRunnerTest.php`.
* `.env`: Armazena as chaves de ambiente, url base da API e o modelo de LLM usado (ex: Gemini).

## 3. Arquitetura e Funcionamento do Agente

A arquitetura do Agente é baseada em *Function Calling* (chamada de ferramentas).
O fluxo principal (`AgentRunner->run()`) funciona assim:
1. O desenvolvedor pode opcionalmente injetar um contexto prévio usando `$runner->setMessages()`.
2. O `AgentRunner` recebe o prompt final do usuário.
3. O prompt do sistema injeta uma lista dinâmica de **Skills** disponíveis no projeto e a data atual.
4. O LLM decide se tem a resposta ou se precisa usar uma Tool. 
5. Se o modelo precisa de uma informação que está em uma *Skill* (ex: analista fiscal), ele aciona a ferramenta `readSkill` e lê as regras de atuação antes de continuar.
6. Se o modelo precisa ler as notícias atuais, ele pode acionar a ferramenta `search` ou `read` em um loop de retroalimentação (`while`).
7. Quando o modelo tiver tudo o que precisa, ele responde o texto final e o loop se encerra.

## 4. Instruções para o Agente de Codificação de IA

Se você é um agente de IA (AI Coding Assistant) realizando manutenções neste repositório, siga as diretrizes abaixo:

### Como Criar Novas Tools (Ferramentas)
1. Crie uma nova classe em `src/AI/Tools/` contendo o método público que fará o processamento.
2. Anote o método usando DocBlocks (`/** ... */`) se for utilizar recursos automáticos do `FunctionBuilder` do LLPhant, ou use a classe `FunctionInfo` com instâncias de `Parameter` definindo os tipos de dados da tool.
3. Registre a nova tool dentro do método `setupTools()` na classe `AgentRunner.php`.

### Como Criar Novas Skills (Habilidades)
As *Skills* não requerem a criação de novos arquivos PHP. 
1. Crie uma pasta nova em `.SKILLS/nome-da-habilidade`.
2. Dentro dela, crie um arquivo `skill.md`.
3. Escreva as regras de negócio em linguagem natural no `skill.md`. O agente central lerá automaticamente este arquivo via `ReadSkillTool` quando achar pertinente.

### Padrão de Testes
O projeto faz uso de PHPUnit. Ao criar ou modificar uma classe central (como uma Tool ou o `AgentRunner`), garanta a cobertura escrevendo testes unitários correspondentes em `tests/` e evite acionar a API real mockando os serviços de chat ou HTTP. Para executar: `vendor/bin/phpunit --bootstrap vendor/autoload.php tests/`

### Gerenciamento de Configuração
Sempre utilize as variáveis `.env` carregadas na raiz para configurar URLs, Modelos ou Chaves. Evite injetar (hardcode) segredos ou datas nos arquivos PHP (use sempre variáveis injetadas via `Dotenv` ou funções nativas como `date()`).

---
**Resumo de Tecnologias:** PHP 8.x, Composer, PHPUnit, LLPhant, Dotenv.
