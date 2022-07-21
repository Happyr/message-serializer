<?php

declare(strict_types=1);

namespace Happyr\MessageSerializer;

use Happyr\MessageSerializer\Hydrator\ArrayToMessageInterface;
use Happyr\MessageSerializer\Hydrator\Exception\HydratorException;
use Happyr\MessageSerializer\Transformer\MessageToArrayInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

final class Serializer implements SerializerInterface
{
    private $transformer;
    private $hydrator;
    private $logger;

    public function __construct(MessageToArrayInterface $transformer, ArrayToMessageInterface $hydrator, LoggerInterface $logger = null)
    {
        $this->transformer = $transformer;
        $this->hydrator = $hydrator;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function decode(array $encodedEnvelope): Envelope
    {
        if (empty($encodedEnvelope['body'])) {
            $this->logger->error('Failed to decode message with no body.');
            throw new MessageDecodingFailedException('Encoded envelope should have at least a "body".');
        }

        try {
            $array = \json_decode($encodedEnvelope['body'], true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->logger->error('Failed to run json_decode on message.', ['exception' => $e]);
            throw new MessageDecodingFailedException(\sprintf('Error when trying to json_decode message: "%s"', $encodedEnvelope['body']), 0, $e);
        }

        $meta = $array['_meta'] ?? [];
        unset($array['_meta']);

        try {
            $message = $this->hydrator->toMessage($array);
            $envelope = $message instanceof Envelope ? $message : new Envelope($message);
        } catch (HydratorException $e) {
            $this->logger->error('Failed to run hydrate message to object.', ['exception' => $e, 'identifier' => $array['identifier'] ?? '(no identifier)', 'version' => $array['version'] ?? '(no version)']);
            throw new MessageDecodingFailedException('Failed to decode message', 0, $e);
        }

        return $this->addMetaToEnvelope($meta, $envelope);
    }

    /**
     * {@inheritdoc}
     */
    public function encode(Envelope $envelope): array
    {
        $envelope = $envelope->withoutStampsOfType(NonSendableStampInterface::class);

        $message = $this->transformer->toArray($envelope);
        $message['_meta'] = $this->getMetaFromEnvelope($envelope);

        return [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => \json_encode($message),
        ];
    }

    private function getMetaFromEnvelope(Envelope $envelope): array
    {
        $meta = [];

        $redeliveryStamp = $envelope->last(RedeliveryStamp::class);
        if ($redeliveryStamp instanceof RedeliveryStamp) {
            $meta['retry-count'] = $redeliveryStamp->getRetryCount();
        }

        return $meta;
    }

    private function addMetaToEnvelope(array $meta, Envelope $envelope): Envelope
    {
        if (isset($meta['retry-count'])) {
            $envelope = $envelope->with(new RedeliveryStamp((int) $meta['retry-count']));
        }

        return $envelope;
    }
}
