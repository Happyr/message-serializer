<?php

declare(strict_types=1);

namespace Happyr\MessageSerializer;

use Happyr\MessageSerializer\Hydrator\ArrayToMessageInterface;
use Happyr\MessageSerializer\Hydrator\Exception\HydratorException;
use Happyr\MessageSerializer\Transformer\MessageToArrayInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

final class Serializer implements SerializerInterface
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
        if (empty($encodedEnvelope['body'])) {
            throw new MessageDecodingFailedException('Encoded envelope should have at least a "body".');
        }

        $array = json_decode($encodedEnvelope['body'], true);

        try {
            $object = $this->hydrator->toMessage($array);

            return $object instanceof Envelope ? $object : new Envelope($object);
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

        return [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($this->transformer->toArray($envelope)),
        ];
    }
}
