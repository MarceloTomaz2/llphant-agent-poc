# Projeto de Testes e Estudos - LLPhant Framework

Este repositório é um laboratório de estudos focado no framework **[LLPhant](https://github.com/theodo-group/llphant)**, uma biblioteca desenvolvida em PHP que facilita a criação de agentes inteligentes integrados a LLMs (Large Language Models).

## 🎯 Objetivo

O objetivo central deste projeto é testar, na prática, a construção de um **Agente Inteligente Autônomo** usando recursos avançados de *Function Calling*. O projeto serve como prova de conceito (PoC) e estudo profundo em:

- **Tools (Ferramentas):** Treinar e configurar o agente para realizar ações dinâmicas. O agente consegue procurar dados ativamente na internet (via `SearchWebTool`) ou fazer a leitura e resumo de páginas web (via `ReadUrlTool`) de modo autônomo.
- **Skills (Habilidades):** Criar um modelo modular e escalável em que instruções complexas e conhecimento de domínio estão armazenados em arquivos de texto na pasta `.SKILLS/`. O agente deduz qual habilidade precisa de acordo com o pedido do usuário e usa a ferramenta `ReadSkillTool` para "aprender" ou internalizar aquele conhecimento no meio da execução.

## 🚀 Arquitetura OOP

Nesta versão de estudos, evoluímos a base para ser totalmente Orientada a Objetos. O núcleo do projeto se apoia na classe `AgentRunner`, que:
1. Possui flexibilidade na configuração: lê automaticamente as variáveis de ambiente com `Dotenv` (identificando inclusive quando é chamado de dentro da pasta `vendor` de outro projeto, como o Adianti Framework), ou recebe chaves e configurações diretamente por um array de opções no construtor.
2. Gerencia o histórico de forma inteligente utilizando a classe `MessageCollection`, permitindo injeção de contexto via `$runner->setMessages()`.
3. Inicia a classe `OpenAIChat` do LLPhant e acopla dinamicamente as *Tools* obrigatórias (permitindo a injeção de novas tools customizadas via `addTool()`).
4. Entra em um loop contínuo de avaliação e execução, garantindo que o Agente faça quantas chamadas de ferramenta forem necessárias até conseguir processar a resposta final para o usuário.

## 🛠️ Como Utilizar
- **Buscas na Internet (SearXNG):** Este projeto utiliza o **[SearXNG](https://github.com/searxng/searxng)** como motor de pesquisa para evitar limites de requisição de buscadores convencionais.
  - Para subir o container do SearXNG, acesse o diretório `docker/searxng` e execute o comando:
    ```bash
    docker-compose up -d
    ```
  - Após iniciar, certifique-se de configurar a variável `SEARXNG_URL` no seu `.env` com a URL local do serviço (ex: `http://localhost:9085/search` ou o IP específico configurado).
- **Configuração:** Configure as chaves de API clonando o `.env_exemple` para `.env` na raiz.
- **Interface de Chat (SSE):** Acesse `chat.php` no seu navegador usando um servidor local (ex: `php -S localhost:8000`) para interagir com o agente através de uma interface gráfica que utiliza o protocolo *Server-Sent Events* e gerencia contexto via sessão.
- **Execução via CLI:** Você também pode rodar e dar uma instrução inicial ao Agente via terminal executando `php index.php`.
- **Testes:** Testes unitários foram preparados com o **PHPUnit** na pasta `tests/` para validar o motor de iterações (`AgentRunner`).

> **Nota:** Este projeto é de caráter puramente experimental. Idealizado como base de código para testar capacidades em IA, desenvolvimento orientado a Skills modulares e manutenções assistidas.
