<?php

declare(strict_types=1);

namespace Tests\Happyr\MessageSerializer\Transformer;

use Happyr\MessageSerializer\Transformer\Exception\TransformerNotFoundException;
use Happyr\MessageSerializer\Transformer\Transformer;
use Happyr\MessageSerializer\Transformer\TransformerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;

class TransformerTest extends TestCase
{
    public function testOutputArray()
    {
        $fooTransformer = $this->getMockBuilder(TransformerInterface::class)
            ->setMethods(['getVersion', 'getIdentifier', 'getPayload', 'supportsTransform'])
            ->getMock();

        $version = 2;
        $identifier = 'foobar';
        $payload = ['foo' => 'bar'];

        $fooTransformer->method('getVersion')->willReturn($version);
        $fooTransformer->method('getIdentifier')->willReturn($identifier);
        $fooTransformer->method('supportsTransform')->willReturn(true);
        $fooTransformer->method('getPayload')->willReturn($payload);

        $transformer = new Transformer([$fooTransformer]);
        $output = $transformer->toArray(new \stdClass());

        $this->assertArrayHasKey('version', $output);
        $this->assertArrayHasKey('identifier', $output);
        $this->assertArrayHasKey('timestamp', $output);
        $this->assertArrayHasKey('payload', $output);

        $this->assertEquals($output['version'], $version);
        $this->assertEquals($output['identifier'], $identifier);
        $this->assertEquals($output['payload'], $payload);
        $this->assertEqualsWithDelta($output['timestamp'], time(), 3);
    }

    public function testTransformerNotFoundExceptionClass()
    {
        $transformer = new Transformer([]);
        $this->expectException(TransformerNotFoundException::class);
        $this->expectExceptionMessage('No transformer found for "stdClass"');
        $output = $transformer->toArray(new \stdClass());
    }

    public function testTransformerNotFoundExceptionInteger()
    {
        $transformer = new Transformer([]);
        $this->expectException(TransformerNotFoundException::class);
        $this->expectExceptionMessage('No transformer found for "integer"');
        $output = $transformer->toArray(4711);
    }

    public function testTransformerNotFoundExceptionEnvelope()
    {
        $transformer = new Transformer([]);
        $this->expectException(TransformerNotFoundException::class);
        $this->expectExceptionMessage('No transformer found for "Envelope<stdClass>"');
        $output = $transformer->toArray(new Envelope(new \stdClass()));
    }
}
