<?php

declare(strict_types=1);

namespace Tests\Happyr\MessageSerializer;

use Happyr\MessageSerializer\SerializerRouter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

/**
 * @internal
 */
final class SerializerRouterTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $happyrSerializerMock;
    /**
     * @var MockObject
     */
    private $symfonySerializerMock;
    /**
     * @var SerializerRouter
     */
    private $serializer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->happyrSerializerMock = $this->getMockBuilder(SerializerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->symfonySerializerMock = $this->getMockBuilder(SerializerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->serializer = new SerializerRouter(
            $this->happyrSerializerMock,
            $this->symfonySerializerMock
        );
    }

    public function testDecodeThrowsExceptionIfNoBody(): void
    {
        $this->expectException(MessageDecodingFailedException::class);

        $this->serializer->decode([]);
    }

    public function testDecodeCallsSymfonySerializerIfEnvelopeBodyNotJson(): void
    {
        $envelope = [
            'body' => serialize(new \stdClass()),
        ];
        $this->symfonySerializerMock->expects(self::once())
            ->method('decode')
            ->with($envelope)
            ->willReturn(new Envelope(new \stdClass()));
        $this->happyrSerializerMock->expects(self::exactly(0))
            ->method('decode')
            ->willReturn(new Envelope(new \stdClass()));

        $this->serializer->decode($envelope);
    }

    public function testDecodeCallHappyrSerializerForJsonWithHappyrSerializerStructure(): void
    {
        $envelope = [
            'body' => json_encode([
                'identifier' => 'some-identifier',
                'version' => 1,
                'timestamp' => time(),
                'payload' => [
                    'message' => 'Some message',
                ],
            ]),
        ];
        $this->happyrSerializerMock->expects(self::once())
            ->method('decode')
            ->with($envelope)
            ->willReturn(new Envelope(new \stdClass()));
        $this->symfonySerializerMock->expects(self::exactly(0))
            ->method('decode')
            ->willReturn(new Envelope(new \stdClass()));

        $this->serializer->decode($envelope);
    }

    /**
     * @dataProvider getNonHappyrSerializerEncodedEnvelope
     */
    public function testDecodeCallsSymfonySerializerForJsonWithDifferentStructure(array $encodedEnvelope): void
    {
        $this->symfonySerializerMock->expects(self::once())
            ->method('decode')
            ->with($encodedEnvelope)
            ->willReturn(new Envelope(new \stdClass()));
        $this->happyrSerializerMock->expects(self::exactly(0))
            ->method('decode')
            ->willReturn(new Envelope(new \stdClass()));

        $this->serializer->decode($encodedEnvelope);
    }

    public function getNonHappyrSerializerEncodedEnvelope(): iterable
    {
        //missing identifier
        yield [[
            'body' => json_encode([
                'version' => 1,
                'timestamp' => time(),
                'payload' => [
                    'message' => 'Some message',
                ],
            ]),
        ]];
        //missing version
        yield [[
            'body' => json_encode([
                'identifier' => 'some-identifier',
                'timestamp' => time(),
                'payload' => [
                    'message' => 'Some message',
                ],
            ]),
        ]];
        //missing timestamp
        yield [[
            'body' => json_encode([
                'identifier' => 'some-identifier',
                'version' => 1,
                'payload' => [
                    'message' => 'Some message',
                ],
            ]),
        ]];
        //missing payload
        yield [[
            'body' => json_encode([
                'identifier' => 'some-identifier',
                'version' => 1,
                'timestamp' => time(),
            ]),
        ]];
        // missing all
        yield [[
            'body' => json_encode([
                'some-key' => 'some-value',
            ]),
        ]];
    }
}
