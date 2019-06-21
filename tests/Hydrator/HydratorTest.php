<?php

declare(strict_types=1);

namespace Tests\Happyr\MessageSerializer\Hydrator;

use Happyr\MessageSerializer\Hydrator\Hydrator;
use Happyr\MessageSerializer\Hydrator\HydratorInterface;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class HydratorTest extends TestCase
{
    public function testTransform()
    {
        $fooHydrator = $this->getMockBuilder(HydratorInterface::class)
            ->setMethods(['toMessage', 'supports'])
            ->getMock();

        $version = 2;
        $identifier = 'foobar';
        $payload = ['foo' => 'bar'];
        $time = \time();
        $data = [
            'version' => $version,
            'identifier' => $identifier,
            'payload' => $payload,
            'timestamp' => $time,
        ];

        $fooHydrator->expects(self::once())
            ->method('supports')
            ->with($identifier, $version)
            ->willReturn(true);

        $fooHydrator->expects(self::once())
            ->method('toMessage')
            ->with($payload, $version)
            ->willReturn(new \stdClass());

        $hydrator = new Hydrator([$fooHydrator]);
        $output = $hydrator->toMessage($data);

        self::assertInstanceOf(\stdClass::class, $output);
    }
}
