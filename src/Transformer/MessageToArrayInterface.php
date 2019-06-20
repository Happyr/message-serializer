<?php

declare(strict_types=1);

namespace Happyr\MessageSerializer\Transformer;

use Happyr\MessageSerializer\Transformer\Exception\TransformerException;

interface MessageToArrayInterface
{
    /**
     * Convert an object to an array
     * @param object $message
     * @throws TransformerException
     */
    public function toArray($message): array;
}