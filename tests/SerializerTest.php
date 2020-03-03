<?php

declare(strict_types=1);

namespace Tests\Happyr\MessageSerializer;

use Happyr\MessageSerializer\Hydrator\ArrayToMessageInterface;
use Happyr\MessageSerializer\Serializer;
use Happyr\MessageSerializer\Transformer\MessageToArrayInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;

/**
 * @internal
 */
final class SerializerTest extends TestCase
{
    public function testDecode()
    {
        $transformer = $this->getMockBuilder(MessageToArrayInterface::class)->getMock();
        $hydrator = $this->getMockBuilder(ArrayToMessageInterface::class)
            ->setMethods(['toMessage'])
            ->getMock();

        $payload = ['a' => 'b'];
        $data = [
            'body' => \json_encode($payload),
        ];

        $hydrator->expects(self::once())
            ->method('toMessage')
            ->with($payload)
            ->willReturn(new \stdClass());

        $serializer = new Serializer($transformer, $hydrator);
        $output = $serializer->decode($data);

        self::assertInstanceOf(Envelope::class, $output);
        self::assertInstanceOf(\stdClass::class, $output->getMessage());
    }

    public function testEncode()
    {
        $transformer = $this->getMockBuilder(MessageToArrayInterface::class)
            ->setMethods(['toArray'])
            ->getMock();
        $hydrator = $this->getMockBuilder(ArrayToMessageInterface::class)->getMock();

        $envelope = new Envelope(new \stdClass('foo'));

        $transformer->expects(self::once())
            ->method('toArray')
            ->with($envelope)
            ->willReturn(['foo' => 'bar']);

        $serializer = new Serializer($transformer, $hydrator);
        $output = $serializer->encode($envelope);

        self::assertArrayHasKey('headers', $output);
        self::assertArrayHasKey('Content-Type', $output['headers']);
        self::assertEquals('application/json', $output['headers']['Content-Type']);

        self::assertArrayHasKey('body', $output);
        self::assertEquals(\json_encode(['foo' => 'bar', '_meta' => []]), $output['body']);
    }
}
