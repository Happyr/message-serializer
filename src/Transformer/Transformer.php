<?php

declare(strict_types=1);

namespace Happyr\MessageSerializer\Transformer;

use Happyr\MessageSerializer\Transformer\Exception\ConvertToArrayException;
use Happyr\MessageSerializer\Transformer\Exception\TransformerNotFoundException;
use Happyr\MessageSerializer\Transformer\Exception\TransformerException;

final class Transformer implements MessageToArrayInterface
{
    /**
     * @var TransformerInterface[]
     */
    private $transformers;

    public function __construct(iterable $transformers)
    {
        $this->transformers = $transformers;
    }

    /**
     * @throws TransformerNotFoundException
     * @throws ConvertToArrayException
     */
    public function toArray($message): array
    {
        foreach ($this->transformers as $transformer) {
            if (!$transformer->supportsTransform($message)) {
                continue;
            }

            try {
                return [
                    'version' => $transformer->getVersion(),
                    'identifier' => $transformer->getIdentifier(),
                    'timestamp' => time(),
                    'payload' => $transformer->getPayload($message),
                ];
            } catch (\Throwable $throwable) {
                throw new ConvertToArrayException(sprintf('Transformer "%s" failed to transform a message.', get_class($transformer)), 0, $throwable);
            }
        }

        throw new TransformerNotFoundException(sprintf('No transformer found for "%s"', is_object($message) ? get_class($message) : gettype($message)));
    }
}
