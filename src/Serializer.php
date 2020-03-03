<?php

declare(strict_types=1);

namespace Happyr\MessageSerializer;

use Happyr\MessageSerializer\Hydrator\ArrayToMessageInterface;
use Happyr\MessageSerializer\Hydrator\Exception\HydratorException;
use Happyr\MessageSerializer\Transformer\MessageToArrayInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;
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

        try {
            $array = \json_decode($encodedEnvelope['body'], true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new MessageDecodingFailedException(\sprintf('Error when trying to json_decode message: "%s"', $encodedEnvelope['body']), 0, $e);
        }

        try {
            $message = $this->hydrator->toMessage($array);
            $envelope = $message instanceof Envelope ? $message : new Envelope($message);

            $envelope = $this->addMetaToEnvelope($array['meta'], $envelope);

            return $envelope;
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

        $message = $this->transformer->toArray($envelope);
        $message['meta'] = $this->getMetaFromEnvelope($envelope);

        return [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => \json_encode($message),
        ];
    }

    private function getMetaFromEnvelope(Envelope $envelope): array
    {
        $meta = [];

        $retryStamp = $envelope->last(RedeliveryStamp::class);
        $meta['retry-count'] = $retryStamp instanceof RedeliveryStamp ? $retryStamp->getRetryCount() : 0;

        return $meta;
    }

declare(strict_types = 1);

namespace Happyr\MessageSerializer;

    use Happyr\MessageSerializer\Hydrator\ArrayToMessageInterface;
    use Happyr\MessageSerializer\Hydrator\Exception\HydratorException;
    use Happyr\MessageSerializer\Transformer\MessageToArrayInterface;
    use Symfony\Component\Messenger\Envelope;
    use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
    use Symfony\Component\Messenger\Stamp\DelayStamp;
    use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;
    use Symfony\Component\Messenger\Stamp\RedeliveryStamp;
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

        try {
            $array = \json_decode($encodedEnvelope['body'], true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new MessageDecodingFailedException(\sprintf('Error when trying to json_decode message: "%s"', $encodedEnvelope['body']), 0, $e);
        }

        try {
            $message = $this->hydrator->toMessage($array);
            $envelope = $message instanceof Envelope ? $message : new Envelope($message);

            $envelope = $this->addMetaToEnvelope($array['meta'], $envelope);

            return $envelope;
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

        $message = $this->transformer->toArray($envelope);
        $message['meta'] = $this->getMetaFromEnvelope($envelope);

        return [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => \json_encode($message),
        ];
    }

    private function getMetaFromEnvelope(Envelope $envelope): array
    {
        $meta = [];

        $retryStamp = $envelope->last(RedeliveryStamp::class);
        $meta['retry-count'] = $retryStamp instanceof RedeliveryStamp ? $retryStamp->getRetryCount() : 0;

        return $meta;
    }

    private function addMetaToEnvelope($meta, $envelope)
    {
        if (0 !== $retryCount = $meta['retry-count'] ?? 0) {
            $envelope = $envelope->with(new RedeliveryStamp($retryCount));
        }

        return $envelope;
    }
}


private function addMetaToEnvelope($meta, $envelope)
    {
        if (0 !== $retryCount = $meta['retry-count'] ?? 0) {
            $envelope = $envelope->with(new RedeliveryStamp($retryCount));
        }

        return $envelope;
    }
}
