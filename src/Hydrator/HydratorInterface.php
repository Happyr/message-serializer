<?php

declare(strict_types=1);

namespace Happyr\MessageSerializer\Hydrator;

interface HydratorInterface
{
    public function toMessage(array $payload, int $version);

    public function supports(string $identifier, int $version): bool;
}