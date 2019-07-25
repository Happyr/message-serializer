<?php

declare(strict_types=1);

namespace Happyr\MessageSerializer\Hydrator\Exception;

/**
 * This is thrown if a Hydator had an issue hydrating a message.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
final class ConvertToMessageFailedException extends \RuntimeException implements HydratorException
{
}
