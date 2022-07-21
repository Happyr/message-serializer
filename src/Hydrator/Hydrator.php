<?php

declare(strict_types=1);

namespace Happyr\MessageSerializer\Hydrator;

use Happyr\MessageSerializer\Hydrator\Exception\ConvertToMessageFailedException;
use Happyr\MessageSerializer\Hydrator\Exception\HydratorNotFoundException;
use Happyr\MessageSerializer\Hydrator\Exception\VersionNotSupportedException;

final class Hydrator implements ArrayToMessageInterface
{
    /**
     * @var HydratorInterface[]
     */
    private $hydrators;

    public function __construct(iterable $hydrators)
    {
        $this->hydrators = $hydrators;
    }

    /**
     * @throws ConvertToMessageFailedException
     * @throws VersionNotSupportedException
     * @throws HydratorNotFoundException
     */
    public function toMessage(array $data)
    {
        // Default exception to be thrown.
        $exception = new HydratorNotFoundException();

        foreach ($this->hydrators as $hydrator) {
            try {
                $isSupported = $hydrator->supportsHydrate($data['identifier'] ?? '', $data['version'] ?? 0);
            } catch (VersionNotSupportedException $e) {
                $exception = $e;
                continue;
            }

            if (!$isSupported) {
                continue;
            }

            try {
                /** @var object|null $object */
                $object = $hydrator->toMessage($data['payload'] ?? [], $data['version'] ?? 0);
            } catch (\Throwable $throwable) {
                throw new ConvertToMessageFailedException(sprintf('Hydrator "%s" failed to transform a message.', get_class($hydrator)), 0, $throwable);
            }

            if (null === $object) {
                throw new ConvertToMessageFailedException(sprintf('Hydrator "%s" failed to transform a message, null returned.', get_class($hydrator)));
            }

            return $object;
        }

        throw $exception;
    }
}
