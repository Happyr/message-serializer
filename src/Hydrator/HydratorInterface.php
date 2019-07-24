<?php

declare(strict_types=1);

namespace Happyr\MessageSerializer\Hydrator;

use Happyr\MessageSerializer\Hydrator\Exception\VersionNotSupportedException;

interface HydratorInterface
{
    /**
     * Convert an array to a message.
     *
     * @return object
     */
    public function toMessage(array $payload, int $version);

    /**
     * Does this Hydrator support this identifier and version?
     *
     * @throws VersionNotSupportedException
     */
    public function supportsHydrate(string $identifier, int $version): bool;
}
