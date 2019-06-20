<?php

declare(strict_types=1);

namespace Happyr\MessageSerializer;

use Happyr\MessageSerializer\Hydrator\ArrayToMessageInterface;
use Happyr\MessageSerializer\Hydrator\Exception\HydratorException;
use Happyr\MessageSerializer\Transformer\MessageToArrayInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;

class Serializer implements SerializerInterface
{
    private $transformer;
    private $hydrator;

    public function __construct(MessageToArrayInterface $transformer, ArrayToMessageInterface $hydrator)
    {
        $this->transformer = $transformer;
        $this->hydrator = $hydrator;
    }

    /**
     * {@inheritdoc}
     */
    public function decode(array $encodedEnvelope): Envelope
    {
        if (empty($encodedEnvelope)) {
            throw new MessageDecodingFailedException('Cannot decode empty array.');
        }

        $array = json_decode($encodedEnvelope, true);

        try {
            return $this->hydrator->toMessage($array);
        } catch (HydratorException $e) {
            throw new MessageDecodingFailedException('Failed to decode message', 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function encode(Envelope $envelope): array
    {
        $envelope = $envelope->withoutStampsOfType(NonSendableStampInterface::class);

        return $this->transformer->toArray($envelope);
    }
}
