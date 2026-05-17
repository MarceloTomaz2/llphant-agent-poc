<?php

namespace SearchAgent\AI\Agent;

use LLPhant\Chat\Message;
use InvalidArgumentException;

class MessageCollection
{
    /** @var Message[] */
    private array $messages = [];

    /**
     * @param Message[] $messages
     */
    public function __construct(array $messages = [])
    {
        $this->addMany($messages);
    }

    public function add(Message $message): void
    {
        $this->messages[] = $message;
    }

    /**
     * @param Message[] $messages
     */
    public function addMany(array $messages): void
    {
        foreach ($messages as $message) {
            if (!$message instanceof Message) {
                throw new InvalidArgumentException('Todos os elementos devem ser instâncias de LLPhant\Chat\Message');
            }
            $this->add($message);
        }
    }

    /**
     * @return Message[]
     */
    public function toArray(): array
    {
        return $this->messages;
    }

    public function reset(): void
    {
        $this->messages = [];
    }
}
