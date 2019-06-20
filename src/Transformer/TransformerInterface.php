<?php

declare(strict_types=1);

namespace Happyr\MessageSerializer\Transformer;

interface TransformerInterface
{
    /**
     * The version of the message.
     */
    public function getVersion(): int;

    /**
     * An message identifier. This should never be changed for a message.
     */
    public function getIdentifier(): string;

    /**
     * @param object $message
     */
    public function getPayload($message): array;

    /**
     * Does this transformer support this kind of message?
     * @param object $message
     */
    public function supports($message): bool;
}
