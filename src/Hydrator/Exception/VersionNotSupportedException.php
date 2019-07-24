<?php

declare(strict_types=1);

namespace Happyr\MessageSerializer\Hydrator\Exception;

/**
 * Throw this when the Hydrator do support the message type but not the version.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
final class VersionNotSupportedException extends \RuntimeException implements HydratorException
{
}
