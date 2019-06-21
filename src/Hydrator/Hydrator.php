<?php

declare(strict_types=1);

namespace Happyr\MessageSerializer\Hydrator;

use Happyr\MessageSerializer\Hydrator\Exception\HydratorException;
use Happyr\MessageSerializer\Hydrator\Exception\HydratorNotFoundException;

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
     * @throws HydratorNotFoundException
     * @throws HydratorException
     */
    public function toMessage(array $data)
    {
        foreach ($this->hydrators as $hydrator) {
            if (!$hydrator->supports($message['identifier'] ?? '', $message['version'] ?? 0)) {
                continue;
            }

            try {
                return $hydrator->toMessage($data['payload'] ?? [], $data['version'] ?? 0);
            } catch (\Throwable $throwable) {
                throw new HydratorException(sprintf('Transformer "%s" failed to transform a message.', get_class($hydrator)), 0, $throwable);
            }
        }

        throw new HydratorNotFoundException();
    }
}
