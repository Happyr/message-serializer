<?php

declare(strict_types=1);

namespace Happyr\MessageSerializer;

use Happyr\MessageSerializer\Transformer\Exception\TransformerNotFoundException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

/**
 * @author Radoje Albijanic <radoje.albijanic@gmail.com>
 */
final class SerializerRouter implements SerializerInterface
{
    private $happyrSerializer;
    private $symfonySerializer;

    public function __construct(SerializerInterface $happyrSerializer, SerializerInterface $symfonySerializer)
    {
        $this->happyrSerializer = $happyrSerializer;
        $this->symfonySerializer = $symfonySerializer;
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
            $envelopeBodyArray = \json_decode($encodedEnvelope['body'], true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return $this->symfonySerializer->decode($encodedEnvelope);
        }

        if (!empty(array_diff(['version', 'identifier', 'timestamp', 'payload'], array_keys($envelopeBodyArray)))) {
            return $this->symfonySerializer->decode($encodedEnvelope);
        }

        return $this->happyrSerializer->decode($encodedEnvelope);
    }

    /**
     * {@inheritdoc}
     */
    public function encode(Envelope $envelope): array
    {
        try {
            return $this->happyrSerializer->encode($envelope);
        } catch (TransformerNotFoundException $e) {
            return $this->symfonySerializer->encode($envelope);
        }
    }
}
